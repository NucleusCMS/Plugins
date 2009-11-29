<?php
// vim: tabstop=2:shiftwidth=2

/**
  * index.php ($Revision: 1.53 $)
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: index.php,v 1.53 2009/11/29 11:41:06 hsur Exp $
*/

/*
  * Copyright (C) 2005-2009 CLES. All rights reserved.
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

$strRel = '../../../';
include ($strRel.'config.php');
include ($DIR_LIBS.'PLUGINADMIN.php');

require_once($DIR_PLUGINS . 'sharedlibs/sharedlibs.php');
require_once('cles/Feedback.php');
require_once('cles/Template.php');

define('NP_CLAP_ITEM_PER_PAGE', 20);

sendContentType('application/xhtml+xml', 'admin-clap', _CHARSET);

// create the admin area page
$oPluginAdmin = new PluginAdmin('Clap');
$oPluginAdmin->start();
$fb =& new cles_Feedback($oPluginAdmin);

if (!($member->isLoggedIn() && $member->isAdmin())){
	echo '<p>' . _ERROR_DISALLOWED . '</p>';
	$oPluginAdmin->end();
	exit;
}

//action
$action = requestVar('action');
$aActionsNotToCheck = array(
	'',
	'report',
);
if (!in_array($action, $aActionsNotToCheck)) {
	if (!$manager->checkTicket()) doError(_ERROR_BADTICKET);
}

$templateEngine =& new cles_Template(dirname(__FILE__).'/template');
define('NP_CLAP_TEMPLATEDIR_INDEX', 'index');
$tplVars = array(
	'indexurl' => serverVar('PHP_SELF'),
	'itemperpage' => NP_CLAP_ITEM_PER_PAGE,
	'optionurl' => $CONF['AdminURL'] . 'index.php?action=pluginoptions&amp;plugid=' . $oPluginAdmin->plugin->getid(),
	'actionurl' => $CONF['ActionURL'],
	'ticket' => $manager->_generateTicket(),
	'plugindirurl' => $oPluginAdmin->plugin->getAdminURL(),
);

// menu
$menu = $templateEngine->fetch('menu', NP_CLAP_TEMPLATEDIR_INDEX);
echo $templateEngine->fill($menu, $tplVars, false);

switch ($action) {
	case 'delete' :
		if( is_numeric(requestVar('id')) ){
			$oPluginAdmin->plugin->deleteClap(intRequestVar('id'));
		}
		$action = 'detail';
		break;
	case 'thanksdelete' :
		if( is_numeric(requestVar('id')) ){
			$oPluginAdmin->plugin->deleteThanksMsg(intRequestVar('id'));
		}
		$action = 'thanksmsg';
		break;
	case 'thankssave' :
		if( is_numeric(requestVar('id')) || requestVar('id') == 'new' ){
			$msg['id'] = requestVar('id');
			$msg['comment'] = requestVar('comment');
			$msg['image'] = requestVar('image');
			$oPluginAdmin->plugin->setThanksMsg($msg);
			
			$oPluginAdmin->plugin->setAssociatedCategories(requestVar('id'), requestArray('assoc'), requestVar('assoc_etc'));
		}
		$action = 'thanksmsg';
	case 'docorrect' :
		$tplVars['message'] = $oPluginAdmin->plugin->_correctBrokenThanksCategory();
		$action = 'thanksmsg';
		break;
}

switch ($action) {
	case 'convert' :
		$content = $templateEngine->fetch('convert', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
		break;
		
	case 'doconvert' :
		$query = 'DELETE FROM ' . sql_table('plugin_clap') . ' where ipaddr = \'127.0.0.2\' ';
		sql_query($query);
		
		$query = 'SELECT inumber, ikarmapos FROM ' . sql_table('item') . ' where ikarmapos > 0';
		$res = sql_query($query);
		
		while( $row = mysql_fetch_assoc($res) ){
			for($i = $row['ikarmapos'] ; $i > 0 ; $i -= 1  ){
			$query = sprintf('INSERT INTO ' . sql_table('plugin_clap') 
					. ' ( `itemkey`, `timestamp`, `ipaddr` ) '
					. " values('%s', '1970-01-01 00:00:00', '127.0.0.2') "
					, mysql_real_escape_string( $row['inumber'] )
			);
			sql_query($query);
			}
		}		
			
		$content = $templateEngine->fetch('doconvert', NP_CLAP_TEMPLATEDIR_INDEX);		$templateEngine->fill($content, $tplVars, null);
		echo $templateEngine->fill($content, $tplVars, null);
		break;

	case 'reset' :
		$content = $templateEngine->fetch('reset', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
		break;
		
	case 'doreset' :
		$query = 'TRUNCATE TABLE ' . sql_table('plugin_clap');
		sql_query($query);
		
		$query = 'TRUNCATE TABLE ' . sql_table('plugin_clap_comment');
		sql_query($query);
	
		$content = $templateEngine->fetch('doreset', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
		break;

	case 'report' :
		$fb->printForm('');
		break;
		
	case 'detail' :
		$tplVars['key'] = requestVar('key') ? requestVar('key') : null;
		if( ! $tplVars['key'] ){
			$tplVars['message'] = '"key" is not set.';
			$content = $templateEngine->fetch('error', NP_CLAP_TEMPLATEDIR_INDEX);
			echo $templateEngine->fill($content, $tplVars, null);
			break;
		}
		$tplVars['offset'] = intRequestVar('offset') ? intRequestVar('offset') : 0;
		
		$res = $oPluginAdmin->plugin->getDetail($tplVars['key'], 0, 99999999);
		$tplVars['rowcount'] = mysql_num_rows($res);

		$res = $oPluginAdmin->plugin->getDetail($tplVars['key'], $tplVars['offset'], $tplVars['itemperpage']);
		if( $tplVars['itemperpage'] + $tplVars['offset'] < $tplVars['rowcount'] ){
			$content = $templateEngine->fetch('detail_nextbutton', NP_CLAP_TEMPLATEDIR_INDEX);
			$tplVars['next_offset'] = $tplVars['offset'] + $tplVars['itemperpage'];
			$tplVars['next_button'] = $templateEngine->fill($content, $tplVars, null);	
		} else {
			$tplVars['itemperpage'] = $tplVars['rowcount'] - $tplVars['offset'];
		}
		
		if( $tplVars['offset'] > 0 ){
			$content = $templateEngine->fetch('detail_prevbutton', NP_CLAP_TEMPLATEDIR_INDEX);
			$tplVars['prev_offset'] = $tplVars['offset'] - NP_CLAP_ITEM_PER_PAGE;
			$tplVars['prev_button'] = $templateEngine->fill($content, $tplVars, null);	
		}

		$content = $templateEngine->fetch('detail_header', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
		
		while( $row = mysql_fetch_assoc($res) ){
			$row = array_merge($tplVars, $row);
			$row['ipaddr'] = ( $row['ipaddr'] == '127.0.0.2' ) ? 'karma' : $row['ipaddr'];
			$row['comment'] = nl2br(htmlspecialchars($row['comment'], ENT_QUOTES));
			$content = $templateEngine->fetch('detail_item', NP_CLAP_TEMPLATEDIR_INDEX);
			echo $templateEngine->fill($content, $row, null);			
		}		
		
		$content = $templateEngine->fetch('detail_footer', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
		
		break;
		
	case 'messagelist' :
		$tplVars['offset'] = intRequestVar('offset') ? intRequestVar('offset') : 0;
		$res = $oPluginAdmin->plugin->getMessageList(0, 99999999);
		$tplVars['rowcount'] = mysql_num_rows($res);

		$res = $oPluginAdmin->plugin->getMessageList($tplVars['offset'], $tplVars['itemperpage']);
		if( $tplVars['itemperpage'] + $tplVars['offset'] < $tplVars['rowcount'] ){
			$content = $templateEngine->fetch('messagelist_nextbutton', NP_CLAP_TEMPLATEDIR_INDEX);
			$tplVars['next_offset'] = $tplVars['offset'] + $tplVars['itemperpage'];
			$tplVars['next_button'] = $templateEngine->fill($content, $tplVars, null);	
		} else {
			$tplVars['itemperpage'] = $tplVars['rowcount'] - $tplVars['offset'];
		}
		
		if( $tplVars['offset'] > 0 ){
			$content = $templateEngine->fetch('messagelist_prevbutton', NP_CLAP_TEMPLATEDIR_INDEX);
			$tplVars['prev_offset'] = $tplVars['offset'] - NP_CLAP_ITEM_PER_PAGE;
			$tplVars['prev_button'] = $templateEngine->fill($content, $tplVars, null);	
		}

		$content = $templateEngine->fetch('messagelist_header', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
		
		while( $row = mysql_fetch_assoc($res) ){
			$row = array_merge($tplVars, $row);
			$row['comment'] = nl2br(htmlspecialchars($row['comment'], ENT_QUOTES));
			$content = $templateEngine->fetch('messagelist_item', NP_CLAP_TEMPLATEDIR_INDEX);
			echo $templateEngine->fill($content, $row, null);			
		}		
		
		$content = $templateEngine->fetch('messagelist_footer', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
		
		break;
		
	case 'thanksmsg' :
		$tplVars['offset'] = intRequestVar('offset') ? intRequestVar('offset') : 0;
		$res = $oPluginAdmin->plugin->getThanksMsgList(0, 99999999);
		$tplVars['rowcount'] = mysql_num_rows($res);
		
		$res = $oPluginAdmin->plugin->getThanksMsgList($tplVars['offset'], $tplVars['itemperpage']);
		if( $tplVars['itemperpage'] + $tplVars['offset'] < $tplVars['rowcount'] ){
			$content = $templateEngine->fetch('thanksmsg_nextbutton', NP_CLAP_TEMPLATEDIR_INDEX);
			$tplVars['next_offset'] = $tplVars['offset'] + $tplVars['itemperpage'];
			$tplVars['next_button'] = $templateEngine->fill($content, $tplVars, null);	
		} else {
			$tplVars['itemperpage'] = $tplVars['rowcount'] - $tplVars['offset'];
		}
		
		if( $tplVars['offset'] > 0 ){
			$content = $templateEngine->fetch('thanksmsg_prevbutton', NP_CLAP_TEMPLATEDIR_INDEX);
			$tplVars['prev_offset'] = $tplVars['offset'] - NP_CLAP_ITEM_PER_PAGE;
			$tplVars['prev_button'] = $templateEngine->fill($content, $tplVars, null);	
		}

		$content = $templateEngine->fetch('thanksmsg_header', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
		
		while( $row = mysql_fetch_assoc($res) ){
			$row = array_merge($tplVars, $row);
			if( trim($row['image']) )
				$row['image'] = '<img border="0" src="'.$oPluginAdmin->plugin->getAdminURL().'silk/picture.png" />';
			else
				$row['image'] = '<img border="0" src="'.$oPluginAdmin->plugin->getAdminURL().'silk/picture_empty.png" />';
			$row['comment'] = shorten(strip_tags($row['comment']),200,'...');
			
			$content = $templateEngine->fetch('thanksmsg_item', NP_CLAP_TEMPLATEDIR_INDEX);
			echo $templateEngine->fill($content, $row, null);			
		}
		
		$content = $templateEngine->fetch('thanksmsg_footer', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
		
		break;
		
	case 'thanksedit':
		if( ! ( is_numeric(requestVar('id')) || requestVar('id') == 'new' ) ){
			$tplVars['message'] = '"id" is not set.';
			$content = $templateEngine->fetch('error', NP_CLAP_TEMPLATEDIR_INDEX);
			echo $templateEngine->fill($content, $tplVars, null);
			break;
		}
		
		$assoc = array();
		if( requestVar('id') != 'new' ){
			$msg = $oPluginAdmin->plugin->getThanksMsg(intRequestVar('id'));
			$assoc = $oPluginAdmin->plugin->getAssociatedCategoriesByThanksId(intRequestVar('id'));
		} else {
			$msg['id'] = 'new';
			$msg['image'] = '<img src="#" alt="" title="" />';
			$assoc[] = NP_CLAP_GLOBALKEY;
		}
		$msg = array_merge($tplVars, $msg);
		
		if( in_array(NP_CLAP_GLOBALKEY, $assoc) )
			$msg['assoc'] = '<input type="checkbox" name="assoc[]" value="'.NP_CLAP_GLOBALKEY.'" checked="checked" />'.NP_CLAP_GLOBALKEY.'<br />';
		else
			$msg['assoc'] = '<input type="checkbox" name="assoc[]" value="'.NP_CLAP_GLOBALKEY.'" />'.NP_CLAP_GLOBALKEY.'<br />';
		
		$blogs = $oPluginAdmin->plugin->getBloglist();
		$res = sql_query('SELECT catid, cname, cblog FROM '.sql_table('category').' ORDER BY cblog, catid');
		if( @mysql_num_rows($res) > 0) {
			$currrentBlog = null;
			while( $o = mysql_fetch_object($res) ){
				if( $currrentBlog != $o->cblog){
					$currrentBlog = $o->cblog;
					$msg['assoc'] .= sprintf(NP_CLAP_ALLCHECK, $o->cblog, $o->cblog);
				}
				
				if( in_array($o->catid, $assoc) )
					$tpl_checkbox = '<input id="%s" type="checkbox" name="assoc[]" value="%s" checked="checked" />%s<br />';
				else
					$tpl_checkbox = '<input id="%s" type="checkbox" name="assoc[]" value="%s" />%s<br />';
				$msg['assoc'] .= sprintf($tpl_checkbox, 
					htmlspecialchars($o->cblog.'_'.$o->catid, ENT_QUOTES),
					htmlspecialchars($o->catid, ENT_QUOTES),
					htmlspecialchars($o->cname.' ( '.$blogs[$o->cblog].' )', ENT_QUOTES)
				);
			}
		}
		
		$assoc_etc = array();
		foreach ( $assoc as $key) {
			if( $key == NP_CLAP_GLOBALKEY ) continue; 
			if( is_numeric($key) ) continue; 
			$assoc_etc[] = $key;
		}
		$msg['assoc_etc'] = implode(',', $assoc_etc);
		
		$content = $templateEngine->fetch('thanksedit', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $msg, null);
		break;
		
	case 'chart' :
		$content = $templateEngine->fetch('chart', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
		break;

	case 'overview' :
	default :
		$tplVars['offset'] = intRequestVar('offset') ? intRequestVar('offset') : 0;
		$tplVars['blog'] = requestVar('blog') ? requestVar('blog') : '' ;
		
		$res = $oPluginAdmin->plugin->getOverview($tplVars['blog'], 0, 99999999);
		$tplVars['rowcount'] = mysql_num_rows($res);

		$res = $oPluginAdmin->plugin->getOverview($tplVars['blog'], $tplVars['offset'], $tplVars['itemperpage']);
		if( $tplVars['itemperpage'] + $tplVars['offset'] < $tplVars['rowcount'] ){
			$content = $templateEngine->fetch('overview_nextbutton', NP_CLAP_TEMPLATEDIR_INDEX);
			$tplVars['next_offset'] = $tplVars['offset'] + $tplVars['itemperpage'];
			$tplVars['next_button'] = $templateEngine->fill($content, $tplVars, null);	
		} else {
			$tplVars['itemperpage'] = $tplVars['rowcount'] - $tplVars['offset'];
		}
				
		if( $tplVars['offset'] > 0 ){
			$content = $templateEngine->fetch('overview_prevbutton', NP_CLAP_TEMPLATEDIR_INDEX);
			$tplVars['prev_offset'] = $tplVars['offset'] - NP_CLAP_ITEM_PER_PAGE;
			$tplVars['prev_button'] = $templateEngine->fill($content, $tplVars, null);
		}
		
		$blogs = $oPluginAdmin->plugin->getBloglist();
		$tplVars['blogselect'] .= '<form method="post" name="blogselect" action="'.$tplVars['indexurl'].'" style="display:inline" >';
		$tplVars['blogselect'] .= '<input type="hidden" name="action" value="overview" />';
		$tplVars['blogselect'] .= '<input type="hidden" name="offset" value="0" />';
		$tplVars['blogselect'] .= '<input type="hidden" name="ticket" value="' . $tplVars['ticket'] . '" />';
		$tplVars['blogselect'] .= '<select name="blog" onchange="javascript:submit()">';
		$tplVars['blogselect'] .= '<option value="">ALL</option>';		
		$tplVars['blogselect'] .= ( $tplVars['blog'] == 'global' ) ? '<option value="global" selected="selected">global</option>' : '<option value="global">global</option>';
		foreach($blogs as $id => $name){
			$tplVars['blogselect'] .= ( $tplVars['blog'] == $id ) ? '<option value="'.intval($id).'" selected="selected">'.htmlspecialchars($name,ENT_QUOTES).'</option>' : '<option value="'.intval($id).'">'.htmlspecialchars($name,ENT_QUOTES).'</option>';
		}		
		$tplVars['blogselect'] .= '</select></form>';
		
		$content = $templateEngine->fetch('overview_header', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
		
		while( $row = mysql_fetch_assoc($res) ){
			$row = array_merge($tplVars, $row);
			$row['blogname'] = $blogs[$row['blog']];
			$content = $templateEngine->fetch('overview_item', NP_CLAP_TEMPLATEDIR_INDEX);
			echo $templateEngine->fill($content, $row, null);			
		}		
		
		$content = $templateEngine->fetch('overview_footer', NP_CLAP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
				
		break;
}

echo '<div align="right">Powered by <a href="http://www.famfamfam.com/lab/icons/silk/">Silk icon</a></div>';

$oPluginAdmin->end();
