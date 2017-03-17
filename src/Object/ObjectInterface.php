<?php
/**
 * @package     Joomla\Testing
 * @subpackage  Object
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Testing\Object;

/**
 * Object interface
 *
 * @since 1.0.0
 */
interface ObjectInterface
{
	/**
	 * Attribute getter
	 *
	 * @param   string  $attribute Attribute to get
	 *
	 * @return  mixed
	 *
	 * @since  1.0.0
	 */
	public function get($attribute);

	/**
	 * Attribute setter
	 *
	 * @param   string  $attribute  Name of the attribute to set
	 * @param   mixed   $value      Value of the attribute
	 *
	 * @return  boolean
	 *
	 * @since 1.0.0
	 */
	public function set($attribute, $value);
}