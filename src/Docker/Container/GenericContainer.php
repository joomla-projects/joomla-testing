<?php
/**
 * @package     Joomla\Testing
 * @subpackage  Docker\Container
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Testing\Docker\Container;

use Joomla\Testing\Docker\DockerObject;
use Joomla\Testing\Docker\Network\Network;
use Joomla\Testing\Util\Command;

/**
 * Generic Container Entity
 *
 * @since  1.0.0
 */
abstract class GenericContainer extends DockerObject implements ContainerInterface
{
	/**
	 * Image name
	 *
	 * @var   string
	 *
	 * @since  1.0.0
	 */
	protected $imageName = '';

	/**
	 * Image tag
	 *
	 * @var   string
	 *
	 * @since  1.0.0
	 */
	protected $imageTag = 'latest';

	/**
	 * Attached Docker Network
	 *
	 * @var    Network
	 *
	 * @since  1.0.0
	 */
	protected $network = null;

	/**
	 * Folders to attach as volumes, keys are volume names
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	protected $volumes = [];

	/**
	 * Ports to expose in the public network
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	protected $ports = [];

	/**
	 * Container name
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	protected $name = '';

	/**
	 * Extra parameters of the container execution
	 *
	 * @var   array
	 *
	 * @since  1.0.0
	 */
	protected $params = [];

	/**
	 * Builds the container
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 *
	 * @todo    Develop
	 */
	public function build()
	{
		return false;
	}

	/**
	 * Runs the container
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 */
	public function run()
	{
		if (empty($this->imageName)
			|| empty($this->name))
		{
			return false;
		}

		$command = 'docker run -itd';
		$command .= ' --name ' . $this->name;

		if (!is_null($this->network))
		{
			$command .= ' --network ' . $this->network->get('name');
		}

		if (!empty($this->params))
		{
			foreach ($this->params as $param => $value)
			{
				$command .= ' -e ' . $param . '=' . $value;
			}
		}

		if (!empty($this->ports))
		{
			foreach ($this->ports as $port => $realPort)
			{
				$command .= ' -p ' . $port . ':' . $realPort;
			}
		}

		if (!empty($this->volumes))
		{
			foreach ($this->volumes as $hostFolder => $containerFolder)
			{
				$command .= ' --volume=' . $hostFolder . ':' . $containerFolder . ':rw';
			}
		}

		$command .= ' ' . $this->imageName . (empty($this->imageTag) ? '' : ':' . $this->imageTag);

		return Command::execute($command);
	}

	/**
	 * Stops the container
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 */
	public function stop()
	{
		if (empty($this->name))
		{
			return false;
		}

		$command = 'docker stop ' . $this->name;

		return Command::execute($command);
	}

	/**
	 * Removes the container
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 */
	public function remove()
	{
		if (empty($this->name))
		{
			return false;
		}

		$command = 'docker rm ' . $this->name;

		return Command::execute($command);
	}
}
