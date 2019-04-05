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
use craft\errors\SiteNotFoundException;
use ether\gnash\Gnash;
use ether\gnash\models\Settings;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
		$cachePath = Craft::parseEnv($settings->cachePath);

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
$cacheValid
XYZZY;

		// Config: Server
		// ---------------------------------------------------------------------

		$skip = '$' . $cacheHandle . '_skip';

		$checks = '';

		foreach ($settings->includedUris as $in)
		{
			$checks .= 'if ($request_uri ~ "' . $in[0] . '") {' . PHP_EOL;
			$checks .= '	set ' . $skip . ' 0;' . PHP_EOL;
			$checks .= '}' . PHP_EOL;
		}

		foreach ($settings->excludedUris as $ex)
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
		file_put_contents($configPath, $config);
		file_put_contents($serverPath, $server);
		file_put_contents($locationPath, $location);

		// 4. Reload Nginx
		if (!empty($settings->reloadCommand))
			exec($settings->reloadCommand);
	}

	public function purgeAll ()
	{
		$cachePath = Craft::parseEnv(Gnash::getInstance()->getSettings()->cachePath);

		if (is_dir($cachePath))
			$this->_purgeDir($cachePath);
		else
			mkdir($cachePath);
	}

	/**
	 * @param Element|ElementInterface $element
	 */
	public function purgeElement ($element)
	{
		// $settings = Gnash::getInstance()->getSettings();
		// $cachePath = Craft::parseEnv($settings->cachePath);
		// $settings->includeQueryString

		// TODO: Work out how to clear the cache for all uris that display this
		//   element AND account for query strings...

		$this->purgeAll();
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

}
