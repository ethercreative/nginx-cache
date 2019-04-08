<?php
/**
 * Nginx Cache for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\gnash\controllers;

use Craft;
use craft\db\Table;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use ether\gnash\Gnash;
use yii\db\Exception;
use yii\db\Query;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Class DefaultController
 *
 * @author  Ether Creative
 * @package ether\gnash\controllers
 */
class DefaultController extends Controller
{

	/**
	 * @return Response
	 * @throws BadRequestHttpException
	 * @throws Exception
	 * @throws MissingComponentException
	 */
	public function actionPurgeAll ()
	{
		Gnash::getInstance()->gnash->purgeAll();

		Craft::$app->getSession()->setNotice(
			Craft::t('nginx-cache', 'Cache Cleared')
		);

		return $this->redirectToPostedUrl();
	}

	/**
	 * @throws BadRequestHttpException
	 * @throws Exception
	 * @throws MissingComponentException
	 */
	public function actionPurgeElement ()
	{
		$request = Craft::$app->getRequest();

		$elementType = $request->getRequiredBodyParam('elementType');
		$elementIds = $request->getRequiredBodyParam('element-' . $elementType);
		$relatedTo = (bool) $request->getBodyParam('relatedTo', false);

		if ($relatedTo)
			$elementIds = Gnash::getInstance()->gnash->getRelatedIds($elementIds);

		foreach ($elementIds as $id)
			Gnash::getInstance()->gnash->purgeElement((int) $id);

		Craft::$app->getSession()->setNotice(
			Craft::t('nginx-cache', 'Cache Cleared')
		);

		return $this->redirectToPostedUrl();
	}

	/**
	 * @throws BadRequestHttpException
	 * @throws Exception
	 * @throws MissingComponentException
	 */
	public function actionPurgeUrl()
	{
		$url = Craft::$app->getRequest()->getRequiredBodyParam('url');
		Gnash::getInstance()->gnash->purgeUrl($url);

		Craft::$app->getSession()->setNotice(
			Craft::t('nginx-cache', 'Cache Cleared')
		);

		return $this->redirectToPostedUrl();
	}

}
