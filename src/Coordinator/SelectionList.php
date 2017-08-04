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

/**
 * Class SelectionList
 * Holds the tasks for a specific server
 *
 * @package Joomla\Testing\Coordinator
 */
class SelectionList
{
	/**
	 * keep a lists of all the tasks
	 * @var
	 */
	private $tasks;
	/**
	 * keep the tasks also stored by their flag
	 * although the data is not normalised, the complexity is better
	 * anyway, memory is cheaper than cpu
	 * @var array
	 */
	private $list = array
	(
		Flag::NO_FLAG  => array(),
		Flag::ASSIGNED => array(),
		Flag::EXECUTED => array(),
		Flag::FAILED   => array(),
	);
	/**
	 * @var int
	 */
	private $wait = 0;
	/**
	 * store the total number of tasks for the isFinished check
	 * @var int
	 */
	private $noTasks;

	/**
	 * SelectionList constructor.
	 * @param $ymlPath
	 */
	public function __construct($ymlPath)
	{
		$tree = Yaml::parse(file_get_contents($ymlPath));
		$this->read($tree, null);
		$this->noTasks = count($this->list[Flag::NO_FLAG]);
	}

	/**
	 * recursively read the tasks.yml file
	 * ensure that all the dependencies of a tasks is considered(also the inherited ones)
	 * @param $tree
	 * @param $parent
	 */
	private function read($tree, $parent)
	{
		foreach($tree as $task => $subTree)
		{
			$this->tasks[$task]['depends'][] = $parent;
			if (!is_null($parent) && !is_null($this->tasks[$parent]['depends'])){
				$this->tasks[$task]['depends'] = array_merge($this->tasks[$task]['depends'], $this->tasks[$parent]['depends']);
			}
			$this->tasks[$task]['flag'] = Flag::NO_FLAG;
			$this->list[Flag::NO_FLAG][] = $task;
			if($subTree)
			{
				$this->read($subTree, $task);
			}
		}
	}

	/**
	 * pop a task if available, return false otherwise
	 * @return bool
	 */
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

					if($this->tasks[$depends]['flag'] == Flag::FAILED)
					{
						$this->changeFlag($task,Flag::FAILED);
						break;
					}
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

	/**
	 * changes the task flag
	 * adjust the list of tasks accordingly
	 * @param $task
	 * @param $flag
	 */
	public function changeFlag($task, $flag)
	{
		$oldFlag = $this->tasks[$task]['flag'];
		$this->list[$flag][] = $task;
		$this->tasks[$task]['flag'] = $flag;
		$this->list[$oldFlag] = array_diff($this->list[$oldFlag], array($task));
	}

	/**
	 * @param $task
	 */
	public function execute($task){
		$this->changeFlag($task, Flag::EXECUTED);
		$this->wait = 0;
	}

	/**
	 * @param $task
	 */
	public function assign($task){
		$this->changeFlag($task, Flag::ASSIGNED);
	}

	/**
	 * @param $task
	 */
	public function fail($task){
		$this->changeFlag($task, Flag::FAILED);
	}

	/**
	 * @return mixed
	 */
	public function getList()
	{
		return $this->list;
	}

	/**
	 * @return bool
	 */
	public function isFinished(){
		if ($this->getNoExecuted() + $this->getNoFailed() == $this->noTasks)
		{
			return true;
		}
		return false;
	}

	/**
	 * @return int
	 */
	public function getNoFailed(){
		return count($this->list[Flag::FAILED]);
	}

	/**
	 * @return int
	 */
	public function getNoExecuted(){
		return count($this->list[Flag::EXECUTED]);
	}

	/**
	 * @return int
	 */
	public function getNoTasks(){
		return $this->noTasks;
	}

	/**
	 * @return bool
	 */
	public function isBlocked(){
		return $this->wait == 1;
	}

}