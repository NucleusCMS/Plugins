<?php
/*******************************************
* mysql_xxx => nucleus_mysql_xxx converter *
*                              for Nucleus *
*     ver 0.8.0   Written by Katsumi       *
*******************************************/

// The license of this script is GPL

function modifyConfigInstall(){
	// Modify config.php
	$pattern=array();
	$replace=array();
	array_push($pattern,'/^([\s\S]*?)include([^\(]*?)\(([\s\S]*?)\$([\s\S]+)\'globalfunctions.php\'([\s\S]*?)$/');
	array_push($replace,'$1include$2($3\$DIR_NUCLEUS.\'sqlite/sqlite.php\'$5'.'$1include$2($3\$$4\'globalfunctions.php\'$5');
	if (file_exists('./config.php')) {
		$before=read_from_file('./config.php');
		if (strpos($before,'sqlite.php')===false) {
			$before=file('./config.php');
			$after='';
			foreach($before as $line) $after.=preg_replace($pattern,$replace,$line);
			if (!write_to_file(realpath('./config.php'),$after)) ExitWithError();
		}
	}
	
	// Modify install.php
	if (file_exists('./install.php')) {
		$before=read_from_file('./install.php');
		if (strpos($before,'sqlite.php')===false) {
		
			// The same pattern/replce is also used for install.php
			array_push($pattern,'/aConfPlugsToInstall([\s\S]+)\'NP_SkinFiles\'/i');
			array_push($replace,'aConfPlugsToInstall$1\'NP_SkinFiles\',\'NP_SQLite\'');
			array_push($pattern,'/<input[^>]+name="mySQL_host"([^\/]+)\/>/i');
			array_push($replace,'<input name="mySQL_host" type="hidden" value="dummy" />Not needed for SQLite');
			array_push($pattern,'/<input[^>]+name="mySQL_user"([^\/]+)\/>/i');
			array_push($replace,'<input name="mySQL_user" type="hidden" value="dummy" />Not needed for SQLite');
			array_push($pattern,'/<input[^>]+name="mySQL_password"([^\/]+)\/>/i');
			array_push($replace,'<input name="mySQL_password" type="hidden" value="dummy" />Not needed for SQLite');
			array_push($pattern,'/<input[^>]+name="mySQL_database"([^\/]+)\/>/i');
			array_push($replace,'<input name="mySQL_database" type="hidden" value="dummy" />Not needed for SQLite');
			array_push($pattern,'/<input[^>]+name="mySQL_create"([^\)]+)<\/label>/i');
			array_push($replace,'Database will be created if not exist.');
			$before=file('./install.php');
			$after='<?php include("nucleus/sqlite/sqlite.php"); ?>';
			foreach($before as $line) $after.=preg_replace($pattern,$replace,$line);
			if (!write_to_file(realpath('./install.php'),$after)) ExitWithError();
		}
	}
	
	// Modify backup.php
	if (!modifyBackup('./nucleus/libs/backup.php')) // less than version 3.3
		modifyBackup('./nucleus/plugins/backup/NP_BackupAdmin.php','class'); // more than version 3.4 (??)

	// Modify install.sql
	if (file_exists('./install.sql')) {
		$before=file('./install.sql');
		$pluginoptiontable=false;
		$after='';
		foreach($before as $line){
			if ($pluginoptiontable) {
				if (preg_match('/TYPE\=MyISAM;/i',$line)) $pluginoptiontable=false;
				else if (preg_match('/`oid`[\s]+int\(11\)[\s]+NOT[\s]+NULL[\s]+auto_increment/i',$line))
					$line=preg_replace('/[\s]+auto_increment/i'," default '0'",$line);
			} else {
				if (preg_match('/CREATE[\s]+TABLE[\s]+`nucleus_plugin_option`/i',$line)) $pluginoptiontable=true;
			}
			$after.=$line;
		}
		if ($after!=$before) {
			if (!write_to_file(realpath('./install.sql'),$after)) ExitWithError();
		}
	}
}

function modifyBackup($file,$type='global'){
	if (!file_exists($file)) return false;
	$before=read_from_file($file);
	if (strpos($before,'sqlite_restore_execute_queries')===false) {
		$pattern='/_execute_queries[\s]*\(([^\)]+)\)[\s]*;/i';
		if ($type=='class') $pattern='/\$this->_execute_queries[\s]*\(([^\)]+)\)[\s]*;/i';
		$replace='sqlite_restore_execute_queries($1);';
		$after=preg_replace($pattern,$replace,$before);
		if (!write_to_file(realpath($file),$after)) ExitWithError();
	}
	return true;	
}

function seekPhpFiles($dir,&$phpfiles,$myself){
	if (!is_dir($dir)) return;
	$d = dir($dir);
	$dirpath=realpath($d->path);
	$dirs=array();
	if (substr($dirpath,-1)!='/' && substr($dirpath,-1)!="\\") $dirpath.='/';
	while (false !== ($entry = $d->read())) {
		if ($entry=='.' || $entry=='..') continue;
		if (is_file($dirpath.$entry) && substr($entry,-4)=='.php' && $entry!==$myself) array_push($phpfiles,realpath($dirpath.$entry));
		if (is_dir($dirpath.$entry) && $entry!='language' && $entry!='sqlite' )  array_push($dirs,realpath($dirpath.$entry));
	}
	$d->close();
	foreach($dirs as $dir) seekPhpFiles($dir,$phpfiles,$myself);
}
function changeFunctions($file){
	if (!is_file($file=realpath($file))) return false;
	if (!is_readable($file)) {
		echo "Cannot read: $file<br />\n";
		return false;
	}
	$before=read_from_file($file);
	$after=do_replace($before);
	if ($before!=$after) return write_to_file($file,$after);
	return true;
}
function do_replace(&$text) {
	// Do this process until change does not occur any more... 
	// Otherwise, sometime file is not completely modified.
	// This is indeed the case for BLOG.php.
	$after=$text;
	do $after=preg_replace('/([^_])mysql_([_a-z]+)([\s]*?)\(/','$1nucleus_mysql_$2(',($before=$after));
	while ($before!=$after);
	return $after;
}
function read_from_file($file) {
	if (function_exists('file_get_contents') ) $ret=file_get_contents($file);
	else {
		ob_start();
		readfile($file);
		$ret=ob_get_contents();
		ob_end_clean();
	}
	return $ret;
}
function write_to_file($file,&$text){
	if (!$handle = @fopen($file, 'w')) {
		echo "Cannot change: $file<br />\n";
		return false;
	}
	fwrite($handle,$text);
	fclose($handle);
	echo "Changed: $file<br />\n";
	return true;
}
function ExitWithError($text='Error occured.') {
	echo "$text</body></html>";
	exit;
}
?>