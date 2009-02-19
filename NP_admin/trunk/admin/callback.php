<?php
class NP_admin_callback {
	var $main,$properties,$rowdata;
	function NP_admin_callback(&$obj){
		$this->main=&$obj;
		$this->properties=&$obj->properties;
		$this->rowdata=&$obj->rowdata;
	}
	function parse_callback($skinType,$eventName, $type=''){
		global $manager;
		$data=call_user_func(array(&$this,"event_$eventName"),$type);
		// If there is return value, raise event here.
		// Otherwise, the event was raised in called method.
		if ($data) $manager->notify($eventName, $data);
	}
	function event_BlogSettingsFormExtras(){
		global $blog;
		return array('blog' => &$blog);
	}
	function event_MemberSettingsFormExtras(){
		// template var.
		$mem = MEMBER::createFromID($this->rowdata['mnumber']);
		return array('member' => &$mem);
	}
	function event_PreAddItemForm(){
		// When this happens, global $blog object must exist.
		global $blog,$manager;
		$contents=array('title'=>'','body'=>'','more'=>'');
		$manager->notify('PreAddItemForm', array('contents' => &$contents, 'blog' => &$blog));
		// Set the rowdata.
		$this->rowdata=$contents;
		return false;
	}
	function event_PrepareItemForEdit(){
		global $member, $manager, $blog;
		$itemid = intRequestVar('itemid');
		// only allow if user is allowed to alter item
		if (!$member->canAlterItem($itemid)) exit(_ERROR_DISALLOWED);
		
		$item =$manager->getItem($itemid,1,1);
		$blog =$manager->getBlog(getBlogIDFromItemID($itemid));
		
		$manager->notify('PrepareItemForEdit', array('item' => &$item));
		// Set the rowdata (note that $item is an array).
		$this->rowdata=$item;
		return false;
	}
	function event_AddItemFormExtras(){
		global $blog;
		return array('blog' => &$blog);
	}
	function event_EditItemFormExtras(){
		// When this happens, global $blog object must exist.
		// $this->rowdata must also exist.
		global $blog;
		return array('blog'=>&$blog, 'variables'=>$this->rowdata, 'itemid'=>$this->rowdata['itemid']);
	}
}