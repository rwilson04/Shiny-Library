<?php
class Shinymayhem_Paginator_Adapter_DbSelect extends Zend_Paginator_Adapter_DbSelect
{
	protected $_model;

	public function setModel($model)
	{
		$this->_model = $model;
	}

	public function getModel()
	{
		return $this->_model;
	}

	public function getItems($offset, $itemCountPerPage)
	{
		$rows = parent::getItems($offset, $itemCountPerPage);
		$results = array();
		$model = $this->getModel();
		foreach ($rows as $row)
		{
			$entry = clone $model;
			try
			{
			$map = $entry->clear(); //sets all mapped properties to null
			}
			catch (Exception $e)
			{
				//echo get_class($entry);
				//echo "<pre>";
				//throw new Exception($e->getMessage());
				return $rows;
			}
			$entry->fromArray($row);
			$results[] = $entry;
			unset($entry);
		}
		return $results;

	}
}
