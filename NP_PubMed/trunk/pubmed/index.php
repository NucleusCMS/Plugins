<?php

$strRel = '../../../';
$DIR_LIBS='';
require($strRel . 'config.php');
$pbadmin=new PubMedAdmin;
exit;

class PubMedAdmin {
	var $oPluginAdmin,$plugin;
	var $blogid;
	function PubMedAdmin(){
		return $this->__construct();
	}
	function __construct(){
		global $DIR_LIBS,$manager,$member,$CONF, $HTTP_POST_VARS;
		include($DIR_LIBS . 'PLUGINADMIN.php');
		
		// Initialize
		$this->oPluginAdmin  = new PluginAdmin('PubMed');
		$this->plugin=&$this->oPluginAdmin->plugin;
		if (!($this->blogid=intPostVar('blogid'))) $this->blogid=intGetVar('blogid');
		$CONF['ItemURL']=quickQuery('SELECT burl as result FROM '.sql_table('blog'). ' WHERE bnumber='.(int)$this->blogid);
		
		// Check if there is right to maintain the blog by member.
		if (!$member->isLoggedIn() || !$member->teamRights($this->blogid) || !$manager->existsBlogID($this->blogid))
		{
			$this->oPluginAdmin->start();
			echo '<p>' . _ERROR_DISALLOWED . '</p>';
			$this->oPluginAdmin->end();
			exit;
		}
		
		// If some data is/are posted, check the ticket.
		if (!isset($_POST)) $_POST=&$HTTP_POST_VARS;
		if (count($_POST) && !$manager->checkTicket()) {
			$this->oPluginAdmin->start();
			echo '<p class="error">Error: ' . _ERROR_BADTICKET . '</p>';
			$this->oPluginAdmin->end();
			exit;
		}
		
		// Resolve action
		if (!($action=postVar('action'))) {
			if (!($action=getVar('action'))) $action='searchform';
		}
		
		// The functions whose name start from '_' are not actions, but private ones.
		if (substr($action,0,1)=='_' || !method_exists($this,$action)) exit('Error: '.__LINE__);
		
		// There are two modes, so far.
		$this->oPluginAdmin->start();
		switch(getVar('action')) {
			case 'manuscriptlist':
				echo '<h2><a href="'.$this->plugin->getAdminURL().'?blogid='.
					(int)$this->blogid.'&amp;action=manuscriptlist">' . 
					'Manuscript management' . "</a></h2>\n";
				break;
			default:
				echo '<h2><a href="'.$this->plugin->getAdminURL().'?blogid='.
					(int)$this->blogid.'">' . 
					'PubMed search' . "</a></h2>\n";
		}
		call_user_func(array(&$this,$action));
		$this->oPluginAdmin->end();
	}
/* Pubmed Search */
	function searchform(){
		global $manager;
?>
<form method="post" action="">
<?php $manager->addTicketHidden(); ?>
<input type="hidden" name="action" value="searchquery" />
<input type="hidden" name="blogid" value="<?php echo (int)$this->blogid; ?>" />
<input type="text" name="query" value="<?php echo htmlspecialchars(postVar('query')); ?>" size="60" />
<input type="submit" value="Search" /><br />
<a href="http://www.ncbi.nlm.nih.gov/sites/entrez?db=PubMed" onclick="window.open(this.href);return false;">Goto the NIH PubMed site</a>
</form>
<?php
	}
	function searchquery(){
		global $manager,$CONF;
		$this->searchform();
		// Get PubMed ids as the result
		$start=intPostVar('retstart');
		if (!($max=intPostVar('retmax'))) $max=20;
		$fhandle=$this->_url_open('http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?'.
			'db=pubmed&retmode=xml'.
			'&retstart='.(int)$start.'&retmax='.(int)$max.
			'&term='.urlencode(postVar('query')));
		if (!$fhandle) exit('Error: '.__LINE__);
		$contents='';
		while(true) {
			$data = fread($fhandle, 8192);
			if (!strlen($data)) break;
			$contents .= $data;
		}
		fclose($fhandle);
		// Get query results
		$result=array();
		$result['max']=$this->_getXmlData($contents,'RetMax');
		$result['start']=$this->_getXmlData($contents,'RetStart');
		$result['count']=$this->_getXmlData($contents,'Count');
		$result['querykey']=$this->_getXmlData($contents,'QueryKey');
		$result['webenv']=$this->_getXmlData($contents,'WebEnv');
		// Get id information
		if (!($contents=$this->_getNestedXmlData($contents,'IdList',''))) {
			echo '<table><tr><th>Summaries</th></tr>';
			echo '<tr><td>No result</td></tr>';
			echo '</table>';
			return;
		}
		$contents=explode('<Id>',$contents);
		$ids='';
		$likes='';
		foreach($contents as $value){
			if (!($value=(int)$value)) continue;
			$ids.='&id='.$value;
			$likes.=' OR ibody LIKE "%<!--PMID: '.(int)$value.'-->%"';
		}
		// Check if there are records for the articles
		$dataexists=array();
		$res=sql_query('SELECT ibody,inumber FROM '.sql_table('item').
			' WHERE (0 '.$likes.') AND iblog='.(int)$this->blogid);//$likes is clean (see few lines above).
		while ($row=mysql_fetch_assoc($res)){
			if (!preg_match('/<!\-\-PMID:[\s]*([0-9]+)\-\->/i',$row['ibody'],$matches)) continue;
			$dataexists[(int)$matches[1]]=(int)$row['inumber'];
		}
		// Get summary
		$fhandle=$this->_url_open('http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?'.
			'db=pubmed'.$ids);
		if (!$fhandle) exit('Error: '.__LINE__);
		$contents='';
		while(true) {
			$data = fread($fhandle, 8192);
			if (!strlen($data)) break;
			$contents .= $data;
		}
		fclose($fhandle);//exit($result['start']+$result['max'].'|'.$result['count']);
		// Get summaries
		// Header
		foreach($result as $key=>$value) $result[$key]=htmlspecialchars($value,ENT_QUOTES);
		echo '<table>';
		ob_start();
?>
<tr><th style="width:70%">Summaries</th><th>
<?php if ($result['start']) { ?>
	<form method="post" action="">
	<?php $manager->addTicketHidden(); ?>
	<input type="hidden" name="action" value="searchquery" />
	<input type="hidden" name="blogid" value="<?php echo (int)$this->blogid; ?>" />
	<input type="hidden" name="query" value="<?php echo htmlspecialchars(postVar('query')); ?>" />
	<input type="hidden" name="retstart" value="<?php echo $result['start']-$max; ?>" />
	<input type="hidden" name="retmax" value="<?php echo (int)$max; ?>" />
	<input type="submit" value="Previous Page" />
	</form>
<?php } ?>
</th><th style="white-space: nowrap;"><?php echo 'page '.(int)(1+($result['start']/$max)).' of '.(int)(1+($result['count']-1)/$max); ?></th><th>
<?php if ($result['start']+$max<=$result['count']) { ?>
	<form method="post" action="">
	<?php $manager->addTicketHidden(); ?>
	<input type="hidden" name="action" value="searchquery" />
	<input type="hidden" name="blogid" value="<?php echo (int)$this->blogid; ?>" />
	<input type="hidden" name="query" value="<?php echo htmlspecialchars(postVar('query')); ?>" />
	<input type="hidden" name="retstart" value="<?php echo $result['start']+$max; ?>" />
	<input type="hidden" name="retmax" value="<?php echo (int)$max; ?>" />
	<input type="submit" value="Next Page" />
	</form>
<?php } ?>
</tr>
<?php
		$tableth=ob_get_contents();
		ob_end_flush();
		// Prepare before showing results
		$template='<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);"><td colspan="4"><a href="http://www.ncbi.nlm.nih.gov/entrez/query.fcgi'.
				'?cmd=Retrieve&amp;db=PubMed&amp;dopt=Citation&amp;list_uids=<%pmid%>"'.
				' onclick="window.open(this.href);return false;"><%authors%></a><br />'.
			'<%title%><br />'.
			'<i><%journal%></i> (<%date%>) <b><%volume%></b> <%pages%><br />'.
			'PMID: <%pmid%>'.
			'<div style="text-align:right;"><%addbutton%></div></td></tr>';
		$defcatid=(int)cookieVar($CONF['CookiePrefix'] . 'NP_PubMed_defcatid');
		if (!$defcatid) $defcatid=quickQuery('SELECT bdefcat as result FROM '.sql_table('blog').' WHERE bnumber='.(int)$CONF['DefaultBlog']);
		$categories='<select name="catid" class="np_pubmed_form"><option value="newcat-'.(int)$this->blogid.'">New category</option>';
		$res=sql_query('SELECT * FROM '.sql_table('category').
			' WHERE cblog='.(int)$this->blogid.' ORDER BY cname ASC');
		while($row=mysql_fetch_assoc($res)){
			if ($row['catid']==$defcatid) $categories.='<option value="'.(int)$row['catid'].'" selected="selected">';
			else $categories.='<option value="'.(int)$row['catid'].'">';
			$categories.=htmlspecialchars(strip_tags($row['cname'])).'</option>';
		}
		$categories.='</select>';
		if (!($contents=$this->_getNestedXmlData($contents,'eSummaryResult',''))) exit('Error: '.__LINE__);
		// Show the results
		$contents=explode('<DocSum>',$contents);
		foreach($contents as $summary){
			if (strpos($summary,'</DocSum>')===false) continue;
			$data=array();
			$data['date']=$this->_getXmlData($summary,'Item','???','Name="PubDate"');
			$data['journal']=$this->_getXmlData($summary,'Item','???','Name="Source"');
			$data['title']=$this->_getXmlData($summary,'Item','???','Name="Title"');
			$data['volume']=$this->_getXmlData($summary,'Item','???','Name="Volume"');
			$data['pages']=$this->_getXmlData($summary,'Item','???','Name="Pages"');
			$data['pmid']=$this->_getXmlData($summary,'Id','???');
			$data['authors']='???';
			if ($num=preg_match_all('!<Item[\s]+Name="Author"[^>]*>([^<]+)</Item>!',$summary,$matches,PREG_SET_ORDER)){
				for ($i=0;$i<$num;$i++) {
					switch($i){
					case 0:
						$data['authors']=htmlspecialchars($matches[$i][1]);
						break;
					case 1:case 2:case 3:case 4:
					case ($num-1):
						$data['authors'].=', '.htmlspecialchars($matches[$i][1]);
						break;
					case 5:
						$data['authors'].=', ... ';
					default:
						break;
					}
				}
			}
			foreach($data as $key=>$value) $data[$key]=htmlspecialchars($value,ENT_QUOTES);
			if (array_key_exists((int)$data['pmid'],$dataexists)) {
				$url=createItemLink($dataexists[(int)$data['pmid']], '');
				$data['addbutton']='(<a href="'.$url.'" onclick="window.open(this.href);return false;">Data exists for this article</a>)';
			} else {
				ob_start();
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
<input type="hidden" name="body" value="PMID:<?php echo (int)$data['pmid']; ?>" class="np_pubmed_form" />
<input type="submit" value="Add this:" class="np_pubmed_form" />
<?php echo $categories; ?>
</form>
<?php
				$data['addbutton']=ob_get_contents();
				ob_end_clean();
			}
			echo TEMPLATE::fill($template,$data);
		}
		// Footer
		echo $tableth;
		echo '</table>';
	}
	function _getXmlData(&$xml,$tag,$default='???',$extra=''){
		if (preg_match('!<'.$tag.'[^>]*'.$extra.'[^>]*>([^<]+)</'.$tag.'>!',$xml,$matches)){
			return $matches[1];
		} else return $default;
	}
	function _getNestedXmlData(&$xml,$tag,$default='???'){
		if (preg_match('!<'.$tag.'[^>]*>([\s\S]+)</'.$tag.'>!',$xml,$matches)){
			return $matches[1];
		} else return $default;
	}
	function _url_open($url){
		return $this->oPluginAdmin->plugin->_url_open($url);
	}

/* Manunscript Management */
	function manuscriptlist(){
		global $member,$manager;
		$mid=$member->getID();
		echo '<a href="'.$this->oPluginAdmin->plugin->getAdminURL().'?blogid='.(int)$this->blogid.'&amp;action=manuscriptlist">Refresh</a><br />';
		echo '<table><tr><th>manuscript</th><th>template</th><th colspan="2">&nbsp;</th></tr>';
		$res=sql_query('SELECT * FROM '.sql_table('plugin_pubmed_manuscripts').
			' WHERE userid='.(int)$mid);
		while($row=mysql_fetch_assoc($res)){
			echo '<tr>';
			echo '<td>'.htmlspecialchars($row['manuscriptname']).'</td>';
			echo '<td>'.htmlspecialchars($row['templatename']).'</td>';
?>
<td><form method="post" action="">
<input type="hidden" name="action" value="deletemanuscript" />
<input type="hidden" name="manuscriptid" value="<?php echo (int)$row['manuscriptid']; ?>" />
<?php $manager->addTicketHIdden() ?>
<input type="submit" value="Delete" />
</form></td>
<td><form method="post" action="">
<input type="hidden" name="action" value="editmanuscript" />
<input type="hidden" name="manuscriptid" value="<?php echo (int)$row['manuscriptid']; ?>" />
<?php $manager->addTicketHIdden() ?>
<input type="submit" value="Edit" />
</form></td>
<?php
			echo "</tr>\n";
		}
		echo "</table>\n";
?>
<form method="post" action="">
<input type="hidden" name="action" value="createmanuscript" />
<?php $manager->addTicketHIdden() ?>
<input type="hidden" name="blogid" value="<?php echo (int)$this->blogid; ?>" />
New manuscript:
<input type="text" name="manuscriptname" value="" />
<input type="submit" value="Create" />
</form>
<?php
	}
	
