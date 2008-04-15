<?php
/*
	NP_LinkList (based on NP_LinksByBlog)
	by Jim Stone
	   fel
	   nakahara21 (http://nakahara21.com)
	   yu (http://nucleus.datoka.jp/)
	
	functions.php (for admin page)
	------------------------------
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
	
*/

/* ----- main functions ----- */

function _linklist_index($msg='') {
	_linklist_makeDialog($msg);
	_linklist_makeList();
	_linklist_makeLinkForm();
	_linklist_makeGroupForm();
	_linklist_makeNav();
}

function _linklist_detail() {
	$type = postVar('type');
	_linklist_makeDetail($type);
	_linklist_makeNav();
}

function _linklist_add() {
	$type = postVar('type');
	$msg = _linklist_doAdd($type);
	_linklist_index($msg);
}

function _linklist_modify() {
	$type = postVar('type');
	$msg = _linklist_doModify($type);
	_linklist_index($msg);
}

function _linklist_quickmod() {
	$type = postVar('type');
	$msg = _linklist_doQuickModify($type);
	_linklist_index($msg);
}

function _linklist_delete() {
	$type = postVar('type');
	$msg = _linklist_doDelete($type);
	_linklist_index($msg);
}

function _linklist_dbupdate() {
	global $oPluginAdmin;
	
	$oPluginAdmin->plugin->update();
	$msg = 'Database was updated.';
	_linklist_index($msg);
}

function _linklist_updatefunc() {
	global $oPluginAdmin;
	_linklist_index($msg);
}


/* ----- Operation ----- */

function _linklist_doAdd($type) {
	global $member, $oPluginAdmin;
	
	$msg = "";
	
	switch ($type) {
	case 'link':
		$arr_post = array(
			'title'       => 'str',
			'description' => 'str',
			'url'         => 'str',
			'imgsrc'      => 'str',
			'gid'         => 'int',
			'sortkey'     => 'int',
			);
		$vars = _linklist_postVarFilter($arr_post);
		if ( !$oPluginAdmin->plugin->_checkGroupList($vars['gid']) ) { //check
			$isok = false;
			break;
		}
		$isok = _linklist_db_insertLink($vars);
		$msg = _LINKLIST_ADMIN_MSG1;
		break;
	case 'group':
		$_POST['bid'] = join(',', $_POST['bid']); //array to str
		$arr_post = array(
			'title'       => 'str',
			'description' => 'str',
			'symbol'      => 'str',
			'bid'         => 'str',
			'sortkey'     => 'str',
			);
		$vars = _linklist_postVarFilter($arr_post);
		if ( !$oPluginAdmin->plugin->_checkBlogList($vars['bid']) ) { //check
			$isok = false;
			break;
		}
		$isok = _linklist_db_insertGroup($vars);
		$msg = _LINKLIST_ADMIN_MSG2;
		$oPluginAdmin->plugin->init_grp(true); //update $arr_grp
		break;
	}
	if (!$isok) $msg = _LINKLIST_ADMIN_ERR1 ."\n". mysql_error();
	return $msg;
}

function _linklist_doModify($type) {
	global $member, $oPluginAdmin;
	
	$msg = "";
	
	switch ($type) {
	case 'link':
		$arr_post = array(
			'id'          => 'int',
			'title'       => 'str',
			'description' => 'str',
			'url'         => 'str',
			'imgsrc'      => 'str',
			'gid'         => 'int',
			'sortkey'     => 'int',
			);
		$vars = _linklist_postVarFilter($arr_post);
		if ( !$oPluginAdmin->plugin->_checkGroupList($vars['gid']) ) { //check
			$isok = false;
			break;
		}
		$isok = _linklist_db_updateLink($vars);
		$msg = _LINKLIST_ADMIN_MSG3;
		break;
	case 'group':
		$_POST['bid'] = join(',', $_POST['bid']); //array to str
		$arr_post = array(
			'id'          => 'int',
			'title'       => 'str',
			'description' => 'str',
			'symbol'      => 'str',
			'bid'         => 'str',
			'sortkey'     => 'str',
			);
		$vars = _linklist_postVarFilter($arr_post);
		if ( !$oPluginAdmin->plugin->_checkBlogList($vars['bid']) ) { //check
			$isok = false;
			break;
		}
		$isok = _linklist_db_updateGroup($vars);
		$msg = _LINKLIST_ADMIN_MSG4;
		$oPluginAdmin->plugin->init_grp(true); //update $arr_grp
		break;
	}
	if (!$isok) $msg = _LINKLIST_ADMIN_ERR2 ."\n". mysql_error();
	return $msg;
}

