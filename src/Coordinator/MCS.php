<?php
/**
 * Created by PhpStorm.
 * User: isac
 * Date: 11/07/2017
 * Time: 2:32 PM
 */

namespace Joomla\Testing\Coordinator;

use Joomla\Testing\Util\Command;
use Joomla\Virtualisation\DockerComposeGeneratorApi;
use Joomla\Testing\Coordinator\Task;
use Memcached;

/**
 * Class MCS - Main Coordinator Static
 * @package Joomla\Testing\Coordinator
 */
class MCS
{
	const selectionLists = 'selectionlists';
	const clients = 'clients';
	const servers = 'servers';
	const runQueue = 'runQueue';
	const manageQueue = 'manageQueue';
	const clientsNo = 'clientsNo';
	const serversNo = 'serversNo';
	const available = 'available';

	public static function prepare($env)
	{
		$servers = array();
		$clients = array();
		$selectionLists = array();
		$runQueue = new \SplQueue();
		$manageQueue= new \SplQueue();
		$clientsNo = $env['selenium.no'];
		$serversNo = 0;

		//TODO How are these exactly generated?
		$prefix = "dockyard_";
		$postfix = "_1";

		$fixName  = function ($name)
		{
			return strtolower(str_replace(['-', '.'], ['v', 'p'], $name));
		};

		//TODO Add task to check if server is ready.
		foreach ($env['php'] as $php)
		{
			foreach ($env['joomla'] as $joomla)
			{
				$name = "http://" . $prefix . $fixName('apache-' . $php . '-' . $joomla) . $postfix;
				$servers[] = $name;
				$selectionLists[$name] = new SelectionList($env["extension.path"] . "/tests/acceptance/tests.yml");
				$manageQueue->enqueue($name);
				$serversNo ++;
			}
		}

		for ($i=0; $i<$clientsNo; $i++)
		{
			$clients[$prefix . "seleniumv$i" .$postfix] = 1;
		}

		//prepare extension - as long as all containers share the same extension folder
		$command = "docker exec " . current(array_keys($clients)) ." /bin/sh -c \"cd /usr/src/tests/tests;vendor/bin/robo run:container-test-preparation\"";
		Command::execute($command);

		$info = array(
			MCS::selectionLists => $selectionLists,
			MCS::clients 		=> $clients,
			MCS::servers 		=> $servers,
			MCS::runQueue		=> $runQueue,
			MCS::manageQueue	=> $manageQueue,
			MCS::clientsNo		=> $clientsNo,
			MCS::serversNo		=> $serversNo,
			MCS::available		=> 1
		);

		MCS::setCacheInfo($info);

		MCS::waitForDbInit($servers[0], current(array_keys($clients)));
	}

	//TODO reuse memcached connection on chained events
	//call on assign and on finished - runQueue size = §clientsNo
	// this is how we ensure maximum efficiency
	public static function fillAndRun($server = null)
	{
		$info = MCS::getCachedInfo();
		$info = MCS::fill($info, $server);
		$info = MCS::run($info);
		MCS::setCacheInfo($info);
	}

	public static function fill($info, $server = null)
	{

		if($server && $info[MCS::runQueue]->count() < $info[MCS::clientsNo])
		{
			if ($codeceptionTask = $info[MCS::selectionLists][$server]->pop())
			{
				$task = new Task($codeceptionTask, $server);
				$info[MCS::runQueue]->add(0, $task);
			}
		}

		$count = 1;
		while ($info[MCS::runQueue]->count() < $info[MCS::clientsNo] && $count)
		{
			$count = 0;
			for($i=0; $i< $info[MCS::serversNo]; $i++)
			{
				$server = $info[MCS::manageQueue]->pop();
				if ($codeceptionTask = $info[MCS::selectionLists][$server]->pop())
				{
					$task = new Task($codeceptionTask, $server);
					$info[MCS::runQueue]->add(0, $task);
					$count ++;
				}

				$info[MCS::manageQueue]->add(0, $server);
			}
		}
		return $info;

	}

	public static function run($info)
	{
		foreach ($info[MCS::clients] as $client => $isAvailable)
		{
			if ($info[MCS::runQueue]->isEmpty()) break;
			if ($isAvailable)
			{
				$task = $info[MCS::runQueue]->pop();
				$task->run($client);
			}
		}

		return $info;
	}

	public static function memcachedInit()
	{
		$memcached = new Memcached;
		$memcached->addServer('127.0.0.1', '11211');
		return $memcached;
	}

	public static function getCachedInfo()
	{
		$memcached = MCS::memcachedInit();

		MCS::aquireLock($memcached);

		$info[MCS::selectionLists] 	= unserialize($memcached->get(MCS::selectionLists));
		$info[MCS::clients] 		= unserialize($memcached->get(MCS::clients));
		$info[MCS::servers] 		= unserialize($memcached->get(MCS::servers));
		$info[MCS::runQueue] 		= unserialize($memcached->get(MCS::runQueue));
		$info[MCS::manageQueue] 	= unserialize($memcached->get(MCS::manageQueue));
		$info[MCS::serversNo] 		= unserialize($memcached->get(MCS::serversNo));
		$info[MCS::clientsNo] 		= unserialize($memcached->get(MCS::clientsNo));

		return $info;
	}

	public static function changeTaskStatus($server, $codeceptionTask, $action){
		$memcached = MCS::memcachedInit();
		$selectionLists = unserialize($memcached->get(MCS::selectionLists));
		$selectionLists[$server]->$action($codeceptionTask);
		$memcached->set(MCS::selectionLists, $selectionLists);
	}

	public static function aquireLock($memcached){
		 while(!MCS::isCacheAvailable($memcached))
		 {
		 	//sleep for 0.2 seconds
			 usleep(200000);
		 }
		 MCS::lockCache($memcached);
	}

	public static function lockCache($memcached)
	{
		$memcached->set(MCS::available, 0);
	}

	public static function unlockCache($memcached)
	{
		$memcached->set(MCS::available, 1);
	}

	public static function isCacheAvailable($memcached)
	{
		return $memcached->get(MCS::available);
	}

	public static function setCacheInfo($info)
	{
		$memcached = MCS::memcachedInit();

		foreach($info as $key => $val){
			$memcached->set($key, serialize($val));
		}

		MCS::unlockCache($memcached);
	}

	public static function generateEnv($env, $dockyardPath)
	{
		(new DockerComposeGeneratorApi())->generateFromConfig($env, $dockyardPath);

		$command = "cd " . $dockyardPath . "&& docker-compose up -d";

		Command::execute($command);
	}

	//TODO make this check by looking at db, instead of servers.
	public static function waitForDbInit($server, $client)
	{
		while (!MCS::isUrlAvailable($server, $client))
		{
			sleep(1);
		}
	}

	/**
	 * Checks if the given URL is available
	 *
	 * @param   string  $url  URL to check
	 *
	 * @return bool
	 */
	public static function isUrlAvailable($url, $client)
	{
		$command = "docker exec " . $client . " /bin/sh -c \"curl -sL -w \"%{http_code}\\n\" -o /dev/null " . $url . "\"";

		$code = Command::executeWithOutput($command);

		return $code == 200;
	}

}