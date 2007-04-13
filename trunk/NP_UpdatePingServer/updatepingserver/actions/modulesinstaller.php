<?php

class UpdatePingServer_modulesinstaller
{

	function execute(&$controller, $msg)
	{
		$admin =& $controller->getAdmin();
		$admin->memberAuth();

		$all_modules = requestArray('modules');
		if (!is_array($all_modules)) {
			$admin->error(_ERROR_BADACTION);
		}

		$plugin =& $admin->getPlugin();
		$plugin->moduleAdmin->getModuleList();

		$allowed_modules = $plugin->moduleAdmin->moduleList;

		foreach ($all_modules as $moduleName => $enable) {
			if ($enable) {
				if (!$plugin->moduleAdmin->checkModule($moduleName)) {
					$plugin->moduleAdmin->_moduleUnInstall($moduleName);
					$admin->error(_NP_PINGSERVER_ERROR_MODFILEERROR . ' (' . $moduleName . ')');
				} elseif (!in_array($moduleName, $allowed_modules)) {
					$plugin->moduleAdmin->_moduleInstall($moduleName);
				}
			} else {
				if (in_array($moduleName, $allowed_modules)) {
					$plugin->moduleAdmin->_moduleUnInstall($moduleName);
				}
			}
		}

		$controller->forward('modulesoverview', _NP_PINGSERVER_MODULE_UPDATED);
	}


}

?>