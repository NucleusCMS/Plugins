<?php
    /****************************************
    * SQLite-MySQL wrapper for Nucleus      *
    *                           ver 0.8.6.0 *
    * Written by Katsumi                    *
    ****************************************/

function sqlite_copytable($table,$newname,$dbhandle){
	// Note that indexes are not copied in this function.
	if (function_exists('microtime')) $tmptable='t'.str_replace(array(' ',','),'',microtime());
	// Getting information from original table and create new table
	$res = sqlite_query($dbhandle,"SELECT sql,name,type FROM sqlite_master WHERE tbl_name = '".$table."' ORDER BY type DESC");
	if(!sqlite_num_rows($res)) return sqlite_ReturnWithError('no such table: '.$table);
	while($row=sqlite_fetch_array($res,SQLITE_ASSOC)){
		if (!preg_match('/^([^\(]*)[\s]([^\(\']+)[\s]*\(([\s\S]*)$/',$row['sql'],$m) &&
			!preg_match('/^([^\(]*)[\s]\'([^\(]+)\'[\s]*\(([\s\S]*)$/',$row['sql'],$m)) return sqlite_ReturnWithError('unknown error');
		sqlite_query($dbhandle,$m[1]." '$newname'(".$m[3]);
	}
	// Copy the items
	sqlite_query($dbhandle,'BEGIN');
	$res=sqlite_unbuffered_query($dbhandle,"SELECT * FROM $table");
	while($row=sqlite_fetch_array($res,SQLITE_ASSOC)){
		$keys=$values=array();
		foreach($row as $key=>$value) {
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
	if ($orgnum[0][0]!=$newnum[0][0]) return sqlite_ReturnWithError('Data transfer failed');
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
		// These are ignored in current version.
		$first=$after=false;
		if (strtoupper($def[$c-1])=='FIRST') {
			$first=true;
			array_pop($def);
		} elseif (strtoupper($def[$c-2])=='AFTER') {
			$after=array_pop($def);
			array_pop($def);
		}
		// ignore CONSTRAINT and COLUMN
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
				$desttablearray[strtolower($field)]="'$field' ".implode(' ',$def);
			}
			break;
		default:
			return sqlite_ReturnWithError('near "'.htmlspecialchars($method).'": syntax error');
		}
	}

	// Create temporary table that has the modified field
	if (function_exists('microtime')) $tmptable='t'.str_replace(array(' ','.'),'',microtime());
	else $tmptable = 't'.rand(0,999999).time();
	$query="CREATE TABLE $tmptable (".implode(',',$desttablearray).')';
	if (!sqlite_query($dbhandle,$query)) return sqlite_ReturnWithError('Error: '.htmlspecialchars($query));
	// Copy the items
	sqlite_query($dbhandle,'BEGIN');
	$res=sqlite_unbuffered_query($dbhandle,"SELECT * FROM $table");
	while($row=sqlite_fetch_array($res,SQLITE_ASSOC)){
		$keys=$values=array();
		foreach($row as $key=>$value) {
			if (!isset($desttablearray[strtolower($key)])) continue;
			$keys[]="'$key'";
			$values[]="'".sqlite_escape_string($value)."'";
		}
		if (!sqlite_query($dbhandle,"INSERT INTO '$tmptable'(".implode(', ',$keys).') VALUES ('.implode(', ',$values).')')) {
			sqlite_query($dbhandle,'COMMIT');
			return false;
		}
	}
	sqlite_query($dbhandle,'COMMIT');
	$orgnum=sqlite_array_query($dbhandle,"SELECT COUNT(*) FROM $table");
	$tempnum=sqlite_array_query($dbhandle,"SELECT COUNT(*) FROM $tmptable");
	if ($orgnum[0][0]!=$tempnum[0][0]) return sqlite_ReturnWithError('Data transfer failed');

	// New temporary table sccusfully made.
	// So, delete the original table and copy back the temporary table as original name.
	sqlite_query("DROP TABLE $table",$dbhandle);
	if (sqlite_copytable($tmptable,$table,$dbhandle)) sqlite_query("DROP TABLE $tmptable",$dbhandle);

	// Add the indexes
	foreach($destindex as $index) sqlite_query($index,$dbhandle);
	return true;
}
?>