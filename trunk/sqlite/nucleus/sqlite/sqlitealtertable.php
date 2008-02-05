<?php
    /****************************************
    * SQLite-MySQL wrapper for Nucleus      *
    *                           ver 0.9.0.0 *
    * Written by Katsumi   License: GPL     *
    ****************************************/

function sqlite_createtable_query($commands){
	$auto_increment=false;
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
	return $query;
}

function sqlite_renametable($commands,$dbhandle){
	$carray=array();
	foreach($commands as $command){
		$command=_sqlite_divideByChar(array(' ',"\t","\r","\n"),$command);
		if (count($command)!=3) return sqlite_ReturnWithError(htmlspecialchars("near '$command[0]': syntax error"));
		if (strtoupper($command[1])!='TO') return sqlite_ReturnWithError(htmlspecialchars("near '$command[1]': syntax error"));
		$carray[str_replace("'",'',$command[0])]=str_replace("'",'',$command[2]);
	}
	foreach($carray as $old=>$new){
		if (!sqlite_copytable($old,$new,$dbhandle)) return false;
		if (!sqlite_query($dbhandle,"DROP TABLE $old")) return sqlite_ReturnWithError(htmlspecialchars("fail to remove table, '$old'"));
	}
	sqlite_query($dbhandle,'VACUUM');
	return true;
}

