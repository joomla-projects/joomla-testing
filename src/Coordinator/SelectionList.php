<?php
/**
 * Created by PhpStorm.
 * User: isac
 * Date: 30/06/2017
 * Time: 1:55 PM
 */

namespace Joomla\Testing\Coordinator;

use Joomla\Testing\Util\Flag;
use Symfony\Component\Yaml\Yaml;

class SelectionList
{
	private $tasks;
	private $list = array
	(
		Flag::NO_FLAG  => array(),
		Flag::ASSIGNED => array(),
		Flag::EXECUTED => array(),
		Flag::FAILED   => array(),
	);
	private $wait = 0;

	public function __construct($ymlPath)
	{
		$tree = Yaml::parse(file_get_contents($ymlPath));
		$this->read($tree, null);
	}

	private function read($tree, $parent)
	{
		foreach($tree as $task => $subTree)
		{
			$this->tasks[$task]['depends'][] = $parent;
			$this->tasks[$task]['flag'] = Flag::NO_FLAG;
			$this->list[Flag::NO_FLAG][] = $task;
			if($subTree)
			{
				$this->read($subTree, $task);
			}
		}
	}

	public function pop()
	{
		foreach($this->list[Flag::NO_FLAG] as $task)
		{
			$ready = 1;
			foreach($this->tasks[$task]['depends'] as $depends)
			{
				if (isset($depends) && $this->tasks[$depends]['flag'] != Flag::EXECUTED)
				{
					$ready = 0;
				}
				if (isset($depends) && $this->tasks[$depends]['flag'] == Flag::FAILED)
				{
					$this->tasks[$task]['flag'] = Flag::FAILED;
					break;
				}
			}
			if ($ready)
			{
				$this->assign($task);
				return $task;
			}
		}

		$this->wait = 1;
		return false;
	}

	public function changeFlag($task, $flag)
	{
		$oldFlag = $this->tasks[$task]['flag'];
		$this->list[$flag][] = $task;
		$this->tasks[$task]['flag'] = $flag;
		$this->list[$oldFlag] = array_diff($this->list[$oldFlag], array($task));
	}

	public function execute($task){
		$this->changeFlag($task, Flag::EXECUTED);
		$this->wait = 0;
	}

	public function assign($task){
		$this->changeFlag($task, Flag::ASSIGNED);
	}

	public function fail($task){
		$this->changeFlag($task, Flag::FAILED);
	}

	/**
	 * @return mixed
	 */
	public function getTasks()
	{
		return $this->tasks;
	}

	/**
	 * @return mixed
	 */
	public function getList()
	{
		return $this->list;
	}

	public function isFinished(){
		if (empty($this->list[Flag::NO_FLAG]))
		{
			return true;
		}
		return false;
	}

	public function isBlocked(){
		return $this->wait == 1;
	}

}