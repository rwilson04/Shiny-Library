<?php
//this class manages global objects and their dependencies for the application
//it is copied from a podcast from zendcon2010 on dependency injection
//http://devzone.zend.com/article/12915

class Shinymayhem_Application_Container
{
	protected $values = array();

	function __set($id, $value)
	{
		$this->values[$id] = $value;
	}

	function __get($id)
	{
		if (!isset($this->values[$id]))
		{
			throw new InvalidArgumentException(sprintf('Value "%s" is not defined.', $id));
		}
		if (is_callable($this->values[$id]))
		{
			return $this->values[$id]($this);
		}
		else
		{
			return $this->values[$id];
		}
	}

	function asShared($callable)
	{
		return function ($c) use ($callable)
		{
			static $object;
			if (is_null($object))
			{
				$object = $callable($c);
			}
			return $object;
		};
	}
}
