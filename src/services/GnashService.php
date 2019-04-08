<?php
/**
 * Nginx Cache for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\gnash\services;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\errors\SiteNotFoundException;
use ether\gnash\Gnash;
use ether\gnash\models\Settings;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use yii\db\Exception;

/**
 * Class GnashService
 *
 * @author  Ether Creative
 * @package ether\gnash\services
 */
class GnashService extends Component
{

	/**
	 * Builds the Nginx config files
	 *
	 * @param Settings $settings
	 *
	 * @throws SiteNotFoundException
	 */
	public function buildNginxConfig (Settings $settings)
	{
		$dir = Craft::getAlias('@storage/nginx/');
		$configPath = $dir . 'cache.conf';
		$serverPath = $dir . 'cache-server.conf';
		$locationPath = $dir . 'cache-location.conf';
		$cacheHandle = 'gnash' . Craft::$app->getSites()->getCurrentSite()->handle;

		// 1. Remove existing config
		if (file_exists($configPath))
			unlink($configPath);

		if (file_exists($serverPath))
			unlink($serverPath);

		if (file_exists($locationPath))
			unlink($locationPath);

		// 2. Build new config
		$cachePath = rtrim(Craft::parseEnv($settings->cachePath), '/');

		// $gnash_request_uri is the $request_uri without $args (set in config.conf)
		$cacheKey = '$host$gnash_request_uri';
		if ($settings->includeQueryString) $cacheKey .= '$is_args$args';

		// Config: HTTP (config)
		// ---------------------------------------------------------------------

		$cacheValid = '';
		foreach ($settings->cacheDuration as $cd)
			$cacheValid .= 'fastcgi_cache_valid ' . $cd[0] . ' ' . $cd[1] . ';' . PHP_EOL;

		$serveStale = $settings->serveStaleOnError ? 'error timeout' : 'off';

		$config = <<<XYZZY
map \$request_uri \$gnash_request_uri {
    "~^(?P<path>[^?]*)(\?.*)?$" \$path;
}
		
fastcgi_cache_path $cachePath levels=1:2 use_temp_path=off keys_zone=$cacheHandle:10m max_size=$settings->maxSize inactive=$settings->inactive;
fastcgi_cache_methods GET HEAD;
fastcgi_cache_key $cacheKey;
fastcgi_cache_use_stale $serveStale;
fastcgi_ignore_headers Cache-Control Expires Set-Cookie;
fastcgi_next_upstream error timeout http_500 http_503;
$cacheValid
XYZZY;

		// Config: Server
		// ---------------------------------------------------------------------

		$skip = '$' . $cacheHandle . '_skip';

		$checks = '';

		if (is_array($settings->includedUris)) foreach ($settings->includedUris as $in)
		{
			$checks .= 'if ($request_uri ~ "' . $in[0] . '") {' . PHP_EOL;
			$checks .= '	set ' . $skip . ' 0;' . PHP_EOL;
			$checks .= '}' . PHP_EOL;
		}

		if (is_array($settings->excludedUris)) foreach ($settings->excludedUris as $ex)
		{
			$checks .= 'if ($request_uri ~ "' . $ex[0] . '") {' . PHP_EOL;
			$checks .= '	set ' . $skip . ' 1;' . PHP_EOL;
			$checks .= '}' . PHP_EOL;
		}

		$server = <<<XYZZY
set $skip 1;
$checks
#location ~ /purge(/.*) {
#	fastcgi_cache_purge $cacheHandle "\$host\$1*";
#}
XYZZY;

		// Config: Location
		// ---------------------------------------------------------------------

		$location = <<<XYZZY
add_header X-Cache \$upstream_cache_status;
# add_header X-Cache-Key $cacheKey;
fastcgi_cache $cacheHandle;
fastcgi_cache_bypass $skip;
fastcgi_no_cache $skip;
XYZZY;

		// 3. Write the config file
		if (!is_dir($dir)) mkdir($dir);
		if ($settings->enabled)
		{
			file_put_contents($configPath, $config);
			file_put_contents($serverPath, $server);
			file_put_contents($locationPath, $location);
		}
		else
		{
			file_put_contents($configPath, '');
			file_put_contents($serverPath, '');
			file_put_contents($locationPath, '');
		}

		// 4. Reload Nginx
		if (!empty($settings->reloadCommand))
			exec($settings->reloadCommand);
	}