function _linklist_doQuickModify($type) {
	global $oPluginAdmin;
	
	$msg = "";
	
	switch ($type) {
	case 'link':
		$arr_post = array(
			'id'          => 'int',
			'gid'         => 'int',
			'sortkey'     => 'int',
			);
		$vars = _linklist_postVarFilter($arr_post);
		if ( !$oPluginAdmin->plugin->_checkGroupList($vars['gid']) ) { //check
			$isok = false;
			break;
		}
		$isok = _linklist_db_updateLink($vars, 'quick');
		$msg = _LINKLIST_ADMIN_MSG3;
		break;
	case 'group':
		$arr_post = array(
			'id'          => 'int',
			'sortkey'     => 'str',
			);
		$vars = _linklist_postVarFilter($arr_post);
		$isok = _linklist_db_updateGroup($vars, 'quick');
		$msg = _LINKLIST_ADMIN_MSG4;
		$oPluginAdmin->plugin->init_grp(true); //update $arr_grp
		break;
	}
	if (!$isok) $msg = _LINKLIST_ADMIN_ERR2 ."\n". mysql_error();
	return $msg;
}

function _linklist_doDelete($type) {
	global $oPluginAdmin;
	
	$msg = "";
	
	switch ($type) {
	case 'link':
		$arr_post = array(
			'id'          => 'int',
			);
		$vars = _linklist_postVarFilter($arr_post);
		$vars['tblname'] = 'plug_linklist';
		$vars['colname'] = 'id';
		$link = _linklist_db_getLinkData($vars['id']);
		if ( !$oPluginAdmin->plugin->_checkGroupList($link['gid']) ) { //check
			$isok = false;
			break;
		}
		$isok = _linklist_db_deleteRow($vars);
		$msg = _LINKLIST_ADMIN_MSG5;
		break;
	case 'group':
		//delete group
		$arr_post = array(
			'id'          => 'int',
			);
		$vars = _linklist_postVarFilter($arr_post);
		$vars['tblname'] = 'plug_linklist_grp';
		$vars['colname'] = 'id';
		
		$bid = 0;
		foreach ($oPluginAdmin->plugin->arr_grp as $grp) {
			if ($grp->id == $vars['id']) $bid = $grp->bid;
		}
		if ( !$oPluginAdmin->plugin->_checkBlogList($bid) ) { //check
			$isok = false;
			break;
		}
		$isok = _linklist_db_deleteRow($vars);
		if (!$isok) break;
		$oPluginAdmin->plugin->init_grp(true); //update $arr_grp
		
		//delete links belong to the group
		$vars['tblname'] = 'plug_linklist';
		$vars['colname'] = 'gid';
		$isok = _linklist_db_deleteRow($vars);
		$msg = _LINKLIST_ADMIN_MSG6;
		break;
	}
	
	if (!$isok) $msg = _LINKLIST_ADMIN_ERR3 ."\n". mysql_error();
	return $msg;
}


/* ----- DB operation ----- */

function _linklist_db_getLinkData($id) {
	$query = sprintf("SELECT * FROM %s WHERE id=%d",
		sql_table('plug_linklist'),
		quote_smart($id)
		);
	$result = mysql_query($query);
	$row = mysql_fetch_assoc($result);
	return stripslashes_array($row);
}

