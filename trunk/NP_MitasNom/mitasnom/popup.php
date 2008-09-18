<?php
/*
 * NP_MitasNom
 * This library is GPL
 */
	$strRel = '../../../'; 
	require($strRel . 'config.php');

	// create the plugin object
	if (!$member->isLoggedIn()) exit;
	$p=&$manager->getPlugin('NP_MitasNom');
	
	if (!class_exists('NucleusFCKeditor')) include (dirname(__FILE__).'/fckclass.php');
	
	$id=htmlspecialchars(requestVar('id'),ENT_QUOTES);
	$blogid=(int)requestVar('blogid');
?><html>
<head>
<script type="text/javascript">
function getId(){
<?php echo "return '$id';\n"; ?>
}
function event_onload(){
  var id=getId();
  if (!id) return;
  document.getElementById('inputbody').value=window.opener.WYSIWYGgettext(id);
}
function event_onsubmit(){
  var id=getId();
  if (!id) return;
  window.opener.focus(); 
  window.opener.WYSIWYGsettext(id,document.getElementById('inputbody').value);
  window.close();
}
</script>
<body>
<form method="get" action="javascript:event_onsubmit();">
<?php
	$FCKedit=new NucleusFCKEditor('inputbody',$p);
	$FCKedit->Width='100%';
	$FCKedit->Height='100%';
	$FCKedit->Create();
?>
<script type="text/javascript">
event_onload();
</script>
</form>
</body></html>