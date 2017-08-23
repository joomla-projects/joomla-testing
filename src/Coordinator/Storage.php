<?php
/**
 * @package     Joomla\Testing
 * @subpackage  Coordinator
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
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
		$selectionLists = unserialize($this->memcached->get(MainCoordinator::SELECTION_LISTS));
		$selectionLists[$server]->$action($codeceptionTask);
		$this->memcached->set(MainCoordinator::SELECTION_LISTS, serialize($selectionLists));

		//unlock client for next execution if task is failed or executed
		if (!is_null($client)){
			$clients = unserialize($this->memcached->get(MainCoordinator::CLIENTS));
			$clients[$client] = 1;
			$this->memcached->set(MainCoordinator::CLIENTS, serialize($clients));
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

		$info[MainCoordinator::SELECTION_LISTS] 	= unserialize($this->memcached->get(MainCoordinator::SELECTION_LISTS));
		$info[MainCoordinator::CLIENTS] 		= unserialize($this->memcached->get(MainCoordinator::CLIENTS));
		$info[MainCoordinator::SERVERS] 		= unserialize($this->memcached->get(MainCoordinator::SERVERS));
		$info[MainCoordinator::RUN_QUEUE] 		= unserialize($this->memcached->get(MainCoordinator::RUN_QUEUE));
		$info[MainCoordinator::MANAGE_QUEUE] 	= unserialize($this->memcached->get(MainCoordinator::MANAGE_QUEUE));
		$info[MainCoordinator::SERVERS_NO] 		= unserialize($this->memcached->get(MainCoordinator::SERVERS_NO));
		$info[MainCoordinator::CLIENTS_NO] 		= unserialize($this->memcached->get(MainCoordinator::CLIENTS_NO));

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
		$this->memcached->set(MainCoordinator::AVAILABLE, 0);
//		echo "memcached locked\n";
	}

	/**
	 *
	 */
	private function unlockCache()
	{
		$this->memcached->set(MainCoordinator::AVAILABLE, 1);
//		echo "memcached unlocked\n";
	}

	/**
	 * @return mixed
	 */
	private function isCacheAvailable()
	{
		return $this->memcached->get(MainCoordinator::AVAILABLE);
	}

}