<?php

require('../../../config.php');

// An instance will be created at the end of this file

class PubMedAdmin extends BaseActions {
	var $oPluginAdmin,$plugin;
	var $blogid;
	function __construct(){
		if (method_exists($this,'BaseActions')) $this->BaseActions();
	
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
		// Therefore, POST method must be used to change important parameter(s).
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
				
		// Take actions.
		// All the method that starts from a-z can be action.
		// Method that starts from _ is private method.
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
		call_user_func(array(&$this,"action_$action"));
		$this->oPluginAdmin->end();
	}
/*
 * Getenal parse routines follow
 */
	private $contents=array();
	private function _getTemplate($name,$type='body'){
		static $templates;
		if (!isset($templates)) {
			// Prepare template data
			$xml=simplexml_load_file(dirname(__FILE__).'/index.xml');
			$templates=array();
			foreach($xml->template as $obj) {
				$templates[(string)$obj->name]==array();
				foreach($obj as $key=>$value) $templates[(string)$obj->name][$key]=(string)$value;
			}
		}
		if ($type=='array') return $templates[$name];
		elseif (isset($templates[$name][$type])) return $templates[$name][$type];
		else return '';
	}
	private function _parse($template){
		static $parser;
		if (!isset($parser)){
			$actions=array('note','blogid','ticket','hsc','stg','int','raw','conf',
				'postvar','getvar','self',
				'if','ifnot','else','elseif','elseifnot','endif');
			$parser =& new PARSER($actions, $this);
		}
		$parser->parse($template);
	}
	private function template_parse($tempname,$data=false,$type='body'){
		$template=$this->_getTemplate($tempname,'array');
		$contents=$this->contents;
		if ($data) $this->contents=$data;
		$this->_parse($template[$type]);
		$this->contents=$contents;
	}
	private function _showUsingArray($tempname,$array){
		$this->template_parse($tempname,false,'head');
		foreach($array as $row) $this->template_parse($tempname,$row);
		$this->template_parse($tempname,false,'foot');
	}
	public function parse_note(){
		// Don't do anythig.
	}
	public function parse_blogid(){
		echo (int)$this->blogid;
	}
	public function parse_ticket(){
		global $manager;
		$manager->addTicketHidden();
	}
	public function parse_hsc($key){
		self::_hsc($this->contents[$key]);
	}
	public function parse_stg($key){
		self::_hsc(strip_tags($this->contents[$key]));
	}
	public function parse_int($key){
		echo (int)$this->contents[$key];
	}
	public function parse_raw($key){
		echo $this->contents[$key];
	}
	public function parse_conf($key){
		global $CONF;
		self::_hsc($CONF[$key]);
	}
	public function parse_getvar($key){
		self::_hsc(getVar($key));
	}
	public function parse_postvar($key){
		self::_hsc(postVar($key));
	}
	public function parse_self(){
		self::_hsc($this->plugin->getAdminURL());
	}
	static private function _hsc($text){
		echo htmlspecialchars($text,ENT_QUOTES,_CHARSET);
	}
	protected function checkCondition($key,$value=false){
		if ($value===false) return $this->contents[$key] ? 1 : 0;
		else return $this->contents[$key]==$value ? 1 : 0;
	}
/* Pubmed Search */
	private function action_searchform(){
		$this->template_parse('searchform');
	}
	private function action_searchquery(){
		global $manager,$CONF;
		$this->action_searchform();
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
		$contents=$this->_getNestedXmlData($contents,'IdList','');
		if (!strstr($contents,'<Id>')) {
			$this->template_parse('noresult');
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
		fclose($fhandle);
		
		// Get summaries
		// Header
		foreach($result as $key=>$value) $result[$key]=htmlspecialchars($value,ENT_QUOTES);
		echo '<table>';
		// Navigation bar
		ob_start();
		$this->template_parse('searchresultth',array(
			'start'=>$result['start'],
			'nextexists'=>$result['start']+$max<=$result['count'],
			'prev'=>$result['start']-$max,
			'next'=>$result['start']+$max,
			'max'=>$max,
			'page'=>(int)(1+($result['start']/$max)),
			'pagemax'=>(int)(1+($result['count']-1)/$max) ));
		$tableth=ob_get_contents(); // Will  be used later to show the same th navigation bar.
		ob_end_flush();
		
		// Prepare select tag for category selection.
		$defcatid=(int)cookieVar($CONF['CookiePrefix'] . 'NP_PubMed_defcatid');
		if (!$defcatid) $defcatid=quickQuery('SELECT bdefcat as result FROM '.sql_table('blog').' WHERE bnumber='.(int)$CONF['DefaultBlog']);
		$res=sql_query('SELECT * FROM '.sql_table('category').
			' WHERE cblog='.(int)$this->blogid.' ORDER BY cname ASC');
		$array=array();
		while($row=mysql_fetch_assoc($res)){
			$row['selected']=($row['catid']==$defcatid);
			$array[]=$row;
		}
		ob_start();
		$this->_showUsingArray('selectcategory',$array);
		$categories=ob_get_contents();
		ob_end_clean();
		
		// Show the results
		if (!($contents=$this->_getNestedXmlData($contents,'eSummaryResult',''))) exit('Error: '.__LINE__);
		$contents=explode('<DocSum>',$contents);
		$array=array();
		foreach($contents as $summary){
			if (strpos($summary,'</DocSum>')===false) continue;
			$row=array();
			$row['date']=$this->_getXmlData($summary,'Item','???','Name="PubDate"');
			$row['journal']=$this->_getXmlData($summary,'Item','???','Name="Source"');
			$row['title']=$this->_getXmlData($summary,'Item','???','Name="Title"');
			$row['volume']=$this->_getXmlData($summary,'Item','???','Name="Volume"');
			$row['pages']=$this->_getXmlData($summary,'Item','???','Name="Pages"');
			$row['pmid']=$this->_getXmlData($summary,'Id','???');
			$row['authors']='???';
			if ($num=preg_match_all('!<Item[\s]+Name="Author"[^>]*>([^<]+)</Item>!',$summary,$matches,PREG_SET_ORDER)){
				for ($i=0;$i<$num;$i++) {
					switch($i){
					case 0:
						$row['authors']=htmlspecialchars($matches[$i][1]);
						break;
					case 1:case 2:case 3:case 4:
					case ($num-1):
						$row['authors'].=', '.htmlspecialchars($matches[$i][1]);
						break;
					case 5:
						$row['authors'].=', ... ';
					default:
						break;
					}
				}
			}
			if (array_key_exists((int)$row['pmid'],$dataexists)) {
				$row['addbutton']=0;
				$row['itemurl']=createItemLink($dataexists[(int)$row['pmid']], '');
			} else {
				$row['addbutton']=1;
				$row['categories']=$categories;
			}
			$array[]=$row;
		}
		$this->_showUsingArray('searchresulttd',$array);
		
		// Navigation bar and Footer
		echo $tableth;
		echo '</table>';
	}
	private function _getXmlData(&$xml,$tag,$default='???',$extra=''){
		if (preg_match('!<'.$tag.'[^>]*'.$extra.'[^>]*>([^<]+)</'.$tag.'>!',$xml,$matches)){
			return $matches[1];
		} else return $default;
	}
	private function _getNestedXmlData(&$xml,$tag,$default='???'){
		if (preg_match('!<'.$tag.'[^>]*>([\s\S]+)</'.$tag.'>!',$xml,$matches)){
			return $matches[1];
		} else return $default;
	}
	private function _url_open($url){
		return $this->oPluginAdmin->plugin->_url_open($url);
	}

/* Manunscript Management */
	private function action_manuscriptlist(){
		global $member;
		$res=sql_query('SELECT * FROM '.sql_table('plugin_pubmed_manuscripts').
			' WHERE userid='.(int)$member->getID());
		$array=array();
		while($row=mysql_fetch_assoc($res)) $array[]=$row;
		$this->_showUsingArray('manuscriptlist',$array);
		return;
	}
	
	private function action_deletemanuscript(){
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
			$this->template_parse('deletemanuscript',array('mname'=>$mname),'notice');
			return $this->action_manuscriptlist();
		}
		$this->template_parse('deletemanuscript',array('mid'=>$manuscriptid,'mname'=>$mname));
	}
	
