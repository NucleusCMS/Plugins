<?php
class NP_admin_actions extends NP_admin_skinvars {
	// See also skins/admin/actions/xxx.inc
	function var_bloglist($template='templates/bloglist.inc'){
		global $member;
		
		$showAll = requestVar('showall');
		if (($member->isAdmin()) && ($showAll == 'yes')) {
			// Super-Admins have access to all blogs! (no add item support though)
			$query =  'SELECT bnumber, bname, 1 as tadmin, burl, bshortname'
				   . ' FROM ' . sql_table('blog')
				   . ' ORDER BY bname';
		} else {
			$query =  'SELECT bnumber, bname, tadmin, burl, bshortname'
				   . ' FROM ' . sql_table('blog') . ', ' . sql_table('team')
				   . ' WHERE tblog=bnumber and tmember=' . $member->getID()
				   . ' ORDER BY bname';
		}
		$res=sql_query($query);
		$amount=$this->showUsingQuery($query,$template,_OVERVIEW_NOBLOGS);
		
		if (($showAll != 'yes') && ($member->isAdmin())) {
			$total = quickQuery('SELECT COUNT(*) as result FROM ' . sql_table('blog'));
			if ($total > $amount)
				echo '<p><a href="?action=overview&amp;showall=yes">Show all blogs</a></p>';
		}
	}
	function var_draftlist($template='templates/draftlist.inc'){
		global $member;
		$query =  'SELECT ititle, inumber, bshortname'
			   . ' FROM ' . sql_table('item'). ', ' . sql_table('blog')
			   . ' WHERE iauthor='.$member->getID().' and iblog=bnumber and idraft=1';
		$this->showUsingQuery($query,$template,_OVERVIEW_NODRAFTS);
	}
	function _checkBlogAdminRight(){
		global $member, $manager, $blog;
		$blogid = $blog->getID();
		if (!$member->teamRights($blogid) && !$member->isAdmin()) return false;
		return $blogid;
	}
	function var_itemlist($template='templates/itemlist.inc'){
		global $member, $manager;
		
		// prepare blog stuffs
		$blogid = intRequestVar('blogid');
		$blog =& $manager->getBlog($blogid);
		// check the access right
		if (!$member->teamRights($blogid) && !$member->isAdmin()) return self::p(_ERROR_DISALLOWED);
		// start index
		$start = intPostVar('start');
		// amount of items to show
		$amount = intPostVar('amount');
		if (!$amount) $amount = 10;
		// search through items
		$search = postVar('search');

		$query =  'SELECT bshortname, bnumber, catid, cname, mname, ititle, ibody, inumber, idraft, itime'
			   . ' FROM ' . sql_table('item') . ', ' . sql_table('blog') . ', ' . sql_table('member') . ', ' . sql_table('category')
			   . ' WHERE iblog=bnumber and iauthor=mnumber and icat=catid and iblog=' . $blogid;

		if ($search)
			$query .= ' and ((ititle LIKE "%' . addslashes($search) . '%") or (ibody LIKE "%' . addslashes($search) . '%") or (imore LIKE "%' . addslashes($search) . '%"))';

		// non-blog-admins can only edit/delete their own items
		if (!$member->blogAdminRights($blogid))
			$query .= ' and iauthor=' . $member->getID();


		$query .= ' ORDER BY itime DESC'
				. " LIMIT $start,$amount";

		$this->showUsingQuery($query,$template,_LISTS_NOMORE);
	}
	function var_blogcommentlist($template='templates/blogcommentlist.inc'){
		global $member, $manager;

		// prepare blog stuffs
		$blogid = intRequestVar('blogid');
		$blog =& $manager->getBlog($blogid);
		// check the access right
		if (!$member->teamRights($blogid) && !$member->isAdmin()) return self::p(_ERROR_DISALLOWED);
		// start index
		$start = intPostVar('start');
		// amount of items to show
		$amount = intPostVar('amount');
		if (!$amount) $amount = 10;
		// search through items
		$search = postVar('search');


		$query =  'SELECT cbody, cuser, cemail, cmail, mname, ctime, chost, cnumber, cip, citem FROM '.sql_table('comment').' LEFT OUTER JOIN '.sql_table('member').' ON mnumber=cmember WHERE cblog=' . intval($blogid);

		if ($search != '')
			$query .= ' and cbody LIKE "%' . addslashes($search) . '%"';


		$query .= ' ORDER BY ctime DESC'
				. " LIMIT $start,$amount";

		$this->showUsingQuery($query,$template,_LISTS_NOMORE);
	}
	function var_blogsettingsteam($template='templates/blogsettingsteam.inc'){
		global $member, $manager;

		// prepare blog stuffs
		$blogid = intRequestVar('blogid');
		$blog =& $manager->getBlog($blogid);
		// check the access right
		if (!$member->teamRights($blogid) && !$member->isAdmin()) return self::p(_ERROR_DISALLOWED);
		
		$query='SELECT mname, mrealname FROM ' . sql_table('member') . ',' . sql_table('team') . ' WHERE mnumber=tmember AND tblog=' . intval($blogid);
		$this->showUsingQuery($query,$template,_LISTS_NOMORE);
	}
	function var_blogsettingsskin($template='templates/blogsettingsskin.inc'){
		global $blog;
		$blogid=$this->_checkBlogAdminRight();
		if (!$blogid) return self::p(_ERROR_DISALLOWED);
		$query =  'SELECT sdname as text, sdnumber as value'
			   . ' FROM '.sql_table('skin_desc');
		$callback=create_function('$row',
			'$row["selected"]= $row["value"]=='.(int)$blog->getDefaultSkin().' ? 1 : 0 ;'.
			'return $row;');
		$this->showUsingQuery($query,$template,'No available skin',$callback);
	}
	function var_blogsettingscategory($template='templates/blogsettingscategory.inc'){
		global $blog;
		$blogid=$this->_checkBlogAdminRight();
		if (!$blogid) return self::p(_ERROR_DISALLOWED);
		$query =  'SELECT cname as text, catid as value'
			. ' FROM '.sql_table('category')
			. ' WHERE cblog=' . $blog->getID();
		$callback=create_function('$row',
			'$row["selected"]= $row["value"]=='.(int)$blog->getDefaultCategory().' ? 1 : 0 ;'.
			'return $row;');
		$this->showUsingQuery($query,$template,'No available skin',$callback);
	}
	function var_additemcategory($template='templates/additemcategory.inc'){
		return $this->var_blogsettingscategory($template);
	}
	function var_blogsettingscategorylist($template='templates/blogsettingscategorylist.inc'){
		global $blog;
		$blogid=$this->_checkBlogAdminRight();
		if (!$blogid) return self::p(_ERROR_DISALLOWED);
		$query = 'SELECT * FROM '.sql_table('category').' WHERE cblog='.$blog->getID().' ORDER BY cname';
		$this->showUsingQuery($query,$template,_LISTS_NOMORE);
	}
	function var_banlist($template='templates/banlist.inc'){
		global $blog;
		$blogid=$this->_checkBlogAdminRight();
		if (!$blogid) return self::p(_ERROR_DISALLOWED);
		$query =  'SELECT * FROM '.sql_table('ban').' WHERE blogid='.$blogid.' ORDER BY iprange';
		$this->showUsingQuery($query,$template,_BAN_NONE);
	}
	function _checkMemberAdminRight(){
		global $member;
		$mid=requestVar('memberid');
		if (!$mid) return $member->getID();
		if ($member->isAdmin()) return $mid;
		else false;
	}
	function var_editmembersettings($template='templates/editmembersettings.inc'){
		$mid=$this->_checkMemberAdminRight();
		if (!mid) return self::p(_ERROR_DISALLOWED);
		$query = 'SELECT mnumber, mname, mrealname, memail, murl, mnotes, madmin, mcanlogin, deflang FROM '
			.sql_table('member'). ' WHERE mnumber='.(int)$mid;
		$this->showUsingQuery($query,$template);
	}
	function var_editmembersettingsdeflang($template='templates/editmembersettingsdeflang.inc'){
		// A template var.
		global $DIR_LANG;
		$rows=array();
		$dirhandle = opendir($DIR_LANG);
		while ($filename = readdir($dirhandle)) {
			if (ereg("^(.*)\.php$",$filename,$matches)) {
				$rows[]=array('name'=>$matches[1]);
			}
		}
		closedir($dirhandle);
		$callback=create_function('$row',
			'$row["selected"]= $row["name"]==\''.addslashes($this->rowdata['deflang']).'\' ? 1 : 0 ;'.
			'return $row;');
		$this->showUsingArray($rows,$template,'No available language file.',$callback);	
	}
	function var_browseownitems($template='templates/browseownitems.inc'){
		global $member, $manager;
		
		// start index
		$start = intPostVar('start');
		// amount of items to show
		$amount = intPostVar('amount');
		if (!$amount) $amount = 10;
		// search through items
		$search = postVar('search');

		$query =  'SELECT bshortname, bnumber, catid, cname, mname, ititle, ibody, inumber, idraft, itime'
			   . ' FROM ' . sql_table('item') . ', ' . sql_table('blog') . ', ' . sql_table('member') . ', ' . sql_table('category')
			   . ' WHERE iblog=bnumber and iauthor=mnumber and icat=catid and iauthor=' . $member->getID();

		if ($search)
			$query .= ' and ((ititle LIKE "%' . addslashes($search) . '%") or (ibody LIKE "%' . addslashes($search) . '%") or (imore LIKE "%' . addslashes($search) . '%"))';


		$query .= ' ORDER BY itime DESC'
				. " LIMIT $start,$amount";

		$this->showUsingQuery($query,$template,_LISTS_NOMORE);
	}
	function var_browseowncomments($template='templates/browseowncomments.inc'){
		global $member, $manager;
		// start index
		$start = intPostVar('start');
		// amount of items to show
		$amount = intPostVar('amount');
		if (!$amount) $amount = 10;
		// search through items
		$search = postVar('search');

		$query =  'SELECT cbody, cuser, cemail, cmail, mname, ctime, chost, cnumber, cip, citem FROM '.
			sql_table('comment').' LEFT OUTER JOIN '.sql_table('member').
			' ON mnumber=cmember WHERE cmember=' . $member->getID();

		if ($search != '')
			$query .= ' and cbody LIKE "%' . addslashes($search) . '%"';

		$query .= ' ORDER BY ctime DESC'
				. " LIMIT $start,$amount";

		$this->showUsingQuery($query,$template,_LISTS_NOMORE);
	}
	function var_inputyesno($name,$key,$extra='',$template='templates/inputyesno.inc'){
		if (!preg_match('#^(.*)/(.*)$#',$key,$m)) {
			$m=array();
			$m[1]='';
			$m[2]=$key;
		}
		switch($m[1]){
			case 'blogsetting':
				$data=$this->_blogsetting($m[2]) ? 1 : 0 ;
				break;
			case 'contents':
				$data=$this->_contents($m[2]) ? 1 : 0 ;
				break;
			case 'filled':
				$data=$this->rowdata[$m[2]] ? 1 : 0 ;
				break;
			case 'set':
				$data=$this->properties[$m[2]] ? 1 : 0 ;
				break;
			default:
				if (is_numeric($key)) $data=$key ? 1 : 0 ;
				else $data=$this->properties[$key] ? 1 : 0 ;
		}
		$row=array();
		if (strlen($extra)) {
			foreach(explode('/',$extra) as $value){
				if (!preg_match('/^(.*)=(.*)$/',$value,$m)) continue;
				$row[$m[1]]=$m[2];
			}
		}
		$row['name']=$name;
		$row['yesno']=$data;
		$this->showUsingArray(array($row),$template);
	}
	function var_inputcheckbox($name,$key,$extra='',$template='templates/inputcheckbox.inc'){
		$this->var_inputyesno($name,$key,$extra,$template);
	}
	function var_jsbutton($type,$code='',$tooltip='',$shortcutkey='',$template='templates/jsbutton.inc'){
		if (defined($tooltip)) $tooltip=constant($tooltip);
		if (strlen($shortcutkey)) $tooltip="$tooltip ($shortcutkey)";
		$row=array();
		$row['type']=$type;
		$row['code']=$code;
		$row['tooltip']=$tooltip;
		$this->showUsingArray(array($row),$template);
	}
	function var_qmenuplugin($template='templates/qmenuplugin.inc'){
		global $manager;
		$aPluginExtras = array();
		$manager->notify(
			'QuickMenu',
			array(
				'options' => &$aPluginExtras
			)
		);
		$this->showUsingArray($aPluginExtras,$template);
	}
	function var_qmenuadd($template='templates/qmenuadd.inc'){
		global $member;
		$showAll = requestVar('showall');
		if (($member->isAdmin()) && ($showAll == 'yes')) {
			// Super-Admins have access to all blogs! (no add item support though)
			$query =  'SELECT bnumber, bname'
				   . ' FROM ' . sql_table('blog')
				   . ' ORDER BY bname';
		} else {
			$query =  'SELECT bnumber, bname'
				   . ' FROM ' . sql_table('blog') . ', ' . sql_table('team')
				   . ' WHERE tblog=bnumber and tmember=' . $member->getID()
				   . ' ORDER BY bname';
		}
		$this->showUsingQuery($query,$template);
	}
}