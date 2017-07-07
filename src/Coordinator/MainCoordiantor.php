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
	}

	public function prepare()
	{
		$this->runQueue = new \SplQueue();
		$this->manageQueue= new \SplQueue();

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
				$this->selectionLists[$name] = new SelectionList($this->env . "/tests/acceptance/tests.yml");
				$this->manageQueue->enqueue($name);
			}
		}

		for ($i=0; $i<$this->env['selenium.no']; $i++)
		{
			$this->clients[] = $prefix . "seleniumv$i" .$postfix;
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

		if ($code == 200)
		{
			return true;
		}

		return false;
	}

}