	function deletemanuscript(){
		global $member,$manager;
		$mid=$member->getID();
		$manuscriptid=intPostVar('manuscriptid');
		$mname=quickQuery('SELECT manuscriptname as result FROM '.sql_table('plugin_pubmed_manuscripts').
			' WHERE manuscriptid='.(int)$manuscriptid.
			' AND userid='.(int)$mid);
		if (!$mname) exit('Invalid manuscriptid!');
		if (postVar('sure')=='yes') {
			sql_query('DELETE r.* FROM '.sql_table('plugin_pubmed_references').' as r, '.
				sql_table('plugin_pubmed_manuscripts').' as m'.
				' WHERE m.manuscriptid='.(int)$manuscriptid.
				' AND m.userid='.(int)$mid.
				' AND m.manuscriptid=r.manuscriptid');
			sql_query('DELETE FROM '.sql_table('plugin_pubmed_manuscripts').
				' WHERE manuscriptid='.(int)$manuscriptid.
				' AND userid='.(int)$mid);
			echo "<b>The manuscript, '".htmlspecialchars($mname)."' was deleted.</b><br />\n";
			return $this->manuscriptlist();
		}
		echo "<b>The manuscript, '".htmlspecialchars($mname)."' will be deleted.</b><br /><br />\n";
?>
<form method="post" action="">
<input type="hidden" name="action" value="deletemanuscript" />
<input type="hidden" name="sure" value="yes" />
<input type="hidden" name="manuscriptid" value="<?php echo (int)$manuscriptid; ?>" />
<?php $manager->addTicketHIdden() ?>
Are you sure?&nbsp;&nbsp;
<input type="submit" value="Yes. Delete it." />&nbsp;&nbsp;
<a href="javascript:history.go(-1);">No I'm not.</a>
</form>
<?php
	}
	
