<?php
//typical mapper for model to db table
class Shinymayhem_Model_Mapper
{
	protected $_dbTable;
	protected $_exceptionClass;

	protected $_modelClass;
	protected $_map;

	public function __construct($dbTable, $modelClass=null, $exceptionClass = "Exception", $map = null)
	{
		$this->setExceptionClass($exceptionClass);
		if (is_string($dbTable))
		{
			try 
			{
				if (class_exists($dbTable))
				{
					$dbTable = new $dbTable();
				}
				else 
				{
					throw new InvalidArgumentException();
				}
			}
			catch (InvalidArgumentException $e)
			{
				throw new $this->_exceptionClass("Class does not exist, or cannot create class of type $dbTable");
			}
		}
		if (!$dbTable instanceof Zend_Db_Table_Abstract)
		{
			if (is_object($dbTable))
			{
				throw new $this->_exceptionClass("dbTable (class=".get_class($dbTable).") is not an instance of Zend_Db_Table_Abstract");
			}
			else
			{
				throw new $this->_exceptionClass("dbTable is not an object and is supposed to be an instance of Zend_Db_Table_Abstract");
			}
		}
		$this->_dbTable = $dbTable;
		if ($modelClass !== null)
		{
			$this->setModelClass($modelClass);
		}
		$this->_modelClass = $modelClass;
		if (!empty($map))
		{
			$this->_map = $map;
		}
		elseif (empty($this->_map))
		{
			$this->_map = array('id'=>'id');
		}
	}

	public function setModelClass($class)
	{
		if (is_string($class))
		{
			if (!class_exists($class))
			{
				throw new InvalidArgumentException('Class "' . $class . '" does not exist');
			}
		}
		else
		{
			throw new InvalidArgumentException("Model class must be a string");
		}
		$this->_modelClass = $class;
	}

	protected function validateModel($model)
	{
		$this->_modelClass;
		if (!$model instanceof $this->_modelClass)
		{
			if (is_object($model))
			{
				throw new $this->_exceptionClass("Model (class=".get_class($model).") is not an instance of " .$this->_modelClass);
			}
			else
			{
				throw new $this->_exceptionClass("Model is not an object and is supposed to be an instance of " . $this->_modelClass);
			}
		}
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

	public function getDbTable()
	{
		return $this->_dbTable;
	}

	//TODO find out if type hinting will work
	//map db columns to model object properties
	protected function populate($row, $model) 
	{
		$this->validateModel($model);
		if (is_array($row))
		{
			$row = new ArrayObject($row, ArrayObject::ARRAY_AS_PROPS);
		}	
		//if (empty($this->_map))
		//{
			//throw new $this->_exceptionClass("Map is empty or not defined");
		//}
		if (!is_array($this->_map))
		{
			throw new $this->_exceptionClass("Map is not an array");
		}
		//properties in row without a mapped value will not be populated
		foreach ($this->_map as $column=>$property)
		{
			if (isset ($row->$column))
			{
				$function = "set" . ucfirst($property);
				$model->$function($row->$column);
			}
		}
	}

	public function save($model) 
	{
		$this->validateModel($model);
		$data = $model->toArray();
		//TODO update this to allow nulls where defaults are set in db but nulls are not allowed
		if (($id = $model->getId()) === null)
		{
			return $this->getDbTable()->insert($data);
		}
		else
		{
			$result =  $this->getDbTable()->update($data, array('id = ?' => $id));
		}

	}

	public function delete($model)
	{
		$this->validateModel($model);
		$where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $model->getId());
		return $this->getDbTable()->delete($where);
	}

	public function find($id, $model)
	{
		$this->validateModel($model);
		$result = $this->getDbTable()->find($id);	
		if (count($result) === 0)
		{
			return false;
		}
		$row = $result->current();
		$this->populate($row, $model);
		return true;
	}

	public function getTableName()
	{
		$info = $this->getDbTable()->info();
		return $info['name'];
	}

	public function fetchAll($model, $where=null, $order=null, $count=null, $offset=null)
	{
		//$entries = array();
		//if (($resultSet = $this->getDbTable()->fetchAll($where, $order, $count, $offset)) !== null)
		//{
		//	foreach ($resultSet as $row)
		//	{
		//		//$entry = $this->_newModel;
		//		//TODO can we/do we need to ensure this new model is empty?
		//		$entry = clone $model;
		//		$this->populate($row, $entry);
		//		$entries[] = $entry;
		//		unset($entry);
		//	}
		//}
		//echo "<pre>";
		//print_r($resultSet);
		//return $entries;
		//
		//TODO validate model?
		if (!is_object($model))
		{
			throw new $this->_exceptionClass("Invalid model passed to fetchAll function");
		}
		//$db = $this->getDbTable()->getAdapter();
		$select = $this->getDbTable()->select();
		//$select = $db->select();
		//$select->from($this->getTableName());
		if (!empty($where))
		{
			$select->where($where);
		}
		$select->order($order);
		$adapter = new Shinymayhem_Paginator_Adapter_DbSelect($select);
		$adapter->setModel($model);
		$paginator = new Zend_Paginator($adapter);
		//default to fetch all, fetch paginated on demand
		$paginator->setDefaultItemCountPerPage(-1);
		return $paginator;
	}

	public function fetchRow(Shinymayhem_Model $model, $where=null, $order=null)
	{
		$this->validateModel($model);
		$row = $this->getDbTable()->fetchRow($where, $order);
		if ($row !== null)
		{
			$this->populate($row, $model);
			//return success
			return true;
		}
		//return failure
		return false;
	}

	public function toArray($model)
	{
		$array = array();
		foreach ($this->_map as $column=>$property)
		{
			$function = 'get' . ucfirst($property);
			$array[$column]=$model->$function();
		}
		return $array;
	}

	public function fromArray(Shinymayhem_Model $model, $properties)
	{
		foreach ($this->_map as $column=>$property)
		{
			//echo "property:$property value:" . $properties[$column] . "<br />";
			if (!empty($properties[$column]))
			{
				$function = 'set' . ucfirst($property);
				$model->$function($properties[$column]);
			}
		}
	}
}
