<?php 
class NP_PubMed extends NucleusPlugin { 
	function getName() { return 'NP_PubMed'; }
	function getMinNucleusVersion() { return 330; }
	function getAuthor()  { return 'Katsumi'; }
	function getVersion() { return '0.2.2'; }
	function getURL() {return 'http://japan.nucleuscms.org/wiki/plugins:authors:katsumi';}
	function getDescription() {
		return $this->getName().' plugin<br />'.
			'This plugin uses the query service of "Entrez Programming Utilities".<br />'.
			'For detail, visit: <br />'.
			'http://eutils.ncbi.nlm.nih.gov/entrez/query/static/eutils_help.html';
	}
	function supportsFeature($what) { return ($what=='SqlTablePrefix')?1:0; }
	function getEventList() { return array('PreAddItem','PreUpdateItem','QuickMenu','EditItemFormExtras'); }
	function getTableList() { return array(sql_table('plugin_pubmed_references'), sql_table('plugin_pubmed_manuscripts')); }
	function install(){
		global $member;
		$this->createOption('lastquerytime','hidden option','text','0','access=hidden');
		$this->createOption('lastmanualpmid','hidden option','text','1000000000','access=hidden');
		$this->createOption('droptable','Drop table when uninstall?','yesno','no');
		$this->createOption('email','E-mail address to be sent to PubMed search site (set blank if not use):','text',$member->getEmail());
		sql_query('CREATE TABLE IF NOT EXISTS '.sql_table('plugin_pubmed_references').' ('.
			' id int(11) not null auto_increment,'.
			' manuscriptid int(11) not null default 0,'.
			' itemid int(11) not null default 0,'.
			' PRIMARY KEY id(id),'.
			' UNIQUE KEY manuscriptid(manuscriptid,itemid) '.
			') TYPE=MyISAM;');
		sql_query('CREATE TABLE IF NOT EXISTS '.sql_table('plugin_pubmed_manuscripts').' ('.
			' manuscriptid int(11) not null auto_increment,'.
			' userid int(11) not null default 0,'.
			' manuscriptname varchar(200) not null default "New Manuscript",'.
			' templatename varchar(200) not null default "default",'.
			' sorttext text not null default "",'.
			' PRIMARY KEY manuscriptid(manuscriptid) '.
			') TYPE=MyISAM;');
	}
	function uninstall(){
		if ($this->getOption('droptable')=='yes') {
			foreach($this->getTableList() as $table) sql_query('DROP TABLE IF EXISTS '.$table);
		}
	}
	function init(){
		global $member;
		$manuid=requestVar('manuscriptid');
		if (!$manuid) return;
		if ($member->isAdmin()) return;
		$ok=quickQuery('SELECT COUNT(*) as result FROM '.sql_table('plugin_pubmed_manuscripts').
			' WHERE manuscriptid='.(int)$manuid.
			' AND userid='.(int)$member->getID() );
		if (!$ok) exit("<!-- rem --><b>You aren't allowed to manage this maniscript!</b>");
	}
	function event_QuickMenu(&$data) {
		global $member,$CONF;
		$blogid=$CONF['DefaultBlog'];
		// only show to admins
		if (!($this->isAdmin($blogid))) return;
		array_push(
			$data['options'], 
			array(
				'title' => 'PubMed search',
				'url' => $this->getAdminURL().'?blogid='.(int)$blogid,
				'tooltip' => 'NP_PubMed'
			)
		);
		array_push(
			$data['options'], 
			array(
				'title' => 'Manuscript management',
				'url' => $this->getAdminURL().'?blogid='.(int)$blogid.'&action=manuscriptlist',
				'tooltip' => 'NP_PubMed'
			)
		);
	}
	function event_EditItemFormExtras(&$data){
		global $member,$manager;
		if (!preg_match('/<\!\-\-PMID:([0-9\s]+)\-\->/i',$data['variables']['body'],$m)) return;
		$pmid=(int)$m[1];
		$itemid=$data['itemid'];
		$mid=$member->getID();
		echo <<<END
<script type="text/javascript">
//<![CDATA[
// Try to go to 'options' tag when the bookmarklet is used.
var np_pubmed_timer;
var np_pubmed_timer_count=0;
function np_pubmed_timer_func(){
  try { flipBlock('options'); } catch(e) { return; }
  if (10<np_pubmed_timer_count++) clearInterval(np_pubmed_timer);
}
if((document.location+'').indexOf('#pubmed')>0) np_pubmed_timer=setInterval("np_pubmed_timer_func()",100);
//]]>
</script>
<table>
<tr><th><a name="pubmed" id="pubmed">NP_PubMed</a></th></tr>
<tr><td>
<input type="button" value="Refresh" onclick="
  document.getElementById('inputtitle').value='';
  document.getElementById('inputbody').value='PMID: {$pmid}';
  document.getElementById('inputmore').value='';
  alert('Now save this item to refresh PubMed data.');
  return false;
" />
If the reloading data from PubMed site is required for this paper, push this button and save the item.
</td></tr>
</table>
END;
	}
	function event_PreUpdateItem(&$data){
		return $this->event_PreAddItem($data);
	}
	function event_PreAddItem(&$data){
		global $CONF;
		$title=&$data['title'];
		$body=&$data['body'];
		$more=&$data['more'];
		$itemid=(int)@$data['itemid'];
		if ($title||$more) return;
		if (!preg_match('/^[\s]*PMID:[\s]*([0-9]+)[\s]*$/i',$body,$matches)) return;
		$pmid=$matches[1];
		if ($founditemid=quickQuery('SELECT inumber as result FROM '.sql_table('item').
				' WHERE ibody LIKE "%<!--PMID: '.(int)$pmid.'-->%"'.
				' AND (NOT inumber='.(int)$itemid.')')) {
			$html='An item for PMID: '.(int)$pmid.' already exists<br />';
			$html.='<a href="'.$CONF['IndexURL'].'?itemid='.(int)$founditemid.'">Go to the item</a>&nbsp;&nbsp;&nbsp;';
			$html.='<a href="'.$CONF['AdminURL'].'?action=itemedit&amp;itemid='.(int)$founditemid.'">Edit the item</a>';
			doError($html);
			exit;
		}
		$fhandle=@$this->_url_open('http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&amp;retmode=xml&amp;id='.
			(int)$pmid,'r');
		if (!$fhandle) return;
		$contents='';
		while(true) {
			$data = fread($fhandle, 8192);
			if (!strlen($data)) break;
			$contents .= $data;
		}
		fclose($fhandle);
		if (!preg_match('!<PubmedArticle[^>]*>([\s\S]+)</PubmedArticle>!',$contents,$matches)) return;
		$more=$matches[1];
		if (!preg_match('!<Article[^>]*>([\s\S]+)</Article>!',$more,$matches)) return;
		$article=$matches[1];
		$body='<!--PMID: '.(int)$pmid.'-->';
		$body.='<a href="<%PubMed(pubmedurl,'.(int)$pmid.')%>" class="np_pubmed_pmidlink">PMID: '.(int)$pmid.'</a>';
		if (preg_match_all('!<LastName[^>]*>([^<]+)</LastName>!',$article,$matches,PREG_SET_ORDER)){
			switch(count($matches)){
			case 1:
				$authors=htmlspecialchars($matches[0][1]);
				break;
			case 2:
				$authors=htmlspecialchars($matches[0][1].' and '.$matches[1][1]);
				break;
			default:
				$authors=htmlspecialchars($matches[0][1].' et al.');
				break;
			}
			$allauthors='';
			if (count($matches)==1) $allauthors=$authors;
			else for ($i=0;$i<count($matches);$i++) {
				if ($i==count($matches)-1) $allauthors.=', and ';
				else if ($i>0) $allauthors.=', ';
				if ($i<10 || $i==count($matches)-1) $allauthors.=htmlspecialchars($matches[$i][1]);
				else if ($i==10) $allauthors.='... ';
			}
		} else $authors='???';
		$year=$this->_getXmlData($article,'Year');
		$journal=$this->_getXmlData($article,'ISOAbbreviation');
		$atitle=$this->_getXmlData($article,'ArticleTitle');
		$abstract=$this->_getXmlData($article,'AbstractText');
		$volume=$this->_getXmlData($article,'Volume');
		$pages=$this->_getXmlData($article,'MedlinePgn');
		$title=htmlspecialchars($authors.' ('.$year.') '.$journal);
		$body='<!--'.(int)$year.'-->'.$body.
			"<br />\n<span class=\"np_pubmed_authors\">".htmlspecialchars($allauthors.' ('.$year.') ')."</span><br />\n".
			'<span class="np_pubmed_article"><i>'.htmlspecialchars($journal).'</i> <b>'.htmlspecialchars($volume).'</b> '.htmlspecialchars($pages)."</span><br />\n".
			'<span class="np_pubmed_title">'.htmlspecialchars($atitle).'</span>';
		$more='<span class="np_pubmed_abstract">'.htmlspecialchars($abstract)."</span><span style=\"display:none;\"><![CDATA[<br />\n".$more."<br />\n]]></span>";
		setcookie($CONF['CookiePrefix'] .'NP_PubMed_defcatid',(int)postVar('catid'),time()+86400,$CONF['CookiePath'],$CONF['CookieDomain'],$CONF['CookieSecure']);
		$search=array('&amp;amp;','&amp;quot;','&amp;lt;','&amp;gt;','&amp;#039;');
		$replace=array('&amp;','&quot;','&lt;','&gt;','&#039;');
		$body=str_replace($search,$replace,$body);
		$more=str_replace($search,$replace,$more);
	}
	function _getXmlData(&$xml,$tag,$default='???'){
		if (preg_match('!<'.$tag.'[^>]*>([^<]+)</'.$tag.'>!',$xml,$matches)){
			return $matches[1];
		} else return $default;
	}
	function _updateManuscriptData($itemid,$request){
		global $member,$manager;
		$mid=$member->getID();
		$res=sql_query('SELECT manuscriptid FROM '.sql_table('plugin_pubmed_manuscripts').
			' WHERE userid='.(int)$mid);
		$manuscripts=array();
		while($row=mysql_fetch_row($res)) $manuscripts[$row[0]]=0;
		if (is_array($request)) foreach($request as $key=>$value) $manuscripts[$key]=(int)$value;
		$res=sql_query('SELECT r.manuscriptid as manuscriptid FROM '.
			sql_table('plugin_pubmed_manuscripts').' as m,'.
			sql_table('plugin_pubmed_references').' as r'.
			' WHERE m.manuscriptid=r.manuscriptid'.
			' AND m.userid='.(int)$mid.
			' AND itemid="'.(int)$itemid.'"');
		$itemdata=array();
		while($row=mysql_fetch_assoc($res)) $itemdata[$row['manuscriptid']]=$row;
		foreach($manuscripts as $key=>$value) {
			if ($value && !$itemdata[$key]) {
				sql_query('INSERT INTO '.sql_table('plugin_pubmed_references').' SET'.
					' itemid='.(int)$itemid.','.
					' manuscriptid='.(int)$key);
			} elseif($itemdata[$key] && !$value) {
				sql_query('DELETE FROM '.sql_table('plugin_pubmed_references').
					' WHERE itemid='.(int)$itemid.
					' AND manuscriptid='.(int)$key);
			}
		}
	}
	function doItemVar(&$item,$mode,$p1=''){
		switch(strtolower($mode)){
		case 'pubmedurl':
		default:
			$url='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&amp;db=PubMed&amp;dopt=Citation&amp;list_uids=';
			echo $url.(int)$p1;
			return;
		}
	}
	function doSkinVar($skintype,$mode,$p1='',$p2=''){
		global $CONF,$manager,$blog,$member;
		$mid=$member->getID();
		switch($mode=strtolower($mode)){
		case 'getvar':
			echo htmlspecialchars(getVar($p1),ENT_QUOTES);
			break;
		case 'postvar':
			echo htmlspecialchars(postVar($p1),ENT_QUOTES);
			break;
		case 'searchlink':
			if (!$this->isAdmin()) return;
			if (!$blog) return;
			$blogid=$blog->getID();
			echo '<a href="'.$this->getAdminURL().'?blogid='.(int)$blogid.'">'.
				htmlspecialchars(strlen($p1)?$p1:'PubMed Search',ENT_QUOTES).'</a>';
			break;
		case 'createnew':
			if (!$this->isAdmin()) return;
			if (!$blog) return;
			$blogid=$blog->getID();
			$defcatid=(int)cookieVar($CONF['CookiePrefix'] . 'NP_PubMed_defcatid');
			if (!$defcatid) $defcatid=$blog->getDefaultCategory();
			$categories='<select name="catid" class="np_pubmed_form"><option value="newcat-'.(int)$blogid.'">New category</option>';
			$res=sql_query('SELECT * FROM '.sql_table('category').
				' WHERE cblog='.(int)$blogid.' ORDER BY cname ASC');
			while($row=mysql_fetch_assoc($res)){
				if ($row['catid']==$defcatid) $categories.='<option value="'.(int)$row['catid'].'" selected="selected">';
				else $categories.='<option value="'.(int)$row['catid'].'">';
				$categories.=htmlspecialchars(strip_tags($row['cname'])).'</option>';
			}
			$categories.='</select>';
			
?>
<form method="post" action="<?php echo $CONF['AdminURL']; ?>" class="np_pubmed_form">
<input type="hidden" name="action" value="additem" />
<input name="blogid" value="<?php echo (int)$blogid; ?>" type="hidden" />
<input type="hidden" name="draftid" value="0" />
<?php $manager->addTicketHidden(); ?>
<input type="hidden" name="title" value="" />
<input type="hidden" name="more" value="" />
<input type="hidden" name="closed" value="0" />
<input type="hidden" name="actiontype" value="addnow" />
<input type="text" name="body" value="PMID:" class="np_pubmed_form" />
<?php echo $categories; ?>
<input type="submit" value="<?php echo htmlspecialchars(strlen($p1)?$p1:'Create'); ?>" class="np_pubmed_form" />
</form>
<?php
			break;
		case 'pubmedlink':
			echo '<a href="http://www.ncbi.nlm.nih.gov/PubMed/" onclick="window.open(this.href,\'PubMed\');return false;">'.
				htmlspecialchars(strlen($p1)?$p1:'PubMed',ENT_QUOTES).'</a>';
			break;
		case 'parse':
			if (!$mid) return;
			if (!$blog) return;
			$msid=intGetVar('manuscriptid');
			if (!$msid) return;
			$action=postVar('batchaction');
			if ($action) $this->batchAction($action,$_POST['batch'],$msid);
			
			// Construct template object.
			require_once($this->getDirectory().'template.php');
			$res=sql_query('SELECT *'.
				' FROM '.sql_table('plugin_pubmed_manuscripts').
				' WHERE manuscriptid='.(int)$msid.
				' AND userid='.(int)$mid);
			$row=mysql_fetch_assoc($res);
			if (!$row) return;
			echo '<p>Template: '.htmlspecialchars($row['templatename'])."</p>\n";
			if ($p1=='edit') $row['templatename']='edit';
			$tobj=PUBMED_TEMPLATE_BASE::getTemplate($row['templatename']);
			if (!$tobj) {
				echo 'The template, "'.htmlspecialchars($row['templatename']).'" cannot be found';
				return;
			}
			$tobj->setSortText($row['sorttext']);
			
			// Set all the data.
			$query='SELECT i.inumber as itemid, i.ibody as body, i.ititle as title, i.imore as more'.
				' FROM '.sql_table('item').' as i, '.
					sql_table('plugin_pubmed_references').' as r,'.
					sql_table('plugin_pubmed_manuscripts').' as m'.
				' WHERE i.inumber=r.itemid'.
				' AND r.manuscriptid='.(int)$msid.
				' AND m.manuscriptid='.(int)$msid.
				' AND m.userid='.(int)$mid.
				' ORDER BY i.ititle ASC';
			$res=sql_query($query);
			while($row=mysql_fetch_assoc($res)){
				$pmid=$tobj->setData($row['more'],$row['itemid']);
			}
			// Sort the papers
			$tobj->sortPapers();
			// Let's parse, finally.
			$tobj->parse_all();
			break;
		case 'manuscriptlist':
			
			if (!$mid) return;
			if (!$blog) return;
			$blogid=$blog->getID();
			$template =& $manager->getTemplate($p1);
			$res=sql_query('SELECT manuscriptname as name, manuscriptid as id'.
				' FROM '.sql_table('plugin_pubmed_manuscripts').
				' WHERE userid='.(int)$mid);
			while($row=mysql_fetch_assoc($res)){
				$values=array(
					'catlink'=>$CONF['IndexURL'].'?special=references&amp;blogid='.(int)$blogid.'&amp;manuscriptid='.(int)$row['id'],
					'catid'=>(int)$row['id'],
					'catname'=>htmlspecialchars($row['name'],ENT_QUOTES)
					);
				echo TEMPLATE::fill($template['CATLIST_LISTITEM'],$values);
			}
			break;
		default:
			break;
		}
	}
	function doTemplateVar(&$item,$type,$p1='',$p2='',$p3='') {
		global $CONF,$member,$DIR_MEDIA;
		switch($type=strtolower($type)){
			case 'pdf': case 'supplement':
				if (!$member->isLoggedIn()) return;
				if (!preg_match('/<!--[\s]*(PMID|pmid)[\s]*:[\s]*([0-9]+)[\s]*-->/',$item->body,$matches)) return;
				$pmid=(int)$matches[2];
				if ($type=='supplement') $pmid.='s';
				$filename=$DIR_MEDIA.'pubmed/'.$pmid.'.pdf';
				if (!file_exists($filename)) return;
				if ($p1) $text=$p1;
				elseif ($type=='supplement') $text='Read supplemental info';
				else $text='Read pdf';
				switch($p2){
					case 'pmid':
						$filename=$pmid;
						break;
					case 'author':
					default:
						$filename=rawurlencode(strip_tags($item->title));
						if ($type=='supplement') $filename.='%20supplement';
						break;
				}
				echo '<a href="'.htmlspecialchars($CONF['ActionURL'].'/'.$filename.'.pdf'.
					'?action=plugin&name=PubMed&type=pdf&file='.
					$pmid.'.pdf').
					'" onclick="window.open(this.href);return false;">'.
					htmlspecialchars($text).'</a>';
				break;
			case 'edit':
				if (!$member->isLoggedIn()) return;
				$itemid=(int)$item->itemid;
				$text=$p1?'manuscript management':htmlspecialchars($p1,ENT_QUOTES);
				$width=(int)$p2;
				$height=(int)$p3;
				$link='?action=plugin&name=PubMed&type=manuscripts&itemid='.$itemid;
				echo <<<END
<a href="{$link}" onclick="
  this.style.display='none';
  var iframe=document.getElementById('iframe_pubmed_{$itemid}');
  iframe.style.display='block';
  iframe.src='{$link}';
  return false;
">{$text}</a>
<iframe style="display:none;" id="iframe_pubmed_{$itemid}" width="{$width}" height="{$height}" src="" ></iframe>
END;
				break;
			default:
				break;
		}
	}
	function doAction($type){
		global $manager,$member,$DIR_MEDIA;
		switch($type=strtolower($type)){
			case 'pdf':
				if (!$member->isLoggedIn()) return 'Login is required to download pdf file.';
				$filename=realpath($DIR_MEDIA.'pubmed/'.getVar('file'));
				if (!file_exists($filename)) return 'File not found.';
				if (strpos($filename,realpath($DIR_MEDIA.'pubmed/'))!==0) return 'Error:'.__LINE__;
				header('Content-type: application/pdf; filename="test1.pdf"');
				readfile($filename);
				return;
			case 'manuscripts':
				if (!$member->isLoggedIn()) return 'Login is required to manage manuscripts.';
				$itemid=intGetVar('itemid');
				if (!$itemid) return 'Itemid not defined.';
				if (postVar('sure')=='yes') {
					if (!$manager->checkTicket()) return 'Invalid or expired ticket.';
					$request=requestArray('msid');
					$this->_updateManuscriptData($itemid,$request);
					$msg='Updated';
				} else $msg='';
				// Determine if the item is selected as references.
				$query='SELECT r.manuscriptid as msid FROM '.
					sql_table('plugin_pubmed_manuscripts').' as m, '.
					sql_table('plugin_pubmed_references').' as r '.
					' WHERE m.userid='.(int)$member->getID().
					' AND m.manuscriptid=r.manuscriptid'.
					' AND r.itemid='.(int)$itemid;
				$res=sql_query($query);
				$checked=array();
				while($row=mysql_fetch_assoc($res)) $checked[]=$row['msid'];
				// Get the manuscript list.
				$query='SELECT * FROM '.sql_table('plugin_pubmed_manuscripts').
					' WHERE userid='.(int)$member->getID();
				$res=sql_query($query);
				$data=array();
				while($row=mysql_fetch_assoc($res)){
					if (in_array($row['manuscriptid'],$checked)) $row['checked']=true;
					else $row['checked']=false;
					foreach($row as $key=>$value) $row[$key]=htmlspecialchars($value,ENT_QUOTES);
					$data[]=$row;
				}
?><html><body><head><title>Manuscript management</title></head>
<body>
<?php if ($msg) echo '<b>'.htmlspecialchars($msg).'</b><br />'; ?>
<form method="post" action="">
<?php $manager->addTicketHidden(); ?>
<input type="hidden" name="sure" value="yes" />
<table>
<?php foreach($data as $row) {
	echo "<tr><td>";
	if ($row['checked']) echo "<input type=\"checkbox\" name=\"msid[$row[manuscriptid]]\" value=\"1\" checked=\"checked\" />";
	else echo "<input type=\"checkbox\" name=\"msid[$row[manuscriptid]]\" value=\"1\" />";
	echo "</td><td>$row[manuscriptname]</td></tr>\n";
}?>
<tr><td></td><td><input type="submit" value="update" /></td></tr>
</table>
</form>
</body></html>
<?php
			default:
				return;
		}
	}
	function doIf($p1='',$p2=''){
		if (preg_match('/^([^=]*)=([.]*)$/',$p2,$matches)) list($p2,$name,$value)=$matches;
		else list($name,$value)=array('','');
		switch($mode=strtolower($p1)){
			case 'getvar': case 'postvar': case 'cookievar':
				return (call_user_func($mode,$name)==$value);
			default:
				return false;
		}
	}
	function isAdmin($blogid=''){
		global $member,$blog;
		if ($blogid) return $member->blogAdminRights($blogid);
		if (!($member->isLoggedIn() && $blog)) return false;
		return $member->blogAdminRights($blog->getID());
	}
	function _url_open($myUrl){
		//wait at least 3 seconds from the previous access.
		for($i=0;$i<3 && time()<=$this->getOption('lastquerytime')+3;$i++) sleep(1);
		$this->setOption('lastquerytime',time());
		//check URL and resolve host, port and URI
		if (substr($myUrl,0,7)!='http://') return false;
		$myUri=substr($myUrl,7);
		if (($i=strpos($myUri,'/',0))===false) return false;
		$myPort=80;
		$myHost=substr($myUri,0,$i);
		$j=strpos($myUri,':',0);
		if ((!($j===false)) && ($j<$i)){
			$myPort=(int)substr($myUri,$j+1,$i-$j-1);
			$myHost=substr($myUri,0,$j);
		}
		$myUri=substr($myUri,$i);
	
		//get header information from browser
		$headers = apache_request_headers();
		while (list($header, $value) = each ($headers)) {
			switch (strtolower($header)) {
			case 'accept':
					$accept=$value;
					break;
			case 'accept-language':
					$acceptlanguage=$value;
					break;
			case 'user-agent':
					$useragent=$value;
					break;
			default:
					break;
			}
		}
	
		//create header for HTTP request
		$t="GET $myUri HTTP/1.0\r\n";
		$t=$t."Conection: Close\r\n";
		$t=$t."Accept: $accept\r\n";
		$t=$t."Accept-Language: $acceptlanguage\r\n";
		$t=$t."User-Agant: $useragent\r\n";
		$t=$t."Host: $myHost\r\n";
		$t=$t."\r\n";
	
		//Connect
		$myStatus=0;
		if (!($fp = @fsockopen($myHost,$myPort, $errno, $errstr, 30))) return false;
		fwrite($fp,$t);
		return $fp;
	}
	function batchAction($action,$batch,$msid){
		global $manager;
		if ($action=='nothing') return;
		if (!$manager->checkTicket()) {
			echo '<p><b>Invalid or expired ticket!</b></p>';
			return;
		}
		// Clean up batch data
		foreach($batch as $key=>$itemid) $batch[$key]=(int)$itemid;
		// Get citation information
		$res=sql_query('SELECT * FROM '. sql_table('plugin_pubmed_references').
			' WHERE manuscriptid='.(int)$msid);
		$references=array();
		while($row=mysql_fetch_assoc($res)){
			$references[]=(int)$row['itemid'];
		}
		$sort=array();
		$sorttext=quickQuery('SELECT sorttext as result FROM '. sql_table('plugin_pubmed_manuscripts').
			' WHERE manuscriptid='.(int)$msid);
		foreach(explode(',',$sorttext) as $itemid){
			$itemid=(int)(trim($itemid));
			if (in_array($itemid,$references)) $sort[]=$itemid;
		}
		foreach($references as $itemid=>$row){
			if (!in_array($itemid,$sort)) $sort[]=$itemid;
		}
		// Take action
		switch($action){
			case 'delete':
				$in='';
				foreach($batch as $itemid){
					if (strlen($in)) $in.=',';
					$in.=(int)$itemid;
				}
				sql_query('DELETE FROM '. sql_table('plugin_pubmed_references').
					' WHERE itemid in ('.$in.')'.
					' AND manuscriptid='.(int)$msid);
				echo '<p> Deleted references: '.$in.'</p>';
				return;
			case 'moveup':
				for($i=0;$i<count($sort);$i++){
					if (in_array($sort[$i],$batch)) {
						$i--;
						break;
					}
				}
				$sorted=array();
				for($j=0;$j<$i;$j++) $sorted[]=$sort[$j];
				foreach($batch as $itemid){
					if (in_array($itemid,$references) && !in_array($itemid,$sorted)) $sorted[]=$itemid;
				}
				foreach($sort as $itemid){
					if (!in_array($itemid,$sorted)) $sorted[]=$itemid;
				}
				break;
			case 'movedown':
				for($i=count($sort)-1;0<=$i;$i--){
					if (in_array($sort[$i],$batch)) {
						if ($i==count($sort)-1) break;
						$i++;
						break;
					}
				}
				$sorted=array();
				for($j=0;$j<=$i;$j++){
					$itemid=$sort[$j];
					if (!in_array($itemid,$batch) && !in_array($itemid,$sorted)) $sorted[]=$itemid;
				}
				foreach($batch as $itemid){
					if (in_array($itemid,$references) && !in_array($itemid,$sorted)) $sorted[]=$itemid;
				}
				foreach($sort as $itemid){
					if (!in_array($itemid,$sorted)) $sorted[]=$itemid;
				}
				break;
			case 'totop':
				$sorted=array();
				foreach($batch as $itemid){
					if (in_array($itemid,$references) && !in_array($itemid,$sorted)) $sorted[]=$itemid;
				}
				foreach($sort as $itemid){
					if (!in_array($itemid,$sorted)) $sorted[]=$itemid;
				}
				break;
			case 'tobottom':
				$sorted=array();
				foreach($sort as $itemid){
					if (!in_array($itemid,$batch) && !in_array($itemid,$sorted)) $sorted[]=$itemid;
				}
				foreach($batch as $itemid){
					if (!in_array($itemid,$sorted)) $sorted[]=$itemid;
				}
				break;
			default:
				return;
		}
		// Save sorted data if exists
		if (isset($sorted)) sql_query('UPDATE '.sql_table('plugin_pubmed_manuscripts').
			' SET sorttext="'.addslashes(implode(',',$sorted)).'"'.
			' WHERE manuscriptid='.(int)$msid);
	}
}
?>