	private function action_createmanuscript(){
		global $member,$manager;
		$mid=$member->getID();
		$mname=$this->_checkmanuscriptname(postVar('manuscriptname'));
		if ($mname) sql_query('INSERT INTO '.sql_table('plugin_pubmed_manuscripts').' SET'.
				' userid='.(int)$mid.','.
				' manuscriptname="'.addslashes($mname).'"'.
				' sorttext="authorname"');
		return $this->action_manuscriptlist();
	}
	private function _getSortMethod($tempname){
		// Note that $tempname is valid once.
		static $ret;
		if (isset($ret)) return $ret;
		require_once(dirname(__FILE__).'/template.php');
		$tobj=PUBMED_TEMPLATE_BASE::getTemplate($tempname);
		if (!$tobj) return false;
		$tobj->setSortText('');
		$tobj->sortPapers();
		if ($tobj->getSortText()=='authorname') $ret='authorname';
		else $ret='manual';
		return $ret;
	}
	private function _checkmanuscriptname($mname,$id=0){
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
	
	private function action_editmanuscript(){
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
			$sorttext=postVar('sorttext');
			$sortmethod=$this->_getSortMethod($template);
			if ($sortmethod) {
				if ($sortmethod=='authorname') $sorttext='authorname';
				sql_query('UPDATE '.sql_table('plugin_pubmed_manuscripts').' SET'.
					' manuscriptname="'.addslashes($mname).'",'.
					' templatename="'.addslashes($template).'",'.
					' sorttext="'.addslashes($sorttext).'"'.
					' WHERE manuscriptid='.(int)$manuscriptid.
					' AND userid='.(int)$mid);
				$this->template_parse('editmanuscript',array('mname'=>$mname),'notice');
			} else {
				echo "<b>The template '".htmlspecialchars($template)."' does not exist.</b>";
			}
			return $this->action_manuscriptlist();
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
		
		// Show using array
		$array=array();
		foreach($templates as $temp) $array[]=array('template'=>$temp,'selected'=>$template==$temp);
		$this->contents=array('mid'=>$manuscriptid,'mname'=>$mname);
		$this->_showUsingArray('editmanuscript',$array);
	}
}

new PubMedAdmin;