function _linklist_db_getGroupData($id) {
	$query = sprintf("SELECT * FROM %s WHERE id=%d",
		sql_table('plug_linklist_grp'),
		quote_smart($id)
		);
	$result = mysql_query($query);
	$row = mysql_fetch_assoc($result);
	return stripslashes_array($row);
}

function _linklist_db_insertLink($vars) {
	$query = sprintf("INSERT INTO %s (title,description,url,imgsrc,gid,sortkey) VALUES (%s, %s, %s, %s, %d, %d)",
		sql_table('plug_linklist'),
		quote_smart($vars['title']),
		quote_smart($vars['description']),
		quote_smart($vars['url']),
		quote_smart($vars['imgsrc']),
		quote_smart($vars['gid']),
		quote_smart($vars['sortkey']) //num
		);
	$result = mysql_query($query);
	return $result;
}

function _linklist_db_insertGroup($vars) {
	$query = sprintf("INSERT INTO %s (title,description,symbol,bid,sortkey) VALUES (%s, %s, %s, %s, %s)",
		sql_table('plug_linklist_grp'),
		quote_smart($vars['title']),
		quote_smart($vars['description']),
		quote_smart($vars['symbol']),
		quote_smart($vars['bid']), //(comma-joined) string
		quote_smart($vars['sortkey']) //alphabet
		);
	$result = mysql_query($query);
	return $result;
}

function _linklist_db_updateLink($vars, $mode='') {
	if ($mode == 'quick') {
		$query = sprintf("UPDATE %s SET gid=%d, sortkey=%d".
			" WHERE id=%d",
			sql_table("plug_linklist"),
			quote_smart($vars['gid']),
			quote_smart($vars['sortkey']), //num
			quote_smart($vars['id'])
			);
	}
	else {
		$query = sprintf("UPDATE %s SET title=%s, description=%s, url=%s, imgsrc=%s, gid=%d, sortkey=%d".
			" WHERE id=%d",
			sql_table("plug_linklist"),
			quote_smart($vars['title']),
			quote_smart($vars['description']),
			quote_smart($vars['url']),
			quote_smart($vars['imgsrc']),
			quote_smart($vars['gid']),
			quote_smart($vars['sortkey']), //num
			quote_smart($vars['id'])
			);
	}
	$result = mysql_query($query);
	return $result;
}

function _linklist_db_updateGroup($vars, $mode='') {
	if ($mode == 'quick') {
		$query = sprintf("UPDATE %s SET sortkey=%s".
			" WHERE id=%d",
			sql_table("plug_linklist_grp"),
			quote_smart($vars['sortkey']), //alphabet
			quote_smart($vars['id'])
			);
	}
	else {
		$query = sprintf("UPDATE %s SET title=%s, description=%s, symbol=%s, bid=%s, sortkey=%s".
			" WHERE id=%d",
			sql_table("plug_linklist_grp"),
			quote_smart($vars['title']),
			quote_smart($vars['description']),
			quote_smart($vars['symbol']),
			quote_smart($vars['bid']), //(comma-joined) string
			quote_smart($vars['sortkey']), //alphabet
			quote_smart($vars['id'])
			);
	}
	$result = mysql_query($query);
	return $result;
}

function _linklist_db_deleteRow($vars) {
	$query = sprintf("DELETE FROM %s WHERE %s=%d",
		sql_table($vars['tblname']),
		$vars['colname'],
		quote_smart($vars['id'])
		);
	$result = mysql_query($query);
	return $result;
}


/* ----- building page ----- */

/* -- main page -- */

function _linklist_makeList() {
	global $member, $oPluginAdmin;
	
	$flg_admin = $member->isAdmin();
	$arr_grp = $oPluginAdmin->plugin->arr_grp;
	foreach ($arr_grp as $grp) {
			_linklist_showGroup($grp);
			_linklist_showLink($grp->id);
	}
}

