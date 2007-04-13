<?php

class PlugController
{
	var $admin;
	
	function PlugController($plugin_name)
	{
		$adminclass = $plugin_name . '_Management';
		$this->admin = new $adminclass($plugin_name);
		$this->admin->init();
		
		if (!$this->admin->getActionDir()) {
			$this->admin->error('Action Directory not found.');
		}
	}

	
	function &getAdmin()
	{
		return $this->admin;
	}

	
	function forward($action, $msg = '')
	{
		
		$dir = $this->admin->getActionDir();
		$fprefix = $this->admin->getActionFilePrefix();
		$cprefix = $this->admin->getActionClassPrefix();
		$default = $this->admin->getDefaultAction();
		
		$action = preg_replace("/[^a-z_]+/", "", $action);
		$action_file = $dir . $fprefix . $action . ".php";
		
		if ($action && is_readable($action_file)) {
			require_once($action_file);
			
		} elseif ($default) {
			$action_file = $dir . $fprefix . $default . ".php";
			if (is_readable($action_file)) {
				$action = $default;
				require_once($action_file);
			}
		}

		if (!$action) {
			$this->admin->disallow();
		}

		$class = $cprefix . $action;
		
		if (class_exists($class)) {
			$obj = new $class();
			
			if (method_exists($obj, "execute")) {
				$obj->execute(&$this, $msg);
				
			} else {
				$this->admin->error('Method not found.');
			}
			
		} else {
			$this->admin->error('Class not found.');
			
		}
		
	}


	function existsAction($action_name)
	{
		$class = $this->admin->getActionClassPrefix() . $action_name;
		
		if (!class_exists($class)) {
			$dir = $this->admin->getActionDir();
			$fprefix = $this->admin->getActionFilePrefix();
			$action_file = $dir . $fprefix . $action_name . '.php';
			if (is_readable($action_file)) {
				require_once($action_file);
			}
		}

		return (class_exists($class)) ? true : false;
	}
	
}


?>