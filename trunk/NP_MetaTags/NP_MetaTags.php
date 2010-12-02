<?php
// vim: tabstop=2:shiftwidth=2

/**
  * NP_MetaTags ($Revision: 1.118 $)
  * by hsur ( http://blog.cles.jp/np_cles )
*/

/*
  * Copyright (C) 2005-2010 CLES. All rights reserved.
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * 
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  * 
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
  * 
  * In addition, as a special exception, cles( http://blog.cles.jp/np_cles ) gives
  * permission to link the code of this program with those files in the PEAR
  * library that are licensed under the PHP License (or with modified versions
  * of those files that use the same license as those files), and distribute
  * linked combinations including the two. You must obey the GNU General Public
  * License in all respects for all of the code used other than those files in
  * the PEAR library that are licensed under the PHP License. If you modify
  * this file, you may extend this exception to your version of the file,
  * but you are not obligated to do so. If you do not wish to do so, delete
  * this exception statement from your version.
*/

require_once(dirname(__FILE__).'/sharedlibs/sharedlibs.php');
require_once("cles/AsyncHTTP.php");

class NP_MetaTags extends NucleusPlugin {
	function getName() {
		return 'MetaTags';
	}
	function getAuthor() {
		return 'hsur';
	}
	function getURL() {
		return 'http://blog.cles.jp/np_cles/category/31/subcatid/4';
	}
	function getVersion() {
		return '1.8';
	}
	function getDescription() {
		return '[$Revision: 1.118 $]<br />This plug-in This plug-in inserts a &lt;META&gt; tag (robots, description, keywords), by using &lt;%MetaTags%&gt;';
	}
	function getMinNucleusVersion() {
		return 320;
	}
	function supportsFeature($what) {
		switch ($what) {
			case 'SqlTablePrefix' :
				return 1;
			default :
				return 0;
		}
	}

	function hasAdminArea() {
		return 1;
	}
	
	function getEventList() {
		return array ('PostPluginOptionsUpdate');
	}

	function install() {
		$this->createOption('yahooAppId', 'Y!API AppId', 'text', '');
		$this->createOption('DescMaxLength', 'Description Max Length', 'text', '80');
		$this->createOption('MaxKeywords', 'Num of Max Keywords', 'text', '6');
		$this->createOption('isRefresh', 'Refresh data ?', 'yesno', 'no');
		$this->createOption('isForceRefresh', 'Force Refresh ?', 'yesno', 'no');

		$this->createItemOption('robots', 'META Robots', 'select', 'INDEX,FOLLOW', 'INDEX,FOLLOW|INDEX,FOLLOW|NOINDEX,FOLLOW|NOINDEX,FOLLOW|INDEX,NOFOLLOW|INDEX,NOFOLLOW|NOINDEX,NOFOLLOW|NOINDEX,NOFOLLOW');
		$this->createItemOption('description', 'META description', 'textarea', '');
		$this->createItemOption('keywords', 'META keywords', 'textarea', '');
	}

	function doSkinVar($skinType, $mode = 'tags') {
		switch( $skinType ){
			case 'item':
				global $itemid;
				$description = $this->getItemOption($itemid, 'description');
				$keywords = $this->getItemOption($itemid, 'keywords');
				$robots = $this->getItemOption($itemid, 'robots');
				
				switch( $mode ){
					case '':
					case 'tags':
						if (!empty ($robots))
							echo "<meta name=\"robots\" content=\"".htmlspecialchars($robots, ENT_QUOTES)."\" />\n";
						if (!empty ($description))
							echo "<meta name=\"description\" content=\"".htmlspecialchars($description, ENT_QUOTES)."\" />\n";
						if (!empty ($keywords)){
							echo "<meta name=\"keywords\" content=\"".htmlspecialchars($keywords, ENT_QUOTES)."\" />\n";
						}
						break;
						
					case 'robots':
						if (!empty ($robots))
							echo "<meta name=\"robots\" content=\"".htmlspecialchars($robots, ENT_QUOTES)."\" />\n";
						break;
					case 'description':
						if (!empty ($description))
							echo "<meta name=\"description\" content=\"".htmlspecialchars($description, ENT_QUOTES)."\" />\n";
						break;
					case 'keywords':
						if (!empty ($keywords))
							echo "<meta name=\"keywords\" content=\"".htmlspecialchars($keywords, ENT_QUOTES)."\" />\n";
						break;
						
					default :
						break;
				}
				break;
				
			case 'archive':
				global $archive, $archivetype;
				$now = time();
				$t = 0;
				if( $archivetype == _ARCHIVETYPE_DAY ){
					sscanf($archive, '%d-%d-%d', $y, $m, $d);
					$t = mktime(0, 0, 0, $m, $d, $y);
				} elseif ( $archivetype == _ARCHIVETYPE_MONTH ) {
					sscanf($archive, '%d-%d', $y, $m);
					$t = mktime(0, 0, 0, $m, 1, $y);
				}
				
				//TODO: remove hard coding.
				if( $t > $now || $t < mktime(0, 0, 0, 2, 1, 2004) )
					echo '<meta name="robots" content="NOINDEX,NOFOLLOW" />'."\n";
				else
					echo '<meta name="robots" content="INDEX,FOLLOW" />'."\n";
				break;
				
			default:
				break;
		}
	}
	
