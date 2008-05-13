<?php 
class NP_subSilver_member { 
	var $plug;
	function NP_subSilver_member(&$plug){
		$this->plug=&$plug;
		foreach($plug as $key=>$value) $this->$key=&$plug->$key;
		global $memberid;
		$this->mid=(int)$memberid;
		$this->_getData();
	}
	var $mid;
	var $memberdata=array();
	var $totalreplies=false;
	function &getData($mid){
		if (!$mid) exit ('Error: NP_subSilver_member::getData()');
		$tempmid=$this->mid;
		$this->mid=$mid;
		if (!isset($this->memberdata[$mid])) $this->_getData();
		$this->mid=$tempmid;
		return $this->memberdata[$mid];
	}
	function _getData(){
		if (!$this->totalreplies) $this->totalreplies=(int)quickQuery('SELECT COUNT(*) as result FROM '.sql_table('comment'));
		if (!$this->totalreplies) $this->totalreplies=1;
		$this->memberdata[$this->mid]=array();
		$this->memberdata[$this->mid]['replies']=(int)quickQuery('SELECT COUNT(*) as result FROM '.sql_table('comment').' WHERE cmember='.(int)$this->mid);
		$this->memberdata[$this->mid]['percent']=0.01*(int)((float)10000*$this->memberdata[$this->mid]['replies']/$this->totalreplies);
		$this->memberdata[$this->mid]['regdate']=strtotime(quickQuery('SELECT ctime as result FROM '.sql_table('comment').' WHERE cmember>='.(int)$this->mid.' ORDER BY ctime ASC LIMIT 1'));
		$this->memberdata[$this->mid]['repliesperday']=0.01*(int)((float)100*$this->memberdata[$this->mid]['replies']*86000/(time()-$this->memberdata[$this->mid]['regdate']));
		$rawurl=quickQuery('SELECT murl as result FROM '.sql_table('member').' WHERE mnumber='.(int)$this->mid);
		$url=htmlspecialchars($rawurl,ENT_QUOTES);
		if ($url && $url!='http://') $url='<a href="'.$url.'" onclick="window.open(this.href);return false;">'.$url.'</a>';
		else $url=$rawurl='';
		$this->memberdata[$this->mid]['url']=$url;
		$this->memberdata[$this->mid]['rawurl']=$rawurl;
		if (quickQuery('SELECT madmin as result FROM '.sql_table('member').' WHERE mnumber='.(int)$this->mid))
			$this->memberdata[$this->mid]['position']='Administrator';
		elseif (quickQuery('SELECT COUNT(*) as result FROM '.sql_table('team').' WHERE tmember='.(int)$this->mid.' AND tadmin=1 LIMIT 1'))
			$this->memberdata[$this->mid]['position']='Moderator';
		else $this->memberdata[$this->mid]['position']='Member';
	}
	function _getGuestData(){
		if (!$this->totalreplies) $this->totalreplies=(int)quickQuery('SELECT COUNT(*) as result FROM '.sql_table('comment'));
		if (!$this->totalreplies) $this->totalreplies=1;
		$this->memberdata[$this->mid]=array();
		$this->memberdata[$this->mid]['replies']=(int)quickQuery('SELECT COUNT(*) as result FROM '.sql_table('comment').' WHERE cuser="'.addslashes($this->mid).'"');
		$this->memberdata[$this->mid]['percent']=0.01*(int)((float)10000*$this->memberdata[$this->mid]['replies']/$this->totalreplies);
		$this->memberdata[$this->mid]['regdate']=strtotime(quickQuery('SELECT ctime as result FROM '.sql_table('comment').' WHERE cuser="'.addslashes($this->mid).'" ORDER BY ctime ASC LIMIT 1'));
		$this->memberdata[$this->mid]['repliesperday']=0.01*(int)((float)100*$this->memberdata[$this->mid]['replies']*86000/(time()-$this->memberdata[$this->mid]['regdate']));
		$url=$rawurl=$this->comment['url'];
		if ($url && $url!='http://') $url='<a href="'.htmlspecialchars($url,ENT_QUOTES).'" onclick="window.open(this.href);return false;">'.$url.'</a>';
		else $url=$rawurl='';
		$this->memberdata[$this->mid]['url']=$url;
		$this->memberdata[$this->mid]['url']=$rawurl;
		$this->memberdata[$this->mid]['position']='Guest';
	}
	function member($type,$p1=''){
		global $manager;
		switch($type=strtolower($type)){
		case 'url':
			echo $this->memberdata[$this->mid]['url'];// This data was sanitized before ( see _getData() and _getGuestData() )
			break;
		case 'rawurl':
		case 'position':
		case 'replies':
		case 'percent':
		case 'repliesperday':
			echo htmlspecialchars($this->memberdata[$this->mid][$type],ENT_QUOTES);
			break;
		case 'regdate':
			$template=&$manager->getTemplate($p1);
			echo strftime($template['FORMAT_DATE'],$this->memberdata[$this->mid]['regdate']);
		default:
			break;
		}
	}
	function doSkinVar() {
		global $memberid;
		if ((int)$memberid) {
			$this->mid=(int)$memberid;
			if (!isset($this->memberdata[$this->mid])) $this->_getData();
		}
		$args=func_get_args();
		$skinType=array_shift($args);
		$type=array_shift($args);
		switch(strtolower($type)){
		case 'member':
			return call_user_func_array(array($this,$type),$args);
		default:
		}
	}
	var $comment;
	function doTemplateCommentsVar(&$item,&$comment) {
		$args=func_get_args();
		$item=array_shift($args);
		$this->comment=array_shift($args);
		$mid=$this->comment['memberid'];
		if (!$mid) {
			$this->mid=$this->comment['user'];
			if (!@$this->memberdata[$this->mid]['regdate']) $this->_getGuestData();
			
		} elseif ($mid!=$this->mid) {
			$this->mid=$mid;
			if (!@$this->memberdata[$this->mid]['regdate']) $this->_getData();
		}
		$type=array_shift($args);
		switch(strtolower($type)){
		case 'member':
			return call_user_func_array(array($this,$type),$args);
		default:
		}
	}
/* Following function is used to avoid submitting <>"'\ by members. */
	function event_PostAuthentication(){
		// exclude body in commentupdate event
		// plugoption[][] -> just unset the var when containing <>"'\
		// The value may contain <>"'\ if the value is equal to nucleus_blog.bname, bdesc or nucleus_category.cname, cdesc.
		// forbidden: createitem/additem
		global $CONF,$action;
		global $HTTP_POST_VARS,$HTTP_GET_VARS;// $HTTP_REQUEST_VARS does not exist.
		// First, remove all $_COOKIE values from $_REQUEST
		// This must be OK because cookieVar() is not checked in requestVar() function for php 4.0.6
		if (isset($_REQUEST) and isset($_COOKIE)) {
			foreach($_COOKIE as $key=>$value) {
				if (isset($_REQUEST[$key])) unset($_REQUEST[$key]);
			}
		}
		$accept=array();
		switch(strtolower($action)){
		case 'createitem':
		case 'itemedit':
		case 'additem':
		case 'itemupdate':
			exit($this->_error('_ERROR_DISALLOWED'));
		case 'commentupdate':
			$accept[]='body';
			break;
		default: break;
		}
		if (isset($_REQUEST)) $this->_valueCheck($_POST,$_GET,$_REQUEST,$accept);
		else {
			// In requestVar() function, postVar() is first checked.
			// In forrowing array_merge function, $HTTP_GET_VARS values are overrided by $HTTP_POST_VARS.
			$request=array_merge($HTTP_GET_VARS,$HTTP_POST_VARS);
			$this->_valueCheck($HTTP_POST_VARS,$HTTP_GET_VARS,$request,$accept);
		}
	}
	function _valueCheck(&$post,&$get,&$request,&$accept,$depth=0){
		foreach($request as $key=>$value){
			if (is_array($value)) {
				//if ($depth==0 && $key=='plugoption') continue;
				$p=$g=false;
				if (isset($post[$key])) $p=&$post[$key];
				if (isset($get[$key])) $g=&$get[$key];
				$this->_valueCheck($p,$g,$request[$key],$accept,$depth+1);
				continue;
			}
			if (!preg_match('/[\x00<>\'"\\\\]/',$value)) continue;
			switch($depth){
			case 0:
				if (in_array($key,$accept)) continue;
				if (quickQuery('SELECT COUNT(*) as result FROM '.
					sql_table('blog').' as b, '.
					sql_table('category').' as c'.
					' WHERE b.bname="'.addslashes($value).'"'.
					' OR b.bdesc="'.addslashes($value).'"'.
					' OR c.cname="'.addslashes($value).'"'.
					' OR c.cdesc="'.addslashes($value).'"'.
					' LIMIT 1')) break;
				exit($this->_error('_ERROR_DISALLOWED',": '$key'=>'$value'"));
			case 2:
				if (quickQuery('SELECT COUNT(*) as result FROM '.
					sql_table('plugin_option').' as o, '.
					sql_table('plugin_option_desc').' as d'.
					' WHERE (d.odef="'.addslashes($value).'" AND NOT (d.ocontext="global"))'.
					' OR (o.ovalue="'.addslashes($value).'" AND o.ocontextid>0)'.
					' LIMIT 1')) break;
			default:
				exit($this->_error('_ERROR_DISALLOWED',": '$key'=>'$value'"));
			}
		}
	}
	function _error($msg,$msg2){
		// Must exit at the end of this function. Do not return.
		global $DIR_LANG,$CONF;
		include_once($DIR_LANG . ereg_replace( '[\\|/]', '', getLanguageName()) . '.php');
		if (defined($msg)) eval('$msg='.$msg.';');
		redirect($CONF['IndexURL'].'?special=error&errormessage='.urlencode($msg.htmlspecialchars($msg2,ENT_QUOTES)));
		exit;
	}
}
// To use NP_PageSwitch for the member list, replace the $blog object.
if (class_exists('BLOG')) {// This statement is requred when the member registration
class NP_subSilver_BLOG_MEMBER extends BLOG {
	var $np_subsilver;
	function NP_subSilver_BLOG_MEMBER($id,&$np_subsilver){
		$this->np_subsilver=&$np_subsilver;
		return $this->BLOG($id);
	}
	function getSqlBlog($extraQuery, $mode=''){
		global $startpos;
		if ($search=getVar('search_author')){
			$where= ' WHERE mrealname LIKE "%'.addslashes($search).'%" ';
		} else {
			$where=' WHERE 1 ';
		}
		if ($mode) {
			$select='SELECT COUNT(*) as result ';
			$limit=' ';
			$order=' ';
		} else {
			$select='SELECT * ';
			$limit=' LIMIT '.(int)$this->np_subsilver->limit.' OFFSET '.(int)$startpos.' ';
			switch(getVar('sort')){
			case 'name_desc':
				$order=' ORDER BY mrealname DESC ';
				break;
			case 'name_asc':
				$order=' ORDER BY mrealname ASC ';
				break;
			case 'id_desc':
				$order=' ORDER BY mnumber DESC ';
				break;
			case 'id_asc':
			default:
				$order=' ORDER BY mnumber ASC ';
			}
		}
		return $select.' FROM '.sql_table('member').$where.$extraQuery.$order.$limit;
	}
}
}
?>