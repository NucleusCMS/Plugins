<?php
    /***************************************
    * SQLite-MySQL database transfer tool  *
    *                           ver 0.8.0  *
    * Written by Katsumi                   *
    ***************************************/
    
// This library is GPL.

error_reporting(E_ERROR | E_WARNING);
chdir('../../../');
if (!file_exists('./nucleus/sqlite/sqlite.php')) exit;

if (!isset($_GET['dbfile'])) exit;

$dbfile=str_replace(array('\\','/'),array('',''),$_GET['dbfile']);
if (isset($_GET['numatonce'])) $numatonce=(int)$_GET['numatonce'];
else $numatonce=20;
if (isset($_GET['refreshwait'])) $refreshwait=(int)$_GET['refreshwait'];
else $refreshwait=1;

// Check $dbfile
if (substr($dbfile,0,1)=='.') exit;
if (preg_match('/\.php$/i',$dbfile)) exit;
if (preg_match('/\.htm$/i',$dbfile)) exit;
if (preg_match('/\.html$/i',$dbfile)) exit;
if (!file_exists('./nucleus/sqlite/'.$dbfile)) exit;

include ('./nucleus/sqlite/sqlite.php');

$dbarray=file('./nucleus/sqlite/'.$dbfile);
if (isset($_GET['dbpoint'])) $dbpoint=(int)$_GET['dbpoint'];
else $dbpoint=0;
$ret='';
$err=false;

while ($numatonce--) {
	$query='';
	$instring=false;
	$cont=true;
	
	//Remove comment
	while (@is_string(($t=$dbarray[$dbpoint]))) {
		if (trim($t)!='' && substr($t,0,1)!='#' && substr($t,0,2)!='--') break;
		$dbpoint++;
	}
	
	//Get query string from array
	while ($cont && @is_string($dbarray[$dbpoint])) {
		$t=$dbarray[$dbpoint++];
		while ($t) {
			if ($instring) {
				for ($i=0;$i<strlen($t);$i++) {
					$query.=($c=$t[0]);
					$t=substr($t,1);
					if ($c=="'") {
						$instring=false;
						break;
					} else if ($c=="\\") {
						$query.=$t[0];
						$t=substr($t,1);
					}
				}
				continue;
			}
			if (($c=$t[0])==';') {
				$cont=false;
				break;
			}
			$query.=$c;
			$t=substr($t,1);
			if ($c=="'") $instring=true;
		}
	}
	if ($query) {
		if (nucleus_mysql_query($query)) $ret.="OK<br/>".htmlspecialchars(substr($query,0,200)).".....<hr />\n";
		else $err=true;
	}
}
nucleus_mysql_close();

//Set 'dbtotal'
$dbtotal=$dbpoint;
while (@is_string(($dbarray[$dbtotal]))) $dbtotal++;

if ($dbtotal==$dbpoint)  { // All done.
	unlink('./nucleus/sqlite/'.$dbfile);
	if (@include('./nucleus/language/japanese-utf8.php')) $lng='UTF-8';
	else if (@include('./nucleus/language/japanese-euc.php')) $lng='EUC-JP';
	else $lng='';
	echo "<html><head><title>Restore Complete</title>\n";
	if ($lng) echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=$lng\" />\n";
	echo "</head><body>\n";
	if ($lng) {
		echo '<p>'._RESTORE_COMPLETE.'</p>';
		echo '<p><a href="../../">'._BACKTOMANAGE.'</a></p>';
	} else {
		echo '<p>Restore Complete</p>';
		echo '<p><a href="../../">Back to Nucleus management</a></p>';
	}
	echo "\n</body></html>";
	exit;
}

$f=(int)((float)100*$dbpoint/$dbtotal);
$ret="$f % done.<hr>\n".$ret;

if (isset($_GET['numatonce'])) $numatonce=$_GET['numatonce'];
else $numatonce=20;
$url="?dbfile=$dbfile&amp;numatonce=$numatonce&amp;refreshwait=$refreshwait&amp;dbpoint=$dbpoint";
if ($err) $refresh='';
else $refresh="<meta http-equiv=\"refresh\" content=\"$refreshwait; url=$url\">";

?><html><head>
<title>Creating database file</title>
<?php echo $refresh; ?>
</head><body>
<a href="<?php echo $url; ?>">Continue</a><hr />
<?php echo $ret;
?></body></html>