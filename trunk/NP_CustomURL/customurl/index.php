<?php
//
//	URL configuration plugin "NP_CustomURL" ADMIN page
//

	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../';

	include($strRel . 'config.php');
	include($DIR_LIBS . 'PLUGINADMIN.php');

	// create the admin area page
	$oPluginAdmin = new PluginAdmin('CustomURL');

	$language = ereg_replace( '[\\|/]', '', getLanguageName());
	if (file_exists($oPluginAdmin->plugin->getDirectory().'language/'.$language.'.php')) {
		include_once($oPluginAdmin->plugin->getDirectory().'language/'.$language.'.php');
	}else {
		include_once($oPluginAdmin->plugin->getDirectory().'language/english.php');
	}

	if (!($member->isLoggedIn() && $member->isAdmin())) {
		ACTIONLOG::add(WARNING, _ACTIONLOG_DISALLOWED . $HTTP_SERVER_VARS['REQUEST_URI']);
		$myAdmin->error(_ERROR_DISALLOWED);
	}

class CustomURL_ADMIN
{

	function CustomURL_ADMIN()
	{
		global $manager, $CONF, $oPluginAdmin;

		$this->plugin =& $oPluginAdmin->plugin;
		$this->name = $this->plugin->getName();
		$this->adminurl = $this->plugin->getAdminURL();
		$this->editurl = $CONF['adminURL'];
		$this->table = sql_table('plug_customurl');
		$this->uScat = ($manager->pluginInstalled('NP_MultipleCategories') == TRUE);
		if ($manager->pluginInstalled('NP_MultipleCategories')) {
			$mplugin =& $manager->getPlugin('NP_MultipleCategories');
			if (method_exists($mplugin,"getRequestName")) {
				$this->mcadmin = $mplugin->getAdminURL();
				global $subcatid;
			}
		}

	}

	function action($action)
	{
		$methodName = 'action_'.$action;
		if (method_exists($this, $methodName)) {
			call_user_func(array(&$this, $methodName));
		} else {
			$this->error(_BADACTION . " ($action)");
		}
	}

	function disallow()
	{
		global $HTTP_SERVER_VARS;

		ACTIONLOG::add(WARNING, _ACTIONLOG_DISALLOWED . $HTTP_SERVER_VARS['REQUEST_URI']);
		$msg = array (0, _ERROR_DISALLOWED, '***', _DISALLOWED_MSG);
		$this->error($msg);
	}

	function error($msg = '')
	{
		global $oPluginAdmin;

		$oPluginAdmin->start();
		echo $msg[1].'name : '.$msg[2].'<br />';
		echo $msg[3].'<br />';
		echo '<a href="'.$this->adminurl.'index.php" onclick="history.back()">'._BACK.'</a>';
		$oPluginAdmin->end();
		exit;
	}

	function action_blogview($msg = '')
	{
		global $CONF, $oPluginAdmin;

		$oPluginAdmin->start();
		echo '<h2><a id="pagetop">'._ADMIN_AREA_TITLE.'</a></h2>';
		echo '<ul style="list-style:none;"><li><a href="'.$this->editurl.'index.php?action=pluginoptions&amp;plugid='.$this->plugin->getID().'">'._OPTION_SETTING.'</a></li>';
		echo '<li><a href="'.$this->adminurl.'index.php?action=memberview">'._FOR_MEMBER_SETTING.'</a></li></ul>';
		echo '<p>'.$msg;
		$this->print_tablehead(_BLOG_LIST_TITLE, _LISTS_ACTIONS);
		$res = sql_query(sprintf('SELECT %s,%s,%s FROM %s', bname, bnumber, bshortname, sql_table('blog')));
		while ($b = mysql_fetch_object($res)) {
		$forCatURI = $this->adminurl.'index.php?action=goCategory&amp;blogid='.$b->bnumber;
		$forItemURI = $this->adminurl.'index.php?action=goItem&amp;blogid='.$b->bnumber;
		$data = array (
				'oid'			=>	$b->bnumber,
				'obd'			=>	0,
				'opr'			=>	'blog',
				'name'			=>	$b->bname,
				'ret'			=>	'blogview',
				'ed_URL'		=>	$this->edhiturl.'index.php?action=blogsettings&blogid='.$b->bnumber,
				'desc'			=>	'[<a href="'.$forItemURI.'" style="font-size:x-small;">'._FOR_ITEMS_SETTING.'</a>]&nbsp;
				[<a href="'.$forCatURI.'" style="font-size:x-small;">'._FOR_CATEGORY_SETTING.'</a>]',
				'path'			=>	$this->plugin->getBlogOption($b->bnumber, 'customurl_bname'),
				'setting_text'	=>	_BLOG_SETTING
				);
		$this->print_tablerow($data);
		}
			echo '</tbody></table>';
		echo '</p>';
		$oPluginAdmin->end();
	}

