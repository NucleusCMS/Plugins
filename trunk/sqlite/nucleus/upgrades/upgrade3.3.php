<?php
function upgrade_do33() {

	if (upgrade_checkinstall(33))
		return 'already installed';

	// alter nucleus_blog table
	$data=upgrade_sqlite_data('blog');
	$query = 'DROP TABLE ' . sql_table('blog');
	upgrade_query('Dropping ' . sql_table('blog') . ' table', $query);
	$query="CREATE TABLE `".sql_table('blog')."` (
  `bnumber` int(11) NOT NULL auto_increment,
  `bname` varchar(60) NOT NULL default '',
  `bshortname` varchar(15) NOT NULL default '',
  `bdesc` varchar(200) default NULL,
  `bcomments` tinyint(2) NOT NULL default '1',
  `bmaxcomments` int(11) NOT NULL default '0',
  `btimeoffset` decimal(3,1) NOT NULL default '0.0',
  `bnotify` varchar(60) default NULL,
  `burl` varchar(100) default NULL,
  `bupdate` varchar(60) default NULL,
  `bdefskin` int(11) NOT NULL default '1',
  `bpublic` tinyint(2) NOT NULL default '1',
  `bsendping` tinyint(2) NOT NULL default '0',
  `bconvertbreaks` tinyint(2) NOT NULL default '1',
  `bdefcat` int(11) default NULL,
  `bnotifytype` int(11) NOT NULL default '15',
  `ballowpast` tinyint(2) NOT NULL default '0',
  `bincludesearch` tinyint(2) NOT NULL default '0',
  `breqemail` TINYINT( 2 ) DEFAULT '0' NOT NULL,
  PRIMARY KEY  (`bnumber`),
  UNIQUE KEY `bnumber` (`bnumber`),
  UNIQUE KEY `bshortname` (`bshortname`)
) TYPE=MyISAM;";
	upgrade_query('Creating ' . sql_table('blog') . ' table', $query);
	upgrade_sqlite_insert('blog',$data);

	// alter nucleus_category
	$data=upgrade_sqlite_data('category');
	$query = 'DROP TABLE ' . sql_table('category');
	upgrade_query('Dropping ' . sql_table('category') . ' table', $query);
	$query="CREATE TABLE `".sql_table(category)."` (
  `catid` int(11) NOT NULL auto_increment,
  `cblog` int(11) NOT NULL default '0',
  `cname` varchar(200) default NULL,
  `cdesc` varchar(200) default NULL,
  PRIMARY KEY  (`catid`)
) TYPE=MyISAM;";
	upgrade_query('Creating ' . sql_table('category') . ' table', $query);
	upgrade_sqlite_insert('category',$data);

	// alter nucleus_comment
	$data=upgrade_sqlite_data('comment');
	$query = 'DROP TABLE ' . sql_table('comment');
	upgrade_query('Dropping ' . sql_table('comment') . ' table', $query);
	$query="CREATE TABLE `".sql_table(comment)."` (
  `cnumber` int(11) NOT NULL auto_increment,
  `cbody` text NOT NULL,
  `cuser` varchar(40) default NULL,
  `cmail` varchar(100) default NULL,
  `cemail` VARCHAR( 100 ),
  `cmember` int(11) default NULL,
  `citem` int(11) NOT NULL default '0',
  `ctime` datetime NOT NULL default '0000-00-00 00:00:00',
  `chost` varchar(60) default NULL,
  `cip` varchar(15) NOT NULL default '',
  `cblog` int(11) NOT NULL default '0',
  PRIMARY KEY  (`cnumber`),
  UNIQUE KEY `cnumber` (`cnumber`),
  KEY `citem` (`citem`),
  FULLTEXT KEY `cbody` (`cbody`)
) TYPE=MyISAM;";
	upgrade_query('Creating ' . sql_table('comment') . ' table', $query);
	upgrade_sqlite_insert('comment',$data);
	
	// 3.2 -> 3.2+
	// update database version  
	$query = 'UPDATE ' . sql_table('config') . ' set value=\'330\' where name=\'DatabaseVersion\'';
	upgrade_query('Updating DatabaseVersion in config table to 330', $query);

	// nothing!
}

function upgrade_sqlite_data($table){
	$data=array();
	$query='SELECT * FROM '.sql_table($table);
	$res=sql_query($query);
	while($row=nucleus_mysql_fetch_assoc($res)) $data[]=$row;
	return $data;
}

function upgrade_sqlite_insert($table,&$data){
	echo '<li>Recovering ' . sql_table($table) . ' table';
	$queries=array();
	foreach($data as $rows){
		$names='';
		$values='';
		foreach($rows as $key=>$value){
			if ($names) $names.=',';
			if ($values) $values.=',';
			$names.="'".$key."'";
			$values.="'".addslashes($value)."'";
		}
		$query='INSERT INTO '.sql_table($table).
			' ('.$names.')'.
			' VALUES ('.$values.')';
		$queries[]=$query;
	}
	sql_query('begin;');
	foreach($queries as $query) sql_query($query);
	sql_query('commit;');
	echo '</li>';
}
?>
