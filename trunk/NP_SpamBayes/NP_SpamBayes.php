<?php

/**
  * NP_SpamBayes(JP) ($Revision: 1.7 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: NP_SpamBayes.php,v 1.7 2008-05-03 22:38:17 hsur Exp $
  *
  * Copyright (C) 2007 cles All rights reserved.
*/

/* 
 * Based on NP_SpamBayes
 * by Xiffy. http://xiffy.nl/weblog/
 *
 * Bayesian filter for comment and trackback spam
 *
 
 ***** BEGIN LICENSE BLOCK *****

 The Initial Developer of the Original Code is
 Loic d'Anterroches [loic_at_xhtml.net].
 Portions created by the Initial Developer are Copyright (C) 2003
 the Initial Developer. All Rights Reserved.

 Contributor(s):

 PHP Naive Bayesian Filter is free software; you can redistribute it
 and/or modify it under the terms of the GNU General Public License as
 published by the Free Software Foundation; either version 2 of
 the License, or (at your option) any later version.

 PHP Naive Bayesian Filter is distributed in the hope that it will
 be useful, but WITHOUT ANY WARRANTY; without even the implied
 warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 See the GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Foobar; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

 Alternatively, the contents of this file may be used under the terms of
 the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 in which case the provisions of the LGPL are applicable instead
 of those above.

 ***** END LICENSE BLOCK *****

 ***** Version history *****
 Version 1.0   : 2006 09 06 Stable on development and fresh installed blog.
 1.0.1 : 2006 09 11 NAN bug solved, some more information on the screens
 1.0.2 : 2006 09 15 Logging filtering applied to both ham and spam as well as different logtypes. Handy when
 a lot of plugins use spambaues as a spam filter.
 1.0.3 : 2006 09 19 Logging now adherse the plugin option setting (thanks VJ)
 Added the feature to train all 'new' comments
 1.0.4 : 2006 09 26 Logging now adherse the plugin option setting also in version 4 of PHP (thanks pepiino)
 1.0.5 : 2006 10 15 Update probabilities now made obsolete. The function is run after all training sessions.
 1.1.0 Beta 2007 01 07 Logger functions have been enhanched dramaticly.
 Items per page is now a user setting.
 It's possible to scan for keywords inside the content
 Explain functionality to see how a logged event scores against SpamBayes keywords. Prints both ham and spam results.
 1.1.0	2007 01 08 	 Promote to weblog. Comments only. Will teach the document a s Ham and publishes the logged event as a legit comment.
 Pagecounter could be wrong..
 ***** End version history *****

 * based on: many sources:
 * http://priyadi.net/archives/2005/10/07/wpbayes-naive-bayesian-comment-spam-filter-for-wordpress/
 * http://www.xhtml.net/php/PHPNaiveBayesianFilter
 * http://www.opensourcetutorials.com/tutorials/Server-Side-Coding/PHP/implement-bayesian-inference-using-php-1/page11.html
 * http://weblogtoolscollection.com/archives/2005/02/19/three-strikes-spam-plugin-updated/
 * http://www-128.ibm.com/developerworks/web/library/wa-bayes1/?ca=dgr-lnxw961Bayesian
 */

class NP_SpamBayes extends NucleusPlugin {

	function NP_SpamBayes() {
		global $DIR_PLUGINS;
		$this->table_cat = sql_table('plug_sb_cat'); // categories
		$this->table_wf  = sql_table('plug_sb_wf');  // word frequencies
		$this->table_ref = sql_table('plug_sb_ref'); // references
		$this->table_log = sql_table('plug_sb_log'); // logging
		include_once($DIR_PLUGINS."spambayes/spambayes.php");
		$this->spambayes = new NaiveBayesian(&$this);
	}

	function getEventList() {
		return array('QuickMenu', 'SpamCheck', 'FormExtra');
	}

	function hasAdminArea() {
		return 1;
	}

	function event_SpamCheck (&$data) {
		global $DIR_PLUGINS;
		if( isset($data['spamcheck']['result']) && $data['spamcheck']['result'] == true) return;
		
		switch( strtolower($data['spamcheck']['type']) ){
			case 'trackback':
			case 'mailtoafriend':
			case 'comment':
				break;
			default:
				return;
		}

		// for SpamCheck API 2.0 compatibility
		if( ! $data['spamcheck']['data'] ){
			$data['spamcheck']['data']  = $data['spamcheck']['body'] ."\n";
			$data['spamcheck']['data'] .= $data['spamcheck']['author'] ."\n";
			$data['spamcheck']['data'] .= $data['spamcheck']['email'] ."\n";
			$data['spamcheck']['data'] .= $data['spamcheck']['url'] ."\n";
		}
		
		$score = $this->spambayes->categorize($data['spamcheck']['data']);

		if( (float)$score['spam'] > (float)$this->getOption('probability') ) {
			$log = $data['spamcheck']['type'] > '' ? $data['spamcheck']['type'] ." SpamCheck":"event SpamCheck";
			$this->spambayes->nbs->logevent(
				$log.' SPAM detected. score: (ham '.$score['ham'].') (spam: '.$score['spam'].')',
				$data['spamcheck']['data'],
				'spam'
			);
			if(isset($data['spamcheck']['return']) && $data['spamcheck']['return'] == true) {
				// Return to caller
                $data['spamcheck']['result'] = true;
				$data['spamcheck']['plugin'] = $this->getName();
				$data['spamcheck']['message'] = 'Marked as spam by NP_SpamBayes spamScore:'.(float)$score['spam'].' hamScore:'.(float)$score['ham'];
				return;
			} else {
				exit;
			}
		} elseif ( trim($data['spamcheck']['data']) != '' ) {
			$log = $data['spamcheck']['type'] > '' ? $data['spamcheck']['type'] ." SpamCheck":"event SpamCheck";
			$this->spambayes->nbs->logevent(
				$log.' HAM detected. score: (ham '.$score['ham'].') (spam: '.$score['spam'].')',
				$data['spamcheck']['data'],
				'ham'
			);
		}
		// in case of SpamCheck we do NOT log HAM events ...
	}

