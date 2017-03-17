<?php
/**
 * @package     Joomla\Testing
 * @subpackage  Docker\Container
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Testing\Docker\Container;

/**
 * Interfaces of every Container
 *
 * @since 1.0.0
 */
interface ContainerInterface
{
	/**
	 * Builds the container
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function build();

	/**
	 * Runs the container
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function run();

	/**
	 * Stops the container
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function stop();

	/**
	 * Removes the container
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function remove();
}
