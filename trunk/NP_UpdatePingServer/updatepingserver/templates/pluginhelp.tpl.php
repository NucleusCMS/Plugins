<?php
if (!class_exists("PlugView")) {
	exit;
}
?>
<h2><?php echo $plugin['name'] ?></h2>

<ul>
  <li><a href="<?php echo $plugin['url'] ?>"><?php echo _NP_PINGSERVER_GENERAL_SETTINGS ?></a></li>
  <li><a href="<?php echo $plugin['url'] ?>index.php?action=moduleorder"><?php echo _NP_PINGSERVER_MODULE_ORDERSETTING ?></a></li>
  <li><a href="index.php?action=pluginoptions&amp;plugid=<?php echo $plugin['id'] ?>"><?php echo _NP_PINGSERVER_PLUGIN_OPTION ?></a></li>
  <li><?php echo _LIST_PLUGS_HELP ?></p></li>
</ul>

<h2>NP_BlogMenu Help</h2>

<?php include_once($helpfile); ?>