<?php
/**
 * Nginx Cache for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\gnash\models;

use craft\base\Model;

/**
 * Class Settings
 *
 * @author  Ether Creative
 * @package ether\gnash\models
 */
class Settings extends Model
{

	/** @var bool - Whether or not nginx caching is enabled */
	public $enabled = true;

	/** @var string - The cache storage path (must be absolute) */
	public $cachePath = '@root/cache';

	/** @var int - The max size the stored cache can use */
	public $maxSize = '200m';

	/** @var string - The amount of time a file has to be inactive before the cache is automatically cleared */
	public $inactive = '1y';

	/** @var bool - Whether or not to include the query string when caching */
	public $includeQueryString = false;

	/** @var array - How long to cache specific responses */
	public $cacheDuration = [
		['any', '7d'],
		[200, '7d'],
		[302, '1d'],
	];

	/** @var bool - Will serve stale content (if available) on a 50x response */
	public $serveStaleOnError = false;

	/** @var array - Array of URIs to include when caching */
	public $includedUris = [
		['(.*)'],
	];

	/** @var array - Array of URIs to exclude when caching */
	public $excludedUris = [
		['\/admin(.*)'],
	];

	/** @var string - The command to reload nginx. Won't run if blank. */
	public $reloadCommand = 'nginx -s reload';

}
