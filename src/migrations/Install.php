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
				'id' => $this->primaryKey(),
				'url' => $this->string()->notNull()->unique(),
				'key' => $this->string()->notNull(),
			]
		);

		return true;
	}

	public function safeDown ()
	{
		$this->dropTableIfExists('{{%gnash}}');

		return true;
	}

}
