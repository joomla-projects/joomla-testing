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
		$memcached = new Memcached;
		$memcached->addServer('127.0.0.1', '11211');
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

		//TODO Create execution handler and move this there. OVERCOMPLICATED
		//initial load
		while ($runQueue->count() < $serversNo)
		{
			$server = $manageQueue->pop();
			$codeceptionTask = $selectionLists[$server]->pop();
			$task = new Task($codeceptionTask, $server);
			$runQueue->enqueue($task);
			$manageQueue->add(0, $server);
		}

		$memcached->add(MCS::selectionLists, serialize($selectionLists));
		$memcached->add(MCS::clients, $clients);
		$memcached->add(MCS::servers, $servers);
		$memcached->add(MCS::runQueue, serialize($runQueue));
		$memcached->add(MCS::manageQueue, serialize($manageQueue));
		$memcached->add(MCS::clientsNo, $clientsNo);
		$memcached->add(MCS::serversNo, $serversNo);

		var_dump($memcached->get(MCS::servers));
	}

	public function generateEnv($env, $dockyardPath)
	{
		(new DockerComposeGeneratorApi())->generateFromConfig($env, $dockyardPath);

		$command = "cd " . $dockyardPath . "&& docker-compose up -d";

		Command::execute($command);
	}

	//TODO make this check by looking at db, instead of servers.
	public function waitForDbInit()
	{
		$timeout = 0;

		$fixName  = function ($name)
		{
			return strtolower(str_replace(['-', '.'], ['', ''], $name));
		};

		while (!$this->isUrlAvailable($this->servers[0]))
		{
			sleep(1);
			$timeout ++;
		}

	}

	//TODO Create execution handler and move this there. OVERCOMPLICATED

//	public function runTasks()
//	{
//		foreach($this->clients as $client => $available)
//		{
//			$this->runQueue->pop()->run($client);
//		}
//		while (!$this->manageQueue->isEmpty())
//		{
//			sleep(1);
//		}
//	}

	//TODO Create execution handler and move this there. OVERCOMPLICATED

//	private function enqueueTask(){
//		$server = $this->manageQueue->current();
//		while(!$codeceptionTask = $this->selectionLists[$server]->pop())
//		{
//			$this->manageQueue->pop();
//			$server = $this->manageQueue->current();
//		};
//		$task = new Task($codeceptionTask, $server, $this->selectionLists[$server], $this);
//		$this->runQueue->enqueue($task);
//		$this->manageQueue->next();
//	}

	/**
	 * Checks if the given URL is available
	 *
	 * @param   string  $url  URL to check
	 *
	 * @return bool
	 */
	private function isUrlAvailable($url)
	{
		$command = "docker exec " . $this->clients[0] . " /bin/sh -c \"curl -sL -w \"%{http_code}\\n\" -o /dev/null " . $url . "\"";

		$code = Command::executeWithOutput($command);

		return $code == 200;
	}

}