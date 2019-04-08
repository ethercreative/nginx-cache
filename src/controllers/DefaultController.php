<?php
/**
 * Nginx Cache for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\gnash\controllers;

use Craft;
use craft\web\Controller;
use ether\gnash\Gnash;
use yii\db\Exception;
use yii\web\BadRequestHttpException;

/**
 * Class DefaultController
 *
 * @author  Ether Creative
 * @package ether\gnash\controllers
 */
class DefaultController extends Controller
{

	/**
	 * @throws Exception
	 */
	public function actionPurgeAll ()
	{
		Gnash::getInstance()->gnash->purgeAll();
	}

	/**
	 * @throws BadRequestHttpException
	 * @throws Exception
	 */
	public function actionPurgeElement ()
	{
		$request = Craft::$app->getRequest();

		$elementType = $request->getRequiredBodyParam('elementType');
		$elementIds = $request->getRequiredBodyParam('element-' . $elementType);

		foreach ($elementIds as $id)
			Gnash::getInstance()->gnash->purgeElement($id);
	}

	/**
	 * @throws BadRequestHttpException
	 * @throws Exception
	 */
	public function actionPurgeUrl()
	{
		$url = Craft::$app->getRequest()->getRequiredBodyParam('url');
		Gnash::getInstance()->gnash->purgeUrl($url);
	}

}
