<?php
    /****************************************
    * SQLite-MySQL wrapper for Nucleus      *
    *                           ver 0.9.0.5 *
    * Written by Katsumi                    *
    ****************************************/

// Check SQLite installed

if (!function_exists('sqlite_open')) exit('Sorry, SQLite is not available from PHP (maybe, not installed in the server).');

// Initializiation stuff
require_once dirname(__FILE__) . '/sqliteconfig.php';
$SQLITE_DBHANDLE=sqlite_open($SQLITECONF['DBFILENAME']);
require_once dirname(__FILE__) . '/sqlitequeryfunctions.php';
$SQLITECONF['VERSION']='0.9.0.5';

//Following thing may work if MySQL is NOT installed in server.
if (!function_exists('mysql_query')) {
	define ("MYSQL_ASSOC", SQLITE_ASSOC);
	define ("MYSQL_BOTH", SQLITE_BOTH);
	define ("MYSQL_NUM", SQLITE_NUM);
	function mysql_connect(){
		global $SQLITECONF;
		$SQLITECONF['OVERRIDEMODE']=true;
		$args=func_get_args();
		return call_user_func_array('nucleus_mysql_connect',$args);
	}
	foreach (array('mysql_affected_rows','mysql_change_user','mysql_client_encoding','mysql_close',
		'mysql_create_db','mysql_data_seek','mysql_db_name','mysql_db_query','mysql_drop_db','mysql_errno',
		'mysql_error','mysql_escape_string','mysql_fetch_array','mysql_fetch_assoc','mysql_fetch_field','mysql_fetch_lengths',
		'mysql_fetch_object','mysql_fetch_row','mysql_field_flags','mysql_field_len','mysql_field_name','mysql_field_seek',
		'mysql_field_table','mysql_field_type','mysql_free_result','mysql_get_client_info','mysql_get_host_info',
		'mysql_get_proto_info','mysql_get_server_info','mysql_info','mysql_insert_id','mysql_list_dbs',
		'mysql_list_fields','mysql_list_processes','mysql_list_tables','mysql_num_fields','mysql_num_rows','mysql_numrows',
		'mysql_pconnect','mysql_ping','mysql_query','mysql_real_escape_string','mysql_result','mysql_select_db',
		'mysql_stat','mysql_tablename','mysql_thread_id','mysql_unbuffered_query')
		 as $value) eval(
		"function $value(){\n".
		"  \$args=func_get_args();\n".
		"  return call_user_func_array('nucleus_$value',\$args);\n".
		"}\n");
}

// Empty object for mysql_fetch_object().
class SQLITE_OBJECT {}

function sqlite_ReturnWithError($text='Not supported',$more=''){
	// Show warning when error_reporting() is set.
	if (!(error_reporting() & E_WARNING)) return false;
	
	// Seek the file and line that originally called sql function.
	$a=debug_backtrace();
	foreach($a as $key=>$btrace) {
		if (!($templine=$btrace['line'])) continue;
		if (!($tempfile=$btrace['file'])) continue;
		$file=str_replace('\\','/',$file);
		if (!$line && !$file && strpos($tempfile,'/sqlite.php')===false && strpos($tempfile,'/sqlitequeryfunctions.php')===false) {
			$line=$templine;
			$file=$tempfile;
		}
		echo "\n<!--$tempfile line:$templine-->\n";
	}
	echo "Warning from SQLite-MySQL wrapper: $text<br />\n";
	if ($line && $file) echo "in <b>$file</b> on line <b>$line</b><br />\n";
	echo $more;
	return false;
}
function sqlite_DebugMessage($text=''){
	global $SQLITECONF;
	if (!$SQLITECONF['DEBUGREPORT']) return;
	if ($text) $SQLITECONF['DEBUGMESSAGE'].="\n".$text."\n";
	if (headers_sent()) {
		echo '<!--sqlite_DebugMessage'.$SQLITECONF['DEBUGMESSAGE'].'sqlite_DebugMessage-->';
		unset($SQLITECONF['DEBUGMESSAGE']);
	}
}