function sqlite_copytable($table,$newname,$dbhandle,$newtablearray=array()){
	// Getting information from original table and create new table
	$res = sqlite_query($dbhandle,"SELECT sql,name,type FROM sqlite_master WHERE tbl_name = '".$table."' ORDER BY type DESC");
	if(!sqlite_num_rows($res)) return sqlite_ReturnWithError('no such table: '.$table);
	if (count($newtablearray)) {
		$query="CREATE TABLE $newname (".implode(',',$newtablearray).')';
		if (!sqlite_query($dbhandle,$query)) return sqlite_ReturnWithError('Table could not be created.');
	} else {
		while($row=sqlite_fetch_array($res,SQLITE_ASSOC)){
			if (!preg_match('/^([^\(]*)[\s]([^\(\']+)[\s]*\(([\s\S]*)$/',$row['sql'],$m) &&
				!preg_match('/^([^\(]*)[\s]\'([^\(]+)\'[\s]*\(([\s\S]*)$/',$row['sql'],$m)) return sqlite_ReturnWithError('unknown error');
			if (!sqlite_query($dbhandle,$m[1]." '$newname' (".$m[3])) return sqlite_ReturnWithError('Table could not be created.');
		}
	}
	// Copy the items
	sqlite_query($dbhandle,'BEGIN');
	$res=sqlite_unbuffered_query($dbhandle,"SELECT * FROM $table");
	while($row=sqlite_fetch_array($res,SQLITE_ASSOC)){
		$keys=$values=array();
		foreach($row as $key=>$value) {
			if (count($newtablearray) && !isset($newtablearray[strtolower($key)])) continue;
			$keys[]="'$key'";
			$values[]="'".sqlite_escape_string($value)."'";
		}
		if (!sqlite_query($dbhandle,"INSERT INTO '$newname'(".implode(', ',$keys).') VALUES ('.implode(', ',$values).')')) {
			sqlite_query($dbhandle,'COMMIT');
			return false;
		}
	}
	sqlite_query($dbhandle,'COMMIT');
	$orgnum=sqlite_array_query($dbhandle,"SELECT COUNT(*) FROM $table");
	$newnum=sqlite_array_query($dbhandle,"SELECT COUNT(*) FROM $newname");
	if ($orgnum[0][0]!=$newnum[0][0]) return sqlite_ReturnWithError('Data transfer failed.');
	return true;
}
function sqlite_altertable($table,$alterdefs,$dbhandle){
	// Almost completely re-written in February 2008.
	$table=str_replace("'",'',$table);

	// Getting information from original table
	$res = sqlite_query($dbhandle,"SELECT sql,name,type FROM sqlite_master WHERE tbl_name = '".$table."' ORDER BY type DESC");
	if(!sqlite_num_rows($res)) return sqlite_ReturnWithError('no such table: '.$table);
	$orgindex=array();
	$row=sqlite_fetch_array($res,SQLITE_ASSOC); //table sql
	$orgsql=$row['sql'];
	while($row=sqlite_fetch_array($res,SQLITE_ASSOC)) $orgindex[strtolower($row['name'])]=$row['sql'];
	if (!preg_match('/^[^\(]+\((.*)\);*$/',$orgsql,$m)) return sqlite_ReturnWithError('unknown error');
	$orgtablearray=array();
	foreach(_sqlite_divideByChar(',',$orgtable=$m[1]) as $value){
		if (!preg_match('/^([^\s\']+)[\s]+([\s\S]*)$/',$value,$m) &&
			!preg_match('/^\'([^\']+)\'[\s]+([\s\S]*)$/',$value,$m)) return sqlite_ReturnWithError('unknown error');
		$orgtablearray[strtolower($m[1])]="'$m[1]' $m[2]";
	}
	$desttablearray=$orgtablearray;
	$destindex=$orgindex;

	// Convert table
	foreach(_sqlite_divideByChar(',',$alterdefs) as $def){
		$def=_sqlite_divideByChar(array(' ',"\t","\r","\n"),trim($def));
		if (($c=count($def))<2) return sqlite_ReturnWithError('near "'.htmlspecialchars($def[0]).'": syntax error');
		// Check if FIRST/AFTER is used.
		$first=$after=false;
		if (strtoupper($def[$c-1])=='FIRST') {
			$first=true;
			array_pop($def);
		} elseif (strtoupper($def[$c-2])=='AFTER') {
			$after=strtolower(str_replace("'",'',array_pop($def)));
			array_pop($def);
		}
		// Ignore CONSTRAINT and COLUMN
		$method=strtoupper(array_shift($def));
		switch(strtoupper($def[0])){
		case 'CONSTRAINT': // delete two
			array_shift($def);
		case 'COLUMN': // delete one
			array_shift($def);
		default:
			break;
		}
		// The main routine of this function follow.
		switch($method){
		case 'MODIFY':
		case 'ALTER':
			if (error_reporting() & E_NOTICE) sqlite_ReturnWithError('ALTER/MODIFY is not supported');
			break;
		case 'DROP':
		case 'CHANGE':
			if (strtoupper($def[0])=='INDEX') {
				// delete index
				unset($destindex[strtolower($def[1])]);
			} else {
				// delete field
				unset($desttablearray[strtolower(str_replace("'",'',$def[0]))]);
			}
			if ($method!='CHANGE') break;
		case 'ADD':
			$field=array_shift($def);
			switch($submedthod=strtoupper($field)){
			case 'UNIQUE':
			case 'PRIMARY':
			case 'FOREIGN':
			case 'INDEX':
			case 'FULLTEXT':
				// add index
				if (strtoupper($index=array_shift($def))=='KEY') $index=array_shift($def);
				$def=implode(' ',$def);
				$destindex[strtolower(str_replace("'",'',$index))]=
					($submedthod=='UNIQUE'?'CREATE UNIQUE INDEX ':'CREATE INDEX ').
					"$index ON '$table' $def";
				break;
			default:
				// add field
				$field=str_replace("'",'',$field);
				if ($first) {
					$desttablearray=array_merge(
						array(strtolower($field)=>"'$field' ".implode(' ',$def)),
						$desttablearray);
				} elseif($after) {
					$temp=$desttablearray;
					$desttablearray=array();
					$ok=false;
					foreach($temp as $key=>$value) {
						$desttablearray[$key]=$value;
						if ($ok || $key!=$after) continue;
						$ok=true;
						$desttablearray[strtolower($field)]="'$field' ".implode(' ',$def);
					}
					if (!$ok) {
						$desttablearray[strtolower($field)]="'$field' ".implode(' ',$def);
						if (error_reporting() & E_NOTICE) sqlite_ReturnWithError(htmlspecialchars("Field '$after' not found."));
					}
				} else {
					$desttablearray[strtolower($field)]="'$field' ".implode(' ',$def);
				}
			}
			break;
		default:
			return sqlite_ReturnWithError('near "'.htmlspecialchars($method).'": syntax error');
		}
	}

	// Create temporary table that has the modified field and copy the items into it.
	if (function_exists('microtime')) $tmptable='t'.str_replace(array(' ','.'),'',microtime());
	else $tmptable = 't'.rand(0,999999).time();
	if (!sqlite_copytable($table,$tmptable,$dbhandle,$desttablearray)) return false;

	// New temporary table sccusfully made.
	// So, delete the original table and copy back the temporary table to one with original name.
	sqlite_query("DROP TABLE $table",$dbhandle);
	if (sqlite_copytable($tmptable,$table,$dbhandle)) sqlite_query("DROP TABLE $tmptable",$dbhandle);

	// Add the indexes, finally.
	foreach($destindex as $index) sqlite_query($index,$dbhandle);
	sqlite_query($dbhandle,'VACUUM');
	return true;
}
function sqlite_showKeysFrom($tname,$dbhandle) {
	// This function is for supporing 'SHOW KEYS FROM' and 'SHOW INDEX FROM'.
	// For making the same result as obtained by MySQL, temporary table is made.
	if (preg_match('/^([^\s]+)\s+LIKE\s+\'([^\']+)\'$/i',$tname,$m)) list($m,$tname,$like)=$m;
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
	foreach(_sqlite_divideByChar(',',substr($tablesql,strpos($tablesql,'(')+1)) as $value) {
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
			if (isset($like) && $name!=$like) continue;
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
	if (preg_match('/^([^\s]+)\s+LIKE\s+\'([^\']+)\'$/i',$tname,$m)) list($m,$tname,$like)=$m;
	$tname=str_replace("'",'',$tname);
	
	// First, get the sql query when the table created
	$res=sqlite_query($dbhandle,"SELECT sql FROM sqlite_master WHERE tbl_name = '$tname' ORDER BY type DESC");
	$a=nucleus_mysql_fetch_assoc($res);
	$tablesql=trim($a['sql']);
	if (preg_match('/^[^\(]+\(([\s\S]*?)\)$/',$tablesql,$matches)) $tablesql=$matches[1];
	$tablearray=array();
	foreach(_sqlite_divideByChar(',',$tablesql) as $value) {
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
		if (isset($like) && $field!=$like) continue;
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

?>