	function createmanuscript(){
		global $member,$manager;
		$mid=$member->getID();
		$mname=$this->_checkmanuscriptname(postVar('manuscriptname'));
		if ($mname) sql_query('INSERT INTO '.sql_table('plugin_pubmed_manuscripts').' SET'.
				' userid='.(int)$mid.','.
				' manuscriptname="'.addslashes($mname).'"');
		return $this->manuscriptlist();
	}
	
	function _checkmanuscriptname($mname,$id=0){
		global $member;
		$mid=$member->getID();
		$mname=preg_replace('/[^0-9a-zA-Z\._\-]+/','',$mname);
		$mname=preg_replace('/[\s]+/',' ',$mname);
		$query='SELECT COUNT(*) as result FROM '.sql_table('plugin_pubmed_manuscripts').
			' WHERE userid='.(int)$mid.
			' AND manuscriptname="'.addslashes($mname).'"';
		if ($id) $query.=' AND NOT (manuscriptid='.(int)$id.')';
		if (!$mname) echo "<b>Manuscript name is empty.</b><br />\n";
		elseif (quickQuery($query)) echo "<b>The same manuscript exists.</b><br />\n";
		else return $mname;
		return false;
	}
	
	function editmanuscript(){
		global $member,$manager;
		$mid=$member->getID();
		$manuscriptid=intPostVar('manuscriptid');
		$res=sql_query('SELECT * FROM '.sql_table('plugin_pubmed_manuscripts').
			' WHERE manuscriptid='.(int)$manuscriptid.
			' AND userid='.(int)$mid.
			' LIMIT 1');
		$row=mysql_fetch_assoc($res);
		if (!$row) exit('Invalid manuscriptid!');
		$mname=postVar('manuscriptname');
		if (!$mname) $mname=$row['manuscriptname'];
		$mname=$this->_checkmanuscriptname($mname,$manuscriptid);
		if (!$mname) return $this->manuscriptlist();
		$template=$row['templatename'];
		if (postVar('sure')=='yes') {
			$template=postVar('templatename');
			sql_query('UPDATE '.sql_table('plugin_pubmed_manuscripts').' SET'.
				' manuscriptname="'.addslashes($mname).'",'.
				' templatename="'.addslashes($template).'"'.
				' WHERE manuscriptid='.(int)$manuscriptid.
				' AND userid='.(int)$mid);
			return $this->manuscriptlist();
		}
		// Get template files
		$templates=array();
		$d=dir(dirname(__FILE__).'/templates/');
		while (false !== ($entry = $d->read())) {
			if (!preg_match('/^(.+)\.php$/',$entry,$m)) continue;
			if ($m[1]!='default') $templates[]=$m[1];
		}
		sort($templates);
		array_unshift($templates,'default');
$d->close(); 
?>
<form method="post" action="">
<input type="hidden" name="action" value="editmanuscript" />
<?php $manager->addTicketHIdden() ?>
<input type="hidden" name="sure" value="yes" />
<input type="hidden" name="manuscriptid" value="<?php echo (int)$manuscriptid; ?>" />
<input type="hidden" name="blogid" value="<?php echo (int)$this->blogid; ?>" />
<table>
<tr><td>Manuscript name:</td>
<td><input type="text" name="manuscriptname" value="<?php echo htmlspecialchars($mname); ?>" /></td></tr>
<tr><td>Template:</td>
<!-- td><input type="text" name="templatename" value="<?php echo htmlspecialchars($template); ?>" /></td></tr -->
<td><select name="templatename">
<?php
		foreach($templates as $temp){
			$temp=htmlspecialchars($temp,ENT_QUOTES);
			echo '<option value="'.$temp.'"'.
				($template==$temp ? ' selected="selected"' : '').
				'>'.$temp."</option>\n";
		}
?>
</select></td>
</tr>
</table>
<input type="submit" value="Edit" />
</form>
<?php
	}
}
?>