// nucleus_mysql_XXXX() functions follow.

function nucleus_mysql_connect($p1=null,$p2=null,$p3=null,$p4=null,$p5=null){
	// All prameters are ignored.
	global $SQLITE_DBHANDLE,$SQLITECONF;
	if (!$SQLITE_DBHANDLE) $SQLITE_DBHANDLE=sqlite_open($SQLITECONF['DBFILENAME']);
	// Initialization queries.
	foreach($SQLITECONF['INITIALIZE'] as $value) nucleus_mysql_query($value);
	// Unregister the function 'php' in sql query.
	sqlite_create_function($SQLITE_DBHANDLE,'php','pi');
	return $SQLITE_DBHANDLE;
}

function nucleus_mysql_close($p1=null){
	global $SQLITE_DBHANDLE;
	if (!($dbhandle=$p1)) $dbhandle=$SQLITE_DBHANDLE;
	$SQLITE_DBHANDLE='';
	return sqlite_close ($dbhandle);
}

function nucleus_mysql_select_db($p1,$p2=null){
	// SQLite does not support multiple databases in a file.
	// So this function do nothing and always returns true.
	// Note: mysql_select_db() function returns true/false,
	// not link-ID.
	return true;
}

function nucleus_mysql_query($p1,$p2=null,$unbuffered=false){//echo htmlspecialchars($p1)."<br />\n";
	global $SQLITE_DBHANDLE,$SQLITECONF;
	if (!($dbhandle=$p2)) $dbhandle=$SQLITE_DBHANDLE;
	$query=trim($p1);
	if (strpos($query,"\xEF\xBB\xBF")===0) $query=substr($query,3);// UTF-8 stuff
	if (substr($query,-1)==';') $query=substr($query,0,strlen($query)-1);
	
	// Escape style is changed from MySQL type to SQLite type here.
	// This is important to avoid possible SQL-injection.
	$strpositions=array();// contains the data show where the strings are (startposition => endposition)
	if (strpos($query,'`')!==false || strpos($query,'"')!==false || strpos($query,"'")!==false)
		$strpositions=sqlite_changeQuote($query);
	//echo "<br />".htmlspecialchars($p1)."<br /><br />\n".htmlspecialchars($query)."<hr />\n";

	// Debug mode
	if ($SQLITECONF['DEBUGMODE']) $query=sqlite_mysql_query_debug($query);
	
	// Anyway try it.
	if ($unbuffered) {
		if ($ret=@sqlite_unbuffered_query($dbhandle,$query)) return $ret;
	} else {
		if ($ret=@sqlite_query($dbhandle,$query)) return $ret;
	}
	
	// Error occured. Query must be translated.
	return sqlite_mysql_query_sub($dbhandle,$query,$strpositions,$p1,$unbuffered);
}
function sqlite_mysql_query_sub($dbhandle,$query,$strpositions=array(),$p1=null,$unbuffered=false){//echo htmlspecialchars($p1)."<br />\n";
	// Query translation is needed, especially when changing the data in database.
	// So far, this routine is written for 'CREATE TABLE','DROP TABLE', 'INSERT INTO',
	// 'SHOW TABLES LIKE', 'SHOW KEYS FROM', 'SHOW INDEX FROM'
	// and several functions used in query.
	// How about 'UPDATE' ???
	global $SQLITE_DBHANDLE,$SQLITECONF;
	$beforetrans=time()+microtime();
	if (!$p1) $p1=$query;
	$morequeries=array();
	$temptable=false;
	$uquery=strtoupper($query);
	if (strpos($uquery,'CREATE TABLE')===0 || ($temptable=(strpos($uquery,'CREATE TEMPORARY TABLE')===0))) {
		if (!($i=strpos($query,'('))) return sqlite_ReturnWithError('nucleus_mysql_query: '.$p1);
		//check if the command is 'CREATE TABLE IF NOT EXISTS'
		if (strpos(strtoupper($uquery),'CREATE TABLE IF NOT EXISTS')===0) {
			$tablename=trim(substr($query,26,$i-26));
			if (substr($tablename,0,1)!="'") $tablename="'$tablename'";
			$res=sqlite_query($dbhandle,"SELECT tbl_name FROM sqlite_master WHERE tbl_name=$tablename LIMIT 1");
			if (nucleus_mysql_num_rows($res)) return true;
		} else {
			$tablename=trim(substr($query,12,$i-12));
			if (substr($tablename,0,1)!="'") $tablename="'$tablename'";
		}
		$query=trim(substr($query,$i+1));
		for ($i=strlen($query);0<$i;$i--) if ($query[$i]==')') break;
		$query=substr($query,0,$i);
		$commands=_sqlite_divideByChar(',',$query);
		require_once(dirname(__FILE__) . '/sqlitealtertable.php');
		$query=sqlite_createtable_query($commands,$tablename,$temptable,$morequeries);
	} else if (strpos($uquery,'DROP TABLE IF EXISTS')===0) {
		if (!($i=strpos($query,';'))) $i=strlen($query);
		$tablename=trim(substr($query,20,$i-20));
		if (substr($tablename,0,1)!="'") $tablename="'$tablename'";
		$res=sqlite_query($dbhandle,"SELECT tbl_name FROM sqlite_master WHERE tbl_name=$tablename LIMIT 1");
		if (!nucleus_mysql_num_rows($res)) return true;
		$query='DROP TABLE '.$tablename;
	} else if (strpos($uquery,'ALTER TABLE ')===0) {
		$query=trim(substr($query,11));
		if ($i=strpos($query,' ')) {
			$tablename=trim(substr($query,0,$i));
			$query=trim(substr($query,$i));
			require_once(dirname(__FILE__) . '/sqlitealtertable.php');
			$ret =sqlite_altertable($tablename,$query,$dbhandle);
			if (!$ret) sqlite_ReturnWithError('SQL error',"<br /><i>".nucleus_mysql_error()."</i><br />".htmlspecialchars($p1)."<br /><br />\n".htmlspecialchars("ALTER TABLE $tablename $query")."<hr />\n");
			return $ret;
		}
		// Else, syntax error
	} else if (strpos($uquery,'RENAME TABLE ')===0) {
		require_once(dirname(__FILE__) . '/sqlitealtertable.php');
		return sqlite_renametable(_sqlite_divideByChar(',',substr($query,13)),$dbhandle);
	} else if (strpos($uquery,'INSERT INTO ')===0 || strpos($uquery,'REPLACE INTO ')===0 ||
			strpos($uquery,'INSERT IGNORE INTO ')===0 || strpos($uquery,'REPLACE IGNORE INTO ')===0) {
		$buff=str_replace(' IGNORE ',' OR IGNORE ',substr($uquery,0,($i=strpos($uquery,' INTO ')+6)));
		$query=trim(substr($query,$i));
		if ($i=strpos($query,' ')) {
			$buff.=trim(substr($query,0,$i+1));
			$query=trim(substr($query,$i));
		}
		if ($i=strpos($query,' ')) {
			if (strpos(strtoupper($query),'SET')===0) {
				$query=trim(substr($query,3));
				$commands=_sqlite_divideByChar(',',$query);
				$query=' VALUES(';
				$buff.=' (';
				foreach($commands as $key=>$value){
					//echo "[".htmlspecialchars($value)."]";
					if (0<$key) {
						$buff.=', ';
						$query.=', ';
					}
					if ($i=strpos($value,'=')) {
						$buff.=trim(substr($value,0,$i));
						$query.=substr($value,$i+1);
					}
				}
				$buff.=')';
				$query.=')';
			} else {
				$beforevalues='';
				$commands=_sqlite_divideByChar(',',$query);
				$query='';
				foreach($commands as $key=>$value){
					if ($beforevalues=='' && preg_match('/^(.*)\)\s+VALUES\s+\(/i',$value,$matches)) {
						$beforevalues=$buff.' '.$query.$matches[1].')';
					}
					if (0<$key) $query.=$beforevalues.' VALUES ';// supports multiple insertion
					$query.=$value.';';
				}
			}
		}
		$query=$buff.' '.$query;
	} else if (strpos($uquery,'SHOW TABLES LIKE ')===0) {
		$query='SELECT name FROM sqlite_master WHERE type=\'table\' AND name LIKE '.substr($query,17);
	} else if (strpos($uquery,'SHOW TABLES')===0) {
		$query='SELECT name FROM sqlite_master WHERE type=\'table\'';
	} else if (strpos($uquery,'SHOW KEYS FROM ')===0) {
		require_once(dirname(__FILE__) . '/sqlitealtertable.php');
		$query=sqlite_showKeysFrom(trim(substr($query,15)),$dbhandle);
	} else if (strpos($uquery,'SHOW INDEX FROM ')===0) {
		require_once(dirname(__FILE__) . '/sqlitealtertable.php');
		$query=sqlite_showKeysFrom(trim(substr($query,16)),$dbhandle);
	} else if (strpos($uquery,'SHOW FIELDS FROM ')===0) {
		require_once(dirname(__FILE__) . '/sqlitealtertable.php');
		$query=sqlite_showFieldsFrom(trim(substr($query,17)),$dbhandle);
	} else if (strpos($uquery,'SHOW COLUMNS FROM ')===0) {
		require_once(dirname(__FILE__) . '/sqlitealtertable.php');
		$query=sqlite_showFieldsFrom(trim(substr($query,18)),$dbhandle);
	} else if (strpos($uquery,'TRUNCATE TABLE ')===0) {
		$query='DELETE FROM '.substr($query,15);
	} else if (preg_match('/^DESC \'([^\']+)\' \'([^\']+)\'$/',$query,$m)) {
		return nucleus_mysql_query("SHOW FIELDS FROM '$m[1]' LIKE '$m[2]'");
	} else if (preg_match('/^DESC ([^\s]+) ([^\s]+)$/',$query,$m)) {
		return nucleus_mysql_query("SHOW FIELDS FROM '$m[1]' LIKE '$m[2]'");
	} else SQLite_Functions::sqlite_modifyQueryForUserFunc($query,$strpositions);

	//Throw query again.
	$aftertrans=time()+microtime();
	if ($unbuffered) {
		$ret=sqlite_unbuffered_query($dbhandle,$query);
	} else {
		$ret=sqlite_query($dbhandle,$query);
	}

	$afterquery=time()+microtime();
	if ($SQLITECONF['MEASURESPEED']) sqlite_DebugMessage("translated query:$query\n".
		'translation: '.($aftertrans-$beforetrans).'sec, query: '.($afterquery-$aftertrans).'sec');
	if (!$ret) sqlite_ReturnWithError('SQL error',"<br /><i>".nucleus_mysql_error()."</i><br />".htmlspecialchars($p1)."<br /><br />\n".htmlspecialchars($query)."<hr />\n");
	foreach ($morequeries as $value) if ($value) @sqlite_query($dbhandle,$value);
	return $ret;
}
function sqlite_changeQuote(&$query){
	// This function is most important.
	// When you modify this function, do it very carefully.
	// Otherwise, you may allow crackers to do SQL-injection.
	// This function returns array that shows where the strings are.
	$sarray=array();
	$ret='';
	$qlen=strlen($query);
	for ($i=0;$i<$qlen;$i++) {
		// Check MySQL specific comment, '--'.
		$temp=substr($query,$i);
		if (preg_match('/^([^"`\']*)[\r\n]\-\-[\s^\r\n][^\r\n]*/',$temp,$m)) {
			// Found.
			$ret.=preg_replace('/[\s]+/',' ',$m[1]); // Change all spacing to ' '.
			$i += strlen($m[0]);
			continue;
		}
		// Go to next quote
		if (($i1=strpos($query,'"',$i))===false) $i1=$qlen;
		if (($i2=strpos($query,"'",$i))===false) $i2=$qlen;
		if (($i3=strpos($query,'`',$i))===false) $i3=$qlen;
		if ($i1==$qlen && $i2==$qlen && $i3==$qlen) {
			$temp=preg_replace('/[\s]+/',' ',substr($query,$i)); // Change all spacing to ' '.
			$ret.=($temp);
			if (strstr($temp,';')) exit('Warning: try to use more than two queries?');
			break;
		}
		if ($i2<($j=$i1)) $j=$i2;
		if ($i3<$j) $j=$i3;
		$temp=preg_replace('/[\s]+/',' ',substr($query,$i,$j-$i)); // Change all spacing to ' '.
		$ret.=($temp);
		$c=$query[($i=$j)]; // $c keeps the type of quote.
		if (strstr($temp,';')) exit('Warning: try to use more than two queries?');
		
		// Check between quotes.
		// $j shows the begging positioin.
		// $i will show the ending position.
		$j=(++$i);
		while ($i<$qlen) {
			if (($i1=strpos($query,$c,$i))===false) $i1=$qlen;
			if (($i2=strpos($query,"\\",$i))===false) $i2=$qlen;
			if ($i2<$i1) {
				// \something. Skip two characters.
				$i=$i2+2;
				continue;
			} if ($i1<($qlen-1) && $query[$i1+1]==$c) {
				// "", '' or ``.  Skip two characters.
				$i=$i1+2;
				continue;
			} else {// OK. Reached the end position
				$i=$i1;
				break;
			}
		}
		$i1=strlen($ret);
		$ret.="'".sqlite_changeslashes(substr($query,$j,$i-$j));
		if ($i<$qlen) $ret.="'"; //else Syntax error in query.
		$i2=strlen($ret);
		$sarray[$i1]=$i2;
	}//echo htmlspecialchars($query).'<br />'.htmlspecialchars($ret).'<br />';
	$query=$ret;
	return $sarray;
}
function sqlite_changeslashes(&$text){
	// By SQLite, "''" is used in the quoted string instead of "\'".
	// In addition, only "'" seems to be allowed for perfect quotation of string.
	// This routine is used for the conversion from MySQL type to SQL type.
	// Do NOT use stripslashes() but use stripcslashes().  Otherwise, "\r\n" is not converted.
	if ($text==='') return '';
	return (sqlite_escape_string (stripcslashes((string)$text)));
}
function _sqlite_divideByChar($char,$query,$limit=-1){
	if (!is_array($char)) $char=array($char);
	$ret=array();
	$query=trim($query);
	$buff='';
	while (strlen($query)){
		$i=strlen($query);
		foreach($char as $value){
			if (($j=strpos($query,$value))!==false) {
				if ($j<$i) $i=$j;
			}
		}
		if (($j=strpos($query,'('))===false) $j=strlen($query);
		if (($k=strpos($query,"'"))===false) $k=strlen($query);
		if ($i<$j && $i<$k) {// ',' found
			$buff.=substr($query,0,$i);
			if (strlen($buff)) $ret[]=$buff;
			$query=trim(substr($query,$i+1));
			$buff='';
			$limit--;
			if ($limit==0) exit;
		} else if ($j<$i && $j<$k) {// '(' found
			if (($i=strpos($query,')',$j))===false) {
				$buff.=$query;
				if (strlen($buff)) $ret[]=$buff;
				$query=$buff='';
			} else {
				$buff.=substr($query,0,$i+1);
				$query=substr($query,$i+1);
			}
		} else if ($k<$i && $k<$j) {// "'" found
			if (($i=strpos($query,"'",$k+1))===false) {
				$buff.=$query;
				if (strlen($buff)) $ret[]=$buff;
				$query=$buff='';
			} else {
				$buff.=substr($query,0,$i+1);
				$query=substr($query,$i+1);
			}
		} else {// last column
			$buff.=$query;
			if (strlen($buff)) $ret[]=$buff;
			$query=$buff='';
		}
	}
	if (strlen($buff)) $ret[]=$buff;
	return $ret;
}
function sqlite_mysql_query_debug(&$query){
	// There is nothing to do here in this version.
	return $query;
}

