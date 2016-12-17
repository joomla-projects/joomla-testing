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
 * Abstract object class
 *
 * @since  1.0.0
 */
abstract class GenericObject implements ObjectInterface
{
	/**
	 * Attribute getter
	 *
	 * @param   string  $attribute  Attribute to get
	 *
	 * @return  mixed
	 *
	 * @since   1.0.0
	 */
	public function get($attribute)
	{
		if (property_exists($this, $attribute))
		{
			return $this->$attribute;
		}

		return false;
	}

	/**
	 * Attribute setter
	 *
	 * @param   string  $attribute  Name of the attribute to set
	 * @param   mixed   $value      Value of the attribute
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 */
	public function set($attribute, $value)
	{
		if (property_exists($this, $attribute))
		{
			$this->$attribute = $value;
		}

		return false;
	}

	/**
	 * Magic function to get/set attributes
	 *
	 * @param   string  $name       Function name
	 * @param   string  $arguments  Function arguments
	 *
	 * @return  mixed
	 *
	 * @since   1.0.0
	 */
	public function __call($name, $arguments)
	{
		// Getter getProperty
		if (substr($name, 0, 3) == 'get')
		{
			return $this->get(lcfirst(substr($name, 3)));
		}

		// Setter setProperty
		if (substr($name, 0, 3) == 'set')
		{
			return $this->set(lcfirst(substr($name, 3)), $arguments[0]);
		}

		return false;
	}
}