function _linklist_showGroup($grp) {
	global $manager, $pluginUrl;
	global $oPluginAdmin;
	
	//get group sortkey array
	$arr_skey = $oPluginAdmin->plugin->arr_skey;
	
	$th = array(
		_LINKLIST_ADMIN_GRP_TH5, //quickmod
		_LINKLIST_ADMIN_GRP_TH2,
		_LINKLIST_ADMIN_GRP_TH3,
		_LINKLIST_ADMIN_GRP_TH4,
		_LINKLIST_ADMIN_GRP_TH6,
		);
	
	$grp = get_object_vars($grp); //object to array
	$grp['title'] = _linklist_makeEmptyNone($grp['title']);
	$grp['title'] = _LINKLIST_ADMIN_GRP_TH1 .': '. $grp['title'];
	$grp['symbol'] = _linklist_makeEmptyNone($grp['symbol']);
	$grp['description'] = _linklist_makeEmptyNone($grp['description']);
	$grp['description'] = shorten($grp['description'], 15, '..');
	$grp = _linklist_escVar($grp);
	
	$select_sortkey = _linklist_makeSelect('sortkey', $arr_skey, $grp['sortkey']);
	$select_bid = _linklist_makeSelect('blogselected', $grp['bid']);
	$btn_update = _LINKLIST_ADMIN_BTN_UPDATE;
	$btn_detail = _LINKLIST_ADMIN_BTN_DETAIL;
	$btn_delete = _LINKLIST_ADMIN_BTN_DELETE;
	
	echo <<<OUT
<h3>{$grp['title']}</h3>
<table class="group">
<tr>
	<th style="width:100px; white-space:nowrap;">{$th[0]}</th>
	<th>{$th[1]}</th>
	<th>{$th[2]}</th>
	<th>{$th[3]}</th>
	<th style="white-space:nowrap;">{$th[4]}</th>
</tr>
<tr>
	<td>
		<form class="button" method="post" action="{$pluginUrl}">
		<input type="hidden" name="action" value="quickmod" />
		<input type="hidden" name="type" value="group" />
		<input type="hidden" name="id" value="{$grp['id']}" />
		<input type="submit" name="submit" value="{$btn_update}" />
		{$select_sortkey}
OUT;
	$manager->addTicketHidden();
	echo <<<OUT
		</form>
	</td>
	<td>{$grp['description']}</td>
	<td>{$grp['symbol']}</td>
	<td>{$select_bid}</td>
	<td>
		<form class="button" method="post" action="{$pluginUrl}">
		<input type="hidden" name="action" value="detail" />
		<input type="hidden" name="type" value="group" />
		<input type="hidden" name="id" value="{$grp['id']}" />
		<input type="submit" name="submit" value="{$btn_detail}" />
OUT;
	$manager->addTicketHidden();
	echo <<<OUT
		</form>
		<form class="button" method="post" action="{$pluginUrl}" onsubmit="return confirm_check('Delete?')">
		<input type="hidden" name="action" value="delete" />
		<input type="hidden" name="type" value="group" />
		<input type="hidden" name="id" value="{$grp['id']}" />
		<input type="submit" name="submit" value="{$btn_delete}" />
OUT;
	$manager->addTicketHidden();
	echo <<<OUT
		</form>
	</td>
</tr>
</table>
OUT;
}

