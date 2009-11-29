<?php
// vim: tabstop=2:shiftwidth=2

/**
  * NP_Clap ($Revision: 1.97 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * 
  * $Id: NP_Clap.php,v 1.97 2009/11/29 11:18:04 hsur Exp $
*/

/*
  * Copyright (C) 2006-2009 CLES. All rights reserved.
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

// load class
require_once(dirname(__FILE__).'/sharedlibs/sharedlibs.php');
require_once('cles/Template.php');
require_once('Jsphon.php');

define('NP_CLAP_GLOBALKEY', 'global');

class NP_Clap extends NucleusPlugin {

	// name of plugin
	function getName() {
		return 'Clap';
	}

	// author of plugin
	function getAuthor() {
		return 'hsur';
	}

	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL() {
		return 'http://blog.cles.jp/np_cles/category/31/subcatid/13';
	}

	// version of the plugin
	function getVersion() {
		return '1.7.0';
	}

	function install() {
		$this->createOption('mailaddr', NP_CLAP_mailaddr, 'text', '');
		$this->createOption('commentedOnly', NP_CLAP_commentedOnly, 'yesno', 'yes');
		
		$this->createOption('antispam_limit', NP_CLAP_antispam_limit, 'text', '10/86400');
		$this->createOption('antispam_check', NP_CLAP_antispam_check, 'yesno', 'yes');
		
		$this->createOption('deleteData', NP_CLAP_deleteData, 'yesno', 'no');
		
		/* Create tables */
		sql_query("
			CREATE TABLE IF NOT EXISTS 
				".sql_table('plugin_clap').'
			(
				`id`        INT(11) NOT NULL AUTO_INCREMENT,
				`itemkey`   CHAR(11), 
				`timestamp` DATETIME, 
				`ipaddr`    CHAR(15), 
				PRIMARY KEY (`id`),
				INDEX itemkey_timestamp (`itemkey`, `timestamp`),
				INDEX ipaddr_timestamp (`ipaddr`, `timestamp`)
			)
		');
		sql_query("
			CREATE TABLE IF NOT EXISTS 
				".sql_table('plugin_clap_comment').'
			(
				`id` INT(11) NOT NULL,
				`user` VARCHAR(255), 				
				`mail_or_url` VARCHAR(255), 
				`comment` TEXT, 
				PRIMARY KEY (`id`)
			)
		');
		sql_query("
			CREATE TABLE IF NOT EXISTS 
				".sql_table('plugin_clap_thanks').'
			(
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`image` TEXT, 
				`comment` TEXT, 
				PRIMARY KEY (`id`)
			)
		');
		
		sql_query("
			CREATE TABLE IF NOT EXISTS 
				".sql_table('plugin_clap_thanks_category').'
			(
				`category` VARCHAR(255),
				`thanksid` INT(11),
				INDEX category_idx (`category`),
				INDEX thanksid_idx (`thanksid`)
			)
		');
	}
	
	function uninstall() {
		if($this->getOption('deleteData') == "yes") {
			sql_query("DROP TABLE ".sql_table('plugin_clap'));
			sql_query("DROP TABLE ".sql_table('plugin_clap_comment'));
			sql_query("DROP TABLE ".sql_table('plugin_clap_thanks'));
			sql_query("DROP TABLE ".sql_table('plugin_clap_thanks_category'));
		}
	}
	
	function getTableList() {
		return array(	
			sql_table('plugin_clap'),
			sql_table('plugin_clap_comment'),
			sql_table('plugin_clap_thanks'),
			sql_table('plugin_clap_thanks_category'),
		);
	}
	
	function getEventList() {
		return array('QuickMenu');
	}

	function getMinNucleusVersion() { return 320; }
	function getMinNucleusPatchLevel() { return 0; }
	
	// a description to be shown on the installed plugins listing
	function getDescription() {
		return '[$Revision: 1.97 $]<br />'.NP_CLAP_description ;
	}
	
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
			case 'HelpPage':
				return 1;
					default:
				return 0;
		}
	}

	function hasAdminArea() { return 1; }

	function event_QuickMenu(&$data) {
		global $member, $nucleus, $blogid;
		
		// only show to admins
		if (!$member->isLoggedIn() || !$member->isAdmin()) return;

		array_push(
			$data['options'],
			array(
				'title' => 'Clap',
				'url' => $this->getAdminURL(),
				'tooltip' => 'Manage your clap'
			)
		);
	}
	
    function init(){
		// include language file for this plugin 
		$language = ereg_replace( '[\\|/]', '', getLanguageName()); 
		if (file_exists($this->getDirectory().'language/'.$language.'.php')) 
			@ include_once($this->getDirectory().'language/'.$language.'.php');
    }
	
	function doAction($type) {
		global $itemid, $member, $manager;
		$aActionsNotToCheck = array(
			'',
			'clap',
			'preview',
		);
		if (!in_array($type, $aActionsNotToCheck)) {
			if (!$manager->checkTicket()) return _ERROR_BADTICKET;
		}
		
		$key = requestVar('key') ? requestVar('key') : false;
		if(!$key){
			# $key = $itemid  ? $itemid : NP_CLAP_GLOBALKEY;
		}
		switch ($type) {
			case 'preview':
				if( $member->isLoggedIn() ){
					$id = requestVar('id');
					if( ! is_numeric($id) ){
						$id = null;
					}
					$this->showPreview($id);
				} else {
					return _ERROR_DISALLOWED;
				}
				break;
			break;
			case 'clap':
				$this->doClap($key);
				break;
				
			case 'chart':
				$year = intRequestVar('year');
				$month = intRequestVar('month');
				$this->echoChartJs($year, $month);
				break;
				
			// other actions result in an error
			case '':
			default:
				return 'Unexisting action: ' . $type;
		}
		return '';
	}
	
	function doTemplateVar(&$item, $what = '', $key = '') {
		$key = $key ? $key : $item->itemid;
		$this->doSkinVar('template', $what, $key);
	}

	function doSkinVar($skinType, $type = '') {
		global $itemid;
		if( $type != 'list'){
			list(,,$key) = func_get_args();
			if( ! $key ){
				$key = $itemid  ? $itemid : NP_CLAP_GLOBALKEY;
			}
		} else {
			list(,,$count,$blog,$order,$numOfDays) = func_get_args();
			$count = $count ? $count : 10;
			if( $blog ){
				$blog = strtr($blog, '/', ',');
			}
			$order = $order ? $order : 'DESC';
			$numOfDays = is_numeric($numOfDays) ? $numOfDays : false;
		}
		
		switch ($type) {
			case 'button':
			case '':
				$this->showButton($key);
				break;
			case 'count':
				echo $this->getCount($key);
				break;
			case 'list':
				$this->showList($blog,$count,$order,$numOfDays);
				break;
			case 'actionurl':
				echo $this->getActionUrl($key);
				break;
			default:
				return 'Unexisting type: ' . $type;
		}
	}
	
	function echoChartJs($year = null, $month = null){
		$year = intval($year) ? intval($year) : date('Y');
		$month = intval($month) ? intval($month) : date('m');
		
		$fromDate = date("Y-m-d H:i:s", mktime( 0, 0, 0, $month, 1 , $year));
		$toDate = date("Y-m-d H:i:s", mktime( 23, 59, 59, $month+1, 0 , $year));
		
		$param = array();
		
		// keys
		$param['key'] = array();
		$query = sprintf('SELECT itemkey, count(*) as count, count(DISTINCT ipaddr) as ipcount, count(c.id) as msgcount FROM ' . sql_table('plugin_clap') 
			. " LEFT OUTER JOIN " . sql_table('plugin_clap_comment')  . " c USING( id )  "
			. " WHERE `timestamp` between '%s' and '%s' "
			. " GROUP BY itemkey ORDER BY count DESC LIMIT 20"
			, mysql_real_escape_string( $fromDate )
			, mysql_real_escape_string( $toDate )
		);
		$res = sql_query($query);

		while( $row = mysql_fetch_array($res) ){
			$param['key']['label'][] = $row['itemkey'];
			$param['key']['count'][] = $row['count'];
			$param['key']['ipcount'][] = $row['ipcount'];
			$param['key']['msgcount'][] = $row['msgcount'];
			if(!$param['key']['max'])
				$param['key']['max'] = $row['count'];
		}
		$param['key']['length'] = count($param['key']['label']);
		if(!$param['key']['length']){
			$param['key']['label'][] = "";
			$param['key']['count'][] = 0;
			$param['key']['ipcount'][] = 0;
			$param['key']['msgcount'][] = 0;
			$param['key']['max'] = 10;
		}

		// dayOfMonth
		$param['dayOfMonth'] = array();
		$query = sprintf('SELECT DAYOFMONTH(`timestamp`) as day_of_month, count(*) as count, count(DISTINCT ipaddr) as ipcount, count(c.id) as msgcount FROM ' . sql_table('plugin_clap') 
			. " LEFT OUTER JOIN " . sql_table('plugin_clap_comment')  . " c USING( id )  "
			. " WHERE `timestamp` between '%s' and '%s' "
			. " GROUP BY day_of_month ORDER BY count"
			, mysql_real_escape_string( $fromDate )
			, mysql_real_escape_string( $toDate )
		);
		$res = sql_query($query);
		
		$lastDay = date("d", mktime( 23, 59, 59, $month+1, 0 , $year));
		$param['dayOfMonth']['label'] = range(1, $lastDay );
		$param['dayOfMonth']['count'] = array_fill(0, $lastDay, 0);
		$param['dayOfMonth']['ipcount'] = array_fill(0, $lastDay, 0);
		$param['dayOfMonth']['msgcount'] = array_fill(0, $lastDay, 0);
		$param['dayOfMonth']['length'] = $lastDay;
		while( $row = mysql_fetch_array($res) ){
			$idx = $row['day_of_month'] - 1;
			$param['dayOfMonth']['count'][$idx] = $row['count'];
			$param['dayOfMonth']['ipcount'][$idx] = $row['ipcount'];
			$param['dayOfMonth']['msgcount'][$idx] = $row['msgcount'];
			$param['dayOfMonth']['max'] = $row['count'];
		}

		// dayOfWeek
		$param['dayOfWeek'] = array();
		$query = sprintf('SELECT DAYOFWEEK(`timestamp`) as day_of_week, count(*) as count, count(DISTINCT ipaddr) as ipcount, count(c.id) as msgcount FROM ' . sql_table('plugin_clap') 
			. " LEFT OUTER JOIN " . sql_table('plugin_clap_comment')  . " c USING( id )  "
			. " WHERE `timestamp` between '%s' and '%s' "
			. " GROUP BY day_of_week ORDER BY count"
			, mysql_real_escape_string( $fromDate )
			, mysql_real_escape_string( $toDate )
		);
		$res = sql_query($query);
		
		$param['dayOfWeek']['label'] = explode(',', NP_CLAP_DAYOFWEEK);
		$param['dayOfWeek']['count'] = array_fill(0, 7, 0);
		$param['dayOfWeek']['ipcount'] = array_fill(0, 7, 0);
		$param['dayOfWeek']['msgcount'] = array_fill(0, 7, 0);
		$param['dayOfWeek']['length'] = 7;
		while( $row = mysql_fetch_array($res) ){
			$idx = $row['day_of_week'] - 1;
			$param['dayOfWeek']['count'][$idx] = $row['count'];
			$param['dayOfWeek']['ipcount'][$idx] = $row['ipcount'];
			$param['dayOfWeek']['msgcount'][$idx] = $row['msgcount'];
			$param['dayOfWeek']['max'] = $row['count'];
		}
		
		// hours
		$param['hours'] = array();
		$query = sprintf('SELECT HOUR(`timestamp`) as hour, count(*) as count, count(DISTINCT ipaddr) as ipcount, count(c.id) as msgcount FROM ' . sql_table('plugin_clap') 
			. " LEFT OUTER JOIN " . sql_table('plugin_clap_comment')  . " c USING( id )  "
			. " WHERE `timestamp` between '%s' and '%s' "
			. " GROUP BY hour ORDER BY count"
			, mysql_real_escape_string( $fromDate )
			, mysql_real_escape_string( $toDate )
		);
		$res = sql_query($query);
		
		$param['hours']['label'] = range(0, 24);
		$param['hours']['count'] = array_fill(0, 24, 0);
		$param['hours']['ipcount'] = array_fill(0, 24, 0);
		$param['hours']['msgcount'] = array_fill(0, 24, 0);
		$param['hours']['length'] = 24;
		while( $row = mysql_fetch_array($res) ){
			$idx = $row['hour'];
			$param['hours']['count'][$idx] = $row['count'];
			$param['hours']['ipcount'][$idx] = $row['ipcount'];
			$param['hours']['msgcount'][$idx] = $row['msgcount'];
			$param['hours']['max'] = $row['count'];
		}

		// convert
		mb_convert_variables('UTF-8', _CHARSET, $param);
		echo Jsphon::encode($param);
	}
	
	function doClap($key){
		global $member;
		
		// spam check
		$limit = !$key || $this->_spamcheck( requestVar('comment') . "\n" . requestVar('mail_or_url'). "\n" . requestVar('user')  );
		if($limit){
			$this->showThanks($key, true);
			return;
		}

		list($maxcount, $lifetime) = explode('/', $this->getOption('antispam_limit'), 2);
		if( !(is_numeric($maxcount) && is_numeric($lifetime)) ){
			$maxcount = 10;
			$lifetime = 86400;
		}
		
		// count
		$query = sprintf('SELECT count(id) as result FROM ' . sql_table('plugin_clap') 
				. " where ipaddr = '%s' and timestamp >  DATE_SUB(NOW(),INTERVAL %s SECOND);"
				, mysql_real_escape_string( serverVar('REMOTE_ADDR') )
				, mysql_real_escape_string( intval($lifetime) )
		);
		$count = quickQuery($query);
		
		if( (!$member->isLoggedIn()) && $count < $maxcount ){
			$query = sprintf('INSERT INTO ' . sql_table('plugin_clap') 
					. '( itemkey, timestamp, ipaddr ) '
					. "values('%s', NOW(), '%s')"
					, mysql_real_escape_string( $key )
					, mysql_real_escape_string( serverVar('REMOTE_ADDR') )
			);
			sql_query($query);
			
			if( trim(requestVar('comment')) ){
				$query = sprintf('INSERT INTO ' . sql_table('plugin_clap_comment') 
						. '( id, user, mail_or_url, comment ) '
						. "values(LAST_INSERT_ID(), '%s', '%s', '%s')"
						, mysql_real_escape_string( trim(requestVar('user')))
						, mysql_real_escape_string( trim(requestVar('mail_or_url')))
						, mysql_real_escape_string( trim(requestVar('comment')))
				);
				sql_query($query);
			}
		}
		if( $count >= $maxcount ){
			$limit = true;
		}
		
		// thanks page
		$this->showThanks($key, $limit);
		
		// send mail
		if( ($this->getOption('commentedOnly') == 'yes') && (!trim(requestVar('comment'))) ){
			return;
		}
		
		if(!$limit){
			$vars = array(
				'user' => requestVar('user'),
				'comment' => requestVar('comment'),
				'mail_or_url' => requestVar('mail_or_url'),
				'key' => $key,
				'ipaddr' => serverVar('REMOTE_ADDR'),
				'hostname' => gethostbyaddr(serverVar('REMOTE_ADDR')),			
				'useragent' => serverVar('HTTP_USER_AGENT'),
				'referer' => serverVar('HTTP_REFERER'),
			);
			$this->_notify($vars);
		}
	}
	
	function showThanks($key, $limit, $contentid = null){
		global $blog, $CONF, $manager;
		$cat = $this->getCatnameByKey($key);
		$content = null;
		$thanksids = array();
		
		if( !$contentid ){
			// random fetch
			$query = sprintf('SELECT thanksid FROM ' . sql_table('plugin_clap_thanks_category')
					. " WHERE category = '%s'"
					, mysql_real_escape_string( $cat )
			);
			$res = sql_query($query);
			while( $row = mysql_fetch_array($res) ){
				$thanksids[] = $row['thanksid'];
			}
			if($thanksids){
				shuffle($thanksids);
				$contentid = array_pop($thanksids);
			}
		}
		
		$content = $this->getThanksMsg($contentid);
		if( !$content ){
			while( $contentid = array_pop($thanksids) ){
				$content = $this->getThanksMsg($contentid);
				if( $content ){
					$this->_correctBrokenThanksCategory();
					break;
				}
			}
			
			if( !$content ){
				$content['comment'] = NP_CLAP_NOCONTENT;
			}
		}
		
		$returnurl = requestVar('returnurl') ? trim(requestVar('returnurl')) : serverVar('HTTP_REFERER');
		if( ! $returnurl ){
			if ($blog) { 
				$b =& $blog; 
			} else { 
				$b =& $manager->getBlog($CONF['DefaultBlog']); 
			} 
			$returnurl = $b->getURL();
		}
		
		$vars = array(
			'charset' => _CHARSET,
			'img' => ($content['image']) ? $content['image'] : '',
			'text' => $content['comment'],
			'returnurl' => htmlspecialchars($returnurl, ENT_QUOTES),
			'actionurl' => $this->getActionUrl($key, false),
			'key' => htmlspecialchars($key, ENT_QUOTES),
			'type' => ($key == 'preview') ? 'preview' : 'clap',
			'contentid' => $content['id'],
			'comment' => htmlspecialchars(requestVar('comment'), ENT_QUOTES),
		);
		
		$tpl = $this->_getTemplateEngine();
		if(!$limit)
			$page = $tpl->fetch('thanks', 'clap_'.$cat) 
				or $page = $tpl->fetch('thanks', strtolower(__CLASS__));
		else
			$page = $tpl->fetch('forbidden', 'clap_'.$cat)
				or $page = $tpl->fetch('forbidden', strtolower(__CLASS__));
			
		echo $tpl->fill($page, $vars, false);
	}
	
	function getCatnameByKey($key){
		$dir = '';
		if( is_numeric($key) ){
			$dir .= $this->getCategoryIDFromItemID($key);
		} else {
			$dir .= preg_replace('/[^A-Za-z0-9_.-]+/','', $key);
		}
		return $dir;
	}
	
	function getCategoryIDFromItemID($itemid) {
		return quickQuery('SELECT icat as result FROM ' . sql_table('item') . ' WHERE inumber=' . intval($itemid) );
	}
		
	function showPreview($id){
		$this->showThanks('preview', false, $id);
	}
	
	function showButton($key){
		$tpl = $this->_getTemplateEngine();
		$vars = array(
			'actionurl' => $this->getActionUrl($key),
			'actionurl_wo_key' => $this->getActionUrl($key,true,false),
			'key' => htmlspecialchars($key, ENT_QUOTES),
			'count' => $this->getCount($key),
		);
		$button = $tpl->fetch('button', strtolower(__CLASS__));
		echo $tpl->fill($button, $vars, false);
	}
	
	function getCount($key){
		$query = sprintf('SELECT count(id) as result FROM ' . sql_table('plugin_clap') 
				. " where itemkey = '%s'"
				, mysql_real_escape_string( $key )
		);
		return quickQuery($query);
	}
	
	function getBloglist(){
		$query = 'SELECT bnumber, bname FROM ' . sql_table('blog');
		$res = sql_query($query);
		$list = array();
		while( $row = mysql_fetch_array($res) ){
			list($blogid, $blogname) = $row;
			$list[$blogid] = $blogname; 
		}
		return $list;
	}
	
	function getOverview($blog = null, $offset = '0', $itemperpage = '20'){		
		if(! $blog ){
			$query = sprintf('SELECT itemkey as `key`, ititle as title, inumber as itemid, iblog as blog, count(id) as count FROM ' . sql_table('plugin_clap') . ' left outer join ' . sql_table('item')
					. " on itemkey = inumber group by `key`, title, itemid order by count DESC limit %s,%s"
					, mysql_real_escape_string( intval($offset) )
					, mysql_real_escape_string( intval($itemperpage) )		
			);
		} elseif( $blog == 'global' ){
			$query = sprintf('SELECT itemkey as `key`, ititle as title, inumber as itemid, iblog as blog, count(id) as count FROM ' . sql_table('plugin_clap') . ' left outer join ' . sql_table('item')
					. " on itemkey = inumber where inumber is null group by `key`, title, itemid order by blog, count DESC limit %s,%s"
					, mysql_real_escape_string( intval($offset) )
					, mysql_real_escape_string( intval($itemperpage) )		
			);
		} else {
			$query = sprintf('SELECT itemkey as `key`, ititle as title, inumber as itemid, iblog as blog, count(id) as count FROM ' . sql_table('plugin_clap') . ' left outer join ' . sql_table('item')
					. " on itemkey = inumber where iblog = %s group by `key`, title, itemid order by blog, count DESC limit %s,%s"
					, mysql_real_escape_string( intval($blog) )
					, mysql_real_escape_string( intval($offset) )
					, mysql_real_escape_string( intval($itemperpage) )		
			);
		}
		return sql_query($query);
	}

	function getDetail($key, $offset = '0', $itemperpage = '20'){		
		$query = sprintf('SELECT c.id, `timestamp`, ipaddr, user, mail_or_url, comment FROM ' . sql_table('plugin_clap') . ' as c left outer join ' . sql_table('plugin_clap_comment')
				. " as m on c.id = m.id where itemkey = '%s' order by `timestamp` DESC limit %s,%s"
				, mysql_real_escape_string( $key )
				, mysql_real_escape_string( intval($offset) )
				, mysql_real_escape_string( intval($itemperpage) )		
		);
		return sql_query($query);
	}
	
	function deleteClap($id){
		$query = sprintf('DELETE FROM ' . sql_table('plugin_clap')
				. " where id = %s limit 1"
				, mysql_real_escape_string( intval($id) )
		);
		sql_query($query);
		$query = sprintf('DELETE FROM ' . sql_table('plugin_clap_comment')
				. " where id = %s limit 1"
				, mysql_real_escape_string( intval($id) )
		);
		sql_query($query);
	}
	
	function getMessagelist($offset = '0', $itemperpage = '20'){		
		$query = sprintf('SELECT itemkey as `key`,`timestamp`, ipaddr, user, mail_or_url, comment FROM ' . sql_table('plugin_clap') . ' as c, ' . sql_table('plugin_clap_comment')
				. " as m where c.id = m.id order by `timestamp` DESC limit %s,%s"
				, mysql_real_escape_string( intval($offset) )
				, mysql_real_escape_string( intval($itemperpage) )		
		);
		return sql_query($query);
	}
		function getThanksMsgList($offset = '0', $itemperpage = '20'){		
		$query = sprintf('SELECT * FROM ' . sql_table('plugin_clap_thanks')
				. " order by id DESC limit %s,%s"
				, mysql_real_escape_string( intval($offset) )
				, mysql_real_escape_string( intval($itemperpage) )		
		);
		return sql_query($query);
	}
	
	function getAssociatedCategoriesByThanksId($thanksid){
		$query = sprintf('SELECT category FROM ' . sql_table('plugin_clap_thanks_category')
				. " WHERE thanksid = %s"
				, mysql_real_escape_string( intval($thanksid) )
		);
		$res = sql_query($query);
		
		$list = array();
		while( $row = mysql_fetch_assoc($res) ){
			$list[] = $row['category']; 
		}
		return $list;
	}
		function getThanksMsg($id){
		$query = sprintf('SELECT * FROM ' . sql_table('plugin_clap_thanks') 
				. " where id = %s limit 1"
				, mysql_real_escape_string( intval($id) )
		);
		$res = sql_query($query);
		return mysql_fetch_assoc($res);
	}

	function deleteThanksMsg($id){
		$query = sprintf('DELETE FROM ' . sql_table('plugin_clap_thanks')
				. " where id = %s limit 1"
				, mysql_real_escape_string( intval($id) )
		);
		sql_query($query);
		$query = sprintf('DELETE FROM ' . sql_table('plugin_clap_thanks_category')
				. " where thanksid = %s"
				, mysql_real_escape_string( intval($id) )
		);
		sql_query($query);
	}

	function setThanksMsg($msg){
		if( $msg['id'] == 'new'){					$query = sprintf('INSERT INTO ' . sql_table('plugin_clap_thanks') 
						. ' ( image, comment ) '
						. " values('%s', '%s')"
						, mysql_real_escape_string( $msg['image'] )
						, mysql_real_escape_string( $msg['comment']  )
			);
		} else {
			$query = sprintf('UPDATE ' . sql_table('plugin_clap_thanks') 
						. " SET image = '%s', comment = '%s' where id = %s limit 1"
						, mysql_real_escape_string( $msg['image'] )
						, mysql_real_escape_string( $msg['comment'] )
						, mysql_real_escape_string( intval($msg['id']) )
			);
		}
		sql_query($query);
	}
	
	function setAssociatedCategories($thanksid, $assoc, $assoc_etc = ''){
		$query = sprintf('DELETE FROM ' . sql_table('plugin_clap_thanks_category')
				. " where thanksid = %s"
				, mysql_real_escape_string( intval($thanksid) )
		);
		sql_query($query);
		
		$etc = explode(',', $assoc_etc);
		if($etc) $assoc = array_merge($assoc, $etc);
		
		$assoc = array_map("trim", $assoc);
		$assoc = array_filter($assoc);
		
		foreach ($assoc as $c) {
			$query = sprintf('INSERT INTO ' . sql_table('plugin_clap_thanks_category') 
						. ' ( category, thanksid ) '
						. " values('%s', %s)"
						, mysql_real_escape_string( $c )
						, mysql_real_escape_string( intval($thanksid) )
			);
			sql_query($query);
		}
	}
	
	function showList($bloglist = null, $count = '10', $order = 'DESC', $numOfDays = false){
		
		$where = '';
		if( $numOfDays){
			$where .= sprintf(' and `timestamp` > DATE_ADD(now(), INTERVAL -%s DAY)'
				, mysql_real_escape_string( intval($numOfDays) )
			);
		}
		if( $bloglist ){
			$where .= sprintf(" and iblog in (%s) and itemkey <> '%s'"
				, mysql_real_escape_string( $bloglist )
				, mysql_real_escape_string( NP_CLAP_GLOBALKEY )
			);
		} else {
			$where .= sprintf(" and itemkey <> '%s'"
				, mysql_real_escape_string( NP_CLAP_GLOBALKEY )
			);		
		}
		
		$query = sprintf('SELECT itemkey as `key`, ititle as title, inumber as itemid, count(id) as count FROM ' . sql_table('plugin_clap') . ', ' . sql_table('item')
				. " where itemkey = inumber %s group by `key`, title, itemid order by count %s limit %s"
				, $where 
				, mysql_real_escape_string( $order )
				, mysql_real_escape_string( intval($count) )
		);
		$res = sql_query($query);
		
		$tpl = $this->_getTemplateEngine();
		echo $tpl->fetch('list_header', strtolower(__CLASS__));
		
		$item = $tpl->fetch('list_item', strtolower(__CLASS__));
		while( $row = mysql_fetch_assoc($res) ){
			$row['title'] = shorten(strip_tags($row['title']),30,'...');
			$row['itemlink'] = createItemLink($row['itemid'], '');
			echo $tpl->fill($item, $row, false);
		}
		
		echo $tpl->fetch('list_footer', strtolower(__CLASS__));
	}
	
	function getActionUrl($key, $withQuery = true, $withKey = true){
		global $CONF;
		$url = $CONF['ActionURL'];
		if($withQuery){
			$url .= '?action=plugin&amp;name=Clap&amp;type=clap';
			if($withKey){
				$url .= '&amp;key='.htmlspecialchars($key, ENT_QUOTES);
			}
		}
		return $url;
	}
	
	function _getTemplateEngine(){
		if( ! $this->templateEngine )
			$this->templateEngine =& new cles_Template(dirname(__FILE__).'/clap/template');
		return $this->templateEngine;
	}
	
	function _notify($vars){
		global $CONF, $DIR_LIBS, $member;
		$destAddress = trim($this->getOption('mailaddr'));
		if ( ! $destAddress ) return;
		
		if (!class_exists('notification'))
			include($DIR_LIBS . 'NOTIFICATION.php');
			
		$tpl = $this->_getTemplateEngine();
		
		$msg = $tpl->fetch('mail_body', strtolower(__CLASS__), 'txt');
		$msg = $tpl->fill($msg, $vars, null);
		
		$subject = $tpl->fetch('mail_subject', strtolower(__CLASS__), 'txt');
		$subject = $tpl->fill($subject, $vars, false);
		
		$notify =& new NOTIFICATION($destAddress);
		$notify->notify($subject, $msg , $CONF['AdminEmail']);
	}
	
	function _spamcheck($text = ''){
		if($this->getOption('antispam_check') == "yes") {
			global $itemid, $manager;
			
			$spamcheck = array (
			    'type' => 'clap',
			    'body' => $text,
			    'author' => '',
			    'url' => '',
			    'id' => $itemid,
				'return'	=> true,
				'live'   	=> true,
				
				/* Backwards compatibility with SpamCheck API 1*/
				'data'		=> $text,
				'ipblock'   => true,
			);
			
			$manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck));
			if (isset($spamcheck['result']) && $spamcheck['result'] == true){
				return true;
			}
		}
		return false;
	}
	
	function _correctBrokenThanksCategory(){
		$query = 'SELECT tc.* FROM ' . sql_table('plugin_clap_thanks_category') . ' tc'
				. ' LEFT OUTER JOIN ' . sql_table('plugin_clap_thanks') . ' t ON tc.thanksid = t.id'
				. ' WHERE id IS NULL';
		$res = sql_query($query);
		
		$count = 0;
		while( $row = mysql_fetch_assoc($res) ){
			$query = sprintf('DELETE FROM ' . sql_table('plugin_clap_thanks_category')
					. " WHERE category = %s and thanksid = %s limit 1"
					, mysql_real_escape_string( intval($row['category']) )
					, mysql_real_escape_string( intval($row['thanksid']) )
			);
			sql_query($query);
			$count++;
		}
		
		$msg = sprintf(NP_CLAP_corrected, $count);
		$this->_warn($msg);
		return $msg;
	}
	
	function _info($msg) {
		if ($this->getOption('debug') == 'yes') {
			ACTIONLOG :: add(INFO, 'Clap: '.$msg);
		}
	}

	function _warn($msg) {
		ACTIONLOG :: add(WARNING, 'Clap: '.$msg);
	}
}

