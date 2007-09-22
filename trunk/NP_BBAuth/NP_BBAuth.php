<?php
// vim: tabstop=2:shiftwidth=2

/**
  * NP_BBAuth ($Revision: 1.1 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: NP_BBAuth.php,v 1.1 2007-09-22 18:52:14 hsur Exp $
  *
*/

/*
  * Copyright (C) 2007 CLES. All rights reserved.
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

define('NP_BBAUTH_AUTH_COOKIE', 'EXTAUTH');

require(dirname(__FILE__).'/sharedlibs/sharedlibs.php');
$v = phpversion();
if ($v[0] == '4') {
	require_once("ybrowserauth.class.php4");
} elseif ($v[0] == '5') {
	require_once("ybrowserauth.class.php5");
}
unset($v);

class NP_BBAuth extends NucleusPlugin {

	function getName() {
		return 'Yahoo! BBAuth';
	}
	function getAuthor() {
		return 'hsur';
	}
	function getURL() {
		return 'http://blog.cles.jp/np_cles/category/31/subcatid/19';
	}
	function getVersion() {
		return '1.0b';
	}
	function getMinNucleusVersion() {
		return 330;
	}
	function getMinNucleusPatchLevel() {
		return 0;
	}
	function getEventList() {
		return array ('FormExtra', 'ValidateForm', 'PreAddComment', 'PostAddComment', 'PostDeleteComment', 'ExternalAuth', 'Logout', 'LoginSuccess');
	}
	function getTableList() {
		return array( sql_table('plugin_bbauth'), sql_table('plugin_bbauth_comment'), sql_table('plugin_bbauth_profile'));
	}
	function getDescription() {
		return '[$Revision: 1.1 $]<br />Adds BBAuth authentication to anonymous comment, to prevent robots from spamming.';
	}
	function supportsFeature($what) {
		switch ($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	function hasAdminArea() {
		return 1;
	}
	
	function init() {
		$this->loginedUser = null;
		$this->comments = array();
	}

	function install() {
		sql_query(
			  'CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_bbauth') 
			. ' ('
  			. '  cookie varchar(40) NOT NULL,'
  			. '  hash varchar(255) NOT NULL unique default \'\','
  			. '  ts datetime NOT NULL default \'0000-00-00 00:00:00\','
  			. '  PRIMARY KEY (cookie)'
			. ' );'
        );
		sql_query(
			  'CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_bbauth_profile') 
			. ' ('
  			. '  hash varchar(255) NOT NULL,'
  			. '  nick varchar(255) NOT NULL unique default \'\','
  			. '  email varchar(255) ,'
  			. '  ts datetime NOT NULL default \'0000-00-00 00:00:00\','
  			. '  PRIMARY KEY (hash)'
			. ' );'
        );
		sql_query(
			  'CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_bbauth_comment') 
			. ' ('
  			. '  cnumber int(11) NOT NULL,'
  			. '  citem int(11) NOT NULL,'
  			. '  hash varchar(255) NOT NULL,'
  			. '  ts datetime NOT NULL default \'0000-00-00 00:00:00\','
 			. '  PRIMARY KEY(cnumber), '
 			. '  INDEX(citem) '
			. ' );'
        );
		
		global $CONF;
		$loginedHtml = "<p>Thanks for signing in. Now you can comment or send mail. (<a href=\"<%url%>\" rel=\"nofollow\">Sign out</a>/<a href=\"<%updateProfileUrl%>\" rel=\"nofollow\" target=\"_blank\">Update Profile</a>)<script type=\"text/javascript\">\ndocument.getElementById('nucleus_cf_name').value = '<%nick%> [Yahoo!]';\ndocument.getElementById('nucleus_cf_name').style.background = '#FFFFCC';\ndocument.getElementById('nucleus_cf_name').readOnly = true;\n</script></p>";
		$notLoginedHtml = '<p>If you have a Yahoo! account, you can <a href="<%url%>" rel="nofollow">sign in</a> to use it here.</p>';
		$templateHtml =  '[Y!:<%hash%>]';
		$templateAdminHtml =  '[Y!:<%hash%>]<a href="mailto:<%email%>">[Mail]</a>';
		        
		$this->createOption('yahooAppId', 'Yahoo! BBAuth Application ID', 'text', '');
		$this->createOption('yahooSecret', 'Yahoo! BBAuth Shared Secret', 'text', '');
		
		$this->createOption('permitComment', 'Permit comments w/o login?', 'yesno', 'yes', '');
		$this->createOption('permitMail', 'Permit mail w/o login?', 'yesno', 'yes', '');
		
		$this->createOption('LoginedHtml', 'Logined Template', 'textarea', $loginedHtml, '');
		$this->createOption('NotLoginedHtml', 'Not Logined Template', 'textarea', $notLoginedHtml, '');
		$this->createOption('TemplateHtml', 'Template html', 'textarea', $templateHtml, '');
		$this->createOption('TemplateAdminHtml', 'Template admin html', 'textarea', $templateAdminHtml, '');
		
		$this->createOption('CommentFormError', 'Error message (comment)', 'text', 'To submit comment, you need to sign-in to Yahoo!.', '');
		$this->createOption('MemberMailError', 'Error message  (mail form)', 'text', 'To send email, you need to sign-in to Yahoo!.', '');
		
		$this->createOption('dropdb', 'Erase  on uninstall?', 'yesno', 'no', '');
		$this->createOption('debug', 'Debug mode ?', 'yesno', 'no');
		
		$this->createOption('enableLinkedWith', 'Enable local account linked with yahoo account ? ', 'yesno', 'no');
		$this->createMemberOption('linkedWith', 'Linked with following account (hash value)', 'text', '');
	}

	function unInstall() {
		if ($this->getOption('dropdb') == 'yes'){
			sql_query('DROP TABLE '.sql_table('plugin_bbauth'));
			sql_query('DROP TABLE '.sql_table('plugin_bbauth_profile'));
			sql_query('DROP TABLE '.sql_table('plugin_bbauth_comment'));
		}
	}
	function doAction($type) {
		switch ($type) {
			case '' :
				if( $this->login() ){
					$this->_info('Authentication success: hash=' . $this->loginedUser->hash);
					$this->_doLoginLocal($this->loginedUser->hash);
					$this->loginedUser->appdata = preg_replace('/action=logout&?/','', $this->loginedUser->appdata);
					$this->_redirect( $this->loginedUser->appdata );
				} else {
					$this->_info('Authentication failure');
					return 'Authentication failure';
				}
				break;
			case 'rd' :
				$this->logout();
				$this->_redirect( requestVar('url') );
				break;
				
			case 'updateProfile':
				if( $this->isLogined() ){
					$profile = array();
					$profile['nick'] = requestVar('nick');
					$profile['email'] = requestVar('email');

					if( requestVar('submit') )	
						$this->_doUpdateProfile($profile);
				} else {
					$this->_info('Authentication failure');
					return 'Authentication failure';
				}
				break;
			default :
				return 'Unknown action: '.$type;
		}
		return '';
	}
	
	function _doUpdateProfile($profile){
		$query = sprintf('REPLACE INTO ' . sql_table('plugin_bbauth_profile') 
				. ' ( hash, nick, email, ts ) '
				. " values('%s', '%s', '%s', now())",
				 mysql_real_escape_string( $this->loginedUser->hash  ),
				 mysql_real_escape_string( $profile['nick']  ),
				 mysql_real_escape_string( $profile['email'] )
		);
		sql_query($query);

		$this->loginedUser->nick = $profile['nick'];
		$this->loginedUser->email = $profile['email'];
		$this->loginedUser->nick = $this->loginedUser->nick ? $this->loginedUser->nick : $this->loginedUser->hash;
		$this->loginedUser->email = $this->loginedUser->email ? $this->loginedUser->email : '';

		setcookie($CONF['CookiePrefix'] . 'comment_user', $this->loginedUser->nick, 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
		setcookie($CONF['CookiePrefix'] . 'comment_email', $this->loginedUser->email, 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
	}
	
	function _doLoginLocal($name){
		if( $this->getOption('enableLinkedWith') != 'yes' ) return false;
		
		$linkedWith = $this->getAllMemberOptions('linkedWith');
		ksort($linkedWith, SORT_NUMERIC);
		
		$localId = -1;
		foreach( $linkedWith as $id => $accountList ){
			$accounts = explode(",", $accountList);
			$accounts = array_map("trim", $accounts);
			
			foreach( $accounts as $account ){
				if( $account == '*' || $account == $name ){
					$localId = $id;
					break 2;
				}
			}
		}
		if( $localId == -1 ) return false;
		
		global $manager, $CONF, $member;
		$member =& MEMBER::createFromID($localId);
		$member->loggedin = 1;
		
		$member->newCookieKey();
		$member->setCookies(0);
		if ( isset($CONF['secureCookieKey']) ) {
			$member->setCookieKey(md5($member->getCookieKey().$CONF['secureCookieKeyIP']));
			$member->write();
		}
		$manager->notify('LoginSuccess', array('member' => &$member) );
		
		$this->_info('Login local account :' . $member->getDisplayName() );
		ACTIONLOG::add(INFO, 'Login successful for '.$member->getDisplayName().' (sharedpc=0, BBAuth)');
		return true;
	}
	
	function _info($msg) {
		if ($this->getOption('debug') == 'yes') {
			ACTIONLOG :: add(INFO, 'BBAuth: '.$msg);
		}
	}

	function _warn($msg) {
		ACTIONLOG :: add(WARNING, 'BBAuth: '.$msg);
	}

	function _redirect($url){
		header('Location: '.$url);
	}
	
	function _generateKey(){
		mt_srand( (double) microtime() * 1000000);
		return md5(uniqid(mt_rand()));
	}
	
	function isLogined(){
		global $CONF;
		if( $this->loginedUser ) return true;
		
		$cookie = cookieVar($CONF['CookiePrefix'] . NP_BBAUTH_AUTH_COOKIE);
		if( ! $cookie ) return false;
		
		$query = sprintf('SELECT a.cookie as cookie, a.hash as hash, a.ts as ts, p.nick as nick, p.email as email  FROM ' . sql_table('plugin_bbauth') . ' a '
				. ' LEFT OUTER JOIN ' . sql_table('plugin_bbauth_profile') . ' p ON a.hash = p.hash '
				. " where a.cookie = '%s' and a.ts > date_sub( now(), interval 1 day)"
				, mysql_real_escape_string( trim($cookie) )
		);
		$res = sql_query($query);
		if( @mysql_num_rows($res) > 0) {
			$this->loginedUser = mysql_fetch_object($res);
			$this->loginedUser->nick = $this->loginedUser->nick ? $this->loginedUser->nick : $this->loginedUser->hash;
			$this->loginedUser->email = $this->loginedUser->email ? $this->loginedUser->email : '';
			
			return true;
		}
		
		return false;
	}
	
	function login(){
		global $CONF;
		if( ! ($appid = $this->getOption('yahooAppId')) ){
			$this->_warn('Yahoo! BBAuth Application ID is not set.');
			$this->yahooerror = 'Yahoo! BBAuth Application ID is not set.';
			return false;
		}
		if( ! ($secret = $this->getOption('yahooSecret')) ){
			$this->_warn('Yahoo! BBAuth shred secret is not set.');
			$this->yahooerror = 'Yahoo! BBAuth shred secret is not set.';
			return false;
		}
		
		$bbauth =& new YBrowserAuth($appid, $secret);
		$result = $bbauth->validate_sig();
		
		if( ! $result ) {
			// Auth Failed
			$this->yahooerror = $bbauth->sig_validation_error;
			return false;
		}
		
		// Auth Success
		$ts = time();
		$cookie = $this->_generateKey();
		$query = sprintf('REPLACE INTO ' . sql_table('plugin_bbauth') 
				. ' ( cookie, hash, ts ) '
				. " values('%s', '%s', '%s')",
				 mysql_real_escape_string( $cookie ),
				 mysql_real_escape_string( $bbauth->userhash  ),
				 mysql_real_escape_string( date("Y/m/d H:i:s", $ts ) )
		);
		sql_query($query);
		
		$query = sprintf('SELECT a.cookie as cookie, a.hash as hash, a.ts as ts, p.nick as nick, p.email as email  FROM ' . sql_table('plugin_bbauth') . ' a '
				. ' LEFT OUTER JOIN ' . sql_table('plugin_bbauth_profile') . ' p ON a.hash = p.hash '
				. " where a.cookie = '%s' and a.ts > date_sub( now(), interval 1 day)"
				, mysql_real_escape_string( trim($cookie) )
		);
		$res = sql_query($query);		
		
		if( @mysql_num_rows($res) > 0) {
			$this->loginedUser = mysql_fetch_object($res);
			$this->loginedUser->nick = $this->loginedUser->nick ? $this->loginedUser->nick : $this->loginedUser->hash;
			$this->loginedUser->email = $this->loginedUser->email ? $this->loginedUser->email : '';
			$this->loginedUser->appdata = $bbauth->appdata;
			
			setcookie($CONF['CookiePrefix'] . NP_BBAUTH_AUTH_COOKIE , $cookie, 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
			setcookie($CONF['CookiePrefix'] . 'comment_user', $this->loginedUser->nick, 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
			setcookie($CONF['CookiePrefix'] . 'comment_email', $this->loginedUser->email, 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
			return true;
		}
		
		return false;
	}
		
	function logout(){
		global $CONF;
		$this->loginedUser = null;
		setcookie($CONF['CookiePrefix'] . NP_BBAUTH_AUTH_COOKIE, '', 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
		setcookie($CONF['CookiePrefix'] . 'comment_user', '', 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
		setcookie($CONF['CookiePrefix'] . 'comment_email', '', 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
		return true;
	}
	
	function event_ExternalAuth(&$data){
		if( $data['externalauth']['source'] == $this->getName() ) return;
        if( isset($data['externalauth']['result']) && $data['externalauth']['result'] == true ){
            return;
        }
		
		if( $this->isLogined() ){
			$data['externalauth']['result'] = true;
			$data['externalauth']['plugin'] = $this->getName();
		}
	}

	function doSkinVar($skinType, $type = "") {
		$data['type'] = 'item';
		$this->event_FormExtra($data);
	}
	
	function event_FormExtra(&$data) {
		global $CONF, $manager, $member;
		if( $member->isLoggedIn() ) return;
		
		switch ($data['type']) {
			case 'commentform-notloggedin' :
			case 'membermailform-notloggedin': 
			case 'item': 
				break;
			default :
				return;
		}
		
		$externalauth = array ( 'source' => $this->getName() );
		$manager->notify('ExternalAuth', array ('externalauth' => &$externalauth));
		if (isset($externalauth['result']) && $externalauth['result'] == true) return;

		if( ! ($appid = $this->getOption('yahooAppId')) ){
			$this->_warn('Yahoo! BBAuth Application ID is not set.');
			echo 'Yahoo! BBAuth Application ID is not set.';
			return false;
		}
		if( ! ($secret = $this->getOption('yahooSecret')) ){
			$this->_warn('Yahoo! BBAuth shred secret is not set.');
			echo 'Yahoo! BBAuth shred secret is not set.';
			return false;
		}
		
		$bbauth =& new YBrowserAuth($appid, $secret);

		$aVars = array();
		if( $this->isLogined() ){
			// Logined
			$return_url = $CONF['PluginURL'] . 'bbauth/rd.php?action=rd&url='
						. urlencode( 'http://'.serverVar("HTTP_HOST") .serverVar("REQUEST_URI") );			
			$aVars['url'] = htmlspecialchars( $return_url, ENT_QUOTES );
			$aVars['nick'] = $this->loginedUser->nick;
			$aVars['email'] = $this->loginedUser->email;
			$aVars['ts'] = $this->loginedUser->ts;
			$aVars['profileUpdateUrl'] = $CONF['PluginURL'] . 'bbauth/profile.php';
			
			echo TEMPLATE::fill($this->getOption('LoginedHtml'), $aVars);
		} else {
			// not logined
			$return_url = 'http://'.serverVar("HTTP_HOST") .serverVar("REQUEST_URI");
			$aVars['url'] = $bbauth->getAuthURL($return_url, true);
			
		    echo TEMPLATE::fill($this->getOption('NotLoginedHtml'), $aVars);
		}		
	}

	function event_ValidateForm(&$data) {
		global $manager, $member;
		if( $member->isLoggedIn() ) return;
		
		$externalauth = array ( 'source' => $this->getName() );
		$manager->notify('ExternalAuth', array ('externalauth' => &$externalauth));
		if (isset($externalauth['result']) && $externalauth['result'] == true) return;
		
		switch ($data['type']) {
			case 'comment' :
				if( (! $this->isLogined() ) && $this->getOption('permitComment') == 'no' )
					$data['error'] = $this->getOption('CommentFormError');
				break;
			case 'membermail' :
				if( (! $this->isLogined() ) && $this->getOption('permitMail') == 'no' )
					$data['error'] = $this->getOption('MemberMailError');
				break;
			default :
				return;
		}
	}
	
	function event_PreAddComment(&$data) {
		global $member;
		if( $member->isLoggedIn() ) return;
		
		if( ! $this->isLogined() ) return;
		$data['comment']['user'] = $this->loginedUser->nick . ' [Yahoo!]';
	}
	
	function event_PostAddComment(&$data) {
		global $member;
		if( $member->isLoggedIn() ) return;
		
		if( ! $this->isLogined() ) return;
		global $itemid;
		$query = sprintf('INSERT INTO ' . sql_table('plugin_bbauth_comment') 
				. '( cnumber, citem, hash, ts ) '
				. "values('%s', '%s', '%s', now() )",
				 mysql_real_escape_string( $data['commentid'] ),
				 mysql_real_escape_string( intval($itemid) ),
				 mysql_real_escape_string( $this->loginedUser->hash  )
		);
		sql_query($query);
	}
	
	function event_PostDeleteComment(&$data) {
		$query = sprintf('DELETE FROM ' . sql_table('plugin_bbauth_comment')
				. " where cnumber = '%s'",
				 mysql_real_escape_string( intval($data['commentid']) )
		);
		sql_query($query);
	}
	
	function event_LoginSuccess(&$data) {
		if( $this->isLogined() )
			$this->logout();
	}
	
	function event_Logout(&$data) {
		if( $this->isLogined() )
			$this->logout();
	}
	
	function doTemplateCommentsVar(&$item, &$comment){
		global $member;
		$itemid = intval($item['itemid']);
		if( ! $this->comments[$itemid] ){
			$this->comments[$itemid]['cached'] = true;
			$query = sprintf('SELECT c.cnumber as cnumber, c.hash as hash, p.nick as nick, p.email as email FROM ' . sql_table('plugin_bbauth_comment') . ' c '
					. ' LEFT OUTER JOIN ' . sql_table('plugin_bbauth_profile') . ' p ON c.hash = p.hash '
					. " WHERE citem = '%s'"
					, mysql_real_escape_string( intval($itemid) )
			);
			$res = sql_query($query);
			$this->comments[$itemid] = array();
			while( $o =& mysql_fetch_object($res)) {
				$cnumber = $o->cnumber;
				$this->comments[$itemid][$cnumber] = $o;
			}
		}
		$cnumber = $comment['commentid'];
		if( $yahooComment = $this->comments[$itemid][$cnumber] ){
			$aVars['hash'] =  $yahooComment->hash;
			$aVars['nick'] =  $yahooComment->nick ? $yahooComment->nick : $yahooComment->hash;
			$aVars['email'] =  $yahooComment->email ? $yahooComment->email : '';
			if ( $member->isLoggedIn() )
				echo TEMPLATE::fill($this->getOption('TemplateAdminHtml'), $aVars);
			else
				echo TEMPLATE::fill($this->getOption('TemplateHtml'), $aVars);
		}
	}
}
