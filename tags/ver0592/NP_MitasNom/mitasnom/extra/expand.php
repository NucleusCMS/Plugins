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

if (!file_exists('compress.lst')) exit('compress.lst not found');
if (!($fHandle=fopen('compress.dat','r'))) exit;

foreach(file('compress.lst') as $value) {
	list($filesize,$filename)=explode('|',$value);// The data contains "filesize|filename".
	$filesize=(int)$filesize;
	$filename=trim($filename);
	echo "($filesize)$filename<br />\n";
	$data=fread($fHandle,$filesize);
	mkdirex(dirname($filename));
	if (!($fHandle2=fopen($filename,'w'))) continue;// Overwrite allowed.
	fwrite($fHandle2,$data);
	fclose($fHandle2);
}

function mkdirex($dir,$mod='777'){
	$dir=str_replace('\\','/',$dir);
	if (substr($dir,0,1)=='/') $temp='/';
	else $temp='';
	eval('$i=0'.$mod.';');
	foreach(explode('/',$dir) as $value) {
		if (!$value) continue;
		$temp.=$value.'/';
		if (!file_exists($temp)) {
			@mkdir($temp);
			@chmod($temp,$i);
		}
	}
}
?>All Done!</body></html>