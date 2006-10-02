<?

class NP_ArchiveListEX extends NucleusPlugin {

	// name of plugin
	function getName() {
		return 'ArchiveListEX'; 
	}
	
	// author of plugin
	function getAuthor()  { 
		return 'nakahara21'; 
	}
	
	// an URL to the plugin website
	function getURL() 
	{
		return 'http://xx.nakahara21.net/'; 
	}
	
	// version of the plugin
	function getVersion() {
		return '0.6'; 
	}
	
	// a description to be shown on the installed plugins listing
	function getDescription() { 
		return 'Show all item title on each archive list. <br />[Required] template: name of the template to use <br />[Optional] mode: [month]shows an entry for each month , [day]shows an entry for each day  <br />[Optional] limit:  limits the amount of links shown (e.g. if you only want to show links to the past 3 months)<br /><br />Usage1: &lt;%ArchiveListEX(default)%&gt; or &lt;%ArchiveListEX(default,month)%&gt; instead of &lt;%archivelist(default)%&gt;,<br />Usage2:  &lt;%ArchiveListEX(default,day,5)%&gt; instead of &lt;%archivedaylist(default,5)%&gt;,<br />Usage3:  &lt;%ArchiveListEX(default,5)%&gt;';
	}

   function doSkinVar($skinType, $template = 'default', $mode = 'month', $limit = 0) { 
		global $manager, $blog, $CONF, $catid, $itemid; 

		$params = func_get_args();
		if ($params[1]){ $template = $params[1]; }
		if ($params[2]){
			if ($params[2] == 'month' | $params[2] == 'day'){ $mode = $params[2]; }
			else{ $limit = intval($params[2]); }
		}
		if ($params[3]){ $limit = intval($params[3]); }

	if ($blog) { 
		$b =& $blog; 
	} else { 
		$b =& $manager->getBlog($CONF['DefaultBlog']); 
	} 
	$blogid = $b->getID();

		if ($catid) {
			$this->linkparams = array('catid' => $catid);
		}

switch($skinType) { 
	case 'archivelist': 

//**********************************************
if($_REQUEST['mode'] == "remarks"){

		$numberOfWritebacks   = 50; // defaults to 50

		// select
        $query = "SELECT c.cnumber, c.cuser, c.cbody, c.citem, c.cmember, c.ctime ,UNIX_TIMESTAMP(c.ctime) as ctimest ,i.inumber, i.ititle 
        FROM nucleus_comment c ,nucleus_item i 
        WHERE c.citem = i.inumber 
        ORDER by ctime DESC 
        LIMIT 0,".$numberOfWritebacks;

        $comments = mysql_query($query);
//             echo ' <ul class="nobullets"> ';

        while($row = mysql_fetch_object($comments)) {
             $cid  = $row->cnumber;
             $ititle  = htmlspecialchars(strip_tags($row->ititle));
             $ct  = $row->ctimest;
             $ctst  = date("Y-m-d H:i",$ct);
             $ctext  = $row->cbody;
//             $ctext  = strip_tags($text);
//             $ctext = substr($text, 0, $numberOfCharacters);
//             $ctext = mb_substr($ctext, 0, -1);

             if (!$row->cmember) $myname = $row->cuser;
             else {
                   $mem = new MEMBER;
                   $mem->readFromID(intval($row->cmember));
                   $myname = $mem->getDisplayName();
             }
             $itemid= $row->citem;
             $itemlink = createItemLink($row->citem, '');
             $l_comments =  "<li class=itembody>■ <span class=\"iteminfo\"><a href=\"".$b->getURL().$itemlink."#c".$cid."\">『".$ititle."』 へのコメント</a></span><br />".$ctext."<br /><span class=\"iteminfo\">".$myname." posted on $ctst</span></li>" ;
//             echo $l_comments;
             
				if(!$arr_res){$arr_res = array();}
//				array_push($arr_res,array($ct => $l_comments));
             $arr_res[$ct] = $l_comments;
             
         }


//=========================

        $query = "SELECT t.title, t.excerpt, t.tb_id, t.blog_name, t.timestamp ,UNIX_TIMESTAMP(t.timestamp) as ttimest ,t.url ,i.inumber, i.ititle 
        FROM nucleus_plugin_tb t ,nucleus_item i 
        WHERE t.tb_id = i.inumber 
        ORDER by timestamp DESC 
        LIMIT 0,".$numberOfWritebacks;

        $comments = mysql_query($query);

        while($row = mysql_fetch_object($comments)) {
             $text  = $row->excerpt;
//             $text  = strip_tags($text);
//             $ctext = mb_substr($text, 0, $numberOfCharacters);

             $title = $row->title;
//             $ctitle = substr($title, 0, $numberOfTitleCharacters+1);
//             $ctitle = mb_substr($ctitle, 0, -1);

             $blogname = $row->blog_name;
             $tbtime = $row->ttimest;
             $ititle  = htmlspecialchars(strip_tags($row->ititle));
             $url = $row->url;
             $ttst  = date("Y-m-d H:i",$tbtime);

             $itemlink = createItemLink($row->tb_id, '');
//             echo "<li><a href=\"".$b->getURL().$itemlink."#trackback\">■".$tbtime.":";
//             echo $blogname."から";
//             echo $ctitle.$ctext;
//             echo "....</a></li>";
             
             $l_tbs = "<li class=itembody>◆ <span class=\"iteminfo\"><a href=\"".$b->getURL().$itemlink."#trackback\">『".$ititle."』へのトラックバック</a></span><br />## ".$title." ##<br />".$text."<br /><span class=\"iteminfo\">『<a href=\"$url\">{$blogname}</a>』 pinged on ".$ttst."</span></li>";

//				if(!$arr_res){$arr_res = array();}
//				array_push($arr_res,$l_tbs);
             $arr_res[$tbtime] = $l_tbs;



         }
//=========================



krsort ($arr_res);
$output = array_slice ($arr_res, 0, $numberOfWritebacks);
//             print_r($output);

		echo '<h3>コメントとトラックバック 最新'.$numberOfWritebacks.'件</h3>';
		echo ' <ul class="nobullets"> ';

		foreach($output as $key => $value){
			echo $value."\n";
		}
		echo " </ul> ";

//**********************************************
}else{

		$template = TEMPLATE::read($template);
		$data['blogid'] = $blogid;
//===================================
		echo TEMPLATE::fill($template['ARCHIVELIST_HEADER'],$data);
//===================================
		echo '<a href="http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'">HOME</a> ';
		if($catid){
			$catName = $blog->getCategoryName($catid);	//カテゴリの名前をget
//			$catName = $blog->getCategoryDesc($catid);	//カテゴリの説明をget
			$archivealllistlink = createArchiveListLink($blogid,"");
			echo ' > <a href="'.$archivealllistlink.'">Archive List</a>';
			echo ' > Category : <u> '.$catName.' </u> ';
		}else{
//			echo 'Category : 選択していません';
			echo ' > Archive List';
			echo '';
		}
//===================================
		echo TEMPLATE::fill($template['ARCHIVELIST_FOOTER'],$data);
//===================================
		echo TEMPLATE::fill($template['ARCHIVELIST_HEADER'],$data);
		$now = time();
		$query = 'SELECT UNIX_TIMESTAMP(itime) as itime, SUBSTRING(itime,1,4) AS Year, SUBSTRING(itime,6,2) AS Month, SUBSTRING(itime,9,2) as Day FROM nucleus_item'
		. ' WHERE iblog=' . $blogid
		. ' and UNIX_TIMESTAMP(itime)<=' . $now	// don't show future items!
		. ' and idraft=0'; // don't show draft items
		
		if ($catid)
			$query .= ' and icat=' . intval($catid);
		
		$query .= ' GROUP BY Year, Month';
		if ($mode == 'day')
			$query .= ', Day';
		
			
//		$query .= ' ORDER BY itime ASC';	//アーカイブリスト昇順
		$query .= ' ORDER BY itime DESC';	//アーカイブリスト降順
		
		if ($limit > 0) 
			$query .= ' LIMIT ' . $limit;
		
		$res = sql_query($query);

		while ($current = mysql_fetch_object($res)) {
			if ($mode == 'day') {
				$archivedate = date('Y-m-d',$current->itime);
				$archive['day'] = date('d',$current->itime);
			} else {
				$archivedate = date('Y-m',$current->itime);			
			}
			$data['month'] = date('m',$current->itime);
			$data['year'] = date('Y',$current->itime);
			$data['archivelink'] = createArchiveLink($blogid,$archivedate,$this->linkparams);

			$temp = TEMPLATE::fill($template['ARCHIVELIST_LISTITEM'],$data);
			echo strftime($temp,$current->itime);
			
	//======================================================		
	echo '<ul>';
		$adquery = 'SELECT inumber, ititle, icat FROM nucleus_item'
		. ' WHERE iblog=' . $blogid
		. ' and UNIX_TIMESTAMP(itime)<=' . $now	// don't show future items!
		. ' and idraft=0' // don't show draft items
		. ' and SUBSTRING(itime,1,4)=' . $data['year']	// year
		. ' and SUBSTRING(itime,6,2)=' . $data['month'];	// month
		if ($mode == 'day')
			$adquery .= ' and SUBSTRING(itime,9,2)=' . $archive['day']; //day
		
		if ($catid)
			$adquery .= ' and icat=' . intval($catid);
		
//		$adquery .= ' ORDER BY itime ASC';	//タイトル一覧昇順
		$adquery .= ' ORDER BY itime DESC';	//タイトル一覧降順
		
//		if ($limit > 0) 
//			$adquery .= ' LIMIT ' . $limit;
		
		$adres = sql_query($adquery);
		
		while ($adcurrent = mysql_fetch_object($adres)) {
			$ititle = htmlspecialchars(strip_tags($adcurrent->ititle));
			$inumber = $adcurrent->inumber;
			$itemlink = createItemLink($inumber,$this->linkparams);

			if($catid){
			echo '<li><a href="'.$itemlink.'">'. $ititle . '</a></li>'."\n";
			}else{
			$icatName = $blog->getCategoryName($adcurrent->icat);	//カテゴリの名前をget
//			$icatName = $blog->getCategoryDesc($adcurrent->icat);	//カテゴリの説明をget
			//タイトル一覧に続いてカテゴリ名を表示
//			echo '<li><a href="'.$itemlink.'">'. $ititle . ' <small>::' . $icatName .'</small></a></li>'."\n";
			//タイトル一覧にマウスをあわせるとカテゴリ名をフロート表示
			echo '<li><a href="'.$itemlink.'" title="Category:'.$icatName.'">'. $ititle . '</a></li>'."\n";
			}
		}
		mysql_free_result($adres);
	echo '</ul>';
	//======================================================
		}
		mysql_free_result($res);
		echo TEMPLATE::fill($template['ARCHIVELIST_FOOTER'],$data);

}

		break;
//===================================
	default: 
//				echo "tttt";
		} 
	}
}
?>