function nucleus_mysql_list_tables($p1=null,$p2=null) {
	global $SQLITE_DBHANDLE,$MYSQL_DATABASE;
	return sqlite_query($SQLITE_DBHANDLE,"SELECT name as Tables_in_$MYSQL_DATABASE FROM sqlite_master WHERE type='table'");
}
function nucleus_mysql_listtables($p1=null,$p2=null) { return nucleus_mysql_list_tables($p1,$p2);}

function nucleus_mysql_affected_rows($p1=null){
	global $SQLITE_DBHANDLE;
	if (!($dbhandle=$p1)) $dbhandle=$SQLITE_DBHANDLE;
	return sqlite_changes($dbhandle);
}

function nucleus_mysql_error($p1=null){
	global $SQLITE_DBHANDLE;
	if (!($dbhandle=$p1)) $dbhandle=$SQLITE_DBHANDLE;
	return sqlite_error_string ( sqlite_last_error ($dbhandle) );
}

function nucleus_mysql_fetch_array($p1,$p2=SQLITE_BOTH){
	return sqlite_fetch_array ($p1,$p2);
}

function nucleus_mysql_fetch_assoc($p1){
	return sqlite_fetch_array($p1,SQLITE_ASSOC);
}

function nucleus_mysql_fetch_object($p1,$p2=SQLITE_BOTH){
	if (is_array($ret=sqlite_fetch_array ($p1,$p2))) {
		$o=new SQLITE_OBJECT;
		foreach ($ret as $key=>$value) {
			if (strstr($key,'.')) {// Remove table name.
				$key=preg_replace('/^(.+)\."(.+)"$/','"$2"',$key);
				$key=preg_replace('/^(.+)\.([^.^"]+)$/','$2',$key);
			}
			$o->$key=$value;
		}
		return $o;
	} else return false;
}

