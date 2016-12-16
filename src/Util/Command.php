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
 * @since  1.0.0
 */
abstract class Command
{
	/**
	 * Executes a command and returns its output
	 *
	 * @param   string  $command  OS command to execute
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 */
	public static function execute($command)
	{
		$process = new Process($command);
		$process->run();

		return $process->isSuccessful();
	}
}
