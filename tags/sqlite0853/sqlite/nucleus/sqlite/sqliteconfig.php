<?php
$SQLITECONF=array();

// Detabase definition.
// Modify here if you use another file for database.
$SQLITECONF['DBFILENAME']=dirname(__FILE__).'/.dbsqlite';

// Options
$SQLITECONF['DEBUGMODE']=false;
$SQLITECONF['DEBUGREPORT']=false;
$SQLITECONF['MEASURESPEED']=false;
$SQLITECONF['INITIALIZE']=array('PRAGMA short_column_names=1;');

?>