function nucleus_mysql_fetch_row($p1){
	return sqlite_fetch_array($p1,SQLITE_NUM);
}

function nucleus_mysql_field_name($p1,$p2){
	return sqlite_field_name ($p1,$p2);
}

function nucleus_mysql_free_result($p1){
	// ???? Cannot find corresponding function of SQLite.
	// Maybe SQLite is NOT used for the high spec server
	// that need mysql_free_result() function because of
	// many SQL-queries in a script.
	return true;
}

function nucleus_mysql_insert_id($p1=null){
	global $SQLITE_DBHANDLE;
	if (!($dbhandle=$p1)) $dbhandle=$SQLITE_DBHANDLE;
	return sqlite_last_insert_rowid ($dbhandle);
}

function nucleus_mysql_num_fields($p1){
	return sqlite_num_fields ($p1);
}

function nucleus_mysql_num_rows($p1){
	return sqlite_num_rows ($p1);
}
function nucleus_mysql_numrows($p1){
	return sqlite_num_rows ($p1);
}

function nucleus_mysql_result($p1,$p2,$p3=null){
	if ($p3) return sqlite_ReturnWithError('nucleus_mysql_result');
	if (!$p2) return sqlite_fetch_single ($p1);
	$a=sqlite_fetch_array ($p1);
	return $a[$p2];
}

