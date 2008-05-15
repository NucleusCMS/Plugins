<?php 
/*
	NP_LinkCounter
	by yu (http://nucleus.datoka.jp/)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
	
	USAGE
	-----
	In item:
	<%media(file path|link text)%> //auto count mode
	<%media(file path|link text|linkcnt=KEYWORD)%> //set original keyword
	<%LinkCounter(link,KEYWORD,URL,linktext,target prop,title prop)%>
	<%LinkCounter(total,KEYWORD)%>
	
	Others:
	<%LinkCounter(link,KEYWORD,URL,linktext,target prop,title prop)%>
	<%LinkCounter(total,KEYWORD)%>
	
	
	HISTORY
	-------
	2008/05/14	Ver 0.4  : [Chg] Unsupport <a linkcnt="*keyword*"> and <#linkcnt_total(*keyword*)#>.
	                       [Add] Support <%LinkCounter()%> in item.
	                       [Add] Option "Keyword of feeds skin name" (exkey).
	2006/11/21	Ver 0.32 : [Fix] Security fix. 
	2006/09/30	Ver 0.31 : [Fix] Security fix. 
	2004/08/12	Ver 0.3  : [Chg] Shorten linkcountURL, and [Add] Auto count mode for media tag. 
	2004/04/14	Ver 0.2  : [Add] skin description, and total count. 
	2004/02/16	Ver 0.1  : First release. 
*/

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')) {
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

// quote variable to make safe
if(!function_exists('quote_smart')) {
}

class NP_LinkCounter extends NucleusPlugin { 
	function getName()      { return 'Link Counter'; } 
	function getAuthor()    { return 'yu'; } 
	function getURL()       { return 'http://works.datoka.jp/'; } 
	function getVersion()   { return '0.4'; } 
	function getMinNucleusVersion() { return 200; }
	function getTableList() { return array( sql_table('plug_linkcounter') ); }
	function getEventList() { return array( 'PreItem' ); }
	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function getDescription() { 
		return 'Link counter. [USAGE] mediavar - <%media(file|text|linkcnt=keyword)%> '.
			'or itemvar - <%LinkCounter(link,url,linktext,target,title)%> '.
			'or <%LinkCounter(total,keyword)%>';
	} 


	function install(){ 
		sql_query ("CREATE TABLE IF NOT EXISTS ". sql_table('plug_linkcounter') ." (
			lkey VARCHAR(64)  NOT NULL,
			cnt  INT UNSIGNED NOT NULL DEFAULT 1,
			url  VARCHAR(255) NOT NULL DEFAULT '',
			primary key (lkey))");
		
		$this->createOption('tpl_cnt',   'Counter Template.', 'text', '[$cnt$word]');
		$this->createOption('tpl_word1', 'Unit word for template (singlar form).', 'text', 'click');
		$this->createOption('tpl_word2', 'Unit word for template (plural form).', 'text', 'clicks');
		$this->createOption('flg_auto',  'Auto count mode for media tag (no need to add "linkcnt" property).', 'yesno', 'yes');
		$this->createOption('exkey',     'Keyword of feeds skin name (invalidate showing counter for XML syndication).' ,'text','feeds');
		$this->createOption('flg_erase', 'Erase data on uninstall.', 'yesno', 'no');
	} 
	
	function unInstall() { 
		if ($this->getOption(flg_erase) == 'yes') {
			sql_query ('DROP TABLE '. sql_table('plug_linkcounter') );
		}
	} 
	
	
	function init() {
		$this->tpl_cnt   = $this->getOption('tpl_cnt');
		$this->tpl_word1 = $this->getOption('tpl_word1');
		$this->tpl_word2 = $this->getOption('tpl_word2');
		$this->flg_auto  = ($this->getOption('flg_auto') == 'yes');
		$this->exkey     = $this->getOption('exkey');
		
		$query = "SHOW TABLES LIKE '". sql_table('plug_linkcounter') ."'";
		$table = sql_query($query);
		if (mysql_num_rows($table) > 0){
			$query = "SELECT * FROM ". sql_table('plug_linkcounter');
			$res = sql_query($query);
			while ($link = mysql_fetch_object($res)) { //copy all data
				$this->link[$link->lkey]['cnt'] = intval($link->cnt);
				$this->link[$link->lkey]['url'] = stripslashes($link->url);
			}
		}
	}
	
	
	function doSkinVar($skinType, $mode='total', $key='', $url='', $linktext='', $target='', $title='') {
		global $CONF;
		
		if ($mode == 'link' and $key) {
			$cnt = $this->link[$key]['cnt'];
			
			$retlink = $this->_make_link($key, $url, $linktext, $target, $title);
			$retcnt  = $this->_make_counter($cnt);
			
			print $retlink.$retcnt;
		}
		else if ($mode == 'total' and $key) {
			$cnt = $this->_get_total($key);
			$retcnt  = $this->_make_counter($cnt);
			print $retcnt;
		}
	}
	
