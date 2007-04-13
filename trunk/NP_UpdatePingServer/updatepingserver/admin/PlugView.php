<?php

class PlugView
{
	var $vars = array();
	var $tpl_file;
	
	function PlugView() {}

	function assign($var, $value = null)
	{
		$this->vars[$var] = $value;
	}

	function unsetAll()
	{
		$this->vars = array();
	}

	function unsetVar($var)
	{
		unset($this->vars[$var]);
	}

	function display($file)
	{
		$this->tpl_file = $file;
		$this->_fetch();
		$this->tpl_file = null;
	}

	function _fetch()
	{
		if (count($this->vars)) extract($this->vars);
		if (file_exists($this->tpl_file)) include($this->tpl_file);
	}

}

?>