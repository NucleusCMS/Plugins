<?php
    /***************************************
    * SQLite-MySQL wrapper for Nucleus     *
    *                           ver 0.8.5.2*
    * Written by Katsumi                   *
    ***************************************/
//
//  The licence of this script is GPL
//
//                ACKOWLEDGMENT
//
//  I thank all the people of Nucleus JP forum 
//  who discussed this project. Especially, I 
//  thank kosugiatkips, mekyo, and nakahara21 
//  for ideas of some part of code.
//  I also thank Jon Jensen for his generous
//  acceptance for using his PHP code in this
//  script.
//
//  The features that are supported by this script but not
//  generally by SQLite are as follows:
//
//  CREATE TABLE IF NOT EXISTS, auto_increment,
//  DROP TABLE IF EXISTS, ALTER TABLE, 
//  INSERT INTO ... SET xx=xx, xx=xx,
//  REPLACE INTO ... SET xx=xx, xx=xx,
//  SHOW KEYS FROM, SHOW INDEX FROM,
//  SHOW FIELDS FROM, SHOW COLUMNS FROM,
//  CREATE TABLE ... KEYS xxx (xxx,xxx)
//  SHOW TABLES LIKE, TRUNCATE TABLE
//  SHOW TABLES
//
// Release note:
//  Version 0.8.0
//    -This is the first established version and
//     exactly the same as ver 0.7.8b.
//
//  Version 0.8.1
//    -Execute "PRAGMA short_column_names=1" first.
//    -Avoid executing outside php file in some very specfic environment.
//    -Avoid executing multiple queries using ";" as delimer.
//    -Add check routine for the installed SQLite
//
//  Version 0.8.5
//    -Use SQLite_Functions class
//    -'PRAGMA synchronous = off;' when installing

// Check SQLite installed

if (!function_exists('sqlite_open')) exit('Sorry, SQLite is not installed in the server.');