function nucleus_mysql_unbuffered_query($p1,$p2=null){
	return nucleus_mysql_query($p1,$p2,true);
}

function nucleus_mysql_client_encoding($p1=null){
	return sqlite_libencoding();
}

function nucleus_mysql_data_seek($p1,$p2) {
	return sqlite_seek($p1,$p2);
}

function nucleus_mysql_errno ($p1=null){
	global $SQLITE_DBHANDLE;
	if (!($dbhandle=$p1)) $dbhandle=$SQLITE_DBHANDLE;
	return sqlite_last_error($dbhandle);
}

function nucleus_mysql_escape_string ($p1){
	// The "'" will be changed to "''".
	// This way works for both MySQL and SQLite when single quotes are used for string.
	// Note that single quote is always used in this wrapper.
	// If a plugin is made on SQLite-Nucleus and such plugin will be used for MySQL-Nucleus,
	// nucleus_mysql_escape_string() will be changed to mysql_escape_string() and
	// this routine won't be used, so this way won't be problem.
	return sqlite_escape_string($p1);
}

function nucleus_mysql_real_escape_string ($p1,$p2=null){
	//addslashes used here.
	return addslashes($p1);
}

function nucleus_mysql_create_db ($p1,$p2=null){
	// All prameters are ignored.
	// Returns always true;
	return true;
}

