<?php
/**
 * Nginx Cache for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\gnash\utilities;

use Craft;
use craft\base\Utility;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class GnashUtility
 *
 * @author  Ether Creative
 * @package ether\gnash\utilities
 */
class GnashUtility extends Utility
{

	public static function displayName (): string
	{
		return Craft::t('nginx-cache', 'Nginx Cache');
	}

	/**
	 * Returns the utility’s unique identifier.
	 *
	 * The ID should be in `kebab-case`, as it will be visible in the URL (`admin/utilities/the-handle`).
	 *
	 * @return string
	 */
	public static function id (): string
	{
		return 'gnash';
	}

	public static function iconPath ()
	{
		return Craft::getAlias('@gnash/icon-util.svg');
	}

	/**
	 * Returns the utility's content HTML.
	 *
	 * @return string
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	public static function contentHtml (): string
	{
		$types = Craft::$app->getElements()->getAllElementTypes();
		$opts = [];

		foreach ($types as $type)
		{
			$type = (string) $type;
			$t = explode('\\', $type);
			$opts[$type] = end($t);
		}

		return Craft::$app->getView()->renderTemplate('nginx-cache/_utility', [
			'typeOpts'     => $opts,
			'elementTypes' => $types,
		]);
	}

}
