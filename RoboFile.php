<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * Download robo.phar from http://robo.li/robo.phar and type in the root of the repo: $ php robo.phar
 * Or do: $ composer update, and afterwards you will be able to execute robo like $ php vendor/bin/robo
 *
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later, see LICENSE.
 * @see        http://robo.li/
 *
 */

require __DIR__ . '/vendor/autoload.php';

use Joomla\Testing\Docker\Network\Network;
use Joomla\Testing\Docker\Container\MySQLContainer;
use Joomla\Testing\Docker\Container\PHPContainer;

/**
 * Class RoboFile
 *
 * @since  1.0.0
 */
class RoboFile extends \Robo\Tasks
{
	use Joomla\Testing\Robo\Tasks\loadTasks;

	/**
	 * It does something
	 *
	 * @return  bool
	 */
	public function doSomething()
	{
		$tmpDir = __DIR__ . '/.tmp';

		$this->taskDeleteDir($tmpDir);
		$this->_mkdir($tmpDir);

		// CMS cloning and setup in the main coordinator
		$this->taskCMSSetup()
			->setBaseTestsPath($tmpDir)
			->cloneCMSRepository()
			->setupCMSPath()
			->run()
			->stopOnFail();

		// Cleans up and creates the new network
		$dockerNetwork = new Network('joomla');
		$dockerNetwork->remove();
		$dockerNetwork->create();

		$dockerDB = new MySQLContainer;
		$dockerDB->set('name', 'db');
		$dockerDB->set('network', $dockerNetwork);
		$dockerDB->set('params', ['MYSQL_ROOT_PASSWORD' => 'root']);
		$dockerDB->set('ports', ['13306' => '3306']);
		$dockerDB->run();

		$dockerPHP = new PHPContainer;
		$dockerPHP->set('imageName', 'joomla');
		$dockerPHP->set('imageTag', '3.6-apache-php7');
		$dockerPHP->set('name', 'php');
		$dockerPHP->set('network', $dockerNetwork);
		$dockerPHP->set('ports', ['8080' => '80']);
		$dockerPHP->set(
			'params',
			[
				'JOOMLA_DB_HOST' => 'db',
				'JOOMLA_DB_USER' => 'root',
				'JOOMLA_DB_PASSWORD' => 'root',
				'JOOMLA_DB_NAME' => 'joomla'
			]
		);
		$dockerPHP->run();

		// Cleans up the network
		$dockerNetwork->remove();
	}
}
