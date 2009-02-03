<?php

class UpdatePingServer_modulesoverview
{
	function execute(&$controller, $msg)
	{
		$admin =& $controller->getAdmin();
		$admin->memberAuth();

		$view =& $admin->getView();
		if ($msg) {
			$view->assign('message', _MESSAGE . ': ' . $msg);
		}

		$plugin =& $admin->getPlugin();
		$plugin->moduleAdmin->getModuleList();
		$allowed_modules = $plugin->moduleAdmin->moduleList;
		$modulePrefix    = $plugin->moduleAdmin->getModulePrefix();

		$module_dir = $plugin->moduleAdmin->getModuleDir();
		$modules    = array();
		$pattern    = 
		$d = dir(substr($module_dir, 0, -1));
		while (false !== ($file = $d->read())) {
			$classname = substr($file, 0, -4);
			if (preg_match('/^' . $modulePrefix . '([-_a-zA-Z0-9.]+)\.php$/', $file, $m)) {
				$moduleClass = $plugin->moduleAdmin->getModule($m[1]);
				$modules[$classname]          = array();
				$modules[$classname]['name']  = $m[1];
				$modules[$classname]['dname'] = $moduleClass->getModuleName();
				$modules[$classname]['desc']  = $moduleClass->getModuleDescription();
				$modules[$classname]['order'] = $plugin->moduleAdmin->getModuleOrder($m[1]);
				if (in_array($m[1], $allowed_modules)) {
					$modules[$classname]['enable'] = 1;
				} else {
					$modules[$classname]['enable'] = 0;
				}
			}
		}
		$d->close();

		$view->assign('modules', $modules);

		$popup              = array();
		$popup['module']    = $admin->createPopup('module');
		$popup['rankbasic'] = $admin->createPopup('rankbasic');

		$view->assign('popup', $popup);

		$view->display('overview.tpl.php');
	}
}

?>