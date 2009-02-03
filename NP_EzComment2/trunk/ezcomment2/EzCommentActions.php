<?php

class EzCommentFormActions extends ACTIONS
{

var $commentItem;

var $loginMember;

	function EzCommentFormActions(&$item, $formdata, $member)
	{
		$this->ACTIONS('item');
		$this->commentItem =& $item;
		$this->formdata    =  $formdata;
		$this->loginMember =  $member;
	}

	function getAllowedActions()
	{
		return array(
					 'text',
					 'self',
					 'formdata',
					 'callback',
					 'errordiv',
					 'ticket',
					 'itemid',
					 'itemlink',
					 'itemtitle',
					 'membername',
					 'memberurl',
					);
	}

	function parse_itemid() {
		echo $this->commentItem->itemid;
	}
	
	function parse_itemlink($linktext = '') {
		global $itemid;
		$this->_itemlink($this->commentItem->itemid, $linktext);
	}

	function parse_itemtitle($format = '') {
		switch ($format) {
			case 'xml':
				echo stringToXML ($this->commentItem->itemtitle);
				break;
			case 'attribute':
				echo stringToAttribute ($this->commentItem->itemtitle);
				break;
			case 'raw':
				echo $this->commentItem->itemtitle;
				break;
			default:
				echo $this->_hsc(strip_tags($this->commentItem->itemtitle));
				break;
		}
	}

	function parse_membername($mode='')
	{
		if ($mode == 'realname') {
			echo $this->_hsc($this->loginMember->getRealName());
		} else {
			echo $this->_hsc($this->loginMember->getDisplayName());
		}
	}

	function parse_memberurl()
	{
		echo $this->_hsc($this->loginMember->getURL());
	}

	function _hsc($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, _CHARSET);
	}

}

class EzCommentActions extends COMMENTACTIONS
{

var $viewnum;

var $postnum;

	function EzCommentActions(&$comments)
	{
		$this->COMMENTACTIONS($comments);
	}

	function getAllowedActions()
	{
		$allowedActions   = $this->getDefinedActions();
		$allowedActions[] = 'viewnum';
		$allowedActions[] = 'postnum';
		$allowedActions[] = 'viewparpost';
		return $allowedActions;
	}

	function setPostnum($postnum)
	{
		$this->postnum = $postnum;
	}

	function setViewnum($viewnum)
	{
		$this->viewnum = $viewnum;
	}

	function parse_viewnum()
	{
		echo intval($this->viewnum);
	}

	function parse_postnum()
	{
		echo intval($this->postnum);
	}

	function parse_viewparpost()
	{
		echo intval($this->viewnum) . ' ';
		if ($this->postnum > $this->viewnum) {
			echo '/ ' . $this->postnum . ' ';
		}
		$this->parse_commentword();
	}

}

