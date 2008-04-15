<?php

/*
	NP_Related developed by Sir_Psycho (http://www.fuckhedz.com/)
	           Ver0.25.1 by radek (http://hulan.info/)
	           Ver0.4    by yu (http://nucleus.datoka.jp/)
	
	USAGE
	-----
	Template:
		<%Related(mode,max,snippet,searchcond)%>
	Skin:
		<%Related(mode,max,snippet,,searchcond)%> ... in item/search page
		<%Related(mode,max,snippet,query,searchcond)%>
	
		mode               ... local / google
		max[option]        ... max amount.  default:5
		snippet[option]    ... true / false.  default:false
		query[option]      ... search keyword.  (if in item/search page, it's filled automatically)
		searchcond[option] ... and / or.  default:or
	
	EXAMPLE
	-------
	In item template or item skintype:
	(search skintype is also available)
	<%Related(local,5)%>
	<%Related(google,5,true)%>
	
	In other skintype:
	<%Related(local,5,true,queryword,and)%>
	
	
	HISTORY
	-------
	Ver0.4 2007/02/27:
	[Chg] remove Amazon mode
	[Chg] change to use Ajax Search API instead of Soap Search API (google)
	
	Ver0.32 2006/09/17:
	[Fix] remove tags from url, title, snippet (google)
	[Chg] change name of function soapclient() to soaplient_old() in nusoap.php (for PHP5 reason).
	
	Ver0.31 2005/10/16:
	[Add] option "erase cache data now", "show snippet", "no header", "search range".
	[Chg] delete inline style "font-size:smaller" for snippets.
	[Chg] cancel keyword manupilation for google search.
	[Chg] include multiple keywords on Amazon search.
	[Fix] in google search, invalid max results is set when max results is larger than 5.
	[Fix] version expression in delete style.
	[Fix] 'DONOTSEARCH' output.
	[Fix] encoding keyword for google search - by nakahara21 (http://nakahara21.com/) 
	[Fix] set input encoding to google "more link" - by mao (http://kirsche.mods.jp/catid/6)
	[Fix] convert encoding for Amazon search - by sakuracandle (http://juntwo.s57.xrea.com/)
	[Fix] remove tags from snippet in title attribute - by pushman (http://blog.heartfield-web.com/)

	Ver0.3jp 2004/11/23:
	[Add] Amazon search in books-jp mode.
	[Add] support FancyURLs - by mao (http://kirsche.mods.jp/catid/6)
	[Fix] loop bug when "()" is used in keyword.
	[Fix] debug code remained.

	Main updates before Ver0.3jp ...
	- SkinVar. This plugin can be used in both skin and template.
	- Multi-keyword search.
	- Pharase search (phrase = quoted words).
	- Snippet for local search.
	
*/


// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_Related extends NucleusPlugin {
	
	// name of plugin
	function getName() {
		return 'Related items/sites'; 
	}
	
	// author of plugin
	function getAuthor()  { 
		return 'Tim Broddin + radek + yu'; 
	}
	
	// version of the plugin
	function getVersion() { return '0.4'; }
	function getMinNucleusVersion() { return '250'; }
	
	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL() {
		return 'http://japan.nucleuscms.org/wiki/plugins:related'; 
	}
	
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	
	function getTableList() {
		return array( sql_table('plug_related'), sql_table('plug_related_cache') );
	}
	
	function getEventList() { 
		return array('PostAddItem','PreUpdateItem','AddItemFormExtras','EditItemFormExtras','PostPluginOptionsUpdate'); 
	}
	
	// Let's get started
	function init() {
		global $manager, $blog, $CONF;
		
		// include language file for this plugin
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory().$language.'.php'))
			include_once($this->getDirectory().$language.'.php');
		else
			include_once($this->getDirectory().'english.php');
		
		if ($blog) $b =& $blog; 
		else $b =& $manager->getBlog($CONF['DefaultBlog']);
		$bid = $b->getID();
		
		if ($this->getBlogOption($bid, "googlekey") != '') $this->google_key = $this->getBlogOption($bid, "googlekey");
		else $this->google_key = $this->getOption("googlekey");
		$this->toexclude = $this->getOption("toexclude");