	function action_categoryview($bid, $msg = '')
	{
		global $CONF, $oPluginAdmin;
		$bname = getBlognameFromID($bid);

		$oPluginAdmin->start();
		echo '<h2><a id="pagetop">'._ADMIN_AREA_TITLE.'</a></h2>';
		echo '<ul style="list-style:none;"><li><a href="'.$this->editurl.'index.php?action=pluginoptions&amp;plugid='.$this->plugin->getID().'">'._OPTION_SETTING.'</a></li>';
		echo '<li><a href="'.$this->adminurl.'index.php?action=blogview">'._FOR_BLOG_SETTING.'</a></li>';
		echo '<li><a href="'.$this->adminurl.'index.php?action=goItem&amp;blogid='.$bid.'">'._FOR_ITEMS_SETTING.'</a></li>';
		echo '<li><a href="'.$this->adminurl.'index.php?action=memberview">'._FOR_MEMBER_SETTING.'</a></li></ul>';
		echo '<p>'.$msg;
			echo '<h3 style="padding-left: 0px">'.$bname.'</h3>';
			$this->print_tablehead(_LISTS_CAT_NAME, _LISTS_DESC);
			$cnm = sql_query(sprintf('SELECT catid, cname, cdesc FROM %s WHERE cblog = %d', sql_table('category'), $bid));
			while ($c = mysql_fetch_object($cnm)) {
				$data = array (
						'oid'		=>	$c->catid,
						'obd'		=>	$bid,
						'opr'		=>	'category',
						'name'		=>	$c->cname,
						'ret'		=>	'catoverview',
						'ed_URL'	=>	$this->edhiturl.'index.php?action=categoryedit&blogid='.$bid.'&catid='.$c->catid,
						'desc'		=>	$c->cdesc,
						'path'		=>	$this->plugin->getCategoryOption($c->catid, 'customurl_cname')
						);
				$this->print_tablerow($data);
				if ($this->uScat) {
					$scnm = sql_query(sprintf('SELECT scatid, sname, sdesc FROM %s WHERE catid = %d', sql_table('plug_multiple_categories_sub'), $c->catid));
					while ($sc = mysql_fetch_object($scnm)) {
						$scpt = sql_query(sprintf('SELECT obj_name FROM %s WHERE obj_param = "subcategory" AND obj_bid = %d AND obj_id = %d', $this->table, $c->catid, $sc->scatid));
						$scp = mysql_fetch_object($scpt);
						$data = array (
								'oid'		=>	$sc->scatid,
								'obd'		=>	$c->catid,
								'opr'		=>	'subcategory',
								'name'		=>	'&raquo;'.$sc->sname,
								'ret'		=>	'catoverview',
								'ed_URL'	=>	$this->mcadmin.'index.php?action=scatedit&catid='.$c->catid.'&scatid='.$sc->scatid,
								'desc'		=>	$sc->sdesc,
								'path'		=>	$scp->obj_name
								);
						$this->print_tablerow($data);
					}
				}
			}
			echo '</tbody></table>';
			echo '<a href="'.$this->adminurl.'index.php" onclick="history.back()">'._BACK.'</a>';
		echo '</p>';
		$oPluginAdmin->end();
	}

	function action_memberview($msg = '')
	{
		global $CONF, $oPluginAdmin;

		$oPluginAdmin->start();
		echo '<h2>'._ADMIN_AREA_TITLE.'</h2>';
		echo '<ul style="list-style:none;"><li><a href="'.$this->editurl.'index.php?action=pluginoptions&amp;plugid='.$this->plugin->getID().'">'._OPTION_SETTING.'</a></li>';
		echo '<li><a href="'.$this->adminurl.'index.php?action=blogview">'._FOR_BLOG_SETTING.'</a></li></ul>';
		echo '<p>'.$msg;
		$this->print_tablehead(_LOGIN_NAME, _MEMBERS_REALNAME);
		$res = sql_query(sprintf('SELECT %s,%s,%s FROM %s', mname, mnumber, mrealname, sql_table('member')));
		while ($m = mysql_fetch_object($res)) {
			$data = array (
					'oid'		=>	$m->mnumber,
					'obd'		=>	0,
					'opr'		=>	'member',
					'name'		=>	$m->mname,
					'ret'		=>	'memberview',
					'ed_URL'	=>	$this->edhiturl.'index.php?action=memberedit&memberid='.$m->mnumber,
					'desc'		=>	$m->mrealname,
					'path'		=>	$this->plugin->getMemberOption($m->mnumber, 'customurl_mname')
					);
			$this->print_tablerow($data);
		}
		echo '</tbody></table></p>';
		$oPluginAdmin->end();
	}

