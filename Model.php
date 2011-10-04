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
}