<?php
class NP_subSilver_search {
	var $plug;
	function NP_subSilver_search(&$plug){
		$this->plug=&$plug;
		foreach($plug as $key=>$value) $this->$key=&$plug->$key;
	}
}
class NP_subSilver_BLOG extends BLOG {
	var $np_subsilver;
	function NP_subSilver_BLOG($id,&$np_subsilver){
		$this->np_subsilver=&$np_subsilver;
		return $this->BLOG($id);
	}
	function getSqlSearch($query, $amountMonths = 0, &$highlight, $mode = ''){
		global $blogid;
		switch(getVar('search_type')){
		case 'unanswered':
			$where=' and s.replynum<1 and s.itemid=i.inumber ';
			if ($stime=intGetVar('search_time')) $where.=' and i.itime>' . mysqldate($this->getCorrectTime()-86400*$stime);
			$query=$this->getSqlBlog($where,$mode);
			$query = preg_replace('/^([\s]*)SELECT[\s]([^\'"=]*)[\s]FROM[\s]/i',
				'SELECT $2 FROM '.$this->np_subsilver->sql_query('name').' as s, ',$query);
			if ($blogid) return $query; // $blogid is '' when not from intRequestVar('blogid') (see event_PostAuthentication)
			return preg_replace('/i\.iblog([\s]*)=([\s]*)([0-9]+)([\s]+)and/i','',$query);
		default:
			return $this->subSilver_getSqlSearch($query,$amountMonths,$highlight,$mode);
		}
	}
	function subSilver_getSqlSearch($query, $amountMonths = 0, &$highlight, $mode = '')
	{
		$searchclass =& new SEARCH($query);

		$highlight	  = $searchclass->inclusive;

		// if querystring is empty, return empty string
		if ($searchclass->inclusive == '')
			return '';

		// where to search
		if (!$this->np_subsilver->query_null) {
			srand((double)microtime()*1000000);
			$cbody = md5(uniqid(rand(), true));
			switch(getVar('search_fields')){
			case 'msgonly':
				$where=$searchclass->boolean_sql_where($cbody);
				break;
			case 'titleonly':
				$where=$searchclass->boolean_sql_where('ititle');
				break;
			case 'all':
			default:
				$where=$searchclass->boolean_sql_where('ititle,'.$cbody);
			}
			$where=str_replace('i.'.$cbody,'d.cbody',$where);
		} else $where='1';
		
		
		// Restrict author
		if ($author=getVar('search_author')) {
			switch(getVar('search_author_method')){
			case 'match':
				$authors=array();
				$res=sql_query('SELECT mnumber FROM '.sql_table('member').' WHERE mrealname LIKE "%'.addslashes($author).'%"');
				while($row=mysql_fetch_row($res)) $authors[]=(int)$row[0];
				mysql_free_result($res);
				if (count($authors)) $where.=' and (d.cmember in ('.implode(',',$authors).') or d.cuser LIKE "%'.addslashes($author).'%") ';
				else $where.=' and d.cuser LIKE "%'.addslashes($author).'%" ';
				break;
			case 'exactly':
			default:
				$authors=array();
				$res=sql_query('SELECT mnumber FROM '.sql_table('member').' WHERE mrealname="'.addslashes($author).'"');
				while($row=mysql_fetch_row($res)) $authors[]=(int)$row[0];
				mysql_free_result($res);
				if (count($authors)) $where.=' and (d.cmember in ('.implode(',',$authors).') or d.cuser="'.addslashes($author).'") ';
				else $where.=' and d.cuser="'.addslashes($author).'" ';
				break;
			}
		}
		
		// Restrict time
		if ($stime=intGetVar('search_time')) {
			   $where.=' and i.itime>' . mysqldate($this->getCorrectTime()-86400*$stime);
		}
		
		// sort method
		switch(getVar('sort_by')){
		case 'replies':
			$select='COUNT(d.cbody LIKE "%'.addslashes($query).'%")';
			break;
		case 'title':
			$select='i.ititle';
			break;
		case 'forum':
			$select='i.icat';
			break;
		case 'time':
		default:
			$select='i.itime';
		}

		// get list of blogs to search
		global $blogid; // $blogid is '' when not from intRequestVar('blogid') (see event_PostAuthentication)
		if ($blogid) $selectblogs=' and i.iblog='.(int)$blogid;
		else {
			$selectblogs=' and i.iblog in (';
			$res=sql_query('SELECT bnumber FROM '.sql_table('blog'));
			while($row=mysql_fetch_row($res)) $selectblogs.=(int)$row[0].',';
			mysql_free_result($res);
			$selectblogs=substr($selectblogs,0,-1).')';
		}

		if ($mode == '')
		{
			$query = 'SELECT DISTINCT i.inumber as itemid, i.ititle as title, i.ibody as body, m.mname as author, m.mrealname as authorname, i.itime, i.imore as more, m.mnumber as authorid, m.memail as authormail, m.murl as authorurl, c.cname as category, i.icat as catid, i.iclosed as closed';
			if ($select) $query .= ', '.$select. ' as score ';
		} else {
			$query = 'SELECT COUNT(DISTINCT i.inumber) as result ';
		}

		$query .= ' FROM '.sql_table('comment').' as d, '.sql_table('item').' as i, '.sql_table('member').' as m, '.sql_table('category').' as c'
			   . ' WHERE i.iauthor=m.mnumber'
			   . ' and i.icat=c.catid'
			   . ' and i.idraft=0'	// exclude drafts
			   . $selectblogs
					// don't show future items
			   . ' and i.itime<=' . mysqldate($this->getCorrectTime())
			   . ' and '.$where
			   . ' and i.inumber=d.citem';

		// take into account amount of months to search
		if ($amountMonths > 0)
		{
			$localtime = getdate($this->getCorrectTime());
			$timestamp_start = mktime(0,0,0,$localtime['mon'] - $amountMonths,1,$localtime['year']);
			$query .= ' and i.itime>' . mysqldate($timestamp_start);
		}

		if ($mode == '')
		{
			if ($select)
				$query .= ' GROUP BY i.inumber ORDER BY score '.(getVar('sort_dir')=='ASC'?'ASC ':'DESC ');
			else
				$query .= ' ORDER BY i.itime '.(getVar('sort_dir')=='ASC'?'ASC ':'DESC ');
		}

		return $query;
	}
}
?>