function nucleus_mysql_pconnect($p1=null,$p2=null,$p3=null,$p4=null,$p5=null){
	global $SQLITE_DBHANDLE,$SQLITECONF;
	sqlite_close ($SQLITE_DBHANDLE);
	$SQLITE_DBHANDLE=sqlite_popen($SQLITECONF['DBFILENAME']);
	return ($SQLITE['DBHANDLE']=$SQLITE_DBHANDLE);
}

function nucleus_mysql_fetch_field($p1,$p2=null){
	if ($p2) return sqlite_ReturnWithError('nucleus_mysql_fetch_field');
	// Only 'name' is supported.
	$o=new SQLITE_OBJECT;
	$o->name=array();
	if(is_array($ret=sqlite_fetch_array ($p1,SQLITE_ASSOC )))
		foreach ($ret as $key=>$value) {
			if (is_string($key)) array_push($o->name,$key);
		}
	return $o;

}
function nucleus_mysql_get_client_info(){
	return nucleus_mysql_get_server_info();
}
function nucleus_mysql_get_server_info(){
	$res=nucleus_mysql_query('SELECT sqlite_version();');
	if (!$res) return '?.?.?';
	$row=nucleus_mysql_fetch_row($res);
	if (!$row) return '?.?.?';
	return $row[0];
}