	/**
	 * Cache the given element at the current URL
	 *
	 * @param $element
	 *
	 * @throws Exception
	 */
	public function cacheElement ($element)
	{
		$url = Craft::$app->getRequest()->getAbsoluteUrl();

		Craft::$app->getDb()->createCommand()
			->upsert(
				'{{%gnash}}',
				[ 'url' => $url, 'elementId' => $element->id ],
				[ 'key' => Gnash::getInstance()->gnash->urlToKey($url) ],
				[],
				false
			)->execute();
	}

	/**
	 * Cache the current URL
	 *
	 * @throws Exception
	 */
	public function cacheUrl ()
	{
		$url = Craft::$app->getRequest()->getAbsoluteUrl();

		Craft::$app->getDb()->createCommand()
			->upsert(
				'{{%gnash}}',
				[ 'url' => $url ],
				[ 'key' => Gnash::getInstance()->gnash->urlToKey($url) ],
				[],
				false
			)->execute();
	}

	/**
	 * Converts the given URL to a cache key
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function urlToKey ($url)
	{
		$parts = parse_url($url);

		$key = $parts['host'] . $parts['path'];

		if (array_key_exists('query', $parts))
			$key .= '?' . $parts['query'];

		return md5($key);
	}

	/**
	 * Purges the entire cache
	 */
	public function purgeAll ()
	{
		$cachePath = Craft::parseEnv(Gnash::getInstance()->getSettings()->cachePath);

		if (is_dir($cachePath))
			$this->_purgeDir($cachePath);
		else
			mkdir($cachePath);
	}

	/**
	 * Purges all caches that contain the given element
	 *
	 * @param Element|ElementInterface|int $elementOrId
	 */
	public function purgeElement ($elementOrId)
	{
		if ($elementOrId instanceof ElementInterface)
			$keys = $this->_getKeysByElementId($elementOrId->id);
		else
			$keys = $this->_getKeysByElementId($elementOrId);

		$this->_purgeKeys($keys);
	}

	/**
	 * Purges all caches for the given URL (will also purge query strings)
	 *
	 * @param string $url
	 */
	public function purgeUrl ($url)
	{
		$keys = $this->_getKeysByUrl($url);
		$this->_purgeKeys($keys);
	}

	// Helpers
	// =========================================================================

	/**
	 * @param $dir
	 */
	private function _purgeDir ($dir)
	{
		$di = new RecursiveDirectoryIterator(
			$dir,
			FilesystemIterator::SKIP_DOTS
		);

		$ri = new RecursiveIteratorIterator(
			$di,
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($ri as $file)
			$file->isDir() ? rmdir($file) : unlink($file);
	}

	private function _purgeKeys ($keys)
	{
		$settings  = Gnash::getInstance()->getSettings();
		$cachePath = rtrim(Craft::parseEnv($settings->cachePath), '/');

		foreach ($keys as $key)
		{
			$l    = strlen($key);
			$path = $cachePath . '/';
			$path .= substr($key, $l - 2) . '/';
			$path .= substr($key, $l - 4, $l - 2) . '/';
			$path .= $key;

			if (file_exists($path))
				unlink($path);
		}
	}

	private function _getKeysByElementId ($id)
	{
		return (new Query())
			->select('key')
			->from('{{%gnash}}')
			->where(['elementId' => $id])
			->column();
	}

	private function _getKeysByUrl ($url)
	{
		if (Craft::$app->getDb()->getDriverName() === 'mysql')
			$where = 'REGEXP_LIKE(url, \'' . $url . '[?]?(.*)\')';
		else
			$where = '[[url]] SIMILAR TO \'' . $url . '[?]?(.*)\'';

		return (new Query())
			->select('key')
			->from('{{%gnash}}')
			->where($where)
			->column();
	}

}
