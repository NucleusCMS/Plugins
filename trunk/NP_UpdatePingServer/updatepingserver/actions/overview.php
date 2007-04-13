<?php

class UpdatePingServer_overview
{
	function execute(&$controller, $msg)
	{
		$admin =& $controller->getAdmin();
		$admin->memberAuth();
		$controller->forward('modulesoverview');
	}
}

?>