<?php
include_once(dirname(__FILE__).'/if.php');
class NP_subSilver_TEMPLATE extends NP_subSilver_if { 
	var $plug;
	function NP_subSilver_TEMPLATE(&$plug){
		$this->plug=&$plug;
		foreach($plug as $key=>$value) $this->$key=&$plug->$key;
	}
	var $tabledata=array();
	function &_getData($itemid){
		// Get all data for the item in SQL table of this plugin
		if (!isset($this->tabledata[$itemid])) {
			$this->tabledata[$itemid]=mysql_fetch_assoc($res=$this->plug->sql_query('SELECT * FROM','WHERE itemid='.(int)$itemid.' LIMIT 1'));
			mysql_free_result($res);
		}
		return $this->tabledata[$itemid];
	}
	var $memberdata=array();
	function &_getMember($mid){
		// Get all data for the member
		if (!isset($this->memberdata[$mid])) {
			$this->memberdata[$mid]=mysql_fetch_assoc($res=sql_query('SELECT * FROM '.sql_table('member').' WHERE mnumber='.(int)$mid.' LIMIT 1'));
			mysql_free_result($res);
		}
		return $this->memberdata[$mid];
	}
	var $commentdata=array();
	function &_getComment($cid){
		// Get all data for the comment
		if (!isset($this->commentdata[$cid])) {
			$this->commentdata[$cid]=mysql_fetch_assoc($res=sql_query('SELECT * FROM '.sql_table('comment').' WHERE cnumber='.(int)$cid.' LIMIT 1'));
			mysql_free_result($res);
		}
		return $this->commentdata[$cid];
	}
	function _getCidFromItem($itemid){
		$row=$this->_getdata($itemid);
		return $row['lastcommentid'];
	}
	function _getAuthorFromItem($itemid){
		$auth=array();
		$row=$this->_getdata($itemid);
		if ($row['authorid']) {
			$memm=$this->_getMember($row['authorid']);
			$auth['authorname']=$memm['mrealname'];
			$auth['authorlink']=createMemberLink($row['authorid']);
		} else {
			$comm=$this->_getComment($row['firstcommentid']);
			$auth['authorname']=$comm['cuser'];
			$auth['authorlink']='#'._EDITC_NONMEMBER;
		}
		return $auth;
	}
	function _itemicon(&$item,&$args){
		global $blog,$member;
		$mid=$member->isLoggedIn()?$member->getId():0;
		// 0:new, 1:new_hot, 2:new_locked, 3:read, 4:read_hot, 5: read_locked, 6:info, 7:sticky
		$data=$this->_getData($item->itemid);
		if ($blog->commentsEnabled()) $closed=$item->closed;
		else $closed=true;
		$hot=$this->plug->popularItems($item->itemid);// Popular topics
		$infosticky=$this->plug->infoSticky();
		$new=strstr($data['unread'],",$mid,");
		switch(strtolower(@$infosticky[$item->itemid])){
		case 'info':   $i=6; break;
		case 'sticky': $i=7; break;
		default:
			$i=$new?0:3;
			if ($closed) $i=$i+2;
			elseif ($hot) $i++;
		}
		echo htmlspecialchars(trim($args[$i]),ENT_QUOTES);
	}
	function doTemplateVar(&$item,$type) {
		$type=strtolower($type);
		if ($this->ob_ok && !in_array($type,array('if','ifnot','endif','else','elseif','elseifnot'))) return; // Just return if ob_ok.
		global $catid;
		$args=func_get_args();
		array_shift($args);
		array_shift($args);
		switch($type){
		case 'readnum':
		case 'replynum':
			$row=$this->_getdata($item->itemid);
			echo htmlspecialchars($row[$type],ENT_QUOTES);
			break;
		case 'authorname':
		case 'authorlink':
			$data=$this->_getAuthorFromItem($item->itemid);
			echo htmlspecialchars($data[$type],ENT_QUOTES);
			break;
		case 'lastreplyby':
		case 'lastreplyauthorlink':
		case 'lastreplylink':
			$data=array();
			$cid=$this->_getCidFromItem($item->itemid);
			$data['lastreplylink']=createItemLink($item->itemid,array($this->commentquery=>(int)$cid)).'#'.$this->commentquery.(int)$cid;
			$comm=$this->_getComment($cid);
			if ($comm['cmember']) {
				$memm=$this->_getMember($comm['cmember']);
				$data['lastreplyby']=$memm['mrealname'];
				$data['lastreplyauthorlink']=createMemberLink($comm['cmember']);
			} else {
				$data['lastreplyby']=$comm['cuser'];
				$data['lastreplyauthorlink']='#'._EDITC_NONMEMBER;
			}
			echo str_replace('&amp;amp;','&amp;',htmlspecialchars($data[$type],ENT_QUOTES));
			break;
		case 'itemicon':
			return $this->_itemicon($item,$args);
		case 'if':
		case 'ifnot':
		case 'else':
		case 'elseif':
		case 'elseifnot':
		case 'endif':
			return $this->_if($type,$item,$args);
		default:
			break;
		}
	}
}
?>