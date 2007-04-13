<?php

class moduleAdmin// extends NP_UpdatePingServer
{

var $myPlugin;
var $modules;
var $moduleList;
var $plugin_name;

	function moduleAdmin ($plugin_name)
	{
		global $manager;
		
		$this->myPlugin    =& $manager->getPlugin('NP_' . $plugin_name);
		$this->plugin_name =  $plugin_name;
		$this->getModuleList();
	}

	function &getPlugin()
	{
		return $this->myPlugin;
	}

	function getModuleDir()
	{
		return $this->myPlugin->getDirectory() . 'modules/';
	}

	function getModulePrefix()
	{
		return $this->plugin_name . 'Module_';
	}

	function getModuleTable()
	{
		return sql_table('plug_' . $this->getModuleTablePrefix() . '_modules');
	}

	function getModuleTablePrefix()
	{
		return $this->myPlugin->getShortName();
	}

	function _loadModule($name) {
		$moduleClass = $this->getModulePrefix() . $name;
		if (!class_exists($moduleClass)) {
			$moduleFile  = $this->getModuleDir() .$moduleClass . '.php';
			if (!file_exists($moduleFile)) {
				$errmsg = 'Module ' . $name . ' was not loaded (File not found)';
				ACTIONLOG::add(WARNING, $errmsg);
				return 0;
			}
			include($moduleFile);
			if (!class_exists($moduleClass)) {
				$errmsg = 'Module ' . $name . ' was not loaded (Class not found in file, possible parse error)';
				ACTIONLOG::add(WARNING, $errmsg);
				return 0;
			}
			eval('$this->modules[$name] =& new ' . $moduleClass . '();');
			$this->modules[$name]->initModule($this->myPlugin);
			return;
		}
		$this->modules[$name] = false;
	}

	function &getModule($name) {
		$module =& $this->modules[$name];

		if (!$module) {
			// load class if needed
			$this->_loadModule($name);
			$module =& $this->modules[$name];
		}
		return $module;
	}

	function getModuleOrder($name)
	{
		$sharedFunctions = new sharedFunctions();
		$query  = 'SELECT '
				. '      moduleorder as result '
				. 'FROM '
				.        $this->getModuleTable()
				. ' WHERE '
				. '      modulename = ' . $sharedFunctions->quoteSmart($name);
		return quickQuery($query);
	}

	function getModuleList()
	{
		$this->moduleList = array();

		$query  = 'SELECT '
				. '      modulename '
				. 'FROM '
				.        $this->getModuleTable()
				. ' ORDER BY '
				. '      moduleorder ASC';
		$result = sql_query($query);
		while ($o = mysql_fetch_object($result)) {
			$moduleName  = $o->modulename;
			$moduleClass = $this->getModulePrefix() . $moduleName;
			$moduleFile  = $this->getModuleDir() . $moduleClass . '.php';
			if (file_exists($moduleFile)) {
				$this->moduleList[] = $moduleName;
			}
		}
//	return $this->moduleList;
	}

	function checkModule($moduleName)
	{
		$moduleClass = $this->getModulePrefix() . $moduleName;
		$moduleFile  = $this->getModuleDir() . $moduleClass . '.php';
		return file_exists($moduleFile);
	}

	function _moduleInstall($moduleName)
	{
		$sharedFunctions = new sharedFunctions();

		$query = 'SELECT '
			   . '      COUNT(moduleid) as result '
			   . 'FROM '
			   .        $this->getModuleTable();
		$mods  = quickQuery($query);
		$order = $mods + 1;
		$query = 'INSERT INTO '
			   .        $this->getModuleTable()
			   . '     ('
			   . '      moduleorder, '
			   . '      modulename'
			   . '     ) '
			   . 'VALUES '
			   . '     ('
			   .        $order . ', '
			   .        $sharedFunctions->quoteSmart($moduleName)
			   . '     )';
		sql_query($query);
		$modId = mysql_insert_id();

		$module =& $this->getModule($moduleName);
		if (!$module) {
			$query = 'DELETE FROM '
				   .        $this->getModuleTable()
				   . ' WHERE '
				   . '      moduleid = ' . intval($modId);
			sql_query($query);
			$this->error(_NP_PINGSERVER_ERROR_MODNOTLOADED);
		}
		$module->initModule($this->myPlugin);
		if (method_exists($module, 'installModule')) {
			$module->installModule();
		}
	}

	function _moduleUnInstall($moduleName)
	{
		$sharedFunctions = new sharedFunctions();

		if (!$this->checkModule($moduleName)) {
			$this->error(_NP_PINGSERVER_ERROR_MODFILEERROR . ' (' . $moduleName . ')');
		}
		$module =& $this->getModule($moduleName);
		$module->initModule($this->myPlugin);
		if (method_exists($module, 'uninstall')) {
			$module->uninstall();
		}
		$query = 'DELETE FROM '
			   .        $this->getModuleTable()
			   . ' WHERE '
			   . '      modulename = ' . $sharedFunctions->quoteSmart($moduleName);
		sql_query($query);
	}

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




}


