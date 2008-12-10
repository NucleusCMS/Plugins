<?php 
/*
	NP_TodoList
	by yu (http://nucleus.datoka.jp/)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
	
	USAGE
	-----
	<%TodoList%>
	<%TodoList(nodate)%> //date setting
	<%TodoList(normal,1)%> //date setting, memberid
	
	HISTORY
	-------
	2008-12-02 Ver0.44: [Fix] "Add TODO" bug fix. (hilbert)
	                    [Chg] Improve quote_smart() function. (yu)
	2008-05-19 Ver0.43: [Fix] "Delete TODO" bug fix. (yu)
	2006-09-30 Ver0.42: [Fix] Security fix. (yu)
	2004-09-29 Ver0.41: [Fix] Check edit authority. (yu)
	2004-05-30 Ver0.4 : [New] Blog members can own each todo list. (yu)
*/

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')) {
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_TodoList extends NucleusPlugin { 
	function getName()      { return 'Todo List'; } 
	function getAuthor()    { return 'yu'; } 
	function getURL()       { return 'http://works.datoka.jp/index.php?itemid=231'; } 
	function getVersion()   { return '0.44'; } 
	function getMinNucleusVersion() { return 200; }
	function getTableList() { return array( sql_table('plug_todolist') ); }
	function getEventList() { return array(); }
	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function getDescription() { 
		return 'Show Todo List. [USAGE] <%TodoList(mode,memberid)%> ex. <%TodoList%>, <%TodoList(nodate)%>, <%TodoList(normal,1)%>';
	} 


	function install(){ 
		sql_query ("CREATE TABLE IF NOT EXISTS ". sql_table('plug_todolist') ." (
			tid      INT UNSIGNED NOT NULL AUTO_INCREMENT,
			title    VARCHAR(255) NOT NULL DEFAULT '',
			rank     INT UNSIGNED NOT NULL DEFAULT 0,
			cond     INT UNSIGNED NOT NULL DEFAULT 0,
			regdate  DATE NOT NULL DEFAULT '1999-01-01',
			enddate  DATE NOT NULL DEFAULT '1999-01-01',
			memberid INT UNSIGNED NOT NULL DEFAULT 1,
			primary key (tid))");
		
		if(getNucleusVersion() < 220) {
			$this->createOption('canedit','Edit authority [self | team | self+admin]', 'text', 'self');
		}
		else {
			$this->createOption('canedit','Edit authority [self | team | self+admin]', 'select', 'self', 'Self|self|Team|team|Self + Admin|self+admin');
		}
		
		$this->createOption('dateFormat','Date format', 'text', 'm/d(D)');
		$this->createOption('flg_pluglink','Show plugin link.','yesno','yes');
		$this->createOption('flg_erase', 'Erase data on uninstall.', 'yesno', 'no');
	} 
	
	function unInstall() { 
		if ($this->getOption(flg_erase) == 'yes') {
			sql_query ('DROP TABLE '. sql_table('plug_todolist') );
		}
	} 
	
	
	// .../action.php?action=plugin&name=TodoList&type=verup&vernum=X.X
	// it need login to update
	function versionUpdate($oldver) { 
		switch ($oldver) {
			case 0.1:
			case 0.2:
			case 0.3:
				sql_query ("ALTER TABLE ". sql_table('plug_todolist'). " ADD (
					memberid INT UNSIGNED NOT NULL DEFAULT 1)");
				break;
			case 0.4:
			default:
				//nothing to do
				break;
		}
	} 
	
	
	function init() {
		$this->rankname  = array('*','**','***');
		$this->condname  = array('notyet','working','finished','pending');
		$this->condstyle = array('background:#fff','background:#fd6','background:#add','background:#999;color:white','background:#f00;color:white');
		
		$query = "SHOW TABLES LIKE '". sql_table('plug_todolist') ."'";
		$table = sql_query($query);
		if (mysql_num_rows($table) > 0){
			$query = "SELECT * FROM ". sql_table('plug_todolist') ." ORDER BY cond, enddate";
			$res = sql_query($query);
			while ($data = mysql_fetch_object($res)) {
				$this->list[$data->memberid][] = $data; //set data by memberid
			}
		}
	}
	
	function doSkinVar($skinType, $showmode='normal',$memid='') {
		global $memberid;
		
		if (!$memid) $memid = $memberid; //in member page
		if (!$memid) $memid = 1; //default
		
		$editmode = intRequestVar('todoedit'); //get or post
		$this->showTodoList($editmode, $showmode, $memid);
	}
	
	function isLoggedIn() {
		global $member;
		return $member->isLoggedIn();
	}
	
	function canEdit($memid) {
		global $blog, $member;
		
		if ($blog) $b =& $blog; 
		else $b =& $manager->getBlog($CONF['DefaultBlog']);
		$bid = $b->getID();
		
		if (!$member->isLoggedIn()) return 0;
		
		switch ($this->getOption('canedit')) {
			case 'self':
				return ($member->getID() == $memid);
				break;
			case 'team':
				return ($member->teamRights($bid));
				break;
			case 'self+admin':
				return ($member->getID() == $memid || $member->blogAdminRights($bid));
				break;
			default:
				return 0;
		}
	}
	
	
	function showEntryForm($editmode, $showmode, $memid) {
		global $CONF;
		
		if (!$editmode) return;
?>
<form class="todolist" method="post" action="<?php echo $CONF['ActionURL'] ?>">
<input type="hidden" name="action" value="plugin"/>
<input type="hidden" name="name" value="TodoList" />
<input type="hidden" name="type" value="add" />
<input type="hidden" name="memid" value="<?php echo $memid ?>" />
<select name="rank">
<?php
		for($i = count($this->rankname)-1; $i>=0; $i--){
			echo "<option value='$i'>{$this->rankname[$i]}</option>\n";
		}
?>
</select>
<select name="cond">
<?php
			$i = 0;
			foreach($this->condname as $cname){
				echo "<option value='$i'>$cname</option>\n";
				$i++;
			}
?>
</select>
<?php
		if ($showmode != 'nodate') {
?>
<input class="formfield"  type="text" name="enddate" value="<?php echo date('Y-m-d', mktime(0,0,0,date('m'),date('d')+1,date('Y'))) ?>" size="9" maxlength="10" />
<?php
		}
?>
<input class="formfield"  type="text" name="title" value="" size="20" maxlength="255" />
<input class="formbutton" type="submit" value="Submit" />
</form>
<?php
	}
	
	
	function showTodoList($editmode, $showmode, $memid) {
		global $CONF, $member;
		
		$img_path = $this->getAdminURL();
		
		$this->showEntryForm($editmode, $showmode, $memid);
		
		if (empty($this->list[$memid])) {
			echo "<p>No data found.</p>";
		}
		else {
			//sort by rank
			foreach($this->list[$memid] as $l) {
				//if ($l->memberid != $memid) continue; // id check
				$byrank[ $l->rank ][] = $l;
			}
			$sortlist = array();
			for($i=count($this->rankname); $i>0; $i--) {
				$sortlist = array_merge($sortlist, (array)$byrank[$i-1]);
			}
			
			echo "<ul class='todolist'>\n";
			
			foreach($sortlist as $l) {
				$tid = $l->tid;
				$title = htmlspecialchars($l->title, ENT_QUOTES);
				$enddate = $l->enddate;
				$rank = $this->rankname[$l->rank];
				$cond = $this->condname[$l->cond];
				
				if ($editmode) {
?>
<form class="todolist" method="post" action="<?php echo $CONF['ActionURL'] ?>">
<input type="hidden" name="action" value="plugin"/>
<input type="hidden" name="name" value="TodoList" />
<input type="hidden" name="type" value="update" />
<input type="hidden" name="tid"  value="<?php echo $tid ?>" />
<select name="rank">
<?php
					for($i = count($this->rankname)-1; $i>=0; $i--){
						$selected = '';
						if ($i == $l->rank) $selected = 'selected';
						echo "<option value='$i' $selected>{$this->rankname[$i]}</option>\n";
					}
?>
</select>
<?php
				}
				else {
					echo "<li>";
					$img_file = 'rank'.$l->rank.'.gif';
					$img_title = $this->rankname[$l->rank];
				echo "<img class='icon-mid' src='$img_path$img_file' width='14' height='14' alt='$img_title' title='$img_title' />";
				}
				
				if ($editmode) {
?>
<select name="cond">
<?php
					$cstyle = $this->condstyle;
					$i = 0;
					foreach($this->condname as $cname){
						$selected = '';
						if ($i == $l->cond) $selected = 'selected';
						echo "<option style='$cstyle[$i]' value='$i' $selected>$cname</option>\n";
						$i++;
					}
					echo "<option style='$cstyle[$i]' value='$i'>[delete]</option>\n";
?>
</select>
<?php
				}
				else {
					$img_file = 'cond'.$l->cond.'.gif';
					$img_title = $this->condname[$l->cond];
				echo " <img class='icon-mid' src='$img_path$img_file' width='52' height='14' alt='$img_title' title='$img_title' />";
				}
				
				if ($editmode and $showmode != 'nodate') {
?>
<input class="formfield"  type="text" name="enddate" value="<?php echo $enddate ?>" size="9" maxlength="10" />
<?php
				}
				else if($showmode != 'nodate') {
					$date_style = 'enddate';
					if ( $enddate == date('Y-m-d', mktime( 0,0,0,date('m'),date('d')+1,date('Y'))) ) {
						$date_style = 'enddate2'; //tomorrow
					}
					else if ($enddate == date('Y-m-d')) {
						$date_style = 'enddate3'; //today
					}
					else if ($enddate < date('Y-m-d')) {
						$date_style = 'enddate4'; //past
					}
					
					//apply date format
					$enddate = date($this->getOption('dateFormat'), strToTime($enddate));
					echo " <span class='$date_style'>$enddate</span>";
				}
				
				if ($editmode) {
?>
<input class="formfield"  type="text" name="title" value="<?php echo $title ?>" size="20" maxlength="255" />
<?php
				}
				else {
					echo " <span class='title'>$title</span></li>\n";
				}
				
				if ($editmode) {
					if ($this->getOption('canedit') == 'team' 
						and $member->getID() != $memid) $disstr = 'disabled';
					else $disstr = '';
?>
<input class="formbutton" type='submit' value='Update' <?php echo $disstr?> />
</form>
<?php
				}
			} //end of foreach($sortlist)
			
			echo "</ul>\n";
			
		}// end of if(isset($this->list))
		
		//edit switch
		if ($this->canEdit($memid)) {
			if ($editmode) $str_edit = "checked";
			else $str_show = "checked"; 
?>
<form class="todolist-r" method="post" action="<?php echo $CONF['ActionURL'] ?>">
<input type="hidden" name="action" value="plugin"/>
<input type="hidden" name="name" value="TodoList" />
<input type="hidden" name="type" value="mode" />
<input type="radio"  name="todoedit" value="0" <?php echo $str_show ?> />Show
<input type="radio"  name="todoedit" value="1" <?php echo $str_edit ?> />Edit
<input class="formbutton" type='submit' value='Change' />
</form>
<?php
		}
		
		//plugin link
		if ($this->getOption('flg_pluglink') == 'yes') {
			$pluglink_url = $this->getURL();
			
			echo "<a href='$pluglink_url' title='Jump to the site of this plugin'>";
			echo "<span style='font-size:9px'>&raquo; Get \"".$this->getName()."\"</span></a>";
		}
		
	} //end of function
	
	
	function doAction($type) {
		global $CONF, $manager, $blog;
		
		if (! $this->isLoggedIn()) return;
		
		if ($blog) $b = &$blog;
		else $b = &$manager->getBlog($CONF['DefaultBlog']);
		
		switch($type) {
			case 'mode':
				$editmode = intRequestVar('todoedit'); //get or post
				$return = serverVar('HTTP_REFERER');
				$return = preg_replace('/[?&]todoedit=[^&]*/', '', $return); //delete old parameter
				if ( preg_match('/\?/',$return) ) $rvalue = "&todoedit=".$editmode;
				else $rvalue = "?todoedit=".$editmode;
				header("Location: $return$rvalue");
				return;
				break;
			case 'add':
				$query = sprintf("INSERT INTO %s SET title=%s, rank=%d, cond=%d, regdate=%s, enddate=%s, memberid=%s",
					sql_table('plug_todolist'),
					$this->quote_smart(postVar('title')),
					$this->quote_smart(intPostVar('rank')),
					$this->quote_smart(intPostVar('cond')),
					date("'Y-m-d'", $b->getCorrectTime()),
					$this->quote_smart(postVar('enddate')),
					$this->quote_smart(intPostVar('memid')) );
				sql_query($query);
				break;
			case 'update':
				if (intPostVar('cond') >= count($this->condname)) { //cond = del
					$query = sprintf("DELETE FROM %s WHERE tid=%d",
						sql_table('plug_todolist'),
						$this->quote_smart(intPostVar('tid')) );
				}
				else {
					$query = sprintf("UPDATE %s SET title=%s, rank=%d, cond=%d, enddate=%s WHERE tid=%d",
						sql_table('plug_todolist'),
						$this->quote_smart(postVar('title')),
						$this->quote_smart(intPostVar('rank')),
						$this->quote_smart(intPostVar('cond')),
						$this->quote_smart(postVar('enddate')),
						$this->quote_smart(intPostVar('tid')) );
				}
				sql_query($query);
				break;
			case 'verup':
				$vernum   = intRequestVar('vernum');
				$this->versionUpdate($vernum);
				break;
			default:
				break;
		}
		Header('Location: ' . serverVar('HTTP_REFERER') );
	}
	
	// quote variable to make safe
	function quote_smart($value) {
		if (get_magic_quotes_gpc()) $value = stripslashes($value);
		if (!is_numeric($value)) {
			$value = "'". mysql_real_escape_string($value) ."'";
		}
		else {
			$value = (int)$value;
		}
		return $value;
	}

} 
?>