	function event_PostPluginOptionsUpdate(& $data) {
		global $manager;
		
		switch($data['context']){
			case 'global':
				$affected = $this->_refreshData();
				break;
			case 'item':
				// var_dump($data);
				// core hack needed.
				if( $data['item']['draft'] ) break;

				$item = $data['item'];
				$item['itemid'] = $data['itemid'];
				$this->_setData($item);
				break;
			default:
				// nothing	
		}
	}
	
	function _refreshData(){
		if( $this->getOption('isRefresh') == yes ){
			ACTIONLOG :: add(INFO, 'MetaTags: Invoked refreshData()');

			$isForce = ($this->getOption('isForceRefresh') == 'yes')? true : false;			
			$this->setOption('isRefresh','no');
			$this->setOption('isForceRefresh','no');
			
			$affected = 0;
			$query = 'SELECT inumber, ibody, imore FROM '.sql_table('item').' order by inumber';
			$res = sql_query($query);
			if (! @mysql_num_rows($res) )
				return;
			while ($assoc = mysql_fetch_assoc($res)) {
				// description
				if( !$isForce ){
					$description = $this->getItemOption($assoc['inumber'], 'description');
					if (empty($description)) {
						$description = $this->_makeDescription($assoc['ibody'].$assoc['imore']);
						$this->setItemOption($assoc['inumber'], 'description', $description);
					}
				} else {
					$description = $this->_makeDescription($assoc['ibody'].$assoc['imore']);
					$this->setItemOption($assoc['inumber'], 'description', $description);
				}
				// keywords
				if( !$isForce ){
					$keywords = $this->getItemOption($assoc['inumber'], 'keywords');
					if (empty($keywords)) {
						$keywords = $this->_getKeywords($assoc['ibody'].$assoc['imore']);
						$this->setItemOption($assoc['inumber'], 'keywords', $keywords);
					}
				} else {
					$keywords = $this->_getKeywords($assoc['ibody'].$assoc['imore']);
					$this->setItemOption($assoc['inumber'], 'keywords', $keywords);
				}
				$affected += 1;
			}
			mysql_free_result($res);
			ACTIONLOG :: add(INFO, "MetaTags: Finished refreshData(): affected items [$affected]");
			return $affected;
		}		
	}

	function _setData(& $data) {
		$itemid = intval($data['itemid']);
		
		$this->setItemOption($itemid, 'lastupdate', time());
		
		$description = $this->getItemOption($itemid, 'description');
		if (empty ($description)) {
			$description = $this->_makeDescription($data['body'].$data['more']);
			$this->setItemOption($itemid, 'description', $description);
		}

		$keywords = $this->getItemOption($itemid, 'keywords');
		if( strlen($keywords) > 100 ) $keywords = null;
		if (empty ($keywords)) {
			$keywords = $this->_getKeywords($data['body'].$data['more']);
			$this->setItemOption($itemid, 'keywords', $keywords);
		}
	}

