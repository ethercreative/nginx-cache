<?php
/**
 * Nginx Cache for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\gnash;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\db\ElementQuery;
use craft\errors\SiteNotFoundException;
use craft\events\ElementEvent;
use craft\events\MoveElementEvent;
use craft\events\PopulateElementEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\queue\jobs\ResaveElements;
use craft\queue\Queue;
use craft\services\Elements;
use craft\services\Structures;
use craft\services\Utilities;
use ether\gnash\models\Settings;
use ether\gnash\services\GnashService;
use ether\gnash\utilities\GnashUtility;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\queue\ExecEvent;

/**
 * Class Gnash
 *
 * @author  Ether Creative
 * @package ether\gnash
 * @property GnashService $gnash
 */
class Gnash extends Plugin
{

	// Properties
	// =========================================================================

	public $hasCpSettings = true;

	// Craft
	// =========================================================================

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function init ()
	{
		parent::init();

		Craft::setAlias(
			'gnash',
			__DIR__
		);

		$this->setComponents([
			'gnash' => GnashService::class,
		]);

		// Events
		// ---------------------------------------------------------------------

		Event::on(
			Utilities::class,
			Utilities::EVENT_REGISTER_UTILITY_TYPES,
			[$this, 'onRegisterUtilityType']
		);

		Event::on(
			Elements::class,
			Elements::EVENT_AFTER_SAVE_ELEMENT,
			[$this, 'onElementEvent']
		);

		Event::on(
			Elements::class,
			Elements::EVENT_AFTER_UPDATE_SLUG_AND_URI,
			[$this, 'onElementEvent']
		);

		Event::on(
			Elements::class,
			Elements::EVENT_BEFORE_DELETE_ELEMENT,
			[$this, 'onElementEvent']
		);

		Event::on(
			Structures::class,
			Structures::EVENT_AFTER_MOVE_ELEMENT,
			[$this, 'onElementMoveEvent']
		);

		Event::on(
			Queue::class,
			Queue::EVENT_BEFORE_EXEC,
			[$this, 'onExecEvent']
		);

		Event::on(
			Queue::class,
			Queue::EVENT_AFTER_EXEC,
			[$this, 'onExecEvent']
		);

		Event::on(
			Queue::class,
			Queue::EVENT_AFTER_ERROR,
			[$this, 'onExecEvent']
		);

		$request = Craft::$app->getRequest();

		if (
			!$request->getIsConsoleRequest() &&
			$request->getIsGet() &&
			!$request->getIsCpRequest() &&
			Craft::$app->getResponse()->getIsOk()
		) {
			Event::on(
				ElementQuery::class,
				ElementQuery::EVENT_AFTER_POPULATE_ELEMENT,
				[$this, 'onElementPopulate']
			);

			$this->gnash->cacheUrl();
		}
	}

	// Settings
	// =========================================================================

	protected function createSettingsModel ()
	{
		return new Settings();
	}

	/**
	 * @return string|null
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	protected function settingsHtml ()
	{
		return Craft::$app->getView()->renderTemplate(
			'nginx-cache/_settings',
			[ 'settings' => $this->getSettings() ]
		);
	}

	/**
	 * @throws SiteNotFoundException
	 */
	public function afterSaveSettings ()
	{
		$this->gnash->buildNginxConfig($this->getSettings());
		parent::afterSaveSettings();
	}

	/**
	 * @return bool|Settings|Model|null
	 */
	public function getSettings ()
	{
		return parent::getSettings();
	}

	// Events
	// =========================================================================

	/**
	 * @throws SiteNotFoundException
	 */
	protected function afterInstall ()
	{
		$this->gnash->buildNginxConfig($this->getSettings());
		parent::afterInstall();
	}

	public function onRegisterUtilityType (RegisterComponentTypesEvent $event)
	{
		$event->types[] = GnashUtility::class;
	}

	/**
	 * @param ElementEvent $event
	 *
	 * @throws Exception
	 */
	public function onElementEvent (ElementEvent $event)
	{
		$this->gnash->purgeElement($event->element);
	}

	/**
	 * @param MoveElementEvent $event
	 *
	 * @throws Exception
	 */
	public function onElementMoveEvent (MoveElementEvent $event)
	{
		$this->gnash->purgeElement($event->element);
	}

	/**
	 * @param ExecEvent $event
	 *
	 * @throws Exception
	 */
	public function onExecEvent (ExecEvent $event)
	{
		if ($event->job instanceof ResaveElements)
			$this->gnash->purgeAll();
	}

	/**
	 * @param PopulateElementEvent $event
	 *
	 * @throws Exception
	 */
	public function onElementPopulate (PopulateElementEvent $event)
	{
		$this->gnash->cacheElement($event->element);
	}

	// Helpers
	// =========================================================================

	/**
	 * Adds support for additional actions on the Default controller
	 *
	 * @param string $route
	 *
	 * @return array|bool
	 * @throws InvalidConfigException
	 */
	public function createController ($route)
	{
		if (strpos($route, '/') === false)
		{
			if (strpos($route, '-') !== false)
			{
				$route = $this->defaultRoute . '/' . $route;
			}
			else
			{
				$className = $className = preg_replace_callback(
				'%-([a-z0-9_])%i',
					function ($matches) {
						return ucfirst($matches[1]);
					}, ucfirst($route)
				) . 'Controller';
				$className = $this->controllerNamespace . '\\' . $className;
				if (!class_exists($className))
					$route = $this->defaultRoute . '/' . $route;
			}
		}

		return parent::createController($route);
	}

}