// Initializiation stuff
require_once dirname(__FILE__) . '/sqliteconfig.php';
$SQLITE_DBHANDLE=sqlite_open($SQLITECONF['DBFILENAME']);
require_once dirname(__FILE__) . '/sqlitequeryfunctions.php';
$SQLITECONF['VERSION']='0.8.5';

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
		$file=str_replace("\\",'/',$file);
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
		$auto_increment=false;
		$commands=sqlite_splitByComma($query);
		$query=' (';
		$first=true;
		foreach($commands as $key => $value) {
			if (strpos(strtolower($value),'auto_increment')==strlen($value)-14) $auto_increment=true;
			$isint=preg_match('/int\(([0-9]*?)\)/i',$value);
			$isint=$isint | preg_match('/tinyint\(([0-9]*?)\)/i',$value);
			$value=preg_replace('/int\(([0-9]*?)\)[\s]+unsigned/i','int($1)',$value);
			$value=preg_replace('/int\([0-9]*?\)[\s]+NOT NULL[\s]+auto_increment$/i',' INTEGER NOT NULL PRIMARY KEY',$value);
			$value=preg_replace('/int\([0-9]*?\)[\s]+auto_increment$/i',' INTEGER PRIMARY KEY',$value);
			if ($auto_increment) $value=preg_replace('/^PRIMARY KEY(.*?)$/i','',$value);
			while (preg_match('/PRIMARY KEY[\s]*\((.*)\([0-9]+\)(.*)\)/i',$value)) // Remove '(100)' from 'PRIMARY KEY (`xxx` (100))'
				$value=preg_replace('/PRIMARY KEY[\s]*\((.*)\([0-9]+\)(.*)\)/i','PRIMARY KEY ($1 $2)',$value);
			
			// CREATE KEY queries for SQLite (corresponds to KEY 'xxxx'('xxxx', ...) of MySQL
			if (preg_match('/^FULLTEXT KEY(.*?)$/i',$value,$matches)) {
				array_push($morequeries,'CREATE INDEX '.str_replace('('," ON $tablename (",$matches[1]));
				$value='';
			} else if (preg_match('/^UNIQUE KEY(.*?)$/i',$value,$matches)) {
				array_push($morequeries,'CREATE UNIQUE INDEX '.str_replace('('," ON $tablename (",$matches[1]));
				$value='';
			} else if (preg_match('/^KEY(.*?)$/i',$value,$matches)) {
				array_push($morequeries,'CREATE INDEX '.str_replace('('," ON $tablename (",$matches[1]));
				$value='';
			}
			
			// Check if 'DEFAULT' is set when 'NOT NULL'
			$uvalue=strtoupper($value);
			if (strpos($uvalue,'NOT NULL')!==false && 
					strpos($uvalue,'DEFAULT')===false &&
					strpos($uvalue,'INTEGER NOT NULL PRIMARY KEY')===false) {
				if ($isint) $value.=" DEFAULT 0";
				else $value.=" DEFAULT ''";
			}
			
			if ($value) {
				if ($first) $first=false;
				else $query.=',';
				 $query.=' '.$value;
			}
		}
		$query.=' )';
		if ($temptable) $query='CREATE TEMPORARY TABLE '.$tablename.$query;
		else $query='CREATE TABLE '.$tablename.$query;
		//echo "<br />".htmlspecialchars($p1)."<br /><br />\n".htmlspecialchars($query)."<hr />\n";
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
			$ret =sqlite_altertable($tablename,$query,$dbhandle);
			if (!$ret) sqlite_ReturnWithError('SQL error',"<br /><i>".nucleus_mysql_error()."</i><br />".htmlspecialchars($p1)."<br /><br />\n".htmlspecialchars("ALTER TABLE $tablename $query")."<hr />\n");
			return $ret;
		}
		// Syntax error
		$query=='DROP TABLE '.$query;
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
				$commands=sqlite_splitByComma($query);
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
				$commands=sqlite_splitByComma($query);
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
		$query=sqlite_showKeysFrom(trim(substr($query,15)),$dbhandle);
	} else if (strpos($uquery,'SHOW INDEX FROM ')===0) {
		$query=sqlite_showKeysFrom(trim(substr($query,16)),$dbhandle);
	} else if (strpos($uquery,'SHOW FIELDS FROM ')===0) {
		$query=sqlite_showFieldsFrom(trim(substr($query,17)),$dbhandle);
	} else if (strpos($uquery,'SHOW COLUMNS FROM ')===0) {
		$query=sqlite_showFieldsFrom(trim(substr($query,18)),$dbhandle);
	} else if (strpos($uquery,'TRUNCATE TABLE ')===0) {
		$query='DELETE FROM '.substr($query,15);
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
		// Go to next quote
		if (($i1=strpos($query,'"',$i))===false) $i1=$qlen;
		if (($i2=strpos($query,"'",$i))===false) $i2=$qlen;
		if (($i3=strpos($query,'`',$i))===false) $i3=$qlen;
		if ($i1==$qlen && $i2==$qlen && $i3==$qlen) {
			$ret.=($temp=substr($query,$i));
			if (strstr($temp,';')) exit('Warning: try to use more than two queries?');
			break;
		}
		if ($i2<($j=$i1)) $j=$i2;
		if ($i3<$j) $j=$i3;
		$ret.=($temp=substr($query,$i,$j-$i));
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
function sqlite_splitByComma($query) {
	// The query is splitted by comma and the data will be put into an array.
	// The commas in quoted strings are ignored.
	$commands=array();
	$i=0;
	$in=false;
	while ($query) {
		if ($query[$i]=="'") {
			$i++;
			while ($i<strlen($query)) {
				if ($query[$i++]!="'") continue;
				if ($query[$i]!="'") break;
				$i++;
			}
			continue;
		} else if ($query[$i]=='(') $in=true;
		else if ($query[$i]==')') $in=false;
		else if ($query[$i]==',' && (!$in)) {
			$commands[]=trim(substr($query,0,$i));
			$query=trim(substr($query,$i+1));
			$i=0;
			continue;
		} // Do NOT add 'else' statement here! '$i++' is important in the following line.
		if (strlen($query)<=($i++)) break;
	}
	if ($query) $commands[]=$query;
	return $commands;
}
function sqlite_changeslashes(&$text){
	// By SQLite, "''" is used in the quoted string instead of "\'".
	// In addition, only "'" seems to be allowed for perfect quotation of string.
	// This routine is used for the conversion from MySQL type to SQL type.
	// Do NOT use stripslashes() but use stripcslashes().  Otherwise, "\r\n" is not converted.
	if ($text==='') return '';
	return (sqlite_escape_string (stripcslashes((string)$text)));
}
function sqlite_altertable($table,$alterdefs,$dbhandle){
	// This function originaly came from Jon Jensen's PHP class, SQLiteDB.
	// There are some modifications by Katsumi.
	$table=str_replace("'",'',$table);
	if (!$alterdefs) return false;
	$result = sqlite_query($dbhandle,"SELECT sql,name,type FROM sqlite_master WHERE tbl_name = '".$table."' ORDER BY type DESC");
	if(!sqlite_num_rows($result)) return sqlite_ReturnWithError('no such table: '.$table);
	$row = sqlite_fetch_array($result); //table sql
	if (function_exists('microtime')) $tmpname='t'.str_replace('.','',str_replace(' ','',microtime()));
	else $tmpname = 't'.rand(0,999999).time();
	$origsql = trim(preg_replace("/[\s]+/"," ",str_replace(",",", ",preg_replace("/[\(]/","( ",$row['sql'],1))));
	$createtemptableSQL = 'CREATE TEMPORARY '.substr(trim(preg_replace("'".$table."'",$tmpname,$origsql,1)),6);
	$createindexsql = array();
	while ($row = sqlite_fetch_array($result)) {//index sql
		$createindexsql[]=$row['sql'];
	}
	$i = 0;
	$defs = preg_split("/[,]+/",$alterdefs,-1,PREG_SPLIT_NO_EMPTY);
	$prevword = $table;
	$oldcols = preg_split("/[,]+/",substr(trim($createtemptableSQL),strpos(trim($createtemptableSQL),'(')+1),-1,PREG_SPLIT_NO_EMPTY);
	$newcols = array();
	for($i=0;$i<sizeof($oldcols);$i++){
		$colparts = preg_split("/[\s]+/",$oldcols[$i],-1,PREG_SPLIT_NO_EMPTY);
		$oldcols[$i] = $colparts[0];
		$newcols[$colparts[0]] = $colparts[0];
	}
	$newcolumns = '';
	$oldcolumns = '';
	reset($newcols);
	while(list($key,$val) = each($newcols)){
		if (strtoupper($val)!='PRIMARY' && strtoupper($key)!='PRIMARY' &&
		    strtoupper($val)!='UNIQUE'  && strtoupper($key)!='UNIQUE'){
			$newcolumns .= ($newcolumns?', ':'').$val;
			$oldcolumns .= ($oldcolumns?', ':'').$key;
		}
	}
	$copytotempsql = 'INSERT INTO '.$tmpname.'('.$newcolumns.') SELECT '.$oldcolumns.' FROM '.$table;
	$dropoldsql = 'DROP TABLE '.$table;
	$createtesttableSQL = $createtemptableSQL;
	foreach($defs as $def){
		$defparts = preg_split("/[\s]+/",$def,-1,PREG_SPLIT_NO_EMPTY);
		$action = strtolower($defparts[0]);
		switch($action){
		case 'modify':
			// Modification does not mean anything for SQLite, so just return true.
			// But this command will be supported in future???
			break;
		case 'add':
			if(($i=sizeof($defparts)) <= 2) return sqlite_ReturnWithError('near "'.$defparts[0].($defparts[1]?' '.$defparts[1]:'').'": syntax error');
			
			// ignore if there is already such table
			$exists=false;
			foreach($oldcols as $value) if (str_replace("'",'',$defparts[1])==str_replace("'",'',$value)) $exists=true;
			if ($exists) break;
			
			// Ignore 'AFTER xxxx' statement.
			// Ignore 'FIRST' statement.
			// Maybe this feature will be supprted later.
			if (4<=$i && strtoupper($defparts[$i-2])=='AFTER') unset($defparts[$i-1],$defparts[$i-2]);
			else if (3<=$i && strtoupper($defparts[$i-1])=='FIRST') unset($defparts[$i-1]);
			
			$createtesttableSQL = substr($createtesttableSQL,0,strlen($createtesttableSQL)-1).',';
			for($i=1;$i<sizeof($defparts);$i++) $createtesttableSQL.=' '.$defparts[$i];
			$createtesttableSQL.=')';
			break;
		case 'change':
			if(sizeof($defparts) <= 3) return sqlite_ReturnWithError('near "'.$defparts[0].($defparts[1]?' '.$defparts[1]:'').($defparts[2]?' '.$defparts[2]:'').'": syntax error');
			if($severpos = strpos($createtesttableSQL,' '.$defparts[1].' ')){
				if($newcols[$defparts[1]] != $defparts[1]){
					sqlite_ReturnWithError('unknown column "'.$defparts[1].'" in "'.$table.'"');
					return false;
				}
				$newcols[$defparts[1]] = $defparts[2];
				$nextcommapos = strpos($createtesttableSQL,',',$severpos);
				$insertval = '';
				for($i=2;$i<sizeof($defparts);$i++) $insertval.=' '.$defparts[$i];
				if($nextcommapos) $createtesttableSQL = substr($createtesttableSQL,0,$severpos).$insertval.substr($createtesttableSQL,$nextcommapos);
				else $createtesttableSQL = substr($createtesttableSQL,0,$severpos-(strpos($createtesttableSQL,',')?0:1)).$insertval.')';
			} else  return sqlite_ReturnWithError('unknown column "'.$defparts[1].'" in "'.$table.'"');
			break;
		case 'drop':
			if(sizeof($defparts) < 2) return sqlite_ReturnWithError('near "'.$defparts[0].($defparts[1]?' '.$defparts[1]:'').'": syntax error');
			if($severpos = strpos($createtesttableSQL,' '.$defparts[1].' ')){
				$nextcommapos = strpos($createtesttableSQL,',',$severpos);
				if($nextcommapos) $createtesttableSQL = substr($createtesttableSQL,0,$severpos).substr($createtesttableSQL,$nextcommapos + 1);
				else $createtesttableSQL = substr($createtesttableSQL,0,$severpos-(strpos($createtesttableSQL,',')?0:1) - 1).')';
				unset($newcols[$defparts[1]]);
			} else  return sqlite_ReturnWithError('unknown column "'.$defparts[1].'" in "'.$table.'"');
			break;
		default:
			return sqlite_ReturnWithError('near "'.$prevword.'": syntax error');
			break;
		}
		$prevword = $defparts[sizeof($defparts)-1];
	}

	//this block of code generates a test table simply to verify that the columns specifed are valid in an sql statement
	//this ensures that no reserved words are used as columns, for example
	if (!sqlite_query($dbhandle,$createtesttableSQL)) return false;
	$droptempsql = 'DROP TABLE '.$tmpname;
	sqlite_query($dbhandle,$droptempsql);
	//end block

	$createnewtableSQL = 'CREATE '.substr(trim(preg_replace("'".$tmpname."'",$table,$createtesttableSQL,1)),17);
	$newcolumns = '';
	$oldcolumns = '';
	reset($newcols);
	while(list($key,$val) = each($newcols)) {
		if (strtoupper($val)!='PRIMARY' && strtoupper($key)!='PRIMARY' &&
		    strtoupper($val)!='UNIQUE'  && strtoupper($key)!='UNIQUE'){
			$newcolumns .= ($newcolumns?', ':'').$val;
			$oldcolumns .= ($oldcolumns?', ':'').$key;
		}
	}
	$copytonewsql = 'INSERT INTO '.$table.'('.$newcolumns.') SELECT '.$oldcolumns.' FROM '.$tmpname;

	sqlite_query($dbhandle,$createtemptableSQL); //create temp table
	sqlite_query($dbhandle,$copytotempsql); //copy to table
	sqlite_query($dbhandle,$dropoldsql); //drop old table

	sqlite_query($dbhandle,$createnewtableSQL); //recreate original table
	foreach($createindexsql as $sql) sqlite_query($dbhandle,$sql); //recreate index
	sqlite_query($dbhandle,$copytonewsql); //copy back to original table
	sqlite_query($dbhandle,$droptempsql); //drop temp table
	return true;
}
function sqlite_showKeysFrom($tname,$dbhandle) {
	// This function is for supporing 'SHOW KEYS FROM' and 'SHOW INDEX FROM'.
	// For making the same result as obtained by MySQL, temporary table is made.
	$tname=str_replace("'",'',$tname);
	
	// Create a temporary table for making result
	if (function_exists('microtime')) $tmpname='t'.str_replace('.','',str_replace(' ','',microtime()));
	else $tmpname = 't'.rand(0,999999).time();
	sqlite_query($dbhandle,"CREATE TEMPORARY TABLE $tmpname ('Table', 'Non_unique', 'Key_name', 'Seq_in_index',".
		" 'Column_name', 'Collation', 'Cardinality', 'Sub_part', 'Packed', 'Null', 'Index_type', 'Comment')"); 
	
	// First, get the sql query when the table created
	$res=sqlite_query($dbhandle,"SELECT sql FROM sqlite_master WHERE tbl_name = '$tname' ORDER BY type DESC");
	$a=nucleus_mysql_fetch_assoc($res);
	$tablesql=$a['sql'];
	
	// Check if each columns are unique
	$notnull=array();
	foreach(sqlite_splitByComma(substr($tablesql,strpos($tablesql,'(')+1)) as $value) {
		$name=str_replace("'",'',substr($value,0,strpos($value,' ')));
		if (strpos(strtoupper($value),'NOT NULL')!==false) $notnull[$name]='';
		else $notnull[$name]='YES';
	}
	
	// Get the primary key (and check if it is unique???).
	if (preg_match('/[^a-zA-Z_\']([\S]+)[^a-zA-Z_\']+INTEGER NOT NULL PRIMARY KEY/i',$tablesql,$matches)) {
		$pkey=str_replace("'",'',$matches[1]);
		$pkeynull='';
	} else if (preg_match('/[^a-zA-Z_\']([\S]+)[^a-zA-Z_\']+INTEGER PRIMARY KEY/i',$tablesql,$matches)) {
		$pkey=str_replace("'",'',$matches[1]);
		$pkeynull='YES';
	} else if (preg_match('/PRIMARY KEY[\s]*?\(([^\)]+)\)/i',$tablesql,$matches)) {
		$pkey=null;// PRIMARY KEY ('xxx'[,'xxx'])
		foreach(explode(',',$matches[1]) as $key=>$value) {
			$value=str_replace("'",'',trim($value));
			$key++;
			$cardinality=nucleus_mysql_num_rows(sqlite_query($dbhandle,"SELECT '$value' FROM '$tname'"));
			sqlite_query($dbhandle,"INSERT INTO $tmpname ('Table', 'Non_unique', 'Key_name', 'Seq_in_index',".
				" 'Column_name', 'Collation', 'Cardinality', 'Sub_part', 'Packed', 'Null', 'Index_type', 'Comment')".
				" VALUES ('$tname', '0', 'PRIMARY', '$key',".
				" '$value', 'A', '$cardinality', null, null, '', 'BTREE', '')"); 
		}
	} else $pkey=null;
	
	// Check the index.
	$res=sqlite_query($dbhandle,"SELECT sql,name FROM sqlite_master WHERE type = 'index' and tbl_name = '$tname' ORDER BY type DESC");
	while ($a=nucleus_mysql_fetch_assoc($res)) {
		if (!($sql=$a['sql'])) {// Primary key
			if ($pkey && strpos(strtolower($a['name']),'autoindex')) {
				$cardinality=nucleus_mysql_num_rows(sqlite_query($dbhandle,"SELECT $pkey FROM '$tname'"));
				sqlite_query($dbhandle,"INSERT INTO $tmpname ('Table', 'Non_unique', 'Key_name', 'Seq_in_index',".
					" 'Column_name', 'Collation', 'Cardinality', 'Sub_part', 'Packed', 'Null', 'Index_type', 'Comment')".
					" VALUES ('$tname', '0', 'PRIMARY', '1',".
					" '$pkey', 'A', '$cardinality', null, null, '$pkeynull', 'BTREE', '')"); 
				$pkey=null;
			}
		} else {// Non-primary key
			if (($name=str_replace("'",'',$a['name'])) && preg_match('/\(([\s\S]+)\)/',$sql,$matches)) {
				foreach(explode(',',$matches[1]) as $key=>$value) {
					$columnname=str_replace("'",'',$value);
					if (strpos(strtoupper($sql),'CREATE UNIQUE ')===0) $nonunique='0';
					else $nonunique='1';
					$cardinality=nucleus_mysql_num_rows(sqlite_query($dbhandle,"SELECT $columnname FROM '$tname'"));
					sqlite_query($dbhandle,"INSERT INTO $tmpname ('Table', 'Non_unique', 'Key_name', 'Seq_in_index',".
						" 'Column_name', 'Collation', 'Cardinality', 'Sub_part', 'Packed', 'Null', 'Index_type', 'Comment')".
						" VALUES ('$tname', '$nonunique', '$name', '".(string)($key+1)."',".
						" '$columnname', 'A', '$cardinality', null, null, '$notnull[$columnname]', 'BTREE', '')"); 
				}
			}
		}
	}
	if ($pkey) { // The case that the key (index) is not defined.
		$cardinality=nucleus_mysql_num_rows(sqlite_query($dbhandle,"SELECT $pkey FROM '$tname'"));
		sqlite_query($dbhandle,"INSERT INTO $tmpname ('Table', 'Non_unique', 'Key_name', 'Seq_in_index',".
			" 'Column_name', 'Collation', 'Cardinality', 'Sub_part', 'Packed', 'Null', 'Index_type', 'Comment')".
			" VALUES ('$tname', '0', 'PRIMARY', '1',".
			" '$pkey', 'A', '$cardinality', null, null, '$pkeynull', 'BTREE', '')"); 
		$pkey=null;
	}
	
	// return the final query to show the keys in MySQL style (using temporary table).
	return "SELECT * FROM $tmpname";
}
function sqlite_showFieldsFrom($tname,$dbhandle){
	// This function is for supporing 'SHOW FIELDS FROM' and 'SHOW COLUMNS FROM'.
	// For making the same result as obtained by MySQL, temporary table is made.
	$tname=str_replace("'",'',$tname);
	
	// First, get the sql query when the table created
	$res=sqlite_query($dbhandle,"SELECT sql FROM sqlite_master WHERE tbl_name = '$tname' ORDER BY type DESC");
	$a=nucleus_mysql_fetch_assoc($res);
	$tablesql=trim($a['sql']);
	if (preg_match('/^[^\(]+\(([\s\S]*?)\)$/',$tablesql,$matches)) $tablesql=$matches[1];
	$tablearray=array();
	foreach(sqlite_splitByComma($tablesql) as $value) {
		$value=trim($value);
		if ($i=strpos($value,' ')) {
			$name=str_replace("'",'',substr($value,0,$i));
			$value=trim(substr($value,$i));
			if (substr($value,-1)==',') $value=substr($value,strlen($value)-1);
			$tablearray[$name]=$value;
		}
	}
	
	// Check if INDEX has been made for the parameter 'MUL' in 'KEY' column
	$multi=array();
	$res=sqlite_query($dbhandle,"SELECT name FROM sqlite_master WHERE type = 'index' and tbl_name = '$tname' ORDER BY type DESC");
	while ($a=nucleus_mysql_fetch_assoc($res)) $multi[str_replace("'",'',$a['name'])]='MUL';
	
	// Create a temporary table for making result
	if (function_exists('microtime')) $tmpname='t'.str_replace('.','',str_replace(' ','',microtime()));
	else $tmpname = 't'.rand(0,999999).time();
	sqlite_query($dbhandle,"CREATE TEMPORARY TABLE $tmpname ('Field', 'Type', 'Null', 'Key', 'Default', 'Extra')"); 
	
	// Check the table
	foreach($tablearray as $field=>$value) {
		if (strtoupper($field)=='PRIMARY') continue;//PRIMARY KEY('xx'[,'xx'])
		$uvalue=strtoupper($value.' ');
		$key=(string)$multi[$field];
		if ($uvalue=='INTEGER NOT NULL PRIMARY KEY ' || $uvalue=='INTEGER PRIMARY KEY ') {
			$key='PRI';
			$extra='auto_increment';
		} else $extra='';
		if ($i=strpos($uvalue,' ')) {
			$type=substr($value,0,$i);
			if (strpos($type,'(') && ($i=strpos($value,')')))
				$type=substr($value,0,$i+1);
		} else $type='';
		if (strtoupper($type)=='INTEGER') $type='int(11)';
		if (strpos($uvalue,'NOT NULL')===false) $null='YES';
		else {
			$null='';
			$value=preg_replace('/NOT NULL/i','',$value);
			$uvalue=strtoupper($value);
		}
		if ($i=strpos($uvalue,'DEFAULT')) {
			$default=trim(substr($value,$i+7));
			if (strtoupper($default)=='NULL') {
				$default="";
				$setdefault="";
			} else {
				if (substr($default,0,1)=="'") $default=substr($default,1,strlen($default)-2);
				$default="'".$default."',";
				$setdefault="'Default',";
			}
		} else if ($null!='YES' && $extra!='auto_increment') {
			if (strpos(strtolower($type),'int')===false) $default="'',";
			else $default="'0',";
			$setdefault="'Default',";
		} else {
			$default="";
			$setdefault="";
		}
		sqlite_query($dbhandle,"INSERT INTO '$tmpname' ('Field', 'Type', 'Null', 'Key', $setdefault 'Extra')".
			" VALUES ('$field', '$type', '$null', '$key', $default '$extra')");
	}
	
	// return the final query to show the keys in MySQL style (using temporary table).
	return "SELECT * FROM $tmpname";
}
function sqlite_mysql_query_debug(&$query){
	// The debug mode is so far used for checking query difference like "SELECT i.itime, ....".
	// This must be chaged to "SELECT i.itime as itime,..." for SQLite.
	// (This feature is not needed any more after the version 0.8.1 (see intialization query))
	$uquery=strtoupper($query);
	if (strpos($uquery,"SELECT ")!==0) return $query;
	if (($i=strpos($uquery," FROM "))===false) return $query;
	$select=sqlite_splitByComma(substr($query,7,$i-7));
	$query=substr($query,$i);
	$ret='';
	foreach($select as $value){
		if (preg_match('/^([a-z_]+)\.([a-z_]+)$/i',$value,$matches)) {
			$value=$value." as ".$matches[2];
			$t=$matches[0]."=>$value\n";
			$a=debug_backtrace();
			foreach($a as $key=>$btrace) {
				if (!($templine=$btrace['line'])) continue;
				if (!($tempfile=$btrace['file'])) continue;
				$tempfile=preg_replace('/[\s\S]*?[\/\\\\]([^\/\\\\]+)$/','$1',$tempfile);
				$t.="$tempfile line:$templine\n";
			}
			sqlite_DebugMessage($t);
		}
		if ($ret) $ret.=', ';
		$ret.=$value;
	}
	return "SELECT $ret $query";
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