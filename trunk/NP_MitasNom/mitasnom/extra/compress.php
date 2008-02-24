<html><body><?php
/*
 * NP_MitasNom
 * Written by Katsumi
 * This library is GPL
 */
$strRel = '../../../../'; 
include($strRel . 'config.php');

if (!($member->isLoggedIn() && $member->isAdmin())) exit('Not logged in');
if (!$manager->checkTicket()) exit('Invalid ticket');

error_reporting (E_ERROR | E_WARNING | E_PARSE);

//$files=listup_files('../');
$files=listup_files('../editor');

if (!($fHandle=fopen('compress.dat','x'))) exit; //Overwrite prohibited.
$list='';
$fpoint=0;

foreach ($files as $value) {
	ob_start();
	$list.=readfile($value).'|'.$value."\n";// The data at each line is: "filesize|filename"
	$data=ob_get_contents();
	ob_end_clean();
	fwrite($fHandle,$data);
	echo "$value<br />\n";
}
fclose($fHandle);

if (!($fHandle=fopen('compress.lst','x'))) exit; //Overwrite prohibited.
fwrite($fHandle,$list);
fclose($fHandle);

function listup_files($dir){
	$dir=str_replace('\\','/',$dir);// Windows support.
	$dir=preg_replace('/[\/]$/','',$dir).'/';
	$files=array();
	if (!is_dir($dir)) return $files;
	$d = dir($dir);
	while (false !== ($entry = $d->read())) {
		if ($entry=='.'||$entry=='..') continue;// Ignore this and parent directory.
		if (is_dir($dir.$entry)) {// The case of directory
			foreach(listup_files($dir.$entry) as $value) $files[]=$value;
			continue;
		}
		$files[]=$dir.$entry;
	}
	$d->close();
	return $files;
}
?>All Done!</body></html>