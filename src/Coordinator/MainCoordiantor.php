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
	 * MainCoordiantor constructor.
	 * @param $env
	 * @param $extensionPath
	 */
	public function __construct($env, $dockyardPath)
	{
		$this->env = $env;
		$this->dockyardPath = $dockyardPath;
	}

	public function prepare(){
		//ToDo How are these exactly generated?
		$prefix = "dockyard_";
		$postfix = "_1";

		$fixName  = function ($name)
		{
			return strtolower(str_replace(['-', '.'], ['v', 'p'], $name));
		};

		foreach ($this->env['php'] as $php)
		{
			foreach ($this->env['joomla'] as $joomla)
			{
				$name= 'apache-' . $php . '-' . $joomla;
				$this->servers[] = $prefix . $fixName($name) . $postfix;
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


}