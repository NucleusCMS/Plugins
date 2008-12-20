<?php 
class NP_PubMed extends NucleusPlugin { 
	function getName() { return 'NP_PubMed'; }
	function getMinNucleusVersion() { return 330; }
	function getAuthor()  { return 'Katsumi'; }
	function getVersion() { return '0.1.7'; }
	function getURL() {return 'http://hp.vector.co.jp/authors/VA016157/';}
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
		$this->createOption('droptable','Drop table when uninstall?','yesno','no');
		$this->createOption('lastquerytime','hidden option','text','0','access=hidden');
		$this->createOption('email','E-mail address to be sent to PubMed search site (set blank if not use):','text',$member->getEmail());
		sql_query('CREATE TABLE IF NOT EXISTS '.sql_table('plugin_pubmed_references').' ('.
			' id int(11) not null auto_increment,'.
			' manuscriptid int(11) not null default 0,'.
			' itemid int(11) not null default 0,'.
			' sort int(11) not null default 0, '.
			' PRIMARY KEY id(id),'.
			' UNIQUE KEY manuscriptid(manuscriptid,itemid) '.
			') TYPE=MyISAM;');
		sql_query('CREATE TABLE IF NOT EXISTS '.sql_table('plugin_pubmed_manuscripts').' ('.
			' manuscriptid int(11) not null auto_increment,'.
			' userid int(11) not null default 0,'.
			' manuscriptname varchar(200) not null default "New Manuscript",'.
			' templatename varchar(200) not null default "default.template",'.
			' sortmethod varchar(40) not null default "author",'.
			' PRIMARY KEY manuscriptid(manuscriptid) '.
			') TYPE=MyISAM;');
	}
	function uninstall(){
		if ($this->getOption('droptable')=='yes') {
			foreach($this->getTableList() as $table) sql_query('DROP TABLE IF EXISTS '.$table);
		}
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
	function event_PreUpdateItem(&$data){
		$ret=$this->event_PreAddItem(&$data);
		$this->_updateManuscriptData($data['itemid']);
		return $ret;
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
	function _updateManuscriptData($itemid){
		global $member,$manager;
		$mid=$member->getID();
		$request=requestArray('np_pubmed_manuscript');
		$res=sql_query('SELECT manuscriptid FROM '.sql_table('plugin_pubmed_manuscripts').
			' WHERE userid='.(int)$mid);
		$manuscripts=array();
		while($row=mysql_fetch_row($res)) $manuscripts[$row[0]]=0;
		if (is_array($request)) foreach($request as $key=>$value) $manuscripts[$key]=(int)$value;
		$res=sql_query('SELECT r.manuscriptid as manuscriptid, r.sort as sort FROM '.
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
		case 'parse': case 'pageswitch':
			if (!$blog) return;
			global $startpos;
			$startpos=(int)$startpos;
			$limit=(int)$p2;
			$template=addslashes($p1);
			$query=$blog->getSqlBlog('');
			$query=preg_replace('/ORDER[\s]+BY[\s][\s\.a-z0-9_]*(ASC|DESC)$/i','ORDER BY i.ititle ASC',$query);
			switch($mode){
			case 'pageswitch':
				$ps=&$manager->getPlugin('NP_PageSwitch');
				$ps->setQuery($query);
				break;
			case 'parse':
			default:
				$blog->showUsingQuery($template, $query.' LIMIT '.$startpos.','.$limit, '', 1, 1);
				break;
			}
		case 'manuscriptlist':
			if (!$mid) return;
			if (!$blog) return;
			$blogid=$blog->getID();
			$template =& $manager->getTemplate($p1);
			//print_r($template['CATLIST_LISTITEM']);exit;
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
	function doTemplateVar(&$item,$type,$p1='') {
		global $CONF,$member,$DIR_MEDIA;
		switch($type=strtolower($type)){
		case 'pdf':
		default:
			if (!$member->isLoggedIn()) return;
			if (!preg_match('/<!--[\s]*(PMID|pmid)[\s]*:[\s]*([0-9]+)[\s]*-->/',$item->body,$matches)) return;
			$pmid=(int)$matches[2];
			$filename=$DIR_MEDIA.'pubmed/'.$pmid.'.pdf';
			if (!file_exists($filename)) return;
			switch($p1){
			case 'pmid':
				$filename=$pmid;
				break;
			case 'author':
			default:
				$filename=rawurlencode(strip_tags($item->title));
				break;
			}
			echo '<a href="'.htmlspecialchars($CONF['ActionURL'].'/'.$filename.'.pdf'.
				'?action=plugin&name=PubMed&type=pdf&file='.
				$pmid.'.pdf').
				'" onclick="window.open(this.href);return false;">'.
				($p1?htmlspecialchars($p1):'Read pdf').'</a>';
			break;
		}
	}
	function doAction($type){
		global $member,$DIR_MEDIA;
		switch($type=strtolower($type)){
		case 'pdf':
		default:
			if (!$member->isLoggedIn()) return 'Login is required to download pdf file.';
			$filename=realpath($DIR_MEDIA.'pubmed/'.getVar('file'));
			if (!file_exists($filename)) return 'File not found.';
			if (strpos($filename,realpath($DIR_MEDIA.'pubmed/'))!==0) return 'Error:'.__LINE__;
			header('Content-type: application/pdf; filename="test1.pdf"');
			readfile($filename);
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
	function event_EditItemFormExtras(&$data){
		global $member,$manager;
		$itemid=$data['itemid'];
		$mid=$member->getID();
?>
<script type="text/javascript">
//<![CDATA[
//if((document.location+'').indexOf('#pubmed')>0) window.onload=function(){flipBlock('options');};
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
<tr><th><a name="pubmed" id="pubmed">Manuscript management (NP_PubMed)</a></th></tr>
<?php
		$res=sql_query('SELECT r.manuscriptid as manuscriptid, r.sort as sort FROM '.
			sql_table('plugin_pubmed_manuscripts').' as m,'.
			sql_table('plugin_pubmed_references').' as r'.
			' WHERE m.manuscriptid=r.manuscriptid'.
			' AND m.userid='.(int)$mid.
			' AND itemid="'.(int)$itemid.'"');
		$itemdata=array();
		while($row=mysql_fetch_assoc($res)) $itemdata[$row['manuscriptid']]=$row;
		$res=sql_query('SELECT * FROM '.sql_table('plugin_pubmed_manuscripts').
			' WHERE userid='.(int)$mid);
		while($row=mysql_fetch_assoc($res)) {
			$checked=$itemdata[$row['manuscriptid']]?'checked="checked"':'';
			echo '<tr><td>';
			echo '<input type="checkbox" name="np_pubmed_manuscript['.(int)$row['manuscriptid'].']" value="1" '.$checked.' />';
			echo htmlspecialchars($row['manuscriptname']);
			echo "</td></tr>\n";
		}
?>
</table>
<?php
	}
}
?>