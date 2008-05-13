<?php 
class NP_subSilver_posting { 
	var $plug;
	function NP_subSilver_posting(&$plug){
		$this->plug=&$plug;
		foreach($plug as $key=>$value) $this->$key=&$plug->$key;
	}
	var $nowposting=false;
	function posting($event,&$data,$p1='') {
		$this->nowposting=true;
		switch($event){
		case 'SpamCheck':
			// Unset global $itemid for parsing category skin.
			global $itemid;
			$itemid=0;
			// Temporaliry accept the comment for current item for posting.
			// This setting won't be saved into MySQL table.
			global $manager;
			$item=&$manager->getItem(postVar('itemid'),false,false);
			$item['closed']=0;
			return;
		case 'ValidateForm':
			// Require title
			if (!postVar('title')) $data['error']=_LISTS_TITLE.':'._ERROR_NOEMPTYITEMS;
			return;
		case 'PostAddComment':
			$newblogitemid=$p1;
			// Create new item.
			$cid=$data['commentid'];
			// Find the administrator
			$adminid=(int)quickQuery('SELECT mnumber as result FROM '.sql_table('member').' WHERE madmin=1 ORDER BY mnumber ASC LIMIT 1');
			global $member;
			$mid=(0<$member->getID())?$member->getID():0;
			if (phpversion() >= '5.0.0') eval('$mcopy = clone $member;');
			else $mcopy=$member;

			$member=MEMBER::createFromID($adminid);

			// Create new item
			if ($newblogitemid) $result=array('status'=>'added','itemid'=>$newblogitemid);
			else $result=ITEM::createFromRequest();

			// Return to original member object
			unset($member);
			if (phpversion() >= '5.0.0') eval('$member = clone $mcopy;');
			else $member=$mcopy;

			// All done. Redirect to item URL
			switch($result['status']){
			case 'added':
				// Remove tags in item.
				$query = 'UPDATE '.sql_table('item').
					' SET ititle="'.addslashes(htmlspecialchars(postVar('title'),ENT_QUOTES)).'"'.
					' , ibody="'.addslashes(htmlspecialchars(postVar('body'),ENT_QUOTES)).'"'.
					' , imore=""'.
					' WHERE inumber=' . (int)$result['itemid'];
				if (!$newblogitemid) sql_query($query);
				// Move the comment to new item
				$query = 'UPDATE '.sql_table('comment').
					' SET citem='.(int)$result['itemid'].
					' WHERE cnumber=' . (int)$cid;
				sql_query($query);
				// List up all members.
				$members=',';
				$res=sql_query('SELECT mnumber FROM '.sql_table('member'));
				while($row=mysql_fetch_row($res)) $members.=$row[0].',';
				mysql_free_result($res);
				// Add data into sql table
				$this->plug->sql_query('DELETE FROM','WHERE itemid='.(int)$result['itemid']);
				$this->plug->sql_query('INSERT INTO','SET'.
					' itemid='.(int)$result['itemid'].
					', authorid='.(int)$mid.
					', firstcommentid='.(int)$cid.
					', lastcommentid='.(int)$cid.
					', unread="'.addslashes($members).'"'.
					', time='.mysqldate($data['comment']['timestamp']).
					', since='.mysqldate($data['comment']['timestamp']));
				// Redirect 
				redirect(createItemLink($result['itemid']));
				exit;
			default: // failed
				$query = 'DELETE FROM '.sql_table('comment').' WHERE cnumber=' . (int)$cid;
				sql_query($query);
				exit('Sorry, an error occured.');
			}
		default:
			return;
		}
	}
	// PostAddItem event occurs when a new blog is created.
	function event_PostAddItem(&$data){
		// Just return if AddItem happens because of a posting of new topic
		if ($this->nowposting) return;
		global $member;
		$itemid=$data['itemid'];
		$row=mysql_fetch_assoc($res=sql_query('SELECT iauthor, ibody, itime FROM '.sql_table('item').' WHERE inumber='.(int)$itemid));
		mysql_free_result($res);
		$comment=array();
		
		$name      = addslashes($comment['user']='');
		$url       = addslashes($comment['userid']='');
		$email     = addslashes($comment['email']='');
		$body      = addslashes($comment['body']=htmlspecialchars($row['ibody'],ENT_QUOTES));
		$host      = addslashes($comment['host']=serverVar('HTTP_HOST'));
		$ip        = addslashes($comment['ip']=serverVar('REMOTE_ADDR'));
		$memberid  = intval($comment['memberid']=$row['iauthor']);
		$timestamp = date('Y-m-d H:i:s', ($comment['timestamp']=strtotime($row['itime'])));
		$itemid    = (int)$itemid;

		$query = 'INSERT INTO '.sql_table('comment').' (CUSER, CMAIL, CEMAIL, CMEMBER, CBODY, CITEM, CTIME, CHOST, CIP, CBLOG) '
			   . "VALUES ('$name', '$url', '$email', $memberid, '$body', $itemid, '$timestamp', '$host', '$ip', '$blogid')";

		sql_query($query);
		$commentid = mysql_insert_id();
		$data=array('comment'=>&$comment,'commentid'=>$commentid);
		$this->posting('PostAddComment',$data,$itemid);
	}
	// WHen  new category is created.
	function event_PostAddCategory(&$data){
		global $member;
		$blog=&$data['blog'];
		$blog->additem($data['catid'],'First Item','You can modify this topic.','',$blog->getId(), $member->getId(),$blog->getCorrectTime(),0,0,0);

	}
}
?>