	function doItemVar(&$item, $mode='total', $key='', $url='', $linktext='', $target='', $title='') {
		$this->doSkinVar('', $mode, $key, $url, $linktext, $target, $title);
	}
	
	function doTemplateVar(&$item, $mode='total', $key='', $url='', $linktext='', $target='', $title='') {
		$this->doSkinVar('', $mode, $key, $url, $linktext, $target, $title);
	}
	
	
	function event_PreItem($data) { 
		// prepare
		$tgt  = '/<%media\((.+?)\)%>/';
		
		// convert to linkcounter
		$obj = &$data["item"];
		$this->authorid = $obj->authorid;
		$obj->body = preg_replace_callback($tgt, array(&$this, 'makelink_callback'), $obj->body); 
		$obj->more = preg_replace_callback($tgt, array(&$this, 'makelink_callback'), $obj->more); 
	} 
	
	
	function doAction($type) {
		global $CONF;
		
		switch($type) {
			case 'c':
				$key = urldecode(getVar('k'));
				$url = getVar('url');
				
				if ($this->link[$key]['cnt']) {
					$query = sprintf("UPDATE %s SET cnt=%d WHERE lkey=%s",
						sql_table('plug_linkcounter'),
						$this->link[$key]['cnt'] +1,
						$this->quote_smart($key) );
					if (!$url) $url = $this->link[$key]['url']; // get url from db (that was first recorded)
				}
				else {
					if (!$url) $url = serverVar('HTTP_REFERER');
					$url = preg_replace('|[^a-z0-9-~+_.?#=&;,/:@%]|i', '', $url);
					$query = sprintf("INSERT INTO %s SET lkey=%s, cnt=1, url=%s",
						sql_table('plug_linkcounter'),
						$this->quote_smart($key),
						$this->quote_smart($url) );
				}
				sql_query($query);
				
				redirect($url);
				break;
			default:
				redirect( serverVar('HTTP_REFERER') );
				break;
		}
	}
	
	
	function makelink_callback($m) {
		global $CONF;
	
		$mcnt = count($m);
		
		if ($mcnt == 2) { // media var
			$mvar = explode('|', $m[1]);
			if (!$mvar[2]) { // no extra property
				if (!$this->flg_auto) return $m[0]; // return as it is
				list($key, $tgt, $tit, $linktext) = array($mvar[0], '', '', $mvar[1]);
			}
			else {
				$lc = split('linkcnt=', $mvar[2]);
				if (!$lc[1]) { // no linkcnt property
					return $m[0]; // return as it is
				}
				list($key, $tgt, $tit, $linktext) = array($lc[1], '', '', $mvar[1]);
			}
			
			if ( strstr($mvar[0], '/') ) $memberdir = '';
			else $memberdir = $this->authorid . '/';
			$url = $CONF['MediaURL'] . $memberdir . $mvar[0];
		}
		else return $m[0]; //invalid match. return as it is
		
		$retlink = $this->_make_link($key, $url, $linktext, $tgt, $tit);
		$cnt = $this->link[$key]['cnt'];
		$retcnt  = $this->_make_counter($cnt);
		return $retlink . $retcnt;
	}
	
	
	//helper function
	function _make_link($key, $url, $linktext, $tgt, $tit) {
		global $CONF;
		
		$base = $CONF['ActionURL'] .'?action=plugin&amp;name=LinkCounter&amp;type=c';
		
		//compare urls
		$saved_url = $this->link[$key]['url'];
		if ($saved_url and $url == $saved_url) 
			$url = ''; // it omits url parameter to make short url
		
		$key = urlencode($key);
		
		if ($url) $urlstr = "&amp;url=$url";
		if ($tgt) $tgtstr = " target='$tgt'";
		if ($tit) $titstr = " title='{$tit}'";
		$retlink = "<a href='{$base}&amp;k={$key}{$urlstr}'{$tgtstr}{$titstr}>$linktext</a>";
		
		return $retlink;
	}
	
	function _make_counter($cnt) {
		global $currentSkinName;
		
		if (strpos($currentSkinName, $this->exkey) !== false) return '';
		
		$tpl  = $this->tpl_cnt;
		if ($cnt <= 1) $word = $this->tpl_word1;
		else $word = $this->tpl_word2;
		$ary_target  = array('$cnt',    '$word');
		$ary_replace = array( (int)$cnt, $word);
		$retcnt = str_replace($ary_target, $ary_replace, $tpl);
		
		return $retcnt;
	}

	function _get_total($key) {
		$key = $this->quote_smart('%'.$key.'%');
		
		// total count
		$query = "SELECT SUM(cnt) AS cnt FROM ". sql_table('plug_linkcounter');
		$query.= " WHERE lkey LIKE $key";
		$res = sql_query($query);
		$rcnt = mysql_fetch_object($res);
		$total = $rcnt->cnt;
		
		return $total;
	}

	function quote_smart($value) {
		if (get_magic_quotes_gpc()) $value = stripslashes($value);
		$value = mysql_real_escape_string($value);
		if (! is_numeric($value)) $value = "'". $value ."'";
		
		return $value;
	}
}
?>