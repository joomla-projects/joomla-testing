<?php
/**
 * Created by PhpStorm.
 * User: isac
 * Date: 06/07/2017
 * Time: 6:00 PM
 */

namespace Joomla\Testing\Coordinator;

use Symfony\Component\Process\Process;

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
		$command = JPATH_BASE . "/vendor/bin/robo run:client-task $this->codeceptionTask $this->server $client >>" .JPATH_BASE. "/coordinator.log 2>&1 &";
		$process = new Process($command);
		$process->setTimeout(3600);
		$process->start();

		//TODO do we need a proper logging system?
		$command = JPATH_BASE . "/vendor/bin/robo manage:task $this->codeceptionTask $this->server $client " . Task::assign . " >>" .JPATH_BASE. "/coordinator.log 2>&1 &";
		$process = new Process($command);
		$process->setTimeout(3600);
		$process->start();

		//ToDo mark $client as busy
	}

}