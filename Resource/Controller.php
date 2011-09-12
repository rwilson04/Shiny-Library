<?php
class Shinymayhem_Resource_Controller implements Zend_Acl_Resource_Interface 
{
	private $_id;

	public function __construct($id)
	{
		$this->_id = $id;
	}

	public function getResourceId()
	{
		if ($this->_id === null)
		{
			return null;
		}
		return 'controller-' . $this->_id;
	}
}
?>