	function _getKeywords($text) {
		if ( ! $this->getOption('yahooAppId') )
			return '';
		$maxKeywords = $this->getOption('MaxKeywords');
		$appid = $this->getOption('yahooAppId');
		$tfidf = array();
		
		if( _CHARSET != 'UTF-8' )
			$string = mb_convert_encoding($string, 'UTF-8', _CHARSET);
		
		$text = strip_tags($text);
		$text = str_replace("\n", "", $text);
		$text = str_replace("\r", "", $text);
		
		$postData = array();
		$postData['appid'] = $appid;
		$postData['results'] = 'uniq';
		$postData['filter'] = '9';
		$postData['response'] = 'surface';
		$postData['sentence'] = $text;

		$ahttp = new cles_AsyncHTTP();
		$ahttp->asyncMode = false;
		$ahttp->userAgent = "NP_MetaTags/".$this->getVersion();
		$ahttp->setRequest('http://jlp.yahooapis.jp/MAService/V1/parse', 'POST', '', $postData);
		list($data) = $ahttp->getResponses();
		if( !$data )
			ACTIONLOG :: add(WARNING, 'NP_MetaTags: AsyncHTTP Error['.$ahttp->getErrorNo(0).']'.$ahttp->getError(0));
		
		if( $data ){
			$p =& new NP_MetaTags_MA_XMLParser();
			$words = $p->parse($data);
			if( $p->isError ){
				ACTIONLOG :: add(WARNING, 'NP_MetaTags: Y!API Error( '. (isset($rawtokens[0]) ? $rawtokens[0] : 'Unknown Error -> '.$data) . ' )');
				$words = array();
			}
			$p->free();
			$p = null;
			
			if( $words ){
				arsort($words);
				$words = array_slice($words, 0, 20);
				
				$postData = array();
				$postData['appid'] = $appid;
				$postData['results'] = '1';
				$postData['adult_ok'] = '1';
				$postData['similar_ok'] = '1';	
				$ahttp = new cles_AsyncHTTP();
				$ahttp->userAgent = "NP_MetaTags/".$this->getVersion();
				
				$requests = array();
				foreach ($words as $word => $count) {
					$postData['query'] = $word;
					
					$qs = array();
					foreach($postData as $k => $v){
						$qs[] = $k."=".urlencode($v);	
					}
					$u = 'http://search.yahooapis.jp/WebSearchService/V2/webSearch?'.implode("&", $qs);
					$id = $ahttp->setRequest($u, 'GET');
					
					$requests[$id] = $word;
				}
				
				$responses = $ahttp->getResponses();
				
				foreach( $requests as $id => $word ){
					if( $respXml = $responses[$id] ){
						$p =& new NP_MetaTags_WS_XMLParser();
						list($totalResultsAvailable) = $p->parse($respXml);
						if( $p->isError ){
							ACTIONLOG :: add(WARNING, 'NP_MetaTags: Y!API Error( '. (isset($totalResultsAvailable) ? $totalResultsAvailable : 'Unknown Error') . ' )');
							$totalResultsAvailable = 0;
						}
						$p->free();
						$p = null;
						
						if( $totalResultsAvailable ){
							$tfidf[$word] = $words[$word] * log10(30000000000/$totalResultsAvailable);
						}
					} else {
						ACTIONLOG :: add(WARNING, $this->getName().': AsyncHTTP Error['.$ahttp->getErrorNo($id).']'.$ahttp->getError($id));
					}
				}
				//var_dump($ahttp);
			}
		}
		//var_dump($tfidf);	

		arsort($tfidf);
		$tfidf = array_slice($tfidf, 0, $maxKeywords);
		$result = "";
		foreach( $tfidf as $word => $score ){
			$result .= $word.',';
		}
		
		if( _CHARSET != 'UTF-8' )
			$result = mb_convert_encoding($result, _CHARSET, 'UTF-8');
		
		return mb_substr($result, 0, -1);
	}

	function _makeDescription($description) {
		$maxLength = $this->getOption('DescMaxLength');
		$description = strip_tags($description);
		$description = Str_Replace("\n", "", $description);
		$description = Str_Replace("\r", "", $description);
		return htmlspecialchars(shorten($description, $maxLength, ''));
	}
}

class NP_MetaTags_Base_XMLParser {
	function init(){
		$this->parser = xml_parser_create('UTF-8');
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, "_open", "_close");
		xml_set_character_data_handler($this->parser, "_cdata");
		
		$this->isError = false;
		$this->inTarget = false;
	}

	function parse($data){
		$this->words = array();
		xml_parse($this->parser, $data);
		$errcode = xml_get_error_code($this->parser);
	    if ( $errcode != XML_ERROR_NONE ) {
	    	$this->isError = true;
	    	$this->words = array();
			$this->words[] = 'XML Parse Error: ' . xml_error_string($errcode) . ' in '. xml_get_current_line_number($this->parser);
	    }
		return $this->words;
	}

	function free(){
		xml_parser_free($this->parser);
		$this->words = null;
	}
}

class NP_MetaTags_MA_XMLParser extends NP_MetaTags_Base_XMLParser {
	function NP_MetaTags_MA_XMLParser(){
		$this->init();
	}
	
	function _open($parser, $name, $attribute){
		switch( $name ){
			case 'WORD':
				$this->tmp = array();
				break;
			case 'SURFACE':
				$this->inTarget = 'SURFACE';
				break;
			case 'COUNT':
				$this->inTarget = 'COUNT';
				break;			
			case 'MESSAGE':
				$this->inTarget = 'MESSAGE';
				break;
			case 'ERROR':
				$this->isError = true;
				break;
		}
	}

	function _close($parser, $name){
		switch( $name ){
			case 'WORD':
				if( $this->tmp['SURFACE'] && $this->tmp['COUNT'] && !is_numeric($this->tmp['SURFACE']) && mb_strlen($this->tmp['SURFACE']) > 1 ){
					$this->words[$this->tmp['SURFACE']] = $this->tmp['COUNT'];
				}
				break;
			case 'MESSAGE':
				$this->words = array();
				$this->words[] = $this->tmp['MESSAGE'];
				break;
		}
		if( $name == $this->inTarget ) $this->inTarget = false;
	}

	function _cdata($parser, $data){
		if( $this->inTarget ){
			$this->tmp[$this->inTarget] = trim($data);
		}
	}
}

class NP_MetaTags_WS_XMLParser extends NP_MetaTags_Base_XMLParser {
	function NP_MetaTags_WS_XMLParser(){
		$this->init();
	}
	
	function _open($parser, $name, $attribute){
		switch( $name ){
			case 'RESULTSET':
				$this->words[] = $attribute['TOTALRESULTSAVAILABLE'];
				break;
		}
	}
	
	function _close($parser, $name){}
	function _cdata($parser, $data){}
}
