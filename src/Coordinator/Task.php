<?php
/**
 * Created by PhpStorm.
 * User: isac
 * Date: 06/07/2017
 * Time: 6:00 PM
 */

namespace Joomla\Testing\Coordinator;

use Joomla\Testing\Util\Command;
use Symfony\Component\Process\Process;

class Task
{
	private $codeceptionTask;
	private $server;
	private $client;

	/**
	 * Task constructor.
	 * @param $codeceptionTask
	 * @param $server
	 * @param $client
	 */
	public function __construct($codeceptionTask, $server)
	{
		$this->codeceptionTask = $codeceptionTask;
		$this->server = $server;
	}

	public function run($client)
	{
		$command = JPATH_BASE . "/vendor/bin/robo run:client-task $this->codeceptionTask $this->server $client";

		$process = new Process($command);
		$process->setTimeout(3600);

		echo "apelez";
		$process->start();

		echo "$client\n";
	}

}