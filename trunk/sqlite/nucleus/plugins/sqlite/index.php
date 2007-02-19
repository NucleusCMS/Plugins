<?php
    /*****************************
    * SQLite database tool       *
    *                 ver 0.8.0  *
    * Written by Katsumi         *
    *****************************/
    
// This library is GPL.

	include('../../../config.php');
	
	include($DIR_LIBS . 'PLUGINADMIN.php');

	// create the admin area page
	$pa = new PluginAdmin('SQLite');
	$pa->start();
	$p=&$pa->plugin;
	$pluginUrl=$p->getAdminURL();


	// check if superadmin is logged in
	if (!($member->isLoggedIn() && $member->isAdmin()))
	{
		echo '<p>' . _ERROR_DISALLOWED . '</p>';
		$pa->end();
		exit;
	}

	// Check ticket
	if (requestVar('SQLiteCommand') && (!$manager->checkTicket())){
		echo '<p>' . _ERROR_BADTICKET . '</p>';
		$pa->end();
		exit;
	}
	$ticket=$manager->addTicketToUrl('');
	$ticket=substr($ticket,strpos($ticket,'ticket=')+7);

?><script type="text/javascript">
//<![CDATA[
function $(id) {
  return document.getElementById(id);
}
//]]>
</script><?php

	$idnum=0;
	echo '<h2>'.$p->translated('SQLite management')."</h2>\n";
	
	$infostr='';
	switch(requestVar('SQLiteCommand')){
	case 'VACUUM':
		nucleus_mysql_query('VACUUM');
		$infostr='VACUUM was done.';
		break;
	case 'integrity_check':
		$res=nucleus_mysql_query('PRAGMA integrity_check');
		$infostr='Integrity check result:'."<br />\n";
		while ($a=nucleus_mysql_fetch_array($res)) $infostr.=$a[0]."<br />\n";
		break;
	case 'plugin_check':
		$pluginfile=requestVar('plugin');
		$query='SELECT COUNT(*) as result FROM `'.sql_table('plugin').'` WHERE pfile="'.addslashes($pluginfile).'"';
		if (!quickQuery($query)) {
			$infostr="No such plugin!";
			break;
		}
		if ($p->modify_plugin($pluginfile)) {
			$infostr=$p->translated('The plugin, ').$pluginfile.$p->translated(' was checked and modified (if modification required)');
			break;
		}

		// Modification failed.  Show the lines that must be modified
		$phpfiles=array();
		array_push($phpfiles,$DIR_PLUGINS.$pluginfile.'.php');
		$admindir=$DIR_PLUGINS.strtolower(substr($pluginfile,3));
		$p->seekPhpFiles($admindir,$phpfiles);
		$infostr=$p->translated('Please modify the PHP files').": <br />\n";
		foreach ($phpfiles as $file) $infostr.=$p->show_Lines($file);
		break;
	case "QUERY":
		$infostr='<table><tr><th>'.'Query'.
'&nbsp;&nbsp;&nbsp;&nbsp;(-&gt;<a href="javascript:Copy this query" onclick="
$(\'ExecQuery\').value=$(\'QueryShown\').innerHTML;
return false;
">Copy</a>)'.
		"</th></td><tr><td id=\"QueryShown\">\n".htmlspecialchars($query=requestVar('query'))."</td></tr></table><br />\n";
		if (requestVar('confirm')!='confirmed'){
			$infostr=$p->translated('Please check the "I am sure." checkbox to execute query').$infostr;
			break;
		}
		ob_start();
		$res=nucleus_mysql_query($query);
		$errorstr=ob_get_contents();
		ob_end_clean();
		if (!$res) {
			$infostr.=nucleus_mysql_error()."<br />\n";
			if (preg_match('/sqlite_query\(\):([^<]*)in <b>/i',$errorstr,$matches)) $infostr.=$matches[1];
			break;
		}
		if (preg_match('/ OFFSET ([0-9]+)$/i',$query,$matches)) $offset=$matches[1];
		else $offset=0;
		
		// Get resut into an array
		$resulttable=array();
		$columnname=array();
		$columnnum=0;
		while ($a=nucleus_mysql_fetch_array($res,SQLITE_ASSOC)) {
			if ($columnnum<count($a)) {
				$i=0;
				foreach ($a as $key=>$value) $columnname[$i++]=$key;
				$columnnum=count($a);
			}
			$templine=array();
			foreach ($a as $key=>$value) $templine[$key]=$value;
			array_push($resulttable,$templine);
		}
		
		// Create table HTML from the array
		$infostr.="<table><tr>";
		for ($i=0;$i<$columnnum;$i++) $infostr.="<th>".$columnname[$i]."</th>";
		$infostr.="</tr>\n";
		foreach ($resulttable as $templine) {
			$infostr.="<tr>";
			for ($i=0;$i<$columnnum;$i++) {
				$value=(string)$templine[$columnname[$i]];
				if (50<strlen($value)) {
					$value='<span id="sqliteobj'.$idnum.'"><a href="" title="'.'Click this to show all'.'" onclick="'.
						'$(\'sqliteobj'.$idnum.'\').innerHTML='.
						'$(\'sqliteobj'.$idnum.'-2\').innerHTML;'.
						'return false;">'.htmlspecialchars(substr($value,0,50)).".....</a></span>\n".
						'<span style="DISPLAY:none;" id="sqliteobj'.$idnum.'-2">'.htmlspecialchars($value)."</span>\n";
					$idnum++;
				} else $value=htmlspecialchars($value);
				switch(requestVar('option')){
				case 'showalltables':
					if ($columnname[$i]!='name') break;
					$query="SELECT * FROM '$value' LIMIT 10";
					$value='<a href="'.htmlspecialchars($pluginUrl).
						'?SQLiteCommand=QUERY&confirm=confirmed&option=showtable&ticket='.$ticket.
						'&query='.htmlspecialchars($query).'">'.
						$value.'</a>';
					break;
				default:
				}
				$value=preg_replace('/\\n$/','',$value);
				$value=str_replace("\n","<br />\n",$value);
				$infostr.="<td>".$value."</td>";
			}
			$infostr.="</tr>\n";
			$offset++;
		}
		$infostr.="</table>\n";
		switch(requestVar('option')){
		case 'showtable':
			$query=requestVar('query');
			$offset=(int)$offset;
			$query=preg_replace('/ OFFSET ([0-9]+)$/i','',$query)." OFFSET $offset";
			$res=nucleus_mysql_query($query);
			if (!nucleus_mysql_fetch_array($res)) break;
			$infostr.='
<form method="POST" action="'.htmlspecialchars($pluginUrl).'">
<input type="hidden" name="SQLiteCommand" value="QUERY">
<input type="hidden" name="confirm" value="confirmed">
<input type="hidden" name="ticket" value="'.$ticket.'">
<input type="hidden" name="query" value="'.htmlspecialchars($query).'">
<input type="hidden" name="option" value="showtable">
<input type="submit" value="More">
</form>';
			break;
		default:
		}
		break;
	default:
	}
	
	echo $p->translated('PHP version: ').phpversion()."<br />\n";
	if ($res = nucleus_mysql_query('SELECT sqlite_version();')) $ret = nucleus_mysql_fetch_array($res);
	if (!$ret) $SQLiteVersion='?.?.?';
	else if (!($SQLiteVersion=$ret[0])) $SQLiteVersion='?.?.?';
	echo $p->translated('SQLite DB version: ').$SQLiteVersion."<br />\n";;
	echo $p->translated('SQLite wrapper version: ').$SQLITECONF['VERSION']."<br />\n";
	echo $p->translated('SQLite DB file size: ').filesize($SQLITECONF['DBFILENAME'])." bytes<br />\n";
	echo "<hr />\n";

?><table><tr><th><?php echo $p->translated('Tools'); ?></th><th><?php echo $p->translated('Execute SQL Query'); ?></th></tr>
<tr><td>
<form method="POST" action="<?php echo htmlspecialchars($pluginUrl);?>">
<input type="hidden" name="SQLiteCommand" value="VACUUM">
<input type="hidden" name="ticket" value="<?php echo $ticket; ?>">
<input type="submit" value="<?php echo $p->translated('VACUUM'); ?>">
</form>
<form method="POST" action="<?php echo htmlspecialchars($pluginUrl);?>">
<input type="hidden" name="SQLiteCommand" value="integrity_check">
<input type="hidden" name="ticket" value="<?php echo $ticket; ?>">
<input type="submit" value="<?php echo $p->translated('Integrity Check'); ?>">
</form>
<form method="POST" action="<?php echo htmlspecialchars($pluginUrl);?>">
<select name="plugin">
<?php
	$res=nucleus_mysql_query('SELECT pfile FROM `'.sql_table('plugin').'`');
	while($result=nucleus_mysql_fetch_row($res)) {
		if (requestVar('plugin')==$result[0]) echo '<option selected value="'.$result[0].'">'.$result[0]."</option>\n";
		else echo '<option value="'.$result[0].'">'.$result[0]."</option>\n";
	}
?>
</select>
<input type="hidden" name="SQLiteCommand" value="plugin_check">
<input type="hidden" name="ticket" value="<?php echo $ticket; ?>">
<input type="submit" value="<?php echo $p->translated('Check plugin'); ?>">
</form>
<?php
	if ('yes'==$p->getOption('allowsql')) {
?><form method="POST" action="<?php echo htmlspecialchars($pluginUrl);?>">
<input type="hidden" name="SQLiteCommand" value="QUERY">
<input type="hidden" name="ticket" value="<?php echo $ticket; ?>">
<input type="hidden" name="confirm" value="confirmed">
<input type="hidden" name="query" value="SELECT name, sqlite_table_structure(name) as table_structure FROM sqlite_master WHERE type='table'">
<input type="hidden" name="option" value="showalltables">
<input type="submit" value="<?php echo $p->translated('Show all tables'); ?>">
</form><?php
	}
?>
</td><td><?php
	if ('yes'!=$p->getOption('allowsql')) echo $p->translated('Query not allowed. To use it, change the plugin option.');
	else {
?><form method="POST" action="<?php echo htmlspecialchars($pluginUrl);?>">
<input type="hidden" name="SQLiteCommand" value="QUERY">
<input type="hidden" name="ticket" value="<?php echo $ticket; ?>">
<textarea name="query" id="ExecQuery" cols="50" rows="10"></textarea><br />
<input type="submit" value="<?php echo $p->translated('Execute'); ?>">
<input type="checkbox" name="confirm" value="confirmed"><?php echo $p->translated('I am sure.'); ?>
</form>
<?php	}
?></td>
</table>
<br /><?php

	if ($infostr) echo "<hr />\n".$infostr;
	$pa->end();
?>