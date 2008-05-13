<?php
class NP_subSilver_if { 
	var $cecomm=array();
	var $comment;
	function _canEditComment(){
		global $member;
		$cid=$this->comment['commentid'];
		if (!$member->isLoggedIn()) return false;
		elseif (!$member->canLogin()) return false;
		elseif ($member->isAdmin()) return true;
		if (!isset($this->cecomm[$cid])) {
			$this->cecomm[$cid]=$member->canAlterComment($cid);
		}
		return $this->cecomm[$cid];
	}
	function _if_comment($mode,&$item,&$comment,&$args){
		$this->comment=&$comment;
		return $this->_if($mode,$item,$args);
	}
	var $ob_ok=false;
	var $parse_done=true;
	var $if_stack=array();
	function _if($mode,&$item,&$args){
		if ($mode=='if' || $mode=='ifnot') {
			array_push($this->if_stack,array($this->ob_ok,$this->parse_done));
			$this->ob_ok=false;
			$this->parse_done=true;
		}
		switch($mode){
		case 'endif':
			if ($this->ob_ok) ob_end_clean();
			list($this->ob_ok,$this->parse_done)=array_pop($this->if_stack);
			return;
		case 'else':
			if ($this->ob_ok) {
				ob_end_clean();
				$this->ob_ok=false;
			} else $this->ob_ok=ob_start();
			return;
		case 'elseif':
		case 'elseifnot':
			if ($this->ob_ok) {
				ob_end_clean();
				$this->ob_ok=false;
			} else if ($this->parse_done) return;
		default:
			break;
		}
		// Several conditions follow
		$type=array_shift($args);
		$cond=$this->_ifCond($type,$item);
		while(count($args)){
			$andor=strtolower(array_shift($args));
			$type=array_shift($args);
			switch($andor){
			case 'and':
				$cond=$cond && $this->_ifCond($type,$item);
				break;
			case 'or':
				$cond=$cond || $this->_ifCond($type,$item);
				break;
			case 'andnot':
				$cond=$cond && !$this->_ifCond($type,$item);
				break;
			case 'ornot':
				$cond=$cond || !$this->_ifCond($type,$item);
				break;
			default:
				exit ('NP_subSilver syntax error: '.htmlspecialchars($andor,ENT_QUOTES));
			}
		}
		// Make decision.
		switch($mode){
		case 'if':
		case 'elseif':
			if (!$cond) $this->ob_ok=ob_start();
			break;
		case 'ifnot':
		case 'elseifnot':
			if ($cond) $this->ob_ok=ob_start();
		default:
			break;
		}
		$this->parse_done=!$this->ob_ok;
	}
	function _ifCond($type,&$item) {
		switch(strtolower($type)){
		case 'sticky':
			$infosticky=$this->plug->infoSticky();
			return (bool)@$infosticky[$item->itemid];
		case 'showstickies':
			return (bool)$this->plug->showstickies;
		case 'new':
			global $member;
			if (!$member->isLoggedIn()) return false;
			$data=$this->_getData($item->itemid);
			return (bool)strstr($data['unread'],','.$member->getId().',');
		case 'search':
			global $query;
			return $query || requestVar('subsilver_search')=='yes';
		case 'caneditcomment':
			return $this->_canEditComment();
		case 'category':
			global $catid;
			return (bool)$catid;
		case 'search':
			global $query;
			return (bool)$query;
		case 'authorismember':
			if (isset($this->comment)) return (bool)$this->comment['userid'];
			else return (bool)$this->plug->quickQuery('SELECT authorid as result FROM','WHERE itemid='.(int)$item->itemid);
		case 'hasurl':
			if (!isset($this->comment)) exit ('if: Error');
			if ($authorid=$this->comment['memberid']) {
				$memberclass=&$this->plug->loadClass('member');
				$memberdata=&$memberclass->getData($authorid);
				return (bool)$memberdata['rawurl'];
			} else {
				return preg_match('!^(http|https)://[^/]+!',$this->comment['userid']);
			}
		default:
			exit ('NP_subSilver syntax error: '.htmlspecialchars($type,ENT_QUOTES));
		}
	}
}
?>