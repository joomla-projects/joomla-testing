<?php
/**
 * @package     Joomla\Testing
 * @subpackage  Docker\Network
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Testing\Docker\Network;

use Joomla\Testing\Docker\DockerObject;
use Joomla\Testing\Util\Command;

/**
 * Docker Network class
 *
 * @since  1.0.0
 */
class Network extends DockerObject
{
	/**
	 * Network name
	 *
	 * @var   string
	 *
	 * @since  1.0.0
	 */
	protected $name = '';

	/**
	 * Newtork constructor.  It can initialize the network with a given or random name
	 *
	 * @param   string  $name  Optional name of the network.  Otherwise it's randomized
	 *
	 * @since  1.0.0
	 */
	public function __construct($name = '')
	{
		$this->name = $name;

		if (empty($this->name))
		{
			$this->createRandomName();
		}
	}

	/**
	 * Assigns a randomized network name
	 *
	 * @return  string
	 *
	 * @since   1.0.0
	 */
	public function createRandomName()
	{
		$length = 16;

		$this->name = substr(
			str_shuffle(
				str_repeat(
					$x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x))
				)
			), 1, $length
		);
	}

	/**
	 * Creates the Docker newtork
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 */
	public function create()
	{
		if (empty($this->name))
		{
			return false;
		}

		return Command::execute('docker network create ' . $this->name);
	}

	/**
	 * Removes the Docker newtork
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

		return Command::execute('docker network rm ' . $this->name);
	}
}
