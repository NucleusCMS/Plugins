<?php 
include_once(dirname(__FILE__).'/if.php');
class NP_subSilver_COMMENTS extends NP_subSilver_if { 
	var $plug;
	function NP_subSilver_COMMENTS(&$plug){
		$this->plug=&$plug;
		foreach($plug as $key=>$value) $this->$key=&$plug->$key;
	}
/* Reply to the topic (see posting.php for creating new topic) */
	function event_PostAddComment(&$data){
		global $manager;
		$itemid=quickQuery('SELECT citem as result FROM '.sql_table('comment').' WHERE cnumber='.(int)$data['commentid']);
		$blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
		$timestamp=$blog->getCorrectTime();
		// List up all members.
		$members=',';
		$res=sql_query('SELECT mnumber FROM '.sql_table('member'));
		while($row=mysql_fetch_row($res)) $members.=$row[0].',';
		mysql_free_result($res);
		// Update data in SQL table
		$itemid=quickQuery('SELECT citem as result FROM '.sql_table('comment').' WHERE cnumber='.(int)$data['commentid']);
		$replies=-1+(int)quickQuery('SELECT COUNT(*) as result FROM '.sql_table('comment').' WHERE citem='.(int)$itemid);
		$this->plug->sql_query('UPDATE','SET lastcommentid='.(int)$data['commentid'].','.
			' time='.mysqldate($timestamp).','.
			' unread="'.addslashes($members).'",'.
			' readip=",,,,,,",'.
			' replynum='.(int)$replies.
			' WHERE itemid='.(int)$itemid);
		// redirect to the new reply
		$url=createItemLink($itemid, array('cid'=>(int)$data['commentid'])).'#cid'.(int)$data['commentid'];
		redirect(str_replace('&amp;','&',$url));
		exit;
	}
/* Following events are used when the comment is modified */
	var $deleteitemid=false;
	function event_PreDeleteComment(&$data){// If there is just one comment for item, register the itemid
		$itemid=quickQuery('SELECT citem as result FROM '.sql_table('comment').' WHERE cnumber='.(int)$data['commentid']);
		if (1==quickQuery('SELECT COUNT(*) as result FROM '.sql_table('comment').' WHERE citem='.(int)$itemid)) $this->deleteitemid=$itemid;
	}
	function event_PostDeleteComment(&$data){// If there were just one comment for item. Let's delete the item.
		if (!$this->deleteitemid) return;
		sql_query('DELETE FROM '.sql_table('item').' WHERE inumber='.(int)$this->deleteitemid);
		$this->plug->sql_query('DELETE FROM','WHERE itemid='.(int)$this->deleteitemid);
	}
	function event_PrepareCommentForEdit(&$data){// Add <title>xxxx</title> if first comment in item.
		if ($itemid=$this->plug->quickQuery('SELECT itemid as result FROM','WHERE firstcommentid='.(int)$data['comment']['commentid'])) {
			$title=quickQuery('SELECT ititle as result FROM '.sql_table('item').' WHERE inumber='.(int)$itemid);
			$data['comment']['body']='&lt;title&gt;'.htmlspecialchars($title)."&lt;/title&gt;\n".$data['comment']['body'];
		}
	}
	function event_PreUpdateComment(&$data){// Pickup <title>xxxx</title> and change the title of item.
		$itemid=$this->plug->quickQuery('SELECT itemid as result FROM','WHERE firstcommentid='.intRequestVar('commentid'));
		if ($itemid && preg_match('!&lt;title&gt;([^\r\n]*)&lt;/title&gt;<br />!',$data['body'],$matches)) {
			$data['body']=trim(str_replace($matches[0],'',$data['body']));
			sql_query('UPDATE '.sql_table('item').' SET ititle="'.addslashes($matches[1]).'" WHERE inumber='.(int)$itemid);
		}// Note that the $data['body'] has been sanitized before comming to this event.
	}
	function doTemplateCommentsVar(&$item, &$comment,$type) {
		$type=strtolower($type);
		if ($this->ob_ok && !in_array($type,array('if','ifnot','endif','else','elseif','elseifnot'))) return; // Just return if ob_ok.
		global $catid;
		$args=func_get_args();
		array_shift($args);
		array_shift($args);
		array_shift($args);
		switch($type){
		case 'adminurl':
			global $CONF;
			echo htmlspecialchars($CONF['AdminURL'],ENT_QUOTES);
			return;
		case 'if':
		case 'ifnot':
		case 'else':
		case 'elseif':
		case 'elseifnot':
		case 'endif':
			return $this->_if_comment($type,$item,$comment,$args);
		default:
			break;
		}
	}
}
?>