//		$this->amazontoken = $this->getOption("amazontoken");
//		$this->aso_id = $this->getOption("aso_id");

		$this->header_lc = $this->getOption("header_lc");
		$this->header_go = $this->getOption("header_go");
//		$this->header_am = $this->getOption("header_am");
		$this->header_end = $this->getOption("header_end");
		
		$this->list_header = $this->getOption("listheading");
		$this->list_footer = $this->getOption("listfooter");
		$this->item_header = $this->getOption("itemheading");
		$this->item_footer = $this->getOption("itemfooter");

		$this->notitle = $this->getOption("notitle");
		$this->noresults = $this->getOption("noresults");
		$this->flg_noheader = $this->getOption("flg_noheader");
		$this->morelink = $this->getOption("morelink");
		$this->maxlength = $this->getOption("maxlength");
		$this->maxlength2 = $this->getOption("maxlength2");
		$this->flg_snippet = $this->getOption('flg_snippet');
		$this->flg_timelocal = $this->getOption('flg_timelocal');
		$this->currentblog = $this->getOption("currentblog");
		$this->searchrange = $this->getOption("searchrange");
		$this->flg_srchcond_and = $this->getOption("flg_srchcond_and");
		
//		$this->interval = $this->getOption("interval");
		$this->_check_cache_size();
	}
	
	// a description to be shown on the installed plugins listing
	function getDescription() { 
		return _RELATED_MESSAGE_DESC;
	}
	
	// Installation
	function install() {
		$this->createOption("googlekey", _RELATED_OPTION_GOOGLEKEY, "text", "");
		$this->createBlogOption("googlekey", _RELATED_OPTION_GOOGLEKEY, "text", "");
		$this->createOption("toexclude", _RELATED_OPTION_TOEXCLUDE, "text", "yourdomain.com");
//		$this->createOption("amazontoken", _RELATED_OPTION_AMAZONTOKEN, "text", "");
//		$this->createOption("aso_id", _RELATED_OPTION_ASO_ID, "text", "");

		$this->createOption("header_lc", _RELATED_OPTION_HEADER_LC, "text", "<h3>Local search for: <em>");
		$this->createOption("header_go", _RELATED_OPTION_HEADER_GO, "text", "<h3>Google search for: <em>");
//		$this->createOption("header_am", _RELATED_OPTION_HEADER_AM, "text", "<h3>Amazon search for: <em>");
		$this->createOption("header_end",  _RELATED_OPTION_HEADER_END, "text", "</em></h3>");
		$this->createOption("listheading", _RELATED_OPTION_LISTHEADING, "text", "<ul class='related'>\n");
		$this->createOption("listfooter",  _RELATED_OPTION_LISTFOOTER, "text", "</ul>\n");		
		$this->createOption("itemheading", _RELATED_OPTION_ITEMHEADING, "text", "<li>\n");
		$this->createOption("itemfooter",  _RELATED_OPTION_ITEMFOOTER, "text", "</li>\n");

		$this->createOption("notitle",   _RELATED_OPTION_NOTITLE, "text", "(no title)");
		$this->createOption("noresults", _RELATED_OPTION_NORESULTS, "text", "<p>No related items.</p>");
		$this->createOption("flg_noheader", _RELATED_OPTION_FLG_NOHEADER, "yesno", "no");
		$this->createOption("morelink",  _RELATED_OPTION_MORELINK, "text", "and more...");
		$this->createOption("maxlength", _RELATED_OPTION_MAXLENGTH, "text", "60");
		$this->createOption("maxlength2", _RELATED_OPTION_MAXLENGTH2, "text", "220");
		$this->createOption("flg_snippet", _RELATED_OPTION_FLG_SNIPPET, "yesno", "yes");
		$this->createOption("flg_timelocal", _RELATED_OPTION_FLG_TIMELOCAL, "yesno", "no");
		$this->createOption("currentblog", _RELATED_OPTION_CURRENTBLOG, "yesno", "yes");
		$this->createOption('searchrange', _RELATED_OPTION_SEARCHRANGE, 'select', 'type2', 
			'Title|type1|Title, Body|type2|Title, Body, More|type3');
		$this->createOption("flg_srchcond_and", _RELATED_OPTION_FLG_SRCHCOND_AND, "yesno", "no");

//		$this->createOption("interval",  _RELATED_OPTION_INTERVAL, "text", "96");
//		$this->createOption("language", _RELATED_OPTION_LANGUAGE, "text", "lang_ja|lang_en");
		$this->createOption("flg_cache_erase", _RELATED_OPTION_FLG_CACHE_ERASE, "yesno", "no");
		$this->createOption("flg_erase", _RELATED_OPTION_FLG_ERASE, "yesno", "no");

		mysql_query("CREATE TABLE IF NOT EXISTS ". sql_table("plug_related") 
			." ( 
			itemid INT(9) NOT NULL, 
			localkey VARCHAR(255) NOT NULL DEFAULT '', 
			googlekey VARCHAR(255) NOT NULL DEFAULT '', 
			amazonkey VARCHAR(255) NOT NULL DEFAULT '', 
			mode VARCHAR(100) NOT NULL DEFAULT '', 
			PRIMARY KEY (itemid)
			)");
		mysql_query("CREATE TABLE IF NOT EXISTS ". sql_table("plug_related_cache") 
			." ( 
			id INT(9) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
			type VARCHAR(255) NOT NULL, 
			keyword VARCHAR(255) NOT NULL, 
			rank INT(9) NOT NULL, 
			url VARCHAR(255) NOT NULL, 
			title VARCHAR(255) NOT NULL, 
			stamp VARCHAR(14) NOT NULL, 
			snippet VARCHAR(255) 
			)");
	}
	
	function uninstall() {
		if ($this->getOption('flg_erase') == 'yes') {
			mysql_query ( "DROP table IF EXISTS ". sql_table("plug_related") );
			mysql_query ( "DROP table IF EXISTS ". sql_table("plug_related_cache") );
		}
	}

	function event_PostPluginOptionsUpdate($data) {
		if ($this->getOption('flg_cache_erase') == 'yes') {
			sql_query("TRUNCATE TABLE ". sql_table("plug_related_cache"));
			$this->setOption('flg_cache_erase', 'no');
			return;
		}
	}

	//Add options to add item form/bookmarklet
	function event_AddItemFormExtras($data) {
		?>
			<h3>Related Keyword</h3>
			<p>
				<label for="related_local">Local keyword(s):</label>
				<input type="text" value="" id="related_local" name="local_keyword" size="60" />
			</p>
			<p>
				<label for="related_google">Google keyword(s):</label>
				<input type="text" value="" id="related_google" name="google_keyword" size="60" />
			</p>
		<?php
	}
	
	//Add options to edit item form/bookmarklet
	function event_EditItemFormExtras($data) {
			$id = $data['variables']['itemid'];
			$result = mysql_query("SELECT itemid, localkey, googlekey, amazonkey, mode FROM ". sql_table("plug_related"). " WHERE itemid='$id'");
			if (@mysql_num_rows($result) > 0) {
				$localkey  = mysql_result($result,0,"localkey");
				$googlekey = mysql_result($result,0,"googlekey");
			}
		?>
			<h3>Related Keyword</h3>
			<p>
				<label for="related_local">Local keyword(s):</label>
				<input type="text" value="<?php echo htmlspecialchars($localkey) ?>" id="related_local" name="local_keyword" size="60" />
			</p>
			<p>
				<label for="related_google">Google keyword(s):</label>
				<input type="text" value="<?php echo htmlspecialchars($googlekey) ?>" id="related_google" name="google_keyword" size="60" />
			</p>
		<?php
	}
	
	//PostAddItem Event
	function event_PostAddItem($data) {
		$local  = requestVar('local_keyword');
		$google = requestVar('google_keyword');
		$amazon = requestVar('amazon_keyword');
		
		// Nothing to do? Get out!!
		if ((!$local) && (!$google) && (!$amazon)) return;
		
		$itemid = $data['itemid'];
		
		$local  = mysql_escape_string($local);
		$google = mysql_escape_string($google);
		$amazon = mysql_escape_string($amazon);
		
		mysql_query("INSERT INTO ". sql_table("plug_related") ." VALUES ('$itemid','$local','$google','$amazon','')");
	}
	
	//PreUpdateItem Event
	function event_PreUpdateItem($data) {
		$local  = requestVar('local_keyword');
		$google = requestVar('google_keyword');
		$amazon = requestVar('amazon_keyword');
		
		$itemid = $data['itemid'];
		
		$local  = mysql_escape_string($local);
		$google = mysql_escape_string($google);
		$amazon = mysql_escape_string($amazon);
		
		$result = mysql_query("SELECT * FROM ". sql_table("plug_related") ." WHERE itemid='$itemid'");
		
		if (@mysql_num_rows($result) > 0) {
			// Nothing to do? Delete it!!
			if ((!$local) && (!$google) && (!$amazon)) {
				mysql_query("DELETE FROM ". sql_table("plug_related") ." WHERE itemid='$itemid'");
				return;
			}
			
			mysql_query("UPDATE ". sql_table("plug_related") ." SET localkey='$local',googlekey='$google',amazonkey='$amazon' WHERE itemid='$itemid'");
			
		} else {
			// Nothing to do? Get out!!
			if ((!$local) && (!$google) && (!$amazon)) return;
			mysql_query("INSERT INTO ". sql_table("plug_related") ." VALUES ('$itemid','$local','$google','$amazon','')");
		}		
	}
	
	// Skinvar Wrapper
	function doSkinVar($skinType, $mode='local', $max='5', $showsnippet='', $skinquery='', $searchcond='') {
		global $manager, $itemid;
		
		if ($skinType == 'item') {
			$item =& $manager->getItem($itemid,0,0);
		}
		else if ($skinquery != '') {
			$item = array(
					'itemid' => 0, //dummy
					'title' => $skinquery,
				);
		}
		else if ($skinType == 'search') {
			$item = array(
					'itemid' => 0, //dummy
					'title' => requestVar('query'),
				);
		}
		else {
			return;
		}
		
		$this->doTemplateVar($item, $mode, $max, $showsnippet, $searchcond, $skinType);
	}
	
	// Handle Related Items
	function doTemplateVar(&$item, $mode='local', $max='5', $showsnippet='', $searchcond='', $skinType='item') {
		global $manager, $blog, $CONF;
		
		if ($showsnippet == '') $showsnippet = $this->flg_snippet;
		if ($showsnippet == 'true' or $showsnippet == 'yes') $showsnippet = true;
		else if ($showsnippet == 'false' or $showsnippet == 'no') $showsnippet = false;
		$this->showsnippet = $showsnippet;
		
		if($blog){
			$b =& $blog; 
		}else{
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		if (is_object($item)) $item = get_object_vars($item);
		$max = intval($max);
		
		//$del_char['local']  = array("\\", '/');
		$del_char['google'] = array('-', '+');
		//$del_char['amazon'] = array('!','?');
		$del_style   = array("/(ver|version)[0-9.]+[0-9a-z.]*$/i", "/\.$/", "/-[0-9]+-$/");
		$quote_style = _RELATED_REGEXP_QUOTESTYLE;
		
		$websvc_url['google'] = "http://api.google.com/search/beta2";
		$websvc_url['amazon'] = "http://soap.amazon.co.jp/schemas3/AmazonWebServices.wsdl"; //E amazon.com
		
		switch($mode) {
			
			//
			// Related LOCAL items
			//
			
			case 'local':
				$q = '';
				$id = $item['itemid'];
				$result = mysql_query("SELECT localkey FROM ". sql_table("plug_related") ." WHERE itemid='$id'");
				if ($msg = mysql_fetch_array($result)) {
					if ($msg['localkey'] == "DONOTSEARCH") $donotsearch = true;
					else $q = $msg['localkey'];
				}
				
				// Is there a keyword present?
				if ($q == "") { 
					$q = strip_tags($item['title']);
				}
				
				if ($donotsearch) {
					if ($this->flg_noheader == 'yes') return;
					$this->_show_header($mode, $q);
					echo $this->noresults;
					return;
				}
				else if ($q == ""){
					if ($this->flg_noheader == 'yes') return;
					$this->_show_header($mode, '(No words)');
					echo $this->noresults;
					return;
				}
				
				// prepare for multi-word search
				$q = trim($q);
				$dispq = $q;
				$str_where = '';
				$ary_modq = array();
				
				// quoted words
				$qt_num = 0;
				$ary_quote = array();
				while ( preg_match($quote_style, $q, $quoted_keys) ) {
					$qlastidx = count($quoted_keys) -1;

//E					if (preg_match("/^[0-9]+$/", $quoted_keys[$qlastidx]) ) { 
					if (preg_match("/^[0-9]+$/", mb_convert_kana($quoted_keys[$qlastidx], 'n', _CHARSET)) ) { 
						// delete series num
						$q = preg_replace("/". preg_quote($quoted_keys[0]) ."/", '', $q);
						continue;
					}
					$qrep = "__QUOTED{$qt_num}__";
					
					// add comma around a quote for splitting
					$ary_quote[$qt_num][0] = stripslashes($quoted_keys[0]); // use first match(with quote chars)
					$ary_quote[$qt_num][1] = stripslashes($quoted_keys[$qlastidx]); //use last(without quote chars)
					$q = preg_replace("/". preg_quote($quoted_keys[0]) ."/", ",$qrep,", $q);
					$qt_num ++;
				}
				
				// split and make multi keywords
				$q = mb_convert_kana($q, 's', _CHARSET);
				$ary_q = preg_split(_RELATED_REGEXP_DELIMITER, $q, -1, PREG_SPLIT_NO_EMPTY);
				
				// set search condition type
				if (strtoupper($searchcond) == 'AND' ||
					strtoupper($searchcond) == 'OR') $qcat = $searchcond;
				else if ($this->flg_srchcond_and == 'yes') $qcat = 'AND';
				else $qcat = 'OR';
				
				foreach ($ary_q as $qpiece) {
					if (preg_match("/^__QUOTED([0-9]+)__$/", $qpiece, $qmatch)) {
						$ary_modq[] = $ary_quote[$qmatch[1]][0]; // with quote chars
						$qpiece = $ary_quote[$qmatch[1]][1];  // without quote chars
					}
					else {
						$qpiece = preg_replace($del_style, '', $qpiece);
						if (mb_strlen($qpiece,_CHARSET) < 2) continue; // skip if the key is one letter
						$ary_modq[] = $qpiece;
					}
					
					$qpiece = mysql_escape_string($qpiece);
					
					$str_cat = ($str_where) ? " $qcat " : '';
					
					switch ($this->searchrange) {
						case 'type1':
							$str_where .= $str_cat ."( ititle LIKE '%$qpiece%' )";
 							break;
						case 'type2':
							$str_where .= $str_cat ."( ititle LIKE '%$qpiece%' OR ibody LIKE '%$qpiece%' )";
 							break;
						case 'type3':
							$str_where .= $str_cat ."( ititle LIKE '%$qpiece%' OR ibody LIKE '%$qpiece%' OR imore LIKE '%$qpiece%' )";
 							break;
					}
					
					if (count($ary_modq) == 3) break; // max 3 words
				}
				$qmore = join($ary_modq, ' '); // for 'and more' query link
				
				// Select only from same weblog?
				if ($this->currentblog == 'yes' and $skinType == 'item') {
					$result = mysql_query("SELECT iblog FROM ". sql_table("item") ." WHERE inumber='$item[itemid]'");
					$msg = mysql_fetch_array($result);
					$bid = $msg['iblog'];
					$str_iblog = " AND iblog='$bid'";
				} else {
					$str_iblog = '';
				}
				$result = mysql_query("SELECT inumber, ititle, itime, ibody FROM ". sql_table("item") 
					." WHERE ($str_where)" . $str_iblog
					." AND idraft=0 AND inumber<>'$id'" 
					." AND itime<=" . mysqldate($b->getCorrectTime())
					." ORDER BY inumber DESC LIMIT 0,$max");
				
				// Do we have any rows?
				if (@mysql_num_rows($result) > 0) {
					$this->_show_header($mode, $qmore);
				
					$first=true;
					while ($row = mysql_fetch_object($result)) {
						
						if ($first){
							$first=false; 
							echo $this->list_header;
						}
						
						// prepare
						if (empty($row->ititle)) $title = $this->notitle;
						else $title = shorten(strip_tags($row->ititle),$this->maxlength,'...');
						$itime = "[$row->itime]";
						$snippet = shorten(strip_tags($row->ibody),$this->maxlength2,'...');
						
						$iid = $row->inumber;
						$bid = getBlogIDFromItemID($iid);
						$b_tmp =& $manager->getBlog($bid);
						$blogurl = $b_tmp->getURL() ;
						if(!$blogurl){ 
							$blogurl = $this->defaultblogurl; 
						} 
						if ($CONF['URLMode'] == 'pathinfo'){ 
							if(substr($blogurl, -1) != '/') 
							$blogurl .= '/';
							$url = $blogurl .'item/'. $iid;
						}
						else {
							$url = createItemLink($iid);
						}
						
						$this->_show_list($mode, $url, $title, $snippet, $itime);
					}
					
					$this->_show_morelink($mode, $qmore, $b->getID());
					
					if (!$first) echo $this->list_footer;
				} 
				else {
					if ($this->flg_noheader == 'yes') return;
					$this->_show_header($mode, $qmore);
					echo $this->noresults;
				}
			break;
			
			//
			// Related GOOGLE sites
			//
			
			case 'google':
			
				$q = '';
				$id = $item['itemid'];
				if ($max > 10) $max = 10;
				$apikey = $this->google_key;
				
				$result = mysql_query("SELECT googlekey FROM ". sql_table("plug_related") ." WHERE itemid='$id'");
				if ($msg = mysql_fetch_array($result)) {
					if ($msg['googlekey'] == "DONOTSEARCH") $donotsearch = true;
					else $q = $msg['googlekey'];
				} 
				
				// Search keyword if no Q is found
				if ($q == ""){
					$q = strip_tags($item['title']);
					$q = str_replace($del_char[$mode], '', $q);
				}
				
				if ($donotsearch) {
					if ($this->flg_noheader == 'yes') return;
					$this->_show_header($mode, $q);
					echo $this->noresults;
					return;
				}
				else if ($q == "") {
					if ($this->flg_noheader == 'yes') return;
					$this->_show_header($mode, '(No words)');
					echo $this->noresults;
					return;
				}
				
				$q = mb_convert_kana($q, 's', _CHARSET); //E comment out
				$q = trim($q);
				$dispq = $q;
				if ($this->toexclude != '') $q .= " -site:". $this->toexclude;
				$q = mysql_escape_string($q);
				
				$this->_show_header($mode, $dispq);
				echo <<<EOS
<style type="text/css">
	@import "http://www.google.com/uds/css/gsearch.css";
	.gsc-control { width: auto; }
</style>
<script src="http://www.google.com/uds/api?file=uds.js&amp;v=1.0&amp;key=$apikey" type="text/javascript"></script>
<script language="Javascript" type="text/javascript">
//<![CDATA[

function OnLoad() {
	// Create a search control
	var searchControl = new GSearchControl();
	
	searchControl.addSearcher(new GwebSearch());
	searchControl.addSearcher(new GblogSearch());
	//searchControl.addSearcher(new GvideoSearch());
	
	// Tell the searcher to draw itself and tell it where to attach
	searchControl.draw(document.getElementById("searchcontrol"));
	
	// Execute an inital search
	searchControl.execute("$q");
}
GSearch.setOnLoadCallback(OnLoad);

//]]>
</script>
<div id="searchcontrol"></div>
EOS;
			break;
		} //end of switch
	}

	// Custom functions
	
	function _make_stamp() {
		return strtotime ("now");
	}
	
	function _check_cache_size() {
		// We don't have to check this every time. By creating a random number between 0 and 50 we can reduce
		// server load (I guess?)
		
		$rand =  mt_rand (0,50);
		if ($rand == 50) {
			$cache = sql_query("SELECT * FROM ". sql_table("plug_related_cache"));
			if (@mysql_num_rows($cache) > 2000) {
				sql_query("TRUNCATE TABLE ". sql_table("plug_related_cache"));
			}
		}
	}

	function _show_header($mode, $q) {
		switch ($mode) {
			case "local":
				echo $this->header_lc .$q. $this->header_end;
				break;
			case "google":
				echo $this->header_go .$q. $this->header_end;
				break;
			case "amazon":
				echo $this->header_am .$q. $this->header_end;
				break;
		}
	}

	function _show_list($mode, $url, $title, $snippet='', $time='') {
		echo "\n" . $this->item_header;
		
		switch ($mode) {
			case "local":
				if ($this->showsnippet) {
					if ($this->flg_timelocal == 'yes') 
						echo '<a href="'. $url .'">'. $title .' '. $time .'</a>';
					else 
						echo '<a href="'. $url .'" title="'. $time .'">'. $title .'</a>';
					echo '<br /> <span class="iteminfo">'. $snippet .'</span>';
				}
				else {
					if ($this->flg_timelocal == 'yes') 
						echo '<a href="'. $url .'" title="'. $snippet .'">'. $title .' '. $time .'</a>';
					else 
						echo '<a href="'. $url .'" title="'. $snippet . $time .'">'. $title . '</a>';
				}
				break;
				
			case "google":
			case "amazon":
				if ($this->showsnippet) {
					echo '<a href="'. $url .'" target="_blank">'. $title .' </a>';
					echo '<br /> <span class="iteminfo">'. $snippet .'</span>';
				}
				else {
					echo '<a href="'. $url .'" title="'. $snippet .'" target="_blank">'. $title .' </a>';
				}
				break;
		}
		
		echo $this->item_footer;
	}

	function _show_morelink($mode, $q, $extra='') {
		global $CONF;
		
		if ($this->morelink == '') return;
		
		echo "\n". $this->item_header;
		switch ($mode) {
			case 'local':
				$bid = $extra;
				if ($CONF['URLMode'] == 'pathinfo'){
					$moreurl = $CONF['BlogURL'].'?amount=0&amp;query='. urlencode($q) .'&blogid='.$bid;
				}
				else {
					$moreurl = createBlogidLink($bid) . '&amp;amount=0&amp;query='. urlencode($q);
				}
				echo '<a href="' . $moreurl . '" title="'. _RELATED_MSG_JUMP_LC .'">'
					. $this->morelink.'</a>';
				break;
				
			case 'google':
				$moreurl = 'http://www.google.com/search?hl=ja&amp;ie='. _CHARSET //E 'hl=en'
					.'&amp;q='. urlencode(stripslashes($q)) .'&lr=';
				echo '<a href="' . $moreurl . '" target="_blank" title="'. _RELATED_MSG_JUMP_GO .'">' 
					. $this->morelink.'</a>';
				break;
		}
		echo $this->item_footer;
	}

}
?>