function _linklist_showLink($gid) {
	global $manager, $pluginUrl;
	global $oPluginAdmin;
	
	$arr_sel = array(); // for group select list
	$arr_grp = $oPluginAdmin->plugin->arr_grp;
	foreach ($arr_grp as $grp) {
		$arr_sel[ $grp->id ] = $grp->title;
	}
	
	$query = sprintf("SELECT * FROM %s ".
		"WHERE gid=%s ORDER BY sortkey,title",
		sql_table('plug_linklist'),
		quote_smart($gid)
		);
	$res = sql_query($query);
	$flg_exists = mysql_num_rows($res);
	
	if ($flg_exists) {
		$th = array(
			_LINKLIST_ADMIN_LIST_TH5, //quickmod
			_LINKLIST_ADMIN_LIST_TH6, //quickmod
			_LINKLIST_ADMIN_LIST_TH1 .' ('. _LINKLIST_ADMIN_LIST_TH2 .')',
			_LINKLIST_ADMIN_LIST_TH3,
			_LINKLIST_ADMIN_LIST_TH4,
			_LINKLIST_ADMIN_LIST_TH7,
			);
		
		echo <<<OUT
<table class="link">
<tr>
	<th>{$th[0]}</th>
	<th>{$th[1]}</th>
	<th>{$th[2]}</th>
	<th>{$th[3]}</th>
	<th>{$th[4]}</th>
	<th style="white-space:nowrap;">{$th[5]}</th>
</tr>
OUT;
	}
	
	$rowcheck = 0;
	while ($li = mysql_fetch_assoc($res)) {
		//prepare
		$li = stripslashes_array($li);
		$li['description'] = _linklist_makeEmptyNone($li['description']);
		$li['description'] = shorten($li['description'], 20, '..');
		$li = _linklist_escVar($li);
		$li['imgsrc']      = _linklist_makeImageLink($li['imgsrc']);
		$select_gid = _linklist_makeSelect('gid', $arr_sel, $li['gid']);
		$btn_update = _LINKLIST_ADMIN_BTN_UPDATE;
		$btn_detail = _LINKLIST_ADMIN_BTN_DETAIL;
		$btn_delete = _LINKLIST_ADMIN_BTN_DELETE;
		
		$rowcheck = ($rowcheck) ? 0 : 1; // change row color
		if ($rowcheck) $rowstr = '';
		else $rowstr = 'class="stripe"';
		
		echo <<<OUT
<tr>
	<form class="button" method="post" action="{$pluginUrl}">
	<input type="hidden" name="action" value="quickmod" />
	<input type="hidden" name="type" value="link" />
	<input type="hidden" name="id" value="{$li['id']}" />
	<td {$rowstr} colspan="2">
		<input type="submit" name="submit" value="{$btn_update}" />
		<input type="text" name="sortkey" value="{$li['sortkey']}" size="3" onkeyup="checkNumeric(this)" onblur="checkNumeric(this)" />
		{$select_gid}
	</td>
OUT;
		$manager->addTicketHidden();
		echo <<<OUT
	</form>
	<td {$rowstr}><a href="{$li['url']}" title="{$li['url']}">{$li['title']}</a></td>
	<td {$rowstr}>{$li['imgsrc']}</td>
	<td {$rowstr}>{$li['description']}</td>
	<td {$rowstr}>
		<form class="button" method="post" action="{$pluginUrl}">
		<input type="hidden" name="action" value="detail" />
		<input type="hidden" name="type" value="link" />
		<input type="hidden" name="id" value="{$li['id']}" />
		<input type="submit" name="submit" value="{$btn_detail}" />
OUT;
		$manager->addTicketHidden();
		echo <<<OUT
		</form>
		<form class="button" method="post" action="{$pluginUrl}" onsubmit="return confirm_check('Delete?')">
		<input type="hidden" name="action" value="delete" />
		<input type="hidden" name="type" value="link" />
		<input type="hidden" name="id" value="{$li['id']}" />
		<input type="submit" name="submit" value="{$btn_delete}" />
OUT;
		$manager->addTicketHidden();
		echo <<<OUT
		</form>
	</td>
</tr>
OUT;
	}

	if ($flg_exists) {
		echo <<<OUT
</table>
OUT;
	}
}

/* -- detail page -- */

function _linklist_makeDetail($type) {
	$id = intPostVar('id');
	
	switch ($type) {
	case 'group':
		_linklist_makeGroupForm($id);
		break;
	case 'link':
		_linklist_makeLinkForm($id);
		break;
	}
}

/* -- form -- */