	/* some default functions for a plugin */
	function getName() 		  { return 'SpamBayes(JP)'; }
	function getAuthor()  	  { return 'xiffy + hsur'; }
	function getURL()  		  { return 'http://blog.cles.jp/np_cles/category/31/subcatid/17'; }
	function getVersion() 	  { return '1.1.0 jp2.0'; }
	function getDescription() { return 'Yahoo!の形態素解析APIとBayesianフィルタによってコメント/トラックバックspamを防ぎます。';	}
	function supportsFeature($what) {
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function event_QuickMenu(&$data) {
		global $member, $nucleus, $blogid;
		// only show to admins
		if (preg_match("/MD$/", $nucleus['version'])) {
			$isblogadmin = $member->isBlogAdmin(-1);
		} else {
			$isblogadmin = $member->isBlogAdmin($blogid);
		}
		if (!($member->isLoggedIn() && ($member->isAdmin() | $isblogadmin))) return;
		if ($this->getOption('enableQuickmenu') == 'yes' ) {
			array_push(
			$data['options'],
			array(
			'title' => 'SpamBayes',
			'url' => $this->getAdminURL(),
			'tooltip' => 'Manage SpamBayes filter'
				)
			);
		}
	}
	
	function event_FormExtra(&$data) {
		switch ($data['type']) {
			case 'commentform-notloggedin' :
			case 'membermailform-notloggedin': 
				break;
			default :
				return;
		}
		
		echo '<!-- Begin Yahoo! JAPAN Web Services Attribution Snippet --><a href="http://developer.yahoo.co.jp/about"><img src="http://i.yimg.jp/images/yjdn/yjdn_attbtn1_125_17.gif" title="Webサービス by Yahoo! JAPAN" alt="Web Services by Yahoo! JAPAN" width="125" height="17" border="0" style="margin:4px 15px 15px 15px" /></a><!-- End Yahoo! JAPAN Web Services Attribution Snippet --><br />';
	}
	
	function install() {
		// create some options
		$this->createOption('probability','spam判定するためのスコアの閾値(0.00～1.00)','text','0.95');
		$this->createOption('ignorelist','無視する単語(カンマもしくはスペース区切り)','textarea','you the for and');
		$this->createOption('enableTrainall','メニューに「全てを学習」を表示するか','yesno','no');
		$this->createOption('enableQuickmenu','管理画面の左メニューにSpamBayesを表示する','yesno','yes');
		$this->createOption('enableLogging','spamの履歴を保存 (DBに十分な容量が必要です。)','yesno','yes');
		
		$this->createOption('appid','Yahoo!Japan AppID','text','');
		$this->createOption('DropTable','アンインストール時に全てのデータを削除する','yesno','no');

		// create some sql tables as well
		sql_query("CREATE TABLE IF NOT EXISTS ".$this->table_cat." (catcode varchar(50) NOT NULL default '', probability double NOT NULL default '0', wordcount bigint(20) NOT NULL default '0',  PRIMARY KEY (catcode))");
		sql_query("CREATE TABLE IF NOT EXISTS ".$this->table_wf." (word varchar(250) NOT NULL default '', catcode varchar(50) NOT NULL default '', wordcount bigint(20) NOT NULL default '0',  PRIMARY KEY (word, catcode))");
		sql_query("CREATE TABLE IF NOT EXISTS ".$this->table_ref." (ref bigint(20) NOT NULL, catcode varchar(250) NOT NULL default '', content text NOT NULL default '',  PRIMARY KEY (ref), KEY(catcode))");
		sql_query("CREATE TABLE IF NOT EXISTS ".$this->table_log." (id bigint(20) NOT NULL auto_increment, log varchar(250) NOT NULL default '', content text NOT NULL default '',  catcode varchar(250) NOT NULL default '', logtime timestamp, PRIMARY KEY (id), KEY(catcode))");
		// create 'ham' and 'spam' categories
		sql_query("insert into ".$this->table_cat." (catcode) values ('ham')");
		sql_query("insert into ".$this->table_cat." (catcode) values ('spam')");
	}

	function unInstall() {
		if ($this->getOption('DropTable') == 'yes') {
			sql_query('drop table if exists '.$this->table_cat);
			sql_query('drop table if exists '.$this->table_ref);
			sql_query('drop table if exists '.$this->table_wf);
			sql_query('drop table if exists '.$this->table_log);
		}
	}

}
