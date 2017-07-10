<?php
/**
 * Created by PhpStorm.
 * User: isac
 * Date: 04/07/2017
 * Time: 10:33 AM
 */

namespace Joomla\Testing\Coordinator;

use Joomla\Testing\Util\Command;
use Joomla\Virtualisation\DockerComposeGeneratorApi;
use Joomla\Testing\Coordinator\Task;


class MainCoordiantor
{
	private $env;
	private $dockyardPath;
	private $servers = array();
	private $clients = array();
	private $selectionLists = array();
	/**
	 * @var
	 * Will keep the prepared tasks for execution
	 */
	private $runQueue;
	/**
	 * @var
	 * used to manage the task allocation to the running Queue
	 * It will ensure that no server will run all its tests before other servers also run tests
	 * the running queue size is limited to the number of clients
	 * therefore a server can take a maximum number fo spots in the running queue equal to
	 * (no of clients - no of servers + 1)
	 */
	private $manageQueue;


	/**
	 * MainCoordiantor constructor.
	 * @param $env
	 * @param $dockyardPath
	 */
	public function __construct($env, $dockyardPath)
	{
		$this->env = $env;
		$this->dockyardPath = $dockyardPath;
		$this->runQueue = new \SplQueue();
		$this->manageQueue= new \SplQueue();
	}

	public function prepare()
	{
		//TODO How are these exactly generated?
		$prefix = "dockyard_";
		$postfix = "_1";

		$fixName  = function ($name)
		{
			return strtolower(str_replace(['-', '.'], ['v', 'p'], $name));
		};

		//TODO Add task to check if server is ready.
		foreach ($this->env['php'] as $php)
		{
			foreach ($this->env['joomla'] as $joomla)
			{
				$name = "http://" . $prefix . $fixName('apache-' . $php . '-' . $joomla) . $postfix;
				$this->servers[] = $name;
				$this->selectionLists[$name] = new SelectionList($this->env["extension.path"] . "/tests/acceptance/tests.yml");
				$this->manageQueue->enqueue($name);
			}
		}

		for ($i=0; $i<$this->env['selenium.no']; $i++)
		{
			$this->clients[$prefix . "seleniumv$i" .$postfix] = 1;
		}

		//TODO Create execution handler and move this there. OVERCOMPLICATED
		while ($this->runQueue->count() <= length($this->servers))
		{
			$server = $this->manageQueue->current();
			$codeceptionTask = $this->selectionLists[$server]->pop();
			$task = new Task($codeceptionTask, $server);
			$this->runQueue->enqueue($task);
			$this->manageQueue->next();
		}

	}

	public function generateEnv()
	{
		(new DockerComposeGeneratorApi())->generateFromConfig($this->env, $this->dockyardPath);

		$command = "cd " . $this->dockyardPath . "&& docker-compose up -d";

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

	public function runTasks()
	{
		foreach($this->clients as $client => $available)
		{
			$this->runQueue->pop()->run($client);
		}
		while (!$this->manageQueue->isEmpty())
		{
			sleep(1);
		}
	}

	//TODO Create execution handler and move this there. OVERCOMPLICATED

	private function enqueueTask(){
		$server = $this->manageQueue->current();
		while(!$codeceptionTask = $this->selectionLists[$server]->pop())
		{
			$this->manageQueue->pop();
			$server = $this->manageQueue->current();
		};
		$task = new Task($codeceptionTask, $server, $this->selectionLists[$server], $this);
		$this->runQueue->enqueue($task);
		$this->manageQueue->next();
	}

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