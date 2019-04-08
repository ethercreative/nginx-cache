<?php
/**
 * Nginx Cache for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\gnash\console\controllers;

use Craft;
use ether\gnash\Gnash;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Exception;

/**
 * Class DefaultController
 *
 * @author  Ether Creative
 * @package ether\gnash\console\controllers
 */
class DefaultController extends Controller
{

	/**
	 * Purge the entire cache
	 *
	 * ./craft nginx-cache/purge-all
	 *
	 * @return int
	 * @throws Exception
	 */
	public function actionPurgeAll ()
	{
		Gnash::getInstance()->gnash->purgeAll();

		return ExitCode::OK;
	}

	/**
	 * Purge elements from the cache by their given IDs
	 *
	 * ./craft nginx-cache/purge-elements 1,3,4 [true]
	 *
	 * @param string $elementIds - Comma-separated string of element IDs
	 * @param bool $relatedTo - Will clear the cache for all elements related
	 *                          to the selected elements
	 *
	 * @return int
	 * @throws Exception
	 */
	public function actionPurgeElements (string $elementIds, $relatedTo = false)
	{
		$elementIds = explode(
			',',
			str_replace(' ', '', $elementIds)
		);

		if ($relatedTo)
			$elementIds = Gnash::getInstance()->gnash->getRelatedIds($elementIds);

		foreach ($elementIds as $id)
			Gnash::getInstance()->gnash->purgeElement((int) $id);

		return ExitCode::OK;
	}

	/**
	 * Purge the given URL from the cache (supports alias / env)
	 *
	 * ./craft nginx-cache/purge-url \$DEFAULT_SITE_URL
	 *
	 * @param string $url - The URL to purge
	 *
	 * @return int
	 * @throws Exception
	 */
	public function actionPurgeUrl (string $url)
	{
		Gnash::getInstance()->gnash->purgeUrl(
			Craft::parseEnv($url)
		);

		return ExitCode::OK;
	}

}
