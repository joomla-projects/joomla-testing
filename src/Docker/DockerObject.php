<?php
/**
 * @package     Joomla\Testing
 * @subpackage  Docker
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Testing\Docker;

use Joomla\Testing\Object\GenericObject;

/**
 * Abstract Docker object class
 *
 * @since 1.0.0
 */
abstract class DockerObject extends GenericObject
{
	/**
	 * DockerObject constructor.
	 * Required to ensure that Docker exists before using anything related to it
	 *
	 * @todo: Verify that docker actually is installed and functional
	 */
	public function __construct()
	{
	}
}