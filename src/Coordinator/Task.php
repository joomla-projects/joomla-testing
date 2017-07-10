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
	public function __construct($codeceptionTask, $server)
	{
		$this->codeceptionTask = $codeceptionTask;
		$this->server = $server;
	}

	public function run($client){
		$command = "docker exec $client /bin/sh -c \"cd /usr/src/tests/tests;vendor/bin/robo run:container-tests 
					--single --test $this->codeceptionTask --server $this->server\"";

		$result = Command::execute($command);
	}

	private function isSuccessfull($result){

	}

}