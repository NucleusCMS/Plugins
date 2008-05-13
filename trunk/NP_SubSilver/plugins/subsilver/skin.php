<?php 
class NP_subSilver_SKIN { 
	var $plug;
	function NP_subSilver_SKIN(&$plug){
		$this->plug=&$plug;
		foreach($plug as $key=>$value) $this->$key=&$plug->$key;
	}
	var $blogid;
	var $blogs;
	var $template='subSilver/index';
	function showBlogList($template, $bnametype='') {
		$this->template=$template;
		$this->blogs=array();
		$query = 'SELECT bnumber FROM '.sql_table('blog').' ORDER BY bnumber ASC';
		$res = sql_query($query);
		while($row=mysql_fetch_row($res)) $this->blogs[]=$row[0];
		ob_start();
		BLOG::showBlogList($template, $bnametype);
		$html=ob_get_contents();
		ob_end_clean();
		echo preg_replace_callback('/<:categorylist:>/',array($this,'showBlogListCallback'),$html);
	}
	function showBlogListCallback($match){
		global $blogid;
		$this->blogid=array_shift($this->blogs);
		if ($this->blogid!=$blogid && !$this->home) return '';
		ob_start();
		$this->showCategoryList($this->template);
		$html=ob_get_contents();
		ob_end_clean();
		return $html;
	}
	function showCategoryList($template) {
		global $manager,$CONF;
		$blog=&$manager->getBlog($this->blogid);
		ob_start();
		$blog->showCategoryList($template);
		$html=ob_get_contents();
		ob_end_clean();

		$template =& $manager->getTemplate($template);
		$search=$replace=array();
		$skinurl=$CONF['SkinsURL'] . PARSER::getProperty('IncludePrefix');
		$search[]='<:skinurl:>';
		$replace[]=htmlspecialchars($skinurl,ENT_QUOTES);

		$categories=array();
		$query='SELECT * FROM '.sql_table('category').' WHERE cblog='.(int)$this->blogid;
		$res=sql_query($query);
		while($row=mysql_fetch_assoc($res)) $categories[]=$row;
		mysql_free_result($res);

		$members=array();
		foreach($categories as $row){
			// Number of topics
			$catid=$row['catid'];
			$query='SELECT COUNT(*) as result FROM '.sql_table('item').' WHERE icat='.(int)$catid;
			$search[]='<:topics:'.$catid.':>';
			$replace[]=(int)quickQuery($query);
			// Number of replies
			$query='SELECT COUNT(c.cnumber) as result FROM '.sql_table('item').' as i, '.sql_table('comment').' as c'.
				' WHERE i.icat='.(int)$catid.
				' AND c.citem=i.inumber';
			$search[]='<:replies:'.$catid.':>';
			$replace[]=(int)quickQuery($query);
			// Last item
			$query='SELECT inumber as result FROM '.sql_table('item').' WHERE icat='.(int)$catid.' ORDER BY itime DESC LIMIT 1';
			$itemid=(int)quickQuery($query);
			$query='SELECT * FROM '.sql_table('comment').' WHERE citem='.(int)$itemid.' ORDER BY ctime DESC LIMIT 1';
			if ($row=mysql_fetch_assoc($res=sql_query($query))) {
				if ($row['cmember']) {
					if (!is_array($members[$row['cmember']])) {
						$query='SELECT * FROM '.sql_table('member').' WHERE mnumber='.(int)$row['cmember'].' LIMIT 1';
						$members[$row['cmember']]=mysql_fetch_assoc($res2=sql_query($query));
						mysql_free_result($res2);
					}
					$authorname=$members[$row['cmember']]['mrealname'];
					$authorlink=createMemberLink($row['cmember']);
				} else {
					$authorname=$row['cuser'];
					$authorlink='#'._EDITC_NONMEMBER;
				}
				$timestamp=strtotime($row['ctime']);
				$search[]='<:lastitem:date:'.$catid.':>';
				$replace[]=strftime($template['FORMAT_DATE'],$timestamp);
				$search[]='<:lastitem:time:'.$catid.':>';
				$replace[]=strftime($template['FORMAT_TIME'],$timestamp);
				$search[]='<:lastitem:authorname:'.$catid.':>';
				$replace[]=htmlspecialchars($authorname,ENT_QUOTES);
				$search[]='<:lastitem:authorlink:'.$catid.':>';
				$replace[]=$authorlink;
				$search[]='<:lastitem:link:'.$catid.':>';
				$replace[]=createItemLink($itemid,array($this->commentquery=>(int)$row['cnumber'])).'#'.$this->commentquery.(int)$row['cnumber'];
			} else {
				$search[]='<:lastitem:link:'.$catid.':>';
				$replace[]=createItemLink($itemid);
			}
			mysql_free_result($res);
			
		}
		
		// Parse category icon: read, unread or locked.
		global $member;
		$mid=$member->isLoggedIn()?$member->getId():0;
		$num=preg_match_all('/<:categoryicon:([0-9]+):([^:]+):([^:]+):([^:]+):>/',$html,$matches,PREG_SET_ORDER);
		for($i=0;$i<$num;$i++){
			$search[]=$matches[$i][0];
			$comm_ok=quickQuery('SELECT b.bcomments as result FROM '.sql_table('blog').' as b, '.
					sql_table('category').' as c'.
					' WHERE c.cblog=b.bnumber'.
					' AND c.catid='.(int)$matches[$i][1].
					' LIMIT 1');
			if ($mid) {
				$new=$this->plug->quickQuery('SELECT COUNT(*) as result FROM','as s, '.
					sql_table('item').' as i, '.
					sql_table('category').' as c '.
					' WHERE i.icat='.(int)$matches[$i][1].
					' AND c.catid='.(int)$matches[$i][1].
					' AND i.inumber=s.itemid'.
					' AND s.unread LIKE "%,'.(int)$mid.',%"');
			} else $new=false;
			if ($comm_ok && $new) $replace[]=htmlspecialchars($matches[$i][2],ENT_QUOTES);
			else if ($comm_ok) $replace[]=htmlspecialchars($matches[$i][3],ENT_QUOTES);
			else $replace[]=htmlspecialchars($matches[$i][4],ENT_QUOTES);
		}

		echo str_replace($search,$replace,$html);
	}
	function showClock($template,$mode='both'){
		global $CONF, $manager, $blog;
		if (!is_array($template)) $template =& $manager->getTemplate($template);
		if ($blog) $timestamp=$blog->getCorrectTime();
		else $timestamp=time();
		if ($mode!='time') echo strftime($template['FORMAT_DATE'],$timestamp);
		if ($mode=='both') echo '&nbsp;';
		if ($mode!='date') echo strftime($template['FORMAT_TIME'],$timestamp);
	}
	function showConf($type){
		global $skin_subsilver_conf;
		echo htmlspecialchars($skin_subsilver_conf[$type],ENT_QUOTES);
	}
	function strftime($format){
		global $blog;
		if ($blog) $timestamp=$blog->getCorrectTime();
		else $timestamp=time();
		echo strftime($format,$timestamp);
	}
	function userdata($mode){
		switch($mode){
		case 'totalreplies':
			echo (int)quickQuery('SELECT COUNT(*) as result FROM '.sql_table('comment'));
			break;
		case 'number':
			echo (int)quickQuery('SELECT COUNT(*) as result FROM '.sql_table('member'));
			break;
		case 'newest':
			echo htmlspecialchars(quickQuery('SELECT mrealname as result FROM '.sql_table('member').' ORDER BY mnumber DESC'),ENT_QUOTES);
			break;
		case 'newestlink':
			$mid=(int)quickQuery('SELECT mnumber as result FROM '.sql_table('member').' ORDER BY mnumber DESC');
			echo createMemberLink($mid);
			break;
		case 'onlinenum':
		case 'onlinemembernum':
		case 'onlineguestnum':
		case 'onlinemembers':
		default:
			echo '?';
		}
	}
	var $handler;
	var $parser;
	function _setHandler(){
		$actions=SKIN::getAllowedActionsForType($this->skintype);
		$actions=array_merge($actions,array('itemid'));// For commentform for new topic
		$this->handler =& new ACTIONS($this->skintype, $this);
		$this->parser =& new PARSER($actions, $this->handler);
		$this->handler->setParser($this->parser);
	}
	function commentform(){// used to show form for new topic
		global $member;
		if (!is_object($this->handler)) $this->_setHandler();

		// values to prefill
		$user = cookieVar($CONF['CookiePrefix'] .'comment_user');
		if (!$user) $user = postVar('user');
		$userid = cookieVar($CONF['CookiePrefix'] .'comment_userid');
		if (!$userid) $userid = postVar('userid');
		$email = cookieVar($CONF['CookiePrefix'] .'comment_email');
		if (!$email) {
			$email = postVar('email');
		}
		$body = postVar('body');


		// Discover an item that belongs to this category.
		global $catid,$itemid;
		if ($catid && !$itemid){
			$itemid=quickQuery('SELECT inumber as result FROM '.sql_table('item').' WHERE icat='.(int)$catid.' ORDER BY itime DESC LIMIT 1');
		} 
		$itemid=(int)$itemid;
		
		$this->handler->formdata = array(
			'destinationurl' => '',
			'actionurl' => htmlspecialchars($actionurl,ENT_QUOTES),
			'itemid' => $itemid,
			'user' => htmlspecialchars($user,ENT_QUOTES),
			'userid' => htmlspecialchars($userid,ENT_QUOTES),
			'email' => htmlspecialchars($email,ENT_QUOTES),
			'body' => htmlspecialchars($body,ENT_QUOTES),
			'membername' => $member->getDisplayName(),
			'rememberchecked' => cookieVar($CONF['CookiePrefix'] .'comment_user')?'checked="checked"':''
		);

		ob_start();
		if (!$member->isLoggedIn()) {
			$this->handler->doForm('commentform-notloggedin');
		} else {
			$this->handler->doForm('commentform-loggedin');
		}
		$html=ob_get_contents();
		ob_end_clean();
		// Insert new tags that are required for posting.
		$newtag='<label for="nucleus_cf_title">'._ADD_TITLE.":</label>\n";
		$newtag.='<input type="hidden" name="subSilver_action" value="posting" />'."\n";
		$newtag.='<input type="text" name="title" value="'.htmlspecialchars(postVar('title'),ENT_QUOTES).
			'" class="formfield" id="nucleus_cf_title" maxlength="160" />'."\n";
		$newtag.='<input type="hidden" name="catid" value="'.(int)$catid.'" />'."\n";
		$newtag.='<input type="hidden" name="more" value="" />'."\n";
		$newtag.='<input type="hidden" name="closed" value="0" />'."\n";
		$newtag.='<input type="hidden" name="actiontype" value="addnow" />'."\n";
		// $this->searchincform is like '<div class="commentform">' (see NP_subSilver.php)
		echo str_replace($this->searchincform,$this->searchincform.$newtag,$html);
	}
	function showStickies($template){
		global $blog,$catid;
		if (!$blog || !$catid) return;
		if (!$infosticky=$this->plug->infoSticky()) return;
		$stickies='';
		foreach($infosticky as $itemid=>$mode) $stickies.=($stickies?',':'').(int)$itemid;
		$query=$blog->getSqlBlog('AND i.inumber in ('.$stickies.')');
		$this->plug->showstickies=true;
		$blog->showUsingQuery($template,$query,'',1);
		$this->plug->showstickies=false;
	}
	function strip_tags(){
		$args=func_get_args();
		$mode=array_shift($args);
		switch(strtolower($mode)){
		case 'begin':
			ob_start();
			break;
		case 'end':
			$html=ob_get_contents();
			ob_end_clean();
			echo htmlspecialchars(strip_tags($html),ENT_QUOTES);
			break;
		default:
			if (!is_object($this->handler)) $this->_setHandler();
			ob_start();
			call_user_func_array(array($this->handler,'parse_'.$mode),$args);
			$html=ob_get_contents();
			ob_end_clean();
			echo htmlspecialchars(strip_tags($html),ENT_QUOTES);
			break;
		}
	}
	function form($type){
		switch($type){
		case 'bloglist':
			$res=sql_query('SELECT bnumber, bname FROM '.sql_table('blog'));
			while($row=mysql_fetch_assoc($res)) {
				echo '<option value="'.
					(int)$row['bnumber'].'">'.
					htmlspecialchars(strip_tags($row['bname'])).
					"</option>\n";
			}
			mysql_free_result($res);
			break;
		case 'categorylist':
			$res=sql_query('SELECT catid, cname FROM '.sql_table('category'));
			while($row=mysql_fetch_assoc($res)) {
				echo '<option value="'.
					(int)$row['catid'].'">'.
					htmlspecialchars(strip_tags($row['cname'])).
					"</option>\n";
			}
			mysql_free_result($res);
			break;
		case 'javascript':
			$res=sql_query('SELECT catid, cblog FROM '.sql_table('category'));
?><script type="text/javascript">
/*<![CDATA[*/
function subSilver_blogidFromCatid(cid){
  switch(cid){
    <?php while($row=mysql_fetch_assoc($res)) echo 'case "'.(int)$row['catid'].'": return '.(int)$row['cblog'].";\n"; ?>
    default: return 0;
  }
}
/*]]>*/
</script><?php
		default:
			break;
		}
	}
	function getVar($name){
		echo htmlspecialchars(getVar($name),ENT_QUOTES);
	}
	function postVar($name){
		echo htmlspecialchars(postVar($name),ENT_QUOTES);
	}
	function requestVar($name){
		echo htmlspecialchars(requestVar($name),ENT_QUOTES);
	}
	function memberlist($template,$limit=10){
		// See NP_subSilver_BLOG_MEMBER::getSqlBlog()
		if (!is_object($this->handler)) $this->_setHandler();
		$this->plug->limit=$limit;
		global $blog,$manager,$memberinfo,$memberid;
		$template =& $manager->getTemplate($template);
		$res=sql_query($blog->getSqlBlog(''));
		$found=false;
		$contents=$template['ARCHIVELIST_LISTITEM'];
		while ($row=mysql_fetch_assoc($res)) {
			$memberid=$row['mnumber'];
			$memberinfo=$manager->getMember($memberid);
			$this->parser->parse($contents);
			$found=true;
		}
		mysql_free_result($res);
		if (($author=getVar('search_author')) && !$found) {
			$contents=TEMPLATE::fill($template['ARCHIVELIST_FOOTER'],array('query'=>htmlspecialchars($author,ENT_QUOTES)));
			$this->parser->parse($contents);
		}
	}
	function sortbutton($descrip,$img_asc,$param_asc,$img_desc,$param_desc){
		global $CONF;
		$params=preg_replace('/(^|&)sort=([^&]*)(&|$)/i','$3',serverVar('QUERY_STRING'));
		if ($params) $params='?'.$params.'&';
		else $params='?';
		$skinurl=$CONF['SkinsURL'] . PARSER::getProperty('IncludePrefix');
		$link_asc=htmlspecialchars($CONF['self'].$params.'sort='.$param_asc);
		$link_desc=htmlspecialchars($CONF['self'].$params.'sort='.$param_desc);
		$img_asc=htmlspecialchars($skinurl.$img_asc);
		$img_desc=htmlspecialchars($skinurl.$img_desc);
		$descrip=htmlspecialchars($descrip);
		if (getVar('sort')==$param_asc) {
?><a href="<?php echo $link_desc; ?>"><img src="<?php echo $img_asc; ?>" alt="<?php echo $descrip; ?>" title="<?php echo $descrip; ?>" border="0" 
onmouseover="
this.src='<?php echo $img_desc; ?>';
" onmouseout="
this.src='<?php echo $img_asc; ?>';
" /></a><?php
		} elseif (getVar('sort')==$param_desc) {
?><a href="<?php echo $link_asc; ?>"><img src="<?php echo $img_desc; ?>" alt="<?php echo $descrip; ?>" title="<?php echo $descrip; ?>" border="0" 
onmouseover="
this.src='<?php echo $img_asc; ?>';
" onmouseout="
this.src='<?php echo $img_desc; ?>';
" /></a><?php
		} else {
?><a href="<?php echo $link_asc; ?>"><img src="<?php echo $img_asc; ?>" alt="<?php echo $descrip; ?>" title="<?php echo $descrip; ?>" border="0" /></a>
<?php
		}
	}
	function includelanguage($file){
		global $DIR_SKINS;
		$dir=$DIR_SKINS.PARSER::getProperty('IncludePrefix');
		$dir.=ereg_replace( '[\\|/]', '', getLanguageName()).'/';
		if (strpos(realpath($dir),realpath($DIR_SKINS))===false) return;
		if (strpos(realpath($dir.$file),realpath($dir))===false) return;
		@readfile($dir.$file);
	}
	function doSkinVar() {
		$args=func_get_args();
		$skinType=array_shift($args);
		$type=array_shift($args);
		switch(strtolower($type)){
		case 'showclock':
		case 'showbloglist':
		case 'showconf':
		case 'strftime':
		case 'userdata':
		case 'commentform':
		case 'showstickies':
		case 'strip_tags':
		case 'form':
		case 'getvar':
		case 'postvar':
		case 'requestvar':
		case 'memberlist':
		case 'sortbutton':
		case 'includelanguage':
			return call_user_func_array(array(&$this,&$type),&$args);
		default:
		}
	}
}
class NP_subSilver_text extends NucleusPlugin{
	// Class to use language file for skin
	function NP_subSilver_text(){
		// Let manager think this is the plugin object
		global $manager;
		if (!$manager->pluginInstalled('NP_text')) {
		    $pid=count($manager->cachedInfo['installedPlugins'])*2;
		    $manager->cachedInfo['installedPlugins'][$pid]='NP_text';
		    $manager->plugins['NP_text']=&$this;
		}
	}
	function doSkinVar(&$skintype,$text){
		if (preg_match('/[^0-9a-zA-Z_]/',$text) || !defined($text)) return;
		eval("echo $text;");
	}
}
?>