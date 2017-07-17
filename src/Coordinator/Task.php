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
use Joomla\Testing\Coordinator\MCS;

class Task
{
	private $codeceptionTask;
	private $server;
	private $client;

	const execute = "execute";
	const assign = "assign";
	const fail   = "fail";

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
		$command = JPATH_BASE . "/vendor/bin/robo run:client-task $this->codeceptionTask $this->server $client >/dev/null 2>&1 &";
		$process = new Process($command);
		$process->setTimeout(3600);
		$process->start();

		MCS::changeTaskStatus($this->server, $this->codeceptionTask, Task::assign);
		MCS::fillAndRun($this->server);

	}

}