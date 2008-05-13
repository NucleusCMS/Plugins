<?php 
class NP_subSilver_install { 
	var $plug;
	function NP_subSilver_install(&$plug){
		$this->plug=&$plug;
		foreach($plug as $key=>$value) $this->$key=&$plug->$key;
	}
	function install() {
		$this->plug->createOption('droptable','Drop SQL table when uninstall?','yesno','no');
		$this->plug->createOption('popularnum','Number of popular topics?','text','5','datatype=numerical');
		$this->plug->createOption('populardef',
			'The definition of populer topic: (Use <%replies%>, <%viewed%>, and <%days%> here)',
			'textarea','(<%viewed%>/<%days%>)*(<%days%> > 6)');
		$this->plug->createOption('unreaddays','The "unread" data are kept for days:','text','7','datatype=numerical');
		$this->plug->createItemOption('iteminfo','This item is:','select','normal','Normal|normal|Information|info|Sticky|sticky');
		$this->plug->createBlogOption('blogpostingbyguest','Allow guest to post new reply?','yesno','yes');
		$this->plug->createBlogOption('blogpublic','Allow guest to see this?','yesno','yes');
		$this->plug->createBlogOption('bloghidden','Hidden category (only siteadmin and moderators can see this)?','yesno','no');
		// SQL table stuffs follow
		$this->plug->sql_query('create','(
			itemid int(11) not null default 0,
			authorid int(11) not null default 0,
			firstcommentid int(11) not null default 0,
			lastcommentid int(11) not null default 0,
			replynum int(11) not null default 0,
			unread text not null default "",
			readip varchar(85) not null default ",,,,,,",
			readnum int(11) not null default 0,
			time datetime not null default  "0000-00-00 00:00:00",
			since datetime not null default  "0000-00-00 00:00:00",
			PRIMARY KEY itemid (itemid),
			KEY authorid (authorid),
			FULLTEXT KEY unread(unread)
			)');
		// List up all items without comment
		$items=array();
		$res=sql_query('SELECT citem FROM '.sql_table('comment'));
		while($row=mysql_fetch_row($res)) $items[$row[0]]=true;
		mysql_free_result($res);
		$res=sql_query('SELECT DISTINCT i.inumber as itemid, i.iauthor as authorid, i.ibody as body, i.itime as time, i.iblog as blogid'.
			' FROM '.sql_table('item').' as i');
		while($row=mysql_fetch_assoc($res)){
			if (isset($items[$row['itemid']])) continue;
			 sql_query('INSERT INTO '.sql_table('comment').
				' SET cbody="'.htmlspecialchars(strip_tags($row['body']),ENT_QUOTES).'"'.
				', cmember='.(int)$row['authorid'].
				', citem='.(int)$row['itemid'].
				', ctime="'.addslashes($row['time']).'"'.
				', cip="'.addslashes(serverVar('REMOTE_ADDR')).'"'.
				', cblog='.(int)$row['blogid']);
		}
		mysql_free_result($res);
		// List up all members.
		$members=',';
		$res=sql_query('SELECT mnumber FROM '.sql_table('member'));
		while($row=mysql_fetch_row($res)) $members.=$row[0].',';
		mysql_free_result($res);
		// List up the data in the table
		$data=array();
		$res=$this->plug->sql_query('SELECT itemid FROM');
		while ($row=mysql_fetch_row($res)) $data[]=$row[0];
		mysql_free_result($res);
		// List up all the unregistered data in table
		$items=array();
		$res=sql_query('SELECT inumber FROM '.sql_table('item'));
		while ($row=mysql_fetch_row($res)) {
			if (!in_array($row[0],$data)) $items[]=$row[0];
		}
		mysql_free_result($res);
		// Create the data if not exists.
		foreach($items as $itemid){
			$query='SELECT cnumber, cmember, ctime FROM '.sql_table('comment').
				' WHERE citem='.(int)$itemid.
				' ORDER BY ctime ASC LIMIT 1';
			$first=mysql_fetch_assoc($res=sql_query($query));
			mysql_free_result($res);
			$query='SELECT cnumber, cmember, ctime FROM '.sql_table('comment').
				' WHERE citem='.(int)$itemid.
				' ORDER BY ctime DESC LIMIT 1';
			$last=mysql_fetch_assoc($res=sql_query($query));
			mysql_free_result($res);
			$unread=(mysqldate(time()-8*86400)<$last['ctime'])?$members:'';// "unread" data is created items in these 7 days.
			$replies=-1+(int)quickQuery('SELECT COUNT(*) as result FROM '.sql_table('comment').' WHERE citem='.(int)$itemid);
			$this->plug->sql_query('INSERT INTO','SET'.
				' itemid='.(int)$itemid.
				',authorid='.(int)$first['cmember'].
				',firstcommentid='.(int)$first['cnumber'].
				',lastcommentid='.(int)$last['cnumber'].
				',replynum='.(int)$replies.
				',unread="'.addslashes($unread).'"'.
				',time="'.addslashes($last['ctime']).'"'.
				',since="'.addslashes($first['ctime']).'"');
		}
	}
	function unInstall() {
		if ($this->plug->getOption('droptable')=='yes') $this->plug->sql_query('drop');
	}
}
?>