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
use craft\errors\SiteNotFoundException;
use craft\events\ElementEvent;
use craft\events\MoveElementEvent;
use craft\queue\jobs\ResaveElements;
use craft\queue\Queue;
use craft\services\Elements;
use craft\services\Structures;
use ether\gnash\models\Settings;
use ether\gnash\services\GnashService;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Event;
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

	public function init ()
	{
		parent::init();

		$this->setComponents([
			'gnash' => GnashService::class,
		]);

		// Events
		// ---------------------------------------------------------------------

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
			'nginx-cache/settings',
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

	public function onElementEvent (ElementEvent $event)
	{
		$this->gnash->purgeElement($event->element);
	}

	public function onElementMoveEvent (MoveElementEvent $event)
	{
		$this->gnash->purgeElement($event->element);
	}

	public function onExecEvent (ExecEvent $event)
	{
		if ($event->job instanceof ResaveElements)
			$this->gnash->purgeAll();
	}

}
