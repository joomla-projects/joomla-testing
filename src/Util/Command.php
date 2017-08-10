<?php
/**
 * @package     Joomla\Testing
 * @subpackage  Util
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Testing\Util;

use Symfony\Component\Process\Process;

/**
 * Utility to execute command-related tasks
 *
 * @since 1.0.0
 */
abstract class Command
{
	/**
	 * Executes a command and returns its success
	 *
	 * @param   string  $command  OS command to execute
	 * @param   int     $timeout  Timeout for the command to execute
	 *
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	public static function execute($command, $timeout = 600)
	{
		$process = new Process($command, null, null, null, $timeout);
		$process->run();

		return $process->isSuccessful();
	}

	/**
	 * Executes a command and returns its output
	 *
	 * @param   string  $command  OS command to execute
	 * @param   int     $timeout  Timeout for the command to execute
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function executeWithOutput($command, $timeout = 600)
	{
		$process = new Process($command, null, null, null, $timeout);
		$process->run();

		return $process->getOutput();
	}
}
