<?php
/**
 * @package     Joomla\Testing
 * @subpackage  Util
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Testing\Util;


final class Flag
{
	const NO_FLAG = 0;
	const ASSIGNED = 1;
	const EXECUTED = 2;
	const FAILED = 3;
}