	function action_itemview($bid, $msg = '')
	{
		global $CONF, $oPluginAdmin;

		$oPluginAdmin->start();
		echo '<h2>'._ADMIN_AREA_TITLE.'</h2>';
		echo '<ul style="list-style:none;"><li><a href="'.$this->editurl.'index.php?action=pluginoptions&amp;plugid='.$this->plugin->getID().'">'._OPTION_SETTING.'</a></li>';
		echo '<li><a href="'.$this->adminurl.'index.php?action=blogview">'._FOR_BLOG_SETTING.'</a></li>';
		echo '<li><a href="'.$this->adminurl.'index.php?action=goCategory&amp;blogid='.$bid.'">'._FOR_CATEGORY_SETTING.'</a></li>';
		echo '<li><a href="'.$this->adminurl.'index.php?action=memberview">'._FOR_MEMBER_SETTING.'</a></li></ul>';
		echo '<p><h3>'.$msg.'</h3>';
		$this->print_tablehead(_LISTS_TITLE, _LISTS_ITEM_DESC);
		$res = sql_query(sprintf('SELECT %s,%s,%s FROM %s WHERE iblog = %d ORDER BY itime DESC', ititle, inumber, ibody, sql_table('item'), $bid));
		while ($i = mysql_fetch_object($res)) {
			$temp_res = quickQuery('SELECT obj_name as result FROM '.sql_table('plug_customurl').' WHERE obj_param = "item" AND obj_id = '.$i->inumber);
			$ipath = substr($temp_res, 0, (strlen($temp_res)-5));
			$data = array (
					'oid'		=>	$i->inumber,
					'obd'		=>	$bid,
					'opr'		=>	'item',
					'name'		=>	$i->ititle,
					'ret'		=>	'itemview',
					'ed_URL'	=>	$this->edhiturl.'index.php?action=itemedit&itemid='.$i->inumber,
					'desc'		=>	mb_substr(strip_tags($i->ibody), 0, 80),
//					'path'		=>	$this->plugin->getItemOption($i->inumber, 'customurl_iname')
					'path'		=>	$ipath
					);
			$this->print_tablerow($data);
		}
		echo '</tbody></table></p>';
		$oPluginAdmin->end();
	}

	function print_tablehead($o_name, $o_desc)
	{
		global $oPluginAdmin;

		$NAME = $o_name;
		$DESC = $o_desc;
		$PATH = _LISTS_PATH;
		$ACTION = _LISTS_ACTIONS;
echo <<< TABLE_HEAD
	<table>
		<thead>
			<tr>
				<th>{$NAME}</th>
				<th>{$DESC}</th>
				<th style="width:180px;">{$PATH}</th>
				<th style="width:80px;">{$ACTION}</th>
			</tr>
		</thead>
		<tbody>
TABLE_HEAD;
	}

	function print_tablerow($data)
	{
		global $oPluginAdmin;

		$updateText = _SETTINGS_UPDATE_BTN;
		$edit = _EDIT;
echo <<< TBODY
			<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">
				<form method="post" action="{$this->adminurl}index.php" />
				<input type="hidden" name="action" value="pathupdate" />
				<input type="hidden" name="oid" value="{$data['oid']}" />
				<input type="hidden" name="obd" value="{$data['obd']}" />
				<input type="hidden" name="opr" value="{$data['opr']}" />
				<input type="hidden" name="name" value="{$data['name']}" />
				<input type="hidden" name="ret" value="{$data['ret']}" />
				<td>{$data['name']}&nbsp;&nbsp;<a href="{$data['ed_URL']}" style="font-size:xx-small;">[{$edit}]</a></td>
				<td>{$data['desc']}</td>
				<td><input type="text" name="path" size="32" value="{$data['path']}"/></td>
				<td><input type="submit" name="update" value="{$updateText}" /></td>
				</form>
			</tr>
TBODY;
	}

	function action_pathupdate()
	{
		global $oPluginAdmin;

		$o_oid = RequestVar('oid');
		$o_bid = RequestVar('obd');
		$o_param = RequestVar('opr');
		$o_name = RequestVar('name');
		$newPath = RequestVar('path');
		$action = RequestVar('ret');

		$msg = $this->plugin->RegistPath($o_oid, $newPath, $o_bid, $o_param, $o_name);
		if ($msg) {
			$this->error($msg);
			if ($msg[0] != 0) {
				return;
				exit;
			}
		}
		$mesage = _UPDATE_SUCCESS;
		switch($action) {
			case 'catoverview':
				if ($o_param == 'subcategory') {
					$bid = getBlogIDFromCatID($o_bid);
				} else {
					$bid = $o_bid;
				}
				$this->action_categoryview($bid, _UPDATE_SUCCESS);
			break;
			case 'memberview':
				$this->action_memberview(_UPDATE_SUCCESS);
			break;
			case 'blogview':
				$this->action_blogview(_UPDATE_SUCCESS);
			break;
			case 'itemview':
				$this->action_itemview($o_bid, _UPDATE_SUCCESS);
			break;
			default:
				echo _UPDATE_SUCCESS;
			break;
		}
		return;
	}

	function action_goItem()
	{
		global $oPluginAdmin;

		$bid = $_GET['blogid'];
		$this->action_itemview($bid);
	}

	function action_goCategory()
	{
		global $oPluginAdmin;

		$bid = $_GET['blogid'];
		$this->action_categoryview($bid);
	}

}

$myAdmin = new CustomURL_ADMIN();

if (requestVar('action')) {
	$myAdmin->action(requestVar('action'));
} else {
	$myAdmin->action('blogview');
}

?>