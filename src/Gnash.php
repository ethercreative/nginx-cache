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
use ether\gnash\models\Settings;
use ether\gnash\services\GnashService;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

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

}
