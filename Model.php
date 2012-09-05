<?php
//typical model with mapper and unique int id
class Shinymayhem_Model
{
	protected $_exceptionClass;
	protected $_mapper;
	protected $_id;

	public function __construct($mapper=null, $exceptionClass="Exception")
	{
		$this->_mapper = $mapper;
		$this->setExceptionClass($exceptionClass);
	}

	//magic getters and setters, only use setter when type not required (null ok)
	//TODO some kind of type checking should still be implemented?
	public function __call($name, $args)
	{
		$property = "_" . lcfirst(substr($name, 3));
		if (substr($name, 0, 3) == "get" && property_exists($this, $property))
		{
			return $this->$property;
		}
		elseif (substr($name, 0, 3) == "set" && property_exists($this, $property))
		{
			$this->$property = $args[0];
			return $this;
		}
		else
		{
			throw new BadMethodCallException("Method $name does not exist");
			//no parent to call
			//parent::__call($name, $args);
		}
	}	

	public function clear()
	{
		$map = $this->getMapper()->getMap();
		if (!is_array($map))
		{
			throw new $this->_exceptionClass("Map is not an array");
		}
		foreach ($map as $column=>$p)
		{
			$property = '_' . $p;
			$this->$property = null;
		}
		return $map;
	}
	
	public function setExceptionClass($class)
	{
		if (is_string($class))
		{
			try {
				if (!class_exists($class) ||  !(new $class instanceof Exception))
				{
					throw new InvalidArgumentException();
				}
			}
			catch (InvalidArgumentException $e)
			{
				throw new InvalidArgumentException('Class "' . $class . '" does not exist or is not an instance of Exception');
			}
		}
		else
		{
			throw new InvalidArgumentException("Exception class must be a string");
		}
		$this->_exceptionClass = $class;
	}

	public function setId($id)
	{
		$this->requireInt($id);
		$this->_id = $id;
		return $this;
	}

	public function getId()
	{
		return $this->_id;
	}


	public function getMapper()
	{
		return $this->_mapper;
	}

	public function toArray()
	{
		return $this->getMapper()->toArray($this);
	}

	public function fromArray($properties)
	{
		return $this->getMapper()->fromArray($this, $properties);
	}

	//public function save($model)
	public function save()
	{
	//	$class = get_class($this);
	//	if (!$model instanceof $class)
	//	{
	//		if (is_object($model))
	//		{
	//			throw new $this->_exceptionClass("Model (class=".get_class($model)."is not an instance of " . get_class($this));
	//		}
	//		else
	//		{
	//			throw new $this->_exceptionClass("Model is not an object and is supposed to be an instance of " . get_class($this));
	//		}
	//	}
		return $this->getMapper()->save($this);
	}

	public function requireString($string)
	{
		if (!is_string($string))
		{
			throw new $this->_exceptionClass("String required");
		}
	}

	public function requireStringOrNull($string)
	{
		if (!is_string($string) && $string !== null)
		{
			throw new $this->_exceptionClass("String required");
		}
	}

	public function requireIntOrNull($int)
	{
		if (is_string($int))
		{
			$int = intval($int);
		}
		if (!is_int($int) && $int!== null)
		{
			throw new $this->_exceptionClass("Int or null required");
		}
	}

	public function requireInt($int)
	{
		if (is_string($int))
		{
			$int = intval($int);
		}
		if (!is_int($int))
		{
			throw new $this->_exceptionClass("Int required");
		}
	}

	public function requireArray($array)
	{
		if (!is_array($array))
		{
			throw new $this->_exceptionClass("Array required");
		}
	}

	public function findById($id)
	{
		$this->requireInt($id);
		$this->getMapper()->find($id, $this);
		return $this; //for chaining
	}

	public function findAll($where=null, $order=null, $count=null, $offset=null)
	{
		return $this->getMapper()->fetchAll($this, $where, $order, $count, $offset);
	}

	public function findAllArray($where=null, $order=null, $count=null, $offset=null)
	{
		$results = array();
		$all = $this->getMapper()->fetchAll($this, $where, $order, $count, $offset);
		foreach ($all as $one)
		{
			$results[] = $one->toArray();
		}
		return $results;
	}

	public function delete()
	{
		$this->getMapper()->delete($this);
	}
}
