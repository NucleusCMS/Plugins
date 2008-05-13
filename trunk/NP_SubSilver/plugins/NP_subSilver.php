<?php 
class NP_subSilver extends NucleusPlugin { 
	function getName() { return 'NP_subSilver'; }
	function getMinNucleusVersion() { return 330; }
	function getAuthor()  { return 'Katsumi'; }
	function getVersion() { return '0.2.9.7'; }
	function getURL() {return 'http://japan.nucleuscms.org/bb/viewtopic.php?t=3257';}
	function getDescription() { return $this->getName().' plugin'; } 
	function supportsFeature($what) { return (int)($what=='SqlTablePrefix'); }
	function getEventList() {
		return array('QuickMenu','InitSkinParse','PostAuthentication',
			'SpamCheck','PostAddComment','ValidateForm',
			'PreDeleteComment','PostDeleteComment','PreUpdateComment','PrepareCommentForEdit',
			'PostAddItem','PostAddCategory');
	}
	function getTableList() { return $this->sql_query('list'); }
	function install() { return call_user_func(array($this->loadClass('install'),'install')); }
	function unInstall() { return call_user_func(array($this->loadClass('install'),'unInstall')); }
	//function init() {}
	function sql_query($mode='name',$p1=''){
		$tablename[0]=sql_table(strtolower('plugin_'.substr(get_class($this),3)));
		switch($mode){
		case 'create': return sql_query('CREATE TABLE IF NOT EXISTS '.$tablename[0].' '.$p1);
		case 'drop':   return sql_query('DROP TABLE IF EXISTS '.$tablename[0]);
		case 'list':   return $tablename;
		case 'name':   return $tablename[0];
		default:       return sql_query($mode.' '.$tablename[0].' '.$p1);
		}
	}
	function quickQuery($mode,$p1){
		$row=mysql_fetch_assoc($res=$this->sql_query($mode,$p1));
		mysql_free_result($res);
		if (!$row) return false;
		return $row['result'];
	}
	var $commentquery='cid';// for example, 'cid' of 'http://www..com/?itemid=1&cid=5#cid5'
	var $searchincform='<div class="commentform">';
	var $home=false;
	var $skin;
	var $skintype;
	var $showstickies=false;// see NP_subSilver_SKIN::showStickies and NP_subSilver_TEMPLATE::_if()
	var $query_null=false;// See NP_subSilver_BLOG::getSqlSearch()
	var $limit=10;// See NP_subSilver_BLOG_MEMBER::getSqlBlog()
	var $langobj;// See NP_subSilver_text::doSkinVar()
	function event_QuickMenu(&$data) {
		global $member;
		if (!($member->isLoggedIn() && $member->isAdmin())) return;
		array_push($data['options'], array('title' => $this->getName(),
						'url' => '?action=pluginoptions&plugid='.(int)$this->getId(),
						'tooltip' => $this->getName() ) );
?><style type="text/css">
/*<![CDATA[*/
.jsbuttonbar {
	display:none;
}
/*]]>*/
</style><script type="text/javascript">
/*<![CDATA[*/
try {
  // Check the more first, then body, in order to avoid hidding comment form.
  document.getElementById('inputmore').style.display='none';
  document.getElementById('inputbody').style.display='none';
} catch(e) {}
/*]]>*/
</script><?php
	}
/* Following event is used to check the values of blog/category settings */
	var $noblogid=false;
	function event_PostAuthentication(){
		// Set blogid for the search page.
		global $blogid,$query,$DIR_NUCLEUS;
		if (strpos(realpath('./'),realpath($DIR_NUCLEUS))!==0 && ($query || getVar('search_author')) && !$blogid) {
			$blogid=(int)quickQuery('SELECT bnumber as result FROM '.sql_table('blog').' LIMIT 1');
			$this->noblogid=true;
		}
		// Restrict member's admin area.
		global $member,$DIR_PLUGINS,$HTTP_POST_VARS,$action;
		if ($member->isAdmin()) return;
		if (strpos(realpath('./'),realpath($DIR_PLUGINS))===0) return;
		if (strpos(realpath('./'),realpath($DIR_NUCLEUS))!==0) return;
		$invalid=array('createitem');
		if ( !(postVar('ticket') || getVar('ticket')) && (!in_array($action,$invalid)) ) return;
		$obj=&$this->loadClass('member');
		return $obj->event_PostAuthentication();
	}
/* General stuffs when the skin parse */
	function event_InitSkinParse(&$data){
		// Reset global $blogid if it's set in event_PostAuthentication.
		// This is for avoiding error on search page when blogid isn't set.
		// Also see the NP_subSilver_BLOG::getSqlSearch() function.
		global $blogid;
		if ($this->noblogid) $blogid='';
		// Check if homepage.
		global $HTTP_SERVER_VARS,$CONF;
		if (!isset($_SERVER)) $_SERVER=&$HTTP_SERVER_VARS;
		if (!isset($_SERVER['REQUEST_URI'])) {
			$_SERVER['REQUEST_URI']=str_replace(array('%2F','%2f'),'/',urlencode($_SERVER['PHP_SELF'])).'?'.$_SERVER['QUERY_STRING'];
		}
		$iuri=preg_replace('!^([^:]+)://([^/]+)/!','/',$CONF['IndexURL']);
		if ($_SERVER['REQUEST_URI']==$iuri || $_SERVER['REQUEST_URI']==$iuri.'index.php') {
			$this->home=true;
		} elseif( !( getVar('blogid') || postVar('blogid') ||
				getVar('catid') || postVar('catid') ||
				getVar('itemid') || postVar('itemid') ) ) {
			$this->home=true;
		}
		// Redirect just after logout.
		if (getVar('action')=='logout') {
			redirect(preg_replace('/^([^\?]+)\?([\s\S]*)$/','$1',$_SERVER['REQUEST_URI']));
			exit;
		}
		// Set skin object
		$this->skin=&$data['skin'];
		$this->skintype=$data['type'];
		// Set category if item page.
		global $catid,$itemid;
		if ($itemid && !$catid) $catid=quickQuery('SELECT icat as result FROM '.sql_table('item').' WHERE inumber='.(int)$itemid);
		// Count up the readnum column of table
		//$_SERVER['REMOTE_ADDR']=rand(0,255).'.'.rand(0,255).'.'.rand(0,255).'.'.rand(0,255);
		if ($itemid) {
			if ($readip=$this->quickQuery('SELECT readip as result FROM','WHERE itemid='.(int)$itemid.
				' AND readip NOT LIKE "%,'.addslashes(serverVar('REMOTE_ADDR')).',%"')){
					$this->sql_query('UPDATE','SET'.
						' readnum=readnum+1'.
						//',readip=CONCAT(SUBSTRING(readip,LOCATE(",",readip,2)),"'.addslashes(serverVar('REMOTE_ADDR')).',")'.
						',readip="'.addslashes(substr($readip,strpos($readip,',',1)).serverVar('REMOTE_ADDR')).',"'.
						' WHERE itemid='.(int)$itemid);
			}
		}
		// Set the item read.
		global $member;
		if ($member->isLoggedIn()) {
			$mstr=addslashes(','.$member->getId().',');
			if ($itemid) {
				$this->sql_query('UPDATE','SET '.
					'unread=CONCAT(LEFT(unread,INSTR(unread,"'.$mstr.'")),SUBSTRING(unread,INSTR(unread,"'.$mstr.'")+'.strlen($mstr).'))'.
					' WHERE itemid='.(int)$itemid);
			} elseif (getVar('subSilver_action')=='mark') {
				if ($cid=intGetVar('catid')) {
					$this->sql_query('UPDATE','as s, '.sql_table('item').' as i SET '.
						's.unread=CONCAT(LEFT(s.unread,INSTR(s.unread,"'.$mstr.'")),SUBSTRING(s.unread,INSTR(s.unread,"'.$mstr.'")+'.strlen($mstr).'))'.
						' WHERE s.itemid=i.inumber'.
						' AND i.icat='.(int)$cid);
				} elseif ($bid=intGetVar('blogid')) {
					$this->sql_query('UPDATE','as s, '.sql_table('item').' as i, '.sql_table('category').' as c'.' SET '.
						's.unread=CONCAT(LEFT(s.unread,INSTR(s.unread,"'.$mstr.'")),SUBSTRING(s.unread,INSTR(s.unread,"'.$mstr.'")+'.strlen($mstr).'))'.
						' WHERE s.itemid=i.inumber'.
						' AND i.icat=c.catid'.
						' AND c.cblog='.(int)$bid);
				} else {
					$this->sql_query('UPDATE','SET '.
						'unread=CONCAT(LEFT(unread,INSTR(unread,"'.$mstr.'")),SUBSTRING(unread,INSTR(unread,"'.$mstr.'")+'.strlen($mstr).'))'.
						' WHERE 1');
				}
			
			}
		}
		// Error message (must use htmlspecialchars)
		global $errormessage;
		if ($msg=getVar('errormessage')) $errormessage=str_replace('&amp;amp;','&amp;',htmlspecialchars($msg,ENT_QUOTES));
		// Replace blog object for searching/memberlist feature.
		global $blog,$query;
		if ($blog && $data['type']=='search') { //if ($blog && ( $query||getVar('search_author') )) {
			$obj=&$this->loadClass('search');
			$bid=$blog->getId();
			$blog=new NP_subSilver_BLOG($bid,$this);
			if (!$query) {
				// Create put temporary string into $query.
				// This will be removed in NP_subSilver_BLOG::getSqlSearch()
				$query = 'author: '.getVar('search_author');
				$this->query_null=true;
			}
		} elseif ($blog && $data['type']=='member'){
			$obj=&$this->loadClass('member');
			$bid=$blog->getId();
			$blog=new NP_subSilver_BLOG_MEMBER($bid,$this);
		}
		// Use language class
		$this->loadClass('skin');
		$this->langobj=new NP_subSilver_text;
	}
/* Following three events are used for posting */
	function event_SpamCheck(){
		if (postVar('subSilver_action')=='posting') {
			$obj=&$this->loadClass('posting');
			return $obj->posting('SpamCheck',$data);
		}
	}
	function event_ValidateForm(&$data){
		if (postVar('subSilver_action')=='posting') {
			$obj=&$this->loadClass('posting');
			return $obj->posting('ValidateForm',$data);
		}
	}
	function event_PostAddComment(&$data){
		$this->_event_everycomment();
		if (postVar('subSilver_action')=='posting') {
			$obj=&$this->loadClass('posting');
			return $obj->posting('PostAddComment',$data);
		} else {
			$obj=&$this->loadClass('comments');
			return $obj->event_PostAddComment(&$data);
		}
	}
	// PostAddItem event occurs when a new blog is created.
	function event_PostAddItem(&$data){
		$obj=&$this->loadClass('posting');
		return $obj->event_PostAddItem($data);
	}
	function event_PostAddCategory(&$data){
		$obj=&$this->loadClass('posting');
		return $obj->event_PostAddCategory($data);
	}
/* Following events are used when the comment is modified */
	function event_PreDeleteComment(&$data){
		$obj=&$this->loadClass('comments');
		return $obj->event_PreDeleteComment(&$data);
	}
	function event_PostDeleteComment(&$data){
		$obj=&$this->loadClass('comments');
		return $obj->event_PostDeleteComment(&$data);
	}
	function event_PrepareCommentForEdit(&$data){
		$obj=&$this->loadClass('comments');
		return $obj->event_PrepareCommentForEdit(&$data);
	}
	function event_PreUpdateComment(&$data){
		$obj=&$this->loadClass('comments');
		return $obj->event_PreUpdateComment(&$data);
	}
/* General class object manager */
	var $classobjects=array();
	function &loadClass($name){
		if (isset($this->classobjects[$name])) return $this->classobjects[$name];
		if (file_exists($phpfile=dirname(__FILE__).'/subsilver/'.$name.'.php')) require_once($phpfile);
		switch($name){
		case 'install':  $this->classobjects[$name]=new NP_subSilver_install($this);  break;
		case 'posting':  $this->classobjects[$name]=new NP_subSilver_posting($this);  break;
		case 'skin':     $this->classobjects[$name]=new NP_subSilver_SKIN($this);     break;
		case 'member':   $this->classobjects[$name]=new NP_subSilver_member($this);   break;
		case 'template': $this->classobjects[$name]=new NP_subSilver_TEMPLATE($this); break;
		case 'comments': $this->classobjects[$name]=new NP_subSilver_COMMENTS($this); break;
		case 'action':   $this->classobjects[$name]=new NP_subSilver_action($this);   break;
		case 'search':   $this->classobjects[$name]=new NP_subSilver_search($this);   break;
		default:         exit('loadClass: error');
		}
		return $this->classobjects[$name];
	}
/* common functions follow */
	var $stickydata;
	function &infoSticky(){
		// List up all "information" and "sticky" items.
		if (!isset($this->stickydata)) {
			$this->stickydata=array();
			$res=sql_query('SELECT o.ovalue as value, o.ocontextid as itemid FROM '.
				sql_table('plugin_option_desc').' as d, '.
				sql_table('plugin_option').' as o '.
				' WHERE d.oname="iteminfo"'.
				' AND d.oid=o.oid'.
				' AND d.opid='.(int)$this->getId().
				' AND NOT (o.ovalue=d.odef)');
			while($row=mysql_fetch_assoc($res)) $this->stickydata[$row['itemid']]=$row['value'];
			mysql_free_result($res);
		}
		return $this->stickydata;
	}
	var $popularitemdata;
	function popularItems($itemid=0){
		$popularnum=$this->getOption('popularnum');
		$populardef=$this->getOption('populardef');
		$populardef=TEMPLATE::fill($populardef,array(
			'days'=>'((1+UNIX_TIMESTAMP(s.time)-UNIX_TIMESTAMP(s.since))/86400)',
			'viewed'=>'s.readnum',
			'replies'=>'s.replynum'
			));
		if (!isset($this->popularitemdata)) {
			$this->popularitemdata=array();
			$res=$this->sql_query('SELECT itemid FROM','as s '.
				' ORDER BY ('.$populardef.')'.
				' DESC LIMIT '.(int)$popularnum);
			while($row=mysql_fetch_row($res)) $this->popularitemdata[]=$row[0];
			mysql_free_result($res);
		}
		if ($itemid) return in_array($itemid,$this->popularitemdata);
		return $this->popularitemdata;
	}
	function _event_everycomment(){
		// This function is called every "add comment" event.
		global $itemid,$manager;
		$blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
		$timestamp=$blog->getCorrectTime();
		// Update the timestamp of item
		sql_query('UPDATE '.sql_table('item').
			' SET itime='.mysqldate($timestamp).
			' WHERE inumber='.(int)$itemid);
		// Remove old "unread" data
		$this->sql_query('UPDATE','SET unread=","'.
			' WHERE time<'.mysqldate($timestamp-$this->getOption('unreaddays')*86400).
			' AND NOT (unread=",")' );
	}
/* doXxx(Vars) follow */
	function doAction(){
		$args=func_get_args();
		$class=&$this->loadClass('action');
		return call_user_func_array(array(&$class,'doAction'),$args);
	}
	function doSkinVar() {
		$args=func_get_args();
		if ($args[0]=='member'){
			if ($args[1]=='member') {
				$class=&$this->loadClass('member');
				return call_user_func_array(array(&$class,'doSkinVar'),$args);
			}
		}
		$class=&$this->loadClass('skin');
		return call_user_func_array(array(&$class,'doSkinVar'),$args);
	}
	function doTemplateVar() {
		$args=func_get_args();
		$class=&$this->loadClass('template');
		return call_user_func_array(array(&$class,'doTemplateVar'),$args);
	}
	function doTemplateCommentsVar(&$item,&$comment,$type) {
		$args=func_get_args();
		switch(strtolower($type)){
		case 'member':
			$class=&$this->loadClass('member');
			return call_user_func_array(array(&$class,'doTemplateCommentsVar'),$args);
		default:
			$class=&$this->loadClass('comments');
			return call_user_func_array(array(&$class,'doTemplateCommentsVar'),$args);
		}
	}
	var $isblogadmin=array();
	var $every=array();
	function doIf($mode,$p1=''){
		global $blog,$member,$memberid,$memberinfo;
		if (!preg_match('/^([^=]+)=([^=]*)$/',$p1,$matches)) exit('doIf: error 1');
		$name=$matches[1];
		$value=$matches[2];
		switch(strtolower($mode)){
		case 'every':
			if (isset($this->every[$name])) $this->every[$name]++;
			else $this->every[$name]=1;
			if ($this->every[$name]<(int)$value) return false;
			$this->every[$name]=0;
			return true;
		case 'globalvar':
			return $GLOBALS[$name]==$value;
		case 'intglobalvar':
			return (int)$GLOBALS[$name]==(int)$value;
		case 'thisvar':
			return $this->$name==$value;
		case 'intthisvar':
			return (int)$this->$name==(int)$value;
		case 'getvar':
			return getVar($name)==$value;
		case 'intgetvar':
			return (int)getVar($name)==(int)$value;
		case 'postvar':
			return postVar($name)==$value;
		case 'intpostvar':
			return (int)postVar($name)==(int)$value;
		case 'requestvar':
			if ($value) return postVar($name)==$value || getVar($name)==$value;
			else return postVar($name)=='' && getVar($name)=='';
		case 'intrequestvar':
			if ($value) return (int)postVar($name)==(int)$value || (int)getVar($name)==(int)$value;
			else return (int)postVar($name)==0 && (int)getVar($name)==0;
		case 'member':
			switch($name){
			case 'position':
				switch(strtolower($value)){
				case 'superadmin':
					return $memberinfo->isAdmin();
				case 'blogadmin':
					if (isset($this->isblogadmin[$memberid])) return $this->isblogadmin[$memberid];
					return $this->isblogadmin[$memberid]=quickQuery('SELECT COUNT(*) as result FROM '.sql_table('team').
						' WHERE tmember='.(int)$memberid.' AND tadmin=1 LIMIT 1');
				case 'ownerofpage':
					if (!$member->isLoggedIn()) return false;
					if ($member->isAdmin()) return true;
					return $member->getId()==$memberid;
				default: exit('doIf: error 4');
				}
			case 'has':
				switch(strtolower($value)){
				case 'url':
					$obj=&$this->loadClass('member');
					$data=$obj->getData($memberid);
					return (bool)$data['url'];
				default: exit('doIf: error 4');
				}
			case 'can':
				switch(strtolower($value)){
				case 'postnew':
					if ($this->getBlogOption($blog->getID(),'blogpostingbyguest')!='yes' && !$member->isLoggedIn()) return false;
				case 'reply':
					if (!$blog->getSetting('bcomments')) return false;
					if ($member->isAdmin() || $member->isBlogAdmin($blog->getID())) return true;
					if ($this->getBlogOption($blog->getID(),'bloghidden')=='yes') return false;
					if ($member->isLoggedIn() || $blog->getSetting('bpublic')) return true;
					return false;
				case 'editcomment':
					if (!$member->isLoggedIn()) return false;
					if (!$member->canLogin()) return false;
					return true;
				default: exit('doIf: error 4');
				}
			default: exit('doIf: error 3');
			}
		default: exit('doIf: error 2');
		}
	}
}
?>