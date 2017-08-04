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

if (!defined('JPATH_BASE'))
{
	// Base path for Robo tasks
	define('JPATH_BASE', __DIR__);
}

use Joomla\Testing\Docker\Network\Network;
use Joomla\Testing\Docker\Container\MySQLContainer;
use Joomla\Testing\Docker\Container\PHPContainer;
use Joomla\Testing\Docker\Container\TestContainer;
use Joomla\Testing\Coordinator\SelectionList;
use Joomla\Testing\Coordinator\MCS;
use Joomla\Testing\Util\Command;
use Joomla\Testing\Coordinator\Task;

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
		$this->prepareExtension($repoOwner, $repoName, $repoBranch);

		$tmpDir = __DIR__ . '/.tmp';

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

	/**
	 * manual test for the SelectionList
	 *
	 * @param $ymlPath
	 */
	public function loadTests($ymlPath)
	{
		$selectionList = new SelectionList($ymlPath);
		$task = $selectionList->pop();
		$selectionList->execute($task);
		for($i=0; $i<5; $i++){
			$task = $selectionList->pop();
		}
		$selectionList->fail($task);
		var_dump($selectionList->getList());
	}

	/**
	 * runs the tests for the servers defined in the below $env
	 * tests are run in parallel with the use of the MainCoordinator(MCS)
	 * environment is started by the use of the virtualisation package
	 *
	 * @param $repoOwner
	 * @param $repoName
	 * @param $repoBranch
	 */
	public function runCoordinator($repoOwner, $repoName, $repoBranch)
	{
		$this->prepareExtension($repoOwner, $repoName, $repoBranch);
		exec("echo start >" .JPATH_BASE. "/coordinator.log");

		$tmpDir = __DIR__ . '/.tmp';
		$dockyardPath = $tmpDir . "/dockyard";

		$env = array(
			'php' => ['7.0', '7.1'],
			'joomla' => ['3.6'],
			'selenium.no' => 3,
			'extension.path' => $tmpDir . '/extension',
			'host.dockyard' => '.tmp/dockyard',
		);

		if (!file_exists($dockyardPath))
		{
			$this->_mkdir($dockyardPath);
		}

		MCS::generateEnv($env, $dockyardPath);
		MCS::prepare($env);
		MCS::fillAndRun();
	}

	/**
	 * runs a specific task on a specific server using a specific client
	 * manages the task after completion
	 *
	 * @param $codeceptionTask
	 * @param $server
	 * @param $client
	 */
	public function runClientTask($codeceptionTask, $server, $client)
	{
		//synchronous
		$command = "docker exec $client /bin/sh -c \"cd /usr/src/tests/tests;vendor/bin/robo run:container-test --test $codeceptionTask --server $server\"";

		$result = Command::executeWithOutput($command, 3600);
		if(strpos($result, "OK") > 0)
		{
			MCS::manageTask($codeceptionTask, $server, Task::execute, $client);
		}
		else
		{
			MCS::manageTask($codeceptionTask, $server, Task::fail, $client);
		}
	}

	/**
	 * prepares the extension for testing:
	 * 1. clones the repo
	 * 2. runs composer install
	 * 3. runs Container Test Preparation
	 *
	 * @param $repoOwner
	 * @param $repoName
	 * @param $repoBranch
	 * @return int
	 */
	public function prepareExtension($repoOwner, $repoName, $repoBranch)
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

		// Prepare the testing package
		$command = "cd " . JPATH_BASE . "/.tmp/extension/tests;vendor/bin/robo run:container-test-preparation >" .JPATH_BASE. "/coordinator.log 2>&1 &";
		Command::execute($command);
	}
}
