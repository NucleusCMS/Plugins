<?php

class PlugManagement
{
	/* PluginAdmin
	 * @var object
	 */
	var $p_admin;
	
	/* Plugin
	 * @var object
	 */
	var $plugin;

	/* PlugView
	 * @var object
	 */
	var $view;

	/**
	 * Constructor
	 *  - create admin, plugin, view object.
	 *
	 * Extended classes don't call this automatically
	 *    if those have Constructor
	 *
	 * @param string $plugin_name : Name of Plugin
	 * @return void
	 */
	function PlugManagement($plugin_name)
	{
		$this->p_admin = new PluginAdmin($plugin_name);
		$this->plugin =& $this->p_admin->plugin;
		$this->view = new PlugAdminView($this);
	}

	/**
	 * returns reference of plugin object
	 * @return object
	 */
	function &getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * returns reference of view object
	 * @return object
	 */
	function &getView()
	{
		return $this->view;
	}

	/**
	 * returns reference of PluginAdmin object
	 * @return object
	 */
	function &getPluginAdmin()
	{
		return $this->p_admin;
	}

	/**
	 * Function for not making action files
	 */
	function doAction($action)
	{
		if (method_exists($this, 'action_'.$action)) {
			call_user_func(array(&$this, 'action_'.$action));
		} else {
			$this->action_overview();
		}
	}

	/***************************************************
	  init, dir/file/class settings (abstract)
	****************************************************/
	function init() {}
	
	// directory of action files (abs path)
	function getActionDir()
	{
		return $this->plugin->getDirectory() . 'actions/';
	}
	
	// directory of template files (abs path)
	function getTemplateDir()
	{
		return $this->plugin->getDirectory() . 'templates/';
	}
	
	// prefix of action file name
	//    default action file name is (action).php
	function getActionFilePrefix() { return '';}
	
	// prefix of action class name
	function getActionClassPrefix()
	{
		return str_replace('np_', '', strtolower(get_class($this->plugin))) . '_';
	}
	
	// default action
	function getDefaultAction() { return 'overview';}


	/**********************************************
	  auth, error and exception
	**********************************************/
	/**
	 * display error page with "disallowed" message.
	 */
	function disallow()
	{
		$this->error(_ERROR_DISALLOWED);
	}

	/**
	 * display error page. 
	 * @pamam string $msg : message to show
	 */
	function error($msg)
	{
		$this->p_admin->start();

		$dir=$this->plugin->getAdminURL();
		?>
		<h2>Error!</h2>
		<?php		echo htmlspecialchars($msg);
		echo "<br />";
		echo "<a href='".$dir."index.php' onclick='history.back()'>"._BACK."</a>";
		
		$this->p_admin->end();
		exit;
	}


	/**
	 * authenticate the member
	 * @pamam string $level : permitted authority level
	 * @param number $extra : blogid (This is necessary excluding "Admin". )
	 */
	function memberAuth($level = 'Admin', $extra = '')
	{
		global $member;

		switch (strtolower($level)) {
		case 'admin':
			$member->isAdmin() or $this->disallow();
			break;
		case 'blogadmin':
			if (!$extra) {
				$this->error('Missing 2nd argument ('.__LINE__.')');
			}
			$member->isBlogAdmin($extra) or $this->disallow();
			break;
		case 'teammember':
			if (!$extra) {
				$this->error('Missing 2nd argument ('.__LINE__.')');
			}
			$member->isTeamMember($extra) or $this->disallow();
			break;
		}
	}


	/**********************************************
	  helper functions
	**********************************************/
	/**
	 * return popup help link
	 * @pamam string $url : url of popup help file
	 * @param string $anchor
	 */
	function createPopupHelpLink($url, $anchor)
	{
		$link = '<a href="'.$url.'#'. $anchor . '" onclick="if (event &amp;&amp; event.preventDefault) event.preventDefault(); return help(this.href);">' . '<img src="documentation/icon-help.gif" width="15" height="15" alt="'._HELP_TT.'" /></a>';
		return $link;
	}


}

class PlugAdminView extends PlugView
{
	var $p_manager;

	function PlugAdminView(&$p_manager)
	{
		$this->p_manager =& $p_manager;
	}
	
	function display($tpl)
	{
		$p_admin =& $this->p_manager->getPluginAdmin();
		
		$p_admin->start();

		$file = $this->p_manager->getTemplateDir() . $tpl;
		parent::display($file);

		$p_admin->end();
	}

}


?>
