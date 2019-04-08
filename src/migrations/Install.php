<?php
/**
 * Nginx Cache for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\gnash\migrations;

use craft\db\Migration;

/**
 * Class Install
 *
 * @author  Ether Creative
 * @package ether\gnash\migrations
 */
class Install extends Migration
{

	public function safeUp ()
	{
		$this->createTable(
			'{{%gnash}}',
			[
				'id'        => $this->primaryKey(),
				'elementId' => $this->integer(),
				'url'       => $this->string()->notNull(),
				'key'       => $this->string()->notNull(),
			]
		);

		$this->createIndex(
			null,
			'{{%gnash}}',
			['elementId', 'url'],
			true
		);

		return true;
	}

	public function safeDown ()
	{
		$this->dropTableIfExists('{{%gnash}}');

		return true;
	}

}
