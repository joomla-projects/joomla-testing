<?php
/**
 * Created by PhpStorm.
 * User: isac
 * Date: 04/08/2017
 * Time: 4:03 PM
 */

namespace Joomla\Testing\Coordinator;


class Storage
{
	private $host;
	private $port;
	private $memcached;

	/**
	 * Storage constructor.
	 * @param $server
	 * @param $port
	 */
	public function __construct($host, $port)
	{
		$this->host = $host;
		$this->port = $port;
		$this->memcached = new \Memcached();
		$this->memcached->addServer($host, $port);

	}

	/**
	 * changes the task status
	 *
	 * @param $codeceptionTask
	 * @param $server
	 * @param $client
	 * @param $action
	 */
	public function changeTaskStatus($codeceptionTask, $server, $client, $action){
		$this->aquireLock();

		//mark task flag
		$selectionLists = unserialize($this->memcached->get(MCS::selectionLists));
		$selectionLists[$server]->$action($codeceptionTask);
		$this->memcached->set(MCS::selectionLists, serialize($selectionLists));

		//unlock client for next execution if task is failed or executed
		if (!is_null($client)){
			$clients = unserialize($this->memcached->get(MCS::clients));
			$clients[$client] = 1;
			$this->memcached->set(MCS::clients, serialize($clients));
		}

		$this->unlockCache();
	}

	/**
	 * gets information from cache and unserialize it
	 *
	 * @return mixed
	 */
	public function getCachedInfo()
	{
		$this->aquireLock();

		$info[MCS::selectionLists] 	= unserialize($this->memcached->get(MCS::selectionLists));
		$info[MCS::clients] 		= unserialize($this->memcached->get(MCS::clients));
		$info[MCS::servers] 		= unserialize($this->memcached->get(MCS::servers));
		$info[MCS::runQueue] 		= unserialize($this->memcached->get(MCS::runQueue));
		$info[MCS::manageQueue] 	= unserialize($this->memcached->get(MCS::manageQueue));
		$info[MCS::serversNo] 		= unserialize($this->memcached->get(MCS::serversNo));
		$info[MCS::clientsNo] 		= unserialize($this->memcached->get(MCS::clientsNo));

		return $info;
	}

	/**
	 * stores the information in cache, serialised
	 *
	 * @param $info
	 */
	public function setCacheInfo($info)
	{
		foreach($info as $key => $val){
			$this->memcached->set($key, serialize($val));
		}

		$this->unlockCache();
	}

	/**
	 *
	 */
	private function aquireLock(){
		while(!$this->isCacheAvailable($this->memcached))
		{
			//sleep for 0.2 seconds
			usleep(200000);
		}
		$this->lockCache();
	}

	/**
	 *
	 */
	private function lockCache()
	{
		$this->memcached->set(MCS::available, 0);
//		echo "memcached locked\n";
	}

	/**
	 *
	 */
	private function unlockCache()
	{
		$this->memcached->set(MCS::available, 1);
//		echo "memcached unlocked\n";
	}

	/**
	 * @return mixed
	 */
	private function isCacheAvailable()
	{
		return $this->memcached->get(MCS::available);
	}

}