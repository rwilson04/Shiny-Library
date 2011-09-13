<?php

//This plugin checks whether a user is authenticated, and whether they can access the requested action. If not, they are redirected 
//to the specified login page.
//If the the object stored in Zend_Auth::getInstance()->getId() is an object rather than a string, that object is required 
//to implement Zend_Acl_Role_Interface for the acl
//It also requires controllers resource id to be in the format 'controller-<controller>' or 'controller-<module>-<controller>' (if _modular),
//where <module> and <controller> are what are returned by $request->getModuleName() and $request->getControllerName()
//Privileges are in the format 'action-<action>'.
//
//Be sure to allow access to the error controller for all users.
//Also, make sure pages like the login page are always accessible.
//
//
//Init this authentication plugin in the bootstrap with the following code
//
//	protected function _initAuthentication()
//	{
//	  $fc = Zend_Controller_Front::getInstance();
//	  $plugin = new Shinymayhem_Plugin_Authentication();
//	  $plugin->loginModule = "default";
//	  $plugin->loginController = "users";
//	  $plugin->loginAction = "login";
//	  $plugin->modular = true;
//	  //$plugin->acl = $acl;
//	  $fc->registerPlugin($plugin);
//	}
//
// when no acl is specified, every page is allowed

class Shinymayhem_Plugin_Authentication extends Zend_Controller_Plugin_Abstract
{
	private $_acl;
	private $_loginModule;
	private $_loginController;
	private $_loginAction;
	private $_user;
	private $_modular;

	public function Shinymayhem_Plugin_Authentication()
	{
		$this->_defaultValues();
		$auth = Zend_Auth::getInstance();
		$id = $auth->getIdentity();
		if ($id === null)
		{
			$id = 'guest';
		}
		$this->user = $id;
	}

	private function _defaultValues()
	{
		$this->loginModule="default";
		$this->loginController="users";
		$this->loginAction="login";
		$this->modular=false;
	}

	public function loginModule($loginModule)
	{
		$this->loginModule = $loginModule;
		return $this;
	}

	public function loginController($loginController)
	{
		$this->loginController = $loginController;
		return $this;
	}

	public function loginAction($loginAction)
	{
		$this->loginAction = $loginAction;
		return $this;
	}

	public function acl($acl)
	{
		$this->acl = $acl;
		return $this;
	}

	public function modular($modular)
	{
		$this->modular = $modular;
		return $this;
	}

	private function _defaultAcl(Zend_Controller_Request_Abstract $request)
	{
		$acl = new Zend_Acl();
		$role = $this->user;
		$privilege = 'action-' . $request->getActionName();
		//$privilege = new Shinymayhem_Resource_Action($request->getActionName());
		$acl->addRole($role);
		$resource = new Shinymayhem_Resource_Controller($request->getControllerName());
		$acl->add($resource);
		$acl->allow($role, $resource, $privilege);
		$resource = new Shinymayhem_Resource_Controller('error');
		$acl->add($resource);
		$acl->allow(null, $resource);
		$this->acl = $acl;
	}

	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		//if ($this->acl === null)
		if (empty($this->acl))
		{
			$this->_defaultAcl($request);
		}
		$role = $this->user;
		if ($this->modular)
		{
			$resource = new Shinymayhem_Resource_Controller($request->getModuleName() . "-" . $request->getControllerName());
		}
		else
		{
			$resource = new Shinymayhem_Resource_Controller($request->getControllerName());
		}
		$privilege = 'action-' . $request->getActionName();
		$debug = Zend_Registry::get('debug');
		if (!$this->acl->has($resource))
		{
			$this->acl->add($resource);
		}
		if (!$this->acl->hasRole($role))
		{
			$this->acl->addRole($role);
		}
		$allowed = $this->acl->isAllowed($role, $resource, $privilege);
		//TODO redirect to login in not allowed, save request first, and retry request after successful login
		//$debug['checking']['role'] = $role;
		//$debug['checking']['resource'] = $resource;
		//$debug['checking']['privilege'] = $privilege;
		//$debug['allowed'] = $this->acl->isAllowed($role, $resource, $privilege);
		//$debug['acl'] = $this->acl;
		//$debug['user'] = $this->user;
		$debug = Zend_Registry::set('debug', $debug);
	}
}
	
?>
