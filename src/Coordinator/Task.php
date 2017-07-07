<?php
/**
 * Created by PhpStorm.
 * User: isac
 * Date: 06/07/2017
 * Time: 6:00 PM
 */

namespace Joomla\Testing\Coordinator;

use Joomla\Testing\Util\Command;

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
	public function __construct($codeceptionTask, $server, $client)
	{
		$this->codeceptionTask = $codeceptionTask;
		$this->server = $server;
		$this->client = $client;
	}

	public function run(){
		$command = "docker exec $this->client /bin/sh -c \"cd /usr/src/tests/tests;vendor/bin/robo run:container-tests 
					--single --test $this->codeceptionTask --server $this->server\"";
		return Command::execute($command);
	}

}