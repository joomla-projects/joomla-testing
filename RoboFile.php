<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * Download robo.phar from http://robo.li/robo.phar and type in the root of the repo: $ php robo.phar
 * Or do: $ composer update, and afterwards you will be able to execute robo like $ php vendor/bin/robo
 *
 * @package     Joomla
 * @subpackage  Testing
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
use Joomla\Testing\Docker\Container\TestContainer;

/**
 * Class RoboFile
 *
 * @package     Joomla
 * @subpackage  Testing
 *
 * @since  __DEPLOY_VERSION__
 */
class RoboFile extends \Robo\Tasks
{
	use Joomla\Testing\Robo\Tasks\loadTasks;

	/**
	 * Runs base servers
	 *
	 * @return  integer
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function runServers()
	{
		// Cleans up and creates the new network
		$dockerNetwork = new Network('joomla');
		$dockerNetwork->remove();
		$dockerNetwork->create();

		$dockerDB = new MySQLContainer;
		$dockerDB->set('name', 'db');
		$dockerDB->set('network', $dockerNetwork);
		$dockerDB->set(
			'params', array(
				'MYSQL_ROOT_PASSWORD' => 'root'
			)
		);
		$dockerDB->set(
			'ports', array(
				'13306' => '3306'
			)
		);
		$dockerDB->pull();
		$dockerDB->run();

		$dockerPHP = new PHPContainer;
		$dockerPHP->set('imageName', 'joomla');
		$dockerPHP->set('imageTag', '3.6.5-apache-php7');
		$dockerPHP->set('name', 'php');
		$dockerPHP->set('network', $dockerNetwork);
		$dockerPHP->set(
			'ports', array(
				'8080' => '80'
			)
		);
		$dockerPHP->set(
			'params', array(
				'JOOMLA_DB_HOST' => 'db',
				'JOOMLA_DB_USER' => 'root',
				'JOOMLA_DB_PASSWORD' => 'root',
				'JOOMLA_DB_NAME' => 'joomla'
			)
		);
		$dockerPHP->pull();
		$dockerPHP->run();

		return 0;
	}

	/**
	 * Stops base servers
	 *
	 * @return  integer
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function stopServers()
	{
		$dockerDB = new MySQLContainer;
		$dockerDB->set('name', 'db');
		$dockerDB->stop();
		$dockerDB->remove();

		$dockerPHP = new PHPContainer;
		$dockerPHP->set('name', 'php');
		$dockerPHP->stop();
		$dockerPHP->remove();

		// Cleans up the network
		$dockerNetwork = new Network('joomla');
		$dockerNetwork->remove();

		return 0;
	}

	/**
	 * Starts tests of a given repository (Github based).  runServers MUST be executed first.
	 *
	 * @param   string  $repoOwner   Repository owner
	 * @param   string  $repoName    Repository name
	 * @param   string  $repoBranch  Branch to use
	 *
	 * @return  integer
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function runTests($repoOwner, $repoName, $repoBranch)
	{
		if (empty($repoOwner) || empty($repoName))
		{
			$this->say('Please specify repository owner and name (Github based)');

			return 1;
		}

		// Temporary dir for the testing repository
		$tmpDir = __DIR__ . '/.tmp';

		if (!file_exists($tmpDir))
		{
			$this->_mkdir($tmpDir);
		}

		// Cloning of the repository we're going to test
		$taskCMSSetup = $this->taskCMSSetup()
			->setBaseTestsPath($tmpDir)
			->setCmsRepository($repoOwner . '/' . $repoName)
			->setCmsPath('extension');

		if (!empty($repoBranch))
		{
			$taskCMSSetup->setCmsBranch($repoBranch);
		}

		$taskCMSSetup->cloneCMSRepository()
			->setupCMSPath()
			->run()
			->stopOnFail();

		// Installs composer dependencies prior to tests
		$this->taskComposerInstall(__DIR__ . '/composer.phar')
			->option('working-dir', $tmpDir . '/extension/tests')
			->preferDist()
			->run();

		// Docker network (assuming it's started)
		$dockerNetwork = new Network('joomla');

		$dockerTesting = new TestContainer;
		$dockerTesting->set('name', 'client');
		$dockerTesting->set('network', $dockerNetwork);
		$dockerTesting->set(
			'ports', array(
				'5901' => '5900'
			)
		);
		$dockerTesting->set(
			'volumes', array(
				$tmpDir . '/extension' => '/usr/src/tests'
			)
		);
		$dockerTesting->pull();
		$dockerTesting->run();

		return 0;
	}
}
