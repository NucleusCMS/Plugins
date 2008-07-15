<?php
/*
	NP_SimpleTag admin area
*/

$strRel = '../../../'; 
include($strRel . 'config.php');
include($DIR_LIBS . 'PLUGINADMIN.php');

$oPluginAdmin = new PluginAdmin('SimpleTag');
$oPluginAdmin->plugin->_makeAdminArea(&$oPluginAdmin);

?>