// This function is called instead of _execute_queries() in backp.php
function sqlite_restore_execute_queries(&$query){
	global $DIR_NUCLEUS,$DIR_LIBS,$DIR_PLUGINS,$CONF;
	
	// Skip until the first "#" or "--"
	if (($i=strpos($query,"\n#"))===false) $i=strlen($query);
	if (($j=strpos($query,"\n--"))===false) $j=strlen($query);
	if ($i<$j) $query=substr($query,$i+1);
	else  $query=substr($query,$j+1);
	
	// Save the query to temporary file in sqlite directory.
	if (function_exists('microtime')) {
		$prefix=preg_replace('/[^0-9]/','',microtime());
	} else {
		srand(time());
		$prefix=(string)rand(0,999999);
	}
	$tmpname=tempnam($DIR_NUCLEUS.'sqlite/',"tmp$prefix");
	if (!($handle=@fopen($tmpname,'w'))) return 'Cannot save temporary DB file.';
	fwrite($handle,$query);
	fclose($handle);
	$tmpname=preg_replace('/[\s\S]*?[\/\\\\]([^\/\\\\]+)$/','$1',$tmpname);
	
	// Read the option from NP_SQLite
	if (!class_exists('NucleusPlugin')) { include($DIR_LIBS.'PLUGIN.php');}
	if (!class_exists('NP_SQLite')) { include($DIR_PLUGINS.'NP_SQLite.php'); }
	$p=new NP_SQLite();
	if (!($numatonce=@$p->getOption('numatonce'))) $numatonce=20;
	if (!($refreshwait=@$p->getOption('refreshwait'))) $refreshwait=1;
	
	// Start process.
	$url="plugins/sqlite/restore.php?dbfile=$tmpname&numatonce=$numatonce&refreshwait=$refreshwait";
	header('HTTP/1.0 301 Moved Permanently');
	header('Location: '.$url);
	exit('<html><body>Moved Permanently</body></html>');
}

?>