function _linklist_makeLinkForm($id=0) {
	global $manager, $member, $pluginUrl;
	global $oPluginAdmin;
	
	$arr_sel = array(); // for group select list
	$arr_grp = $oPluginAdmin->plugin->arr_grp;
	foreach ($arr_grp as $grp) {
		$arr_sel[ $grp->id ] = $grp->title;
	}
	
	if (empty($id)) {
		$title = _LINKLIST_ADMIN_FORM_LIST1;
		$action = 'add';
		$val = array(
			'id' => '',
			'title' => '',
			'url' => 'http://',
			'imgsrc' => '',
			'description' => '',
			'sortkey' => '20',
			'gid' => '',
			);
		$btn_submit = _LINKLIST_ADMIN_BTN_ADDLIST;
	}
	else {
		$title = _LINKLIST_ADMIN_FORM_LIST2;
		$action = 'modify';
		$val = _linklist_db_getLinkData($id);
		$btn_submit = _LINKLIST_ADMIN_BTN_UPDLIST;
	}
	
	$th = array(
		_LINKLIST_ADMIN_LIST_TH1,
		_LINKLIST_ADMIN_LIST_TH2,
		_LINKLIST_ADMIN_LIST_TH3,
		_LINKLIST_ADMIN_LIST_TH4,
		_LINKLIST_ADMIN_LIST_TH5,
		_LINKLIST_ADMIN_LIST_TH6,
		);
	$select_gid = _linklist_makeSelect('gid', $arr_sel, $val['gid']);
	
	echo <<<OUT
<h3>{$title}</h3>
<table>
<form method="post" action="{$pluginUrl}">
<input type="hidden" name="action" value="{$action}" />
<input type="hidden" name="type" value="link" />
<input type="hidden" name="id" value="{$val['id']}" />
OUT;
	$manager->addTicketHidden();
	echo <<<OUT
<tr>
	<th>{$th[0]}</th><td><input type="text" name="title" value="{$val['title']}" size="60" /></td>
</tr>
<tr>
	<th>{$th[1]}</th><td><input type="text" name="url" value="{$val['url']}" size="60" /></td>
</tr>
<tr>
	<th>{$th[2]}</th><td><input type="text" name="imgsrc" value="{$val['imgsrc']}" size="60" /></td>
</tr>
<tr>
	<th>{$th[3]}</th><td><input type="text" name="description" value="{$val['description']}" size="60" /></td>
</tr>
<tr>
	<th>{$th[4]} (0~255)</th><td><input type="text" name="sortkey" value="{$val['sortkey']}" size="3" onkeyup="checkNumeric(this)" onblur="checkNumeric(this)" /></td>
</tr>
<tr>
	<th>{$th[5]}</th><td>{$select_gid}</td>
</tr>
<tr>
	<th></th><td><input type="submit" name="submit" value="{$btn_submit}" /></td>
</tr>
</form>
</table>

OUT;
}

function _linklist_makeGroupForm($id=0) {
	global $manager, $member, $pluginUrl;
	global $oPluginAdmin;
	
	//get bloglist array
	$arr_sel = array();
	$edit = $oPluginAdmin->plugin->getOption('sel_edit');
	if ( $member->isAdmin() ) $arr_sel[0] = 'ALL';
	$query = "SELECT bnumber, bname FROM ".sql_table('blog')." ORDER BY bnumber";
	$res = sql_query($query);
	while ($row = mysql_fetch_assoc($res)) {
		if ( $member->isAdmin() or 
			($edit == 'blogadmin' and $member->blogAdminRights($row['bnumber'])) or 
			($edit == 'blogteam' and $member->teamRights($row['bnumber']))
			) {
			$arr_sel[ $row['bnumber'] ] = shorten($row['bname'], 15, '..');
		}
	}
	
	//get group sortkey array
	$arr_skey = $oPluginAdmin->plugin->arr_skey;
	
	if (empty($id)) {
		$title = _LINKLIST_ADMIN_FORM_GRP1;
		$action = 'add';
		$val = array(
			'id' => '',
			'title' => '',
			'description' => '',
			'symbol' => '',
			'sortkey' => 'm',
			'bid' => '',
			);
		$btn_submit = _LINKLIST_ADMIN_BTN_ADDGRP;
	}
	else {
		$title = _LINKLIST_ADMIN_FORM_GRP2;
		$action = 'modify';
		$val = _linklist_db_getGroupData($id);
		$btn_submit = _LINKLIST_ADMIN_BTN_UPDGRP;
	}
	
	$th = array(
		_LINKLIST_ADMIN_GRP_TH1,
		_LINKLIST_ADMIN_GRP_TH2,
		_LINKLIST_ADMIN_GRP_TH3,
		_LINKLIST_ADMIN_GRP_TH5,
		_LINKLIST_ADMIN_GRP_TH4,
		);
	$select_sortkey = _linklist_makeSelect('sortkey', $arr_skey, $val['sortkey']);
	$select_bid = _linklist_makeSelect('bid[]', $arr_sel, explode(',', $val['bid']));
	
	echo <<<OUT
<h3>{$title}</h3>
<table>
<form method="post" action="{$pluginUrl}">
<input type="hidden" name="action" value="{$action}" />
<input type="hidden" name="type" value="group" />
<input type="hidden" name="id" value="{$val['id']}" />
OUT;
	$manager->addTicketHidden();
	echo <<<OUT
<tr>
	<th>{$th[0]}</th><td><input type="text" name="title" value="{$val['title']}" size="60" /></td>
</tr>
<tr>
	<th>{$th[1]}</th><td><input type="text" name="description" value="{$val['description']}" size="60" /></td>
</tr>
<tr>
	<th>{$th[2]}</th><td><input type="text" name="symbol" value="{$val['symbol']}" size="60" /></td>
</tr>
<tr>
	<th>{$th[3]} (A~Z)</th><td>{$select_sortkey} (Z=hidden)</td>
</tr>
<tr>
	<th>{$th[4]}</th><td>{$select_bid}</td>
</tr>
<tr>
	<th></th><td><input type="submit" name="submit" value="{$btn_submit}" /></td>
</tr>
</form>
</table>
OUT;
}

function _linklist_updateForm() {
	global $manager, $pluginUrl;
	
	echo <<<OUT
<h2>Update Database</h2>
<p>For version 0.6, it needs to update database.</p>
<form method="post" action="{$pluginUrl}">
<input type="hidden" name="action" value="dbupdate" />
OUT;
	$manager->addTicketHidden();
	echo <<<OUT
<input type="submit" value="Click this button to update" />
</form>
OUT;
}

/* -- misc (parts) -- */

function _linklist_makeSelect($mode, $data, $default='') {
	global $member;
	
	$arr = array();
	$str = '';
	$arr_def = (array)$default;
	
	$size = '';
	$multiple = '';
	
	switch ($mode) {
	case 'bid[]':
		$size = 'size="3"';
		$multiple = 'multiple="multiple"';
		$arr =& $data;
		if (count($arr) < 2) { //set default
			$arr_def = array_keys($arr);
		}
		if (!$member->isAdmin() and 
			count($arr_def) == 1 and 
			empty($arr_def[0]) ) {
			$arr_def[0] = array_shift( array_keys($arr) );
		}
		break;
	case 'gid':
	case 'sortkey': //alphabet (group)
		$arr =& $data;
		break;
	case 'blogselected':
		if ($data == 0 or 
			!preg_match("/^[0-9,]+$/", $data)) {
			//hidden
			$arr[0] = 'ALL';
		}
		else {
			//get blogname from ids
			$query = sprintf("SELECT bnumber, bname FROM %s ".
				"WHERE bnumber IN (%s) ORDER BY bnumber",
				sql_table('blog'),
				$data //as it is ... no quote_smart
				);
			$res = sql_query($query);
			while ($row = mysql_fetch_assoc($res)) {
				$arr[ $row['bnumber'] ] = shorten($row['bname'], 15, '..');
			}
		}
		break;
	}
	
	$str .= <<<OUT
<select name="$mode" $size $multiple>
OUT;
	foreach ($arr as $key => $val) {
		$selected = ( in_array($key, $arr_def) ) ? 'selected="selected"' : '';
		if ($mode == 'bid[]') $val = "$key:$val";
		$str .= <<<OUT
<option value="$key" $selected>$val</option>
OUT;
	}
	$str .= "</select>";
	
	return $str;
}

function _linklist_makeEmptyNone($str) {
	return (empty($str)) ? '(none)' : $str;
}

function _linklist_makeImageLink($str) {
	global $oPluginAdmin;
	
	if (!empty($str)) {
		if (! preg_match('{^http://}', $str) ) //add image path
			$str = $oPluginAdmin->plugin->getOption('path_image') . $str;
		return "<a href='$str' title='$str'>Y</a>";
	}
	else {
		return 'N';
	}
}

function _linklist_makeDialog($msg) {
	if ($msg) {
		$msg = htmlspecialchars($msg);
		$msg = nl2br($msg);
		echo <<<OUT
<p class="message">{$msg}<p>
OUT;
	}
}

/* -- navigation link -- */

function _linklist_makeNav() {
	global $manager, $member, $pluginUrl;
	global $oPluginAdmin, $CONF;
	
	$navtitle = _LINKLIST_ADMIN_STR2;
	
	$li = array();
	
	$li['url'][]  = $pluginUrl;
	$li['text'][] = _LINKLIST_ADMIN_NAV1;
	if ($member->isAdmin()) {
		$li['url'][]  = $CONF['AdminURL'] ."index.php?action=pluginoptions&amp;plugid=". $oPluginAdmin->plugin->plugid;
		$li['text'][] = _LINKLIST_ADMIN_NAV2;
	}
	
	echo <<<OUT
<h2>{$navtitle}</h2>
<ul>
OUT;
	for ($i=0; $i < count($li['url']); $i++) {
		$url  = $li['url'][$i];
		$text = $li['text'][$i];
		echo "<li><a href='{$url}'>{$text}</a></li>";
	}
	echo <<<OUT
</ul>
OUT;
}


/* ----- helper functions ----- */

function _linklist_getVarFilter($arr_chk) {
	$arr_filter = array();
	
	if (is_array($arr_chk)) {
		foreach ($arr_chk as $key => $chk) {
			$chk_func = "_linklist_{$chk}Var";
			$arr_filter[$key] = $chk_func($_GET[$key]);
		}
	}
	
	return $arr_filter;
}
function _linklist_postVarFilter($arr_chk) {
	$arr_filter = array();
	
	if (is_array($arr_chk)) {
		foreach ($arr_chk as $key => $chk) {
			$chk_func = "_linklist_{$chk}Var";
			$arr_filter[$key] = $chk_func($_POST[$key]);
		}
	}
	
	return $arr_filter;
}
function _linklist_intVar($val) {
	return (int)$val;
}
function _linklist_strVar($val) {
	return (string)$val;	
}
function _linklist_escVar($val) {
	return _linklist_htmlEsc($val);	
}
function _linklist_unescVar($val) {
	return _linklist_htmlUnEsc($val);	
}

function _linklist_htmlEsc($value) {
	$value = (is_array($value)) ?
		array_map("_linklist_htmlEsc", $value) :
		htmlspecialchars($value, ENT_QUOTES);
	
	return $value;
}

function _linklist_htmlUnEsc($value) {
	if (is_array($value)) {
		$value = array_map("_linklist_htmlUnEsc", $value);
	}
	else {
		$arr_escaped = array(
			'&amp;',
			'&quot;',
			'&#039;',
			'&lt;',
			'&gt;',
			);
		$arr_unesc = array(
			'&',
			'"',
			"'",
			'<',
			'>',
			);
		$value = str_replace($arr_escaped, $arr_unesc, $value);
	}
	
	return $value;
}


?>
