<?php
if (!class_exists("PlugView")) {
	exit;
}
global $manager;
?>

<h2><?php echo $plugin['name'] ?></h2>

<?php if ($message): ?>
	<p class="batchoperations" style="text-align:left"><?php echo htmlspecialchars($message) ?></p>
<?php endif; ?>

<ul>
  <li><?php echo _NP_PINGSERVER_GENERAL_SETTINGS ?></li>
  <li><a href="index.php?action=pluginoptions&amp;plugid=<?php echo $plugin['id'] ?>"><?php echo _NP_PINGSERVER_PLUGIN_OPTION ?></a></li>
  <li><a href="<?php echo $plugin['url'] ?>index.php?action=moduleorder"><?php echo _NP_PINGSERVER_MODULE_ORDERSETTING ?></a></li>
  <li><a href="<?php echo $plugin['helpurl'] ?>"><?php echo _LIST_PLUGS_HELP ?></a></p></li>
</ul>

<h2><?php echo _NP_PINGSERVER_GENERAL_SETTINGS ?></h2>

<h3><?php echo _NP_PINGSERVER_MODULE_TITLE  . $popup['module'] ?></h3>
<form method="post" action="<?php echo $plugin['url'] ?>index.php"><div>
  <input name="action" value="modulesinstaller" type="hidden" />
  <?php $manager->addTicketHidden() ?>
<table>
  <tr>
    <th><?php echo _LISTS_NAME ?></th>
    <th><?php echo _LISTS_DESC ?></th>
    <th><?php echo _NP_PINGSERVER_MODULE_STATE ?></th>
  </tr>
  <?php foreach($modules as $module): ?>
  <tr>
    <td><?php echo $module['dname'] ?></td>
    <td><?php echo $module['desc'] ?></td>
    <td><?php ADMIN::input_yesno('modules[' . $module['name'] . ']', $module['enable']);?></td>
  </tr>
  <?php endforeach; ?>
</table>

		<input type="submit" value="<?php echo _SETTINGS_UPDATE_BTN ?>" />
</div></form>
