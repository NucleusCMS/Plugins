<?php

if (!function_exists('sql_table')) {
	function sql_table($name)
	{
		return 'nucleus_' . $name;
	}
}

if (!function_exists('htmlspecialchars_decode')) {
	function htmlspecialchars_decode($text)
	{
		return strtr($text, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
	}
}

if (!defined('_CUSTOMURL_TABLE_DEFINED')) {
	define('_CUSTOMURL_TABLE_DEFINED',	1);
	define('_CUSTOMURL_TABLE',	sql_table('plug_customurl'));
	define('_C_SUBCAT_TABLE',	sql_table('plug_multiple_categories_sub'));
}

class NP_CustomURL extends NucleusPlugin
 {

	function getMinNucleusVersion()
	{
		return '322';
	}

	function getName()
	{
		return 'Customized URL';
	}

	function getAuthor()
	{
		return 'shizuki';
	}

	function getURL()
	{
		return 'http://shizuki.kinezumi.net/NucleusCMS/Plugins/NP_CustomURL/NP_CustomURL.html';
	}

	function getVersion()
	{
		return '0.3.1';
	}

	function getDescription()
	{
		return _DESCRIPTION;
	}

	function supportsFeature($what)
	{
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			case 'HelpPage':
				return 1;
			default:
				return 0;
		}
	}

	function hasAdminArea()
	{
		return 1;
	}

	function event_QuickMenu(&$data)
	{
		global $member;
		if(($this->getOption( 'customurl_quicklink') == 'no') || !($member->isLoggedIn() && $member->isAdmin()) ) return;
		array_push(
			$data['options'],
			array(
				'title'		=> _ADMIN_TITLE,
				'url'		=> $this->getAdminURL(),
				'tooltip'	=> _QUICK_TIPS
			)
		);
	}

	function getTableList()
	{
		return	array(
			_CUSTOMURL_TABLE
		);
	}

	function getEventList()
	{
		return	array(
			'QuickMenu',
			'ParseURL',
			'GenerateURL',
			'PostAddBlog',
			'PostAddItem',
			'PostUpdateItem',
			'PostRegister',
			'PostAddCategory',
			'PostDeleteBlog',
			'PostDeleteItem',
			'PostDeleteMember',
			'PostDeleteCategory',
			'PrePluginOptionsUpdate',
			'PreItem',
			'PostItem',
			'PreSkinParse',
			'AddItemFormExtras',
			'EditItemFormExtras',
			'PostMoveCategory',
			'PostMoveItem',
			'PreSendContentType',
			'InitSkinParse',
		);
	}

	function install()
	{
		global $manager, $CONF;
// Keys initialize
		if (empty($CONF['ArchiveKey'])) {
			$CONF['ArchiveKey'] = 'archive';
		}
		if (empty($CONF['ArchivesKey'])) {
			$CONF['ArchivesKey'] = 'archives';
		}
		if (empty($CONF['MemberKey'])) {
			$CONF['MemberKey'] = 'member';
		}
		if (empty($CONF['ItemKey'])) {
			$CONF['ItemKey'] = 'item';
		}
		if (empty($CONF['CategoryKey'])) {
			$CONF['CategoryKey'] = 'category';
		}

//Plugins sort
		$myid = intval($this->getID());
		$res = sql_query('SELECT pid, porder FROM '.sql_table('plugin'));
		while($p = mysql_fetch_array($res)) {
			if (intval($p['pid']) == $myid) {
				sql_query(sprintf('UPDATE %s SET porder = %d WHERE pid = %d', sql_table('plugin'), 1, $myid));
			} else {
				sql_query(sprintf('UPDATE %s SET porder = %d WHERE pid = %d', sql_table('plugin'), $p['porder']+1, $p['pid']));
			}
		}

//create plugin's options and set default value
		$this->createOption(		'customurl_archive',	_OP_ARCHIVE_DIR_NAME,	'text',		$CONF['ArchiveKey']);
		$this->createOption(		'customurl_archives',	_OP_ARCHIVES_DIR_NAME,	'text',		$CONF['ArchivesKey']);
		$this->createOption(		'customurl_member',		_OP_MEMBER_DIR_NAME,	'text',		$CONF['MemberKey']);
		$this->createOption(		'customurl_dfitem',		_OP_DEF_ITEM_KEY,		'text',		$CONF['ItemKey']);
		$this->createOption(		'customurl_dfcat',		_OP_DEF_CAT_KEY,		'text',		$CONF['CategoryKey']);
		$this->createOption(		'customurl_dfscat',		_OP_DEF_SCAT_KEY,		'text',		'subcategory');
		$this->createOption(		'customurl_tabledel',	_OP_TABLE_DELETE,		'yesno',	'no');
		$this->createOption(		'customurl_quicklink',	_OP_QUICK_LINK,			'yesno',	'yes');
		$this->createOption(		'customurl_notfound',	_OP_NOT_FOUND,			'select',	'404',	'404 Not Found|404|303 See Other|303');
		$this->createBlogOption(	'use_customurl',		_OP_USE_CURL,			'yesno',	'yes');
		$this->createBlogOption(	'redirect_normal',		_OP_RED_NORM,			'yesno',	'yes');
		$this->createBlogOption(	'redirect_search',		_OP_RED_SEARCH,			'yesno',	'yes');
		$this->createBlogOption(	'customurl_bname',		_OP_BLOG_PATH,			'text');
//		$this->createItemOption(	'customurl_iname',		_OP_ITEM_PATH,			'text',		$CONF['ItemKey']);
		$this->createMemberOption(	'customurl_mname',		_OP_MEMBER_PATH,		'text');
		$this->createCategoryOption('customurl_cname',		_OP_CATEGORY_PATH,		'text');

		$this->setOption('customurl_archive',	$CONF['ArchiveKey']);			//default archive directory name
		$this->setOption('customurl_archives',	$CONF['ArchivesKey']);			//default archives directory name
		$this->setOption('customurl_member',	$CONF['MemberKey']);			//default member directory name
		$this->setOption('customurl_dfitem',	$CONF['ItemKey']);				//default itemkey_template
		$this->setOption('customurl_dfcat',		$CONF['CategoryKey']);			//default categorykey_template
		$this->setOption('customurl_dfscat',	'subcategory');					//default subcategorykey_template

//create data table
		$sql = 'CREATE TABLE IF NOT EXISTS '._CUSTOMURL_TABLE.' ('
				. ' `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `obj_param` VARCHAR(15) NOT NULL, '
				. ' `obj_name` VARCHAR(128) NOT NULL, '
				. ' `obj_id` INT(11) NOT NULL, '
				. ' `obj_bid` INT(11) NOT NULL,'
				. ' INDEX (`obj_name`)'
				. ' )';
		sql_query($sql);

//setting default aliases
		$this->_createNewPath('blog',		'blog',		'bnumber',	'bshortname');
		$this->_createNewPath('item',		'item',		'inumber',	'iblog');
		$this->_createNewPath('category',	'category',	'catid',	'cblog');
		$this->_createNewPath('member',		'member',	'mnumber',	'mname');

		if ($this->pluginCheck('MultipleCategories')) {
			$this->_createNewPath('subcategory', 'plug_multiple_categories_sub', 'scatid', 'catid');
		}

	}

	function _createNewPath($type, $n_table, $id, $bids)
	{
		$tmpTable = sql_table('plug_customurl_temp');
		sql_query(sprintf('CREATE TABLE %s SELECT obj_id, obj_param FROM %s WHERE obj_param = "%s"', $tmpTable, _CUSTOMURL_TABLE, $type));
		$TmpQuery = 'SELECT %s, %s FROM %s LEFT JOIN %s ON %s.%s = %s.obj_id WHERE %s.obj_id is null';
		$temp = sql_query(sprintf($TmpQuery, $id, $bids, sql_table($n_table), $tmpTable, sql_table($n_table), $id, $tmpTable, $tmpTable));
		if ($temp) {
			while ($row=mysql_fetch_array($temp)) {
				switch ($type) {
					case 'blog':
						$newPath = $row[$bids];		//set access by BlogshortName/
						$blgid = 0;
					break;
					case 'item':
						$tque = 'SELECT itime as result FROM %s WHERE inumber = %d';
						$itime = quickQuery( sprintf($tque ,sql_table('item'), intval($row[$id]) ) );
						$y = $m = $d = '';
						sscanf($itime,'"%d-%d-%d %s"',$y,$m,$d,$temp);
						$ikey = TEMPLATE::fill($this->getOption('customurl_dfitem'), array ('year' => $y, 'month' => $m, 'day' => $d));
						$newPath = $ikey . '_' . $row[$id] . '.html';		//set access by (itemkey_template)_itemid.html
						$blgid = $row[$bids];
					break;
					case 'category':
						$newPath = $this->getOption('customurl_dfcat') . '_' . $row[$id];		//set access by (categorykey_template)_categoryid/
						$blgid = $row[$bids];
					break;
					case 'member':
						$newPath = $row[$bids] . '.html';		//set access by loginName.html
						$blgid = 0;
					break;
					case 'subcategory':
						$newPath = $this->getOption('customurl_dfscat') . '_' . $row[$id];		//set access by (subcategorykey_template)_subcategoryid/
						$blgid = $row[$bids];
					break;
					default:
					break;
				}
				sql_query(sprintf('INSERT INTO %s (obj_param, obj_id, obj_name, obj_bid) VALUES ("%s", %d, "%s", %d)', _CUSTOMURL_TABLE, $type, intval($row[$id]), $newPath, intval($blgid)));
			}
		}
		$temp = sql_query(sprintf('SELECT obj_id, obj_name FROM %s WHERE obj_param = "%s"', _CUSTOMURL_TABLE, $type));
		while ($row=mysql_fetch_array($temp)) {
			switch ($type) {
				case 'blog':
					$this->setBlogOption($row[obj_id], 'customurl_bname',$row[obj_name]);
				break;
				case 'category':
					$this->setCategoryOption($row[obj_id], 'customurl_cname', $row[obj_name]);
				break;
				case 'member':
					$obj_name = substr($row[obj_name], 0, -5);
					$this->setMemberOption($row[obj_id], 'customurl_mname', $obj_name);
				break;
				default:
				break;
			}
		}

		sql_query('DROP TABLE IF EXISTS '.$tmpTable);
	}

	function init()
	{
		global $admin;
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory().'language/'.$language.'.php')) {
			include_once($this->getDirectory().'language/'.$language.'.php');
		}else {
			include_once($this->getDirectory().'language/english.php');
		}
	}

	function pluginCheck($pluginName)
	{
		global $manager;
		if (!$manager->pluginInstalled('NP_'.$pluginName)) return;
		$plugin =& $manager->getPlugin('NP_'.$pluginName);
		return $plugin;
	}

	function unInstall()
	{
		if($this->getOption('customurl_tabledel') == 'yes') {
			sql_query("DROP TABLE "._CUSTOMURL_TABLE);
		}
		$this->deleteOption('customurl_archive');
		$this->deleteOption('customurl_archives');
		$this->deleteOption('customurl_member');
		$this->deleteOption('customurl_dfitem');
		$this->deleteOption('customurl_dfcat');
		$this->deleteOption('customurl_dfscat');
		$this->deleteOption('customurl_notfound');
		$this->deleteOption('customurl_tabledel');
		$this->deleteOption('customurl_quicklink');
		$this->deleteBlogOption('use_customurl');
		$this->deleteBlogOption('redirect_normal');
		$this->deleteBlogOption('redirect_search');
		$this->deleteBlogOption('customurl_bname');
//		$this->deleteItemOption('customurl_iname');
		$this->deleteMemberOption('customurl_mname');
		$this->deleteCategoryOption('customurl_cname');
	}

	function event_ParseURL($data)
	{
		global $CONF, $manager, $curl_blogid, $blogid, $itemid, $catid, $memberid, $archivelist, $archive, $query;
// initialize
		$info = $data['info'];
		$complete =& $data['complete'];
		if ($complete) return;

// Use NP_MultipleCategories ?
		$mcategories = $this->pluginCheck('MultipleCategories');
		if ($mcategories) {
			if (method_exists($mcategories, "getRequestName")) {
				$mcategories->event_PreSkinParse(array());
				global $subcatid;
				$subrequest = $mcategories->getRequestName();
			}
		}
		if (!$subrequest) $subrequest = 'subcatid';

// initialize and sanitize '$blogid'
		if (!$blogid) {
			if ( getVar('blogid') ) {
				if ( is_numeric(getVar('blogid')) ) {
					$blogid = intval( getVar('blogid') );
				} else {
					$blogid = intval( getBlogIDFromName(getVar('blogid')) );
				}
			} elseif ($curl_blogid) {
				$blogid = intval($curl_blogid);
			} else {
				$blogid = $CONF['DefaultBlog'];
			}
		} else {
			if (is_numeric($blogid)) {
				$blogid = intval($blogid);
			} else {
				$blogid = intval(getBlogIDFromName($blogid));
			}
		}

		if (!$info) {
			if (serverVar('PATH_INFO')) {
				$info = serverVar('PATH_INFO');
			} elseif (getNucleusVersion() < 330) {
				if (getVar('virtualpath')) $info = getVar('virtualpath');
			} else {
				return;
			}
		}

// Sanitize 'PATH_INFO'
		$info = trim($info, '/');
		$v_path = explode("/", $info);
		foreach($v_path as $key => $value) {
			$value = urlencode($value);
			$value = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $value);
			$v_path[$key] = $value;
//			$blog = $manager->getBlog($blogid);
//			$exLink = TRUE;
		}
		if (phpversion() >= '4.1.0') {
			$_SERVER['PATH_INFO'] = implode('/', $v_path);
		}
		global $HTTP_SERVER_VARS;
		$HTTP_SERVER_VARS['PATH_INFO'] = implode('/', $v_path);

// Admin area check
		$uri = str_replace('/', '\/', sprintf("%s%s%s","http://",serverVar("HTTP_HOST"),serverVar("SCRIPT_NAME")));
		$plug_url = str_replace('/', '\/', $CONF['PluginURL']);
		$u_plugAction = (getVar('action') == 'plugin' && getVar('name'));
		if (strpos($uri, $plug_url) === 0 || $u_plugAction) $UsingPlugAdmin = TRUE;

// redirect to other URL style
		if ($this->getBlogOption(intval($blogid), 'use_customurl') == 'yes' && !$UsingPlugAdmin && !$CONF['UsingAdminArea']) {
// Search query redirection
// 301 permanent ? or 302 temporary ?
			$search_q = (getVar('query') || strpos(serverVar('REQUEST_URI'), 'query=') !== FALSE);
			if ($this->getBlogOption(intval($blogid), 'redirect_search') == 'yes') {
				if ($search_q) {
					$que_str = getVar('query');
					$que_str = htmlspecialchars($que_str);
					$que_str = mb_eregi_replace('/', 'ssslllaaassshhh', $que_str);
					$que_str = mb_eregi_replace("'", 'qqquuuooottt', $que_str);
					$que_str = mb_eregi_replace('&', 'aaammmppp', $que_str);
					$que_str = urlencode($que_str);
					$search_path = '/search/' . $que_str;
					$b_url = createBlogidLink($blogid);
					$redurl = sprintf("%s%s", $b_url, $search_path);
					redirect($redurl); // 302 Moved temporary
					exit;
				}
			}
			if ($this->getBlogOption(intval($blogid), 'redirect_search') == 'no' && $search_q) {
				$exLink = TRUE;
			}

// redirection nomal URL to FancyURL
			$temp_req = explode('?', serverVar('REQUEST_URI'));
			$request_path = reset($temp_req);
			$feeds = ($request_path == '/xml-rss1.php' || $request_path == '/xml-rss2.php' || $request_path == '/atom.php');
			if ($feeds) return;
			if ($this->getBlogOption(intval($blogid), 'redirect_normal') == 'yes' && serverVar('QUERY_STRING') && !$feeds && !$exLink) {
				$temp = explode('&', serverVar('QUERY_STRING'));
				foreach ($temp as $k => $val) {
					if (preg_match('/^virtualpath/', $val)) {
						unset($temp[$k]);
					}
				}
				if (!empty($temp)) {
					$p_arr = array();
					foreach ($temp as $key => $value) {
						$p_key = explode('=', $value);
						switch (reset($p_key)) {
							case 'blogid';
								$p_arr[] = $CONF['BlogKey'] . '/' . intval(getVar('blogid'));
								unset($temp[$key]);
								break;
							case 'catid';
								$p_arr[] = $CONF['CategoryKey'] . '/' . intval(getVar('catid'));
								unset($temp[$key]);
								break;
							case $subrequest;
								$p_arr[] = $subrequest . '/' . intval(getVar($subrequest));
								unset($temp[$key]);
								break;
							case 'itemid';
								$p_arr[] = $CONF['ItemKey'] . '/' . intval(getVar('itemid'));
								unset($temp[$key]);
								break;
							case 'memberid';
								$p_arr[] = $CONF['MemberKey'] . '/' . intval(getVar('memberid'));
								unset($temp[$key]);
								break;
							case 'archivelist';
								$p_arr[] = $CONF['ArchivesKey'] . '/' . intval(getVar('archivelist'));
								unset($temp[$key]);
								break;
							case 'archive';
								$p_arr[] = $CONF['ArchiveKey'] . '/' . intval(getVar('archive'));
								unset($temp[$key]);
								break;
							default:
								break;
						}
					}
					if (reset($p_arr)) {
						$b_url = createBlogidLink($blogid);
						$red_path = '/' . implode('/', $p_arr);
						if (substr($b_url, -1) == '/') $b_url = rtrim($b_url, '/');
						$redurl = sprintf("%s%s", $b_url, $red_path);
			// HTTP status 301 "Moved Permanentry"
						header( "HTTP/1.1 301 Moved Permanently" );
						header('Location: ' . $redurl);
						exit;
					}
				}
			} elseif ($this->getBlogOption(intval($blogid), 'redirect_normal') == 'yes' && $feeds) {
				$b_url = rtrim(createBlogidLink($blogid), '/');
				switch ($request_path) {
					case 'xml-rss1.php':
						$feed_code = '/index.rdf';
						break;
					case 'xml-rss2.php':
						$feed_code = '/rss2.xml';
						break;
					case 'atom.php':
						$feed_code = '/atom.xml';
						break;
					default:
						break;
				}
			// HTTP status 301 "Moved Permanentry"
				header('HTTP/1.1 301 Moved Permanently');
				header('Location: ' . $b_url . $feed_code);
				exit;
			}
		}
// decode path_info

// decode unofficial Page switch '/page_2.html'
		foreach($v_path as $pathName) {
			if (preg_match('/^page_/', $pathName)) {
				$temp_info = explode('page_', $pathName);
				$_GET['page'] = intval($temp_info[1]);
				$page = array_pop($v_path);
			}
		}

// decode TrackBack URL shorten ver.
		$tail = end($v_path);
		if (substr($tail, -10, 10) == '.trackback') {
			$v_pathName = substr($tail, 0, strlen($tail)-10);
			if (is_numeric($v_pathName)) {
				$this->_trackback($blogid, $v_pathName);
			} else {
				$this->_trackback($blogid, $v_pathName . '.html');
			}
		}

// decode other type URL
		$bLink = $cLink = $iLink = $exLink = FALSE;
		if (empty($info)) $bLink = TRUE;
		$linkObj = array (
			'bid' => 0,
			'name' => reset($v_path),
			'linkparam' => 'blog'
		);
		$blog_id = $this->getRequestPathInfo($linkObj);
		if ($blog_id) {
			$blogid = $blog_id;
			$trush = array_shift($v_path);
			$bLink = TURE;
		}
		if ($this->getBlogOption(intval($blogid), 'use_customurl') == 'no') {
			return;
		}
		$i = 1;
		foreach($v_path as $pathName) {
			switch ($pathName) {
// decode FancyURLs and redirection to Customized URL
				// for blogs
				case $CONF['BlogKey']:
					if (isset($v_path[$i]) && is_numeric($v_path[$i])) {
						if ($this->getBlogOption(intval($v_path[$i]), 'use_customurl') == 'no') {
							$blogid = intval($v_path[$i]);
							$bLink = TRUE;
						} else {
							$red_uri = createBlogidLink(intval($v_path[$i]));
						}
					}
				break;
				// for items
				case $CONF['ItemKey']:
					if (isset($v_path[$i]) && is_numeric($v_path[$i])) {
						if ($this->getBlogOption(intval($blogid), 'use_customurl') == 'no') {
							$itemid = intval($v_path[$i]);
							$iLink = TRUE;
						} else {
							$red_uri = createItemLink(intval($v_path[$i]));
						}
					}
				break;
				// for categories
				case $CONF['CategoryKey']:
				case 'catid':
					if (isset($v_path[$i]) && is_numeric($v_path[$i])) {
						if ($this->getBlogOption(intval($blogid), 'use_customurl') == 'no') {
							$catid = intval($v_path[$i]);
							$cLink = TRUE;
						} else {
							$red_uri = createCategoryLink(intval($v_path[$i]));
						}
					}
				break;
				// for subcategories
				case $subrequest:
					$c = $i - 2;
					if ($mcategories && isset($v_path[$i]) && is_numeric($v_path[$i]) && $i >= 3 && is_numeric($v_path[$c])) {
						if ($this->getBlogOption(intval($blogid), 'use_customurl') == 'no') {
							$subcatid = intval($v_path[$i]);
							$catid = intval($v_path[$c]);
							$cLink = TRUE;
						} else {
							$red_uri = createCategoryLink(intval($v_path[$c])) . $this->_createSubCategoryLink(intval($v_path[$i]));
						}
					}
				break;
				// for archives
				case $CONF['ArchivesKey']:
				case $this->getOption('customurl_archives'):
				// FancyURL
					if (isset($v_path[$i]) && is_numeric($v_path[$i])) {
						if ($this->getBlogOption(intval($v_path[$i]), 'use_customurl') == 'no') {
							$archivelist = intval($v_path[$i]);
							$blogid = $archivelist;
							$exLink = TRUE;
						} else {
							$red_uri = createArchiveListLink(intval($v_path[$i]));
						}
				// Customized URL
					} elseif (isset($v_path[$i])) {
						$archivelist = $blogid;
						$red_uri = createArchiveListLink($archivelist);
					} else {
						$archivelist = $blogid;
						$exLink = TRUE;
					}
				break;
				// for archive
				case $CONF['ArchiveKey']:
				case $this->getOption('customurl_archive'):
					$y = $m = $d = '';
					$ar = $i + 1;
					if (isset($v_path[$i])) {
						$darc = (ereg('([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})',$v_path[$i]));
						$marc = (ereg('([0-9]{4})-([0-9]{1,2})',$v_path[$i]));
						$adarc = (ereg('([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})',$v_path[$ar]));
						$amarc = (ereg('([0-9]{4})-([0-9]{1,2})',$v_path[$ar]));
				// FancyURL
						if (is_numeric($v_path[$i]) && !$darc && !$marc && isset($v_path[$ar]) && ($adarc || $amarc)) {
							sscanf($v_path[$ar],'%d-%d-%d',$y,$m,$d);
							if (isset($d)) {
								$archive = sprintf('%04d-%02d-%02d',$y,$m,$d);
							} else {
								$archive = sprintf('%04d-%02d',$y,$m);
							}
							if ($this->getBlogOption(intval($v_path[$i]), 'use_customurl') == 'no') {
								$blogid = intval($v_path[$i]);
								$exLink = TRUE;
							} else {
								$red_uri = createArchiveLink(intval($v_path[$i]), $archive);
							}
				// Customized URL
						} elseif ($darc || $marc) {
							sscanf($v_path[$i],'%d-%d-%d',$y,$m,$d);
							if (isset($d)) {
								$archive = sprintf('%04d-%02d-%02d',$y,$m,$d);
							} else {
								$archive = sprintf('%04d-%02d',$y,$m);
							}
							$exLink = TRUE;
						} else {
							$red_uri = createArchiveListLink($blogid);
						}
					} else {
						$red_uri = createArchiveListLink($blogid);
					}
				break;
				// for member
				case $CONF['MemberKey']:
				case $this->getOption('customurl_member'):
				// Customized URL
					if (isset($v_path[$i]) && substr($v_path[$i], -5, 5) == '.html') {
						$member_id = $this->getRequestPathInfo(array ('linkparam' => 'member', 'bid' => 0, 'name' => $v_path[$i]));
						$memberid = intval($member_id);
						$exLink = TRUE;
				// FancyURL
					} elseif (isset($v_path[$i]) && is_numeric($v_path[$i])) {
						if ($this->getBlogOption(intval($blogid), 'use_customurl') == 'no') {
							$memberid = intval($v_path[$i]);
							$exLink = TRUE;
						} else {
							$red_uri = createMemberLink(intval($v_path[$i]));
						}
					} else {
						$red_url = createBlogidLink($blogid);
					}
				break;
				// for tag
				case 'tag':
					if (isset($v_path[$i]) && is_string($v_path[$i])) {
						$_REQUEST['tag'] = $v_path[$i];
						$exLink = TRUE;
					}
				break;
				// for search query
				case 'search':
//					if (isset($v_path[$i]) && is_string($v_path[$i])) {
//						if (!serverVar('QUERY_STRING')) {
//							$_SERVER['QUERY_STRING'] = 'query='.$v_path[$i];
//						} else {
//							$_SERVER['QUERY_STRING'] .= '&query='.$v_path[$i];
//						}
//						$_REQUEST['query'] = urldecode($v_path[$i]);
						$que_str = urldecode($v_path[$i]);
						$que_str = mb_eregi_replace('ssslllaaassshhh', '/', $que_str);
						$que_str = mb_eregi_replace('qqquuuooottt', "'", $que_str);
						$que_str = mb_eregi_replace('aaammmppp', '&', $que_str);
						$que_str = htmlspecialchars_decode($que_str);
						$_GET['query'] = $que_str;
						$query = $que_str;
						$exLink = TRUE;
//					}
				break;
				// for pageswitch
				case 'page':
					if (isset($v_path[$i]) && is_numeric($v_path[$i])) {
						$_GET['page'] = intval($v_path[$i]);
						$exLink = TRUE;
					}
				break;
				// for trackback
				case 'trackback':
					if (isset($v_path[$i]) && is_string($v_path[$i])) $this->_trackback($blogid, $v_path[$i]);
					return;
				break;

// decode Customized URL
				default:
				// initialyze
					$linkObj = array (
						'bid' => $blogid,
						'name' => $pathName
					);
					$comp = FALSE;
				// category ?
					if (!$comp && !$cLink && !$iLink && substr($pathName, -5) != '.html') {
						$linkObj['linkparam'] = 'category';
						$cat_id = $this->getRequestPathInfo($linkObj);
						if (!empty($cat_id)) {
							$catid = intval($cat_id);
							$cLink = TURE;
							$comp = TRUE;
						}
					}
				// subcategory ?
					if (!$comp && $cLink && !$iLink && $mcategories && substr($pathName, -5) != '.html') {
						$linkObj['linkparam'] = 'subcategory';
						$linkObj['bid'] = $catid;
						$subcat_id = $this->getRequestPathInfo($linkObj);
						if (!empty($subcat_id)) {
							$_REQUEST[$subrequest] = intval($subcat_id);
							$subcatid = intval($subcat_id);
							$sc = $i;
							$comp = TRUE;
						}
					}
				// item ?
					if (substr($pathName, -5) == '.html') {
						$linkObj['linkparam'] = 'item';
						$item_id = $this->getRequestPathInfo($linkObj);
						if (!empty($item_id)) {
							$itemid = intval($item_id);
							$iLink = TRUE;
						}
					}
				break;
			}
			if (preg_match('/^[0-9page]$/', $pathName)) $exLink = $pathName;
			$i++;
		}

// FancyURL redirect to Customized URL if use it
// HTTP status 301 "Moved Permanentry"
		if ($red_uri) {
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: ' . $red_uri);
			exit;
		}

		$feedurl = array(
						'rss1.xml',
						'index.rdf',
						'rss2.xml',
						'atom.xml',
						'sitemap.xml'
						);
		$request_path = end($v_path);
		$feeds = in_array($request_path, $feedurl, true);
//		$feeds = ($request_path == 'rss1.xml' || $request_path == 'index.rdf' || $request_path == 'rss2.xml' || $request_path == 'atom.xml');


// finish decode
		if (!$exLink && !$feeds) {
// URL Not Found
			if (substr(end($v_path), -5) == '.html' && !$iLink) {
				$notFound = TRUE;
				if (!empty($subcatid)) {
					$uri = createCategoryLink($catid) . $this->_createSubCategoryLink($subcatid);
				} elseif (!empty($catid)) {
					$uri = createCategoryLink($catid);
				} else {
					$uri = createBlogidLink($blogid);
				}
			} elseif (count($v_path) > $sc && !empty($subcatid) && !$iLink) {
				$notFound = TRUE;
				$uri = createCategoryLink($catid) . $this->_createSubCategoryLink($subcatid);
			} elseif (count($v_path) >= 2 && !$subcatid && !$iLink) {
				$notFound = TRUE;
				if (isset($catid)) {
					$uri = createCategoryLink($catid);
				} else {
					$uri = createBlogidLink($blogid);
				}
			} elseif (reset($v_path) && !$catid && !$subcatid && !$iLink) {
				$notFound = TRUE;
				$uri = createBlogidLink($blogid);
			} else {
// Found
// setting $CONF['Self'] for other plugins
				$uri = createBlogidLink($blogid);
				$CONF['Self'] = rtrim($uri, '/');
				$complete = TRUE;
				return ;
			}
		} else {
			$uri = createBlogidLink($blogid);
			$CONF['Self'] = rtrim($uri, '/');
			$complete = TRUE;
				return ;
		}
// Behavior Not Found
		if ($notFound) {
			if (substr($uri, -1) != '/') $uri .= '/';
			if ($this->getOption('customurl_notfound') == '404') {
				header('HTTP/1.1 404 Not Found');
				doError(_NO_SUCH_URI);
				exit;
			} else {
				header('HTTP/1.1 303 See Other');
				header('Location: ' . $uri);
				exit;
			}
		}
	}

// decode 'path name' to 'id'
	function getRequestPathInfo($linkObj)
	{
		$query = 'SELECT obj_id as result FROM %s WHERE obj_name = "%s" AND obj_bid = %d AND obj_param = "%s"';
		$ObjID = quickQuery(sprintf($query, _CUSTOMURL_TABLE, $this->quote_smart($linkObj['name']), $this->quote_smart($linkObj['bid']), $this->quote_smart($linkObj['linkparam'])));
		if(!$ObjID) {
			return;
		} else {
			return intval($ObjID);
		}
	}

// Receive TrackBack ping
	function _trackback($bid, $path)
	{
		$blog_id = intval($bid);
		$TrackBack = $this->pluginCheck('TrackBack');
		if ($TrackBack) {
			if (substr($path, -5, 5) == '.html') {
				$linkObj = array ('linkparam' => 'item', 'bid' => $blog_id, 'name' => $path);
				$item_id = $this->getRequestPathInfo($linkObj);
				if ($item_id) {
					$tb_id = intval($item_id);
				} else {
					doError(_NO_SUCH_URI);
				}
			} else {
				$tb_id = intval($path);
			}
			$TrackBack->handlePing($tb_id);
		}
		return;
	}

	function event_GenerateURL($data)
	{
		global $CONF, $manager, $blogid;
		if ($data['completed']) return;

		$mcategories = $this->pluginCheck('MultipleCategories');
		if ($mcategories) {
			if (method_exists($mcategories, 'getRequestName')) {
				$mcategories->event_PreSkinParse(array());
				global $subcatid;
				$subrequest = $mcategories->getRequestName();
			}
		}
		$OP_ArchiveKey	= $this->getOption('customurl_archive');
		$OP_ArchivesKey	= $this->getOption('customurl_archives');
		$OP_MemberKey	= $this->getOption('customurl_member');
		$params = $data['params'];
		switch ($data['type']) {
			case 'item':
				if (!is_numeric($params['itemid'])) return;
				$item_id = intval($params['itemid']);
				$bid = getBlogIDFromItemID($item_id);
				if ($this->getBlogOption($bid, 'use_customurl') == 'no') return;
				$query = 'SELECT obj_name as result FROM %s WHERE obj_param = "item" AND obj_id = %d';
				$path = quickQuery(sprintf($query, _CUSTOMURL_TABLE, $item_id));
				if ($path) {
					$objPath = $path;
				} else {
					if (!$this->_isValid(array('item', 'inumber', $item_id))) {
						$objPath = _NOT_VALID_ITEM;
					} else {
						$y = $m = $d = $temp = '';
						$tque = 'SELECT itime as result FROM %s WHERE inumber = %d';
						$itime = quickQuery( sprintf($tque ,sql_table('item'), intval($item_id) ) );
						sscanf($itime,'"%d-%d-%d %s"',$y,$m,$d,$temp);
						$ikey = TEMPLATE::fill($this->getOption('customurl_dfitem'), array ('year' => $y, 'month' => $m, 'day' => $d));
						$ipath = $ikey . '_' . $item_id;
						$query = 'SELECT ititle as result FROM %s WHERE inumber = %d';
						$iname = quickQuery(sprintf($query, sql_table('item'), $item_id));
						$this->RegistPath($item_id, $ipath, $bid, 'item', $iname, TRUE);
						$objPath = $ipath . '.html';
					}
				}
				if ($params['extra']['catid'] && $subcatid) {
					$params['extra'][$subrequest] = intval($subcatid);
				}
				if ($bid != $blogid) {
					$burl = $this->_generateBlogLink(intval($bid));
				} else {
					$burl = $this->_generateBlogLink(intval($blogid));
				}
			break;
			case 'member':
				if (!is_numeric($params['memberid']) || $this->getBlogOption(intval($blogid), 'use_customurl') == 'no') return;
				$member_id = intval($params['memberid']);
				$path = $this->getMemberOption($member_id, 'customurl_mname');
				if ($path) {
					$data['url'] = $this->_generateBlogLink(intval($blogid)) . '/' . $OP_MemberKey . '/' . $path . '.html';
					$data['completed'] = TRUE;
					return;
				} else {
					if (!$this->_isValid(array('member', 'mnumber', $member_id))) {
						$data['url'] = $this->_generateBlogLink(intval($blogid)) . '/' . _NOT_VALID_MEMBER;
						$data['completed'] = TRUE;
						return;
					} else {
						$query = 'SELECT mname as result FROM %s WHERE mnumber = %d';
						$mname = quickQuery(sprintf($query, sql_table('member'), $member_id));
						$this->RegistPath($member_id, $mname, 0, 'member', $mname, TRUE);
						$data['url'] = $this->_generateBlogLink(intval($blogid)) . '/' . $OP_MemberKey . '/' . $mname . '.html';
						$data['completed'] = TRUE;
						return;
					}
				}
			break;
			case 'category':
				if (!is_numeric($params['catid'])) return;
				$cat_id = intval($params['catid']);
				$bid = getBlogidFromCatID($cat_id);
				if ($this->getBlogOption(intval($bid), 'use_customurl') == 'no') return;
				$objPath = $this->_generateCategoryLink($cat_id);
				if ($bid != $blogid) {
					$burl = $this->_generateBlogLink(intval($bid));
				}
			break;
			case 'archivelist':
				if ($this->getBlogOption(intval($blogid), 'use_customurl') == 'no') return;
				$objPath = $OP_ArchivesKey . '/';
				$bid = $blogid;
				if ($subcatid) {
					$params['extra'][$subrequest] = intval($subcatid);
				}
			break;
			case 'archive':
				if ($this->getBlogOption(intval($blogid), 'use_customurl') == 'no') return;
				sscanf($params['archive'],'%d-%d-%d',$y,$m,$d);
				if ($d) {
					$arc = sprintf('%04d-%02d-%02d',$y,$m,$d);
				} else {
					$arc = sprintf('%04d-%02d',$y,$m);
				}
				$objPath = $OP_ArchiveKey . '/' . $arc . '/';
				$bid = $blogid;
				if ($subcatid) {
					$params['extra'][$subrequest] = intval($subcatid);
				}
			break;
			case 'blog':
				if (!is_numeric($params['blogid'])) return;
				$bid = intval($params['blogid']);
				$burl = $this->_generateBlogLink($bid);
			break;
			default:
				return;
		}
		if (!$burl) $burl = $this->_generateBlogLink(intval($blogid));

		//NP_Analyze AdminArea check
		$aplugin = $this->pluginCheck('Analyze');
		if ($aplugin) {
			$aadmin = str_replace('/', '\/', $aplugin->getAdminURL());
			$p_arr = explode('/', serverVar('SCRIPT_NAME'));
			$tmp = array_pop($p_arr);
			$p_info = implode('\/', $p_arr);
		}
		if ($p_info) {
			if (strpos($aadmin, $p_info)) $CONF['UsingAdminArea'] = TRUE;
		}
		//NP_Analyze AdminArea check end

		if ($bid != $blogid && !$CONF['UsingAdminArea']) $params['extra'] = array();
		if ($objPath || $data['type'] == 'blog') {
			$LinkURI = $this->_addLinkParams($objPath, $params['extra']);
			if ($LinkURI) {
				$data['url'] =  $burl . '/' . $LinkURI;
			} else {
				$data['url'] = $burl;
			}
			$isArchives = ((preg_match('/' . $OP_ArchivesKey . '/', $data['url'])) || (preg_match('/' . $OP_ArchiveKey . '/', $data['url'])));
			$isItem = (substr($data['url'], -5, 5) == '.html');
			$isDirectory = (substr($data['url'], -1) == '/');
			if ($isArchives && !$isItem && !$isDirectory) {
				$data['url'] .= '/';
			}
			$data['completed'] = TRUE;
			if (strstr ($data['url'], '//')) $link = preg_replace("/([^:])\/\//", "$1/", $data['url']);
			return $data;
		}
	}

	function _createSubCategoryLink($scid)
	{
		$scids = $this->getParents(intval($scid));
		$subcatids = explode('/', $scids);
		$eachPath = array();
		foreach ($subcatids as $sid) {
			$subcat_id = intval($sid);
			$query = 'SELECT obj_name as result FROM %s WHERE obj_id = %d AND obj_param = "%s"';
			$path = quickQuery(sprintf($query, _CUSTOMURL_TABLE, $subcat_id, 'subcategory'));
			if ($path) {
				$eachPath[] = $path;
			} else {
				if (!$this->_isValid(array('plug_multiple_categories_sub', 'scatid', $subcat_id))) {
					return $url = _NOT_VALID_SUBCAT;
				} else {
					$scpath = $this->getOption('customurl_dfscat') . '_' . $subcat_id;
					$query = 'SELECT catid as result FROM %s WHERE scatid = %d';
					$cid= quickQuery(sprintf($query, _C_SUBCAT_TABLE, $subcat_id));
					if (!$cid) return 'no_such_subcat=' . $subcat_id . '/';
					$this->RegistPath($subcat_id, $scpath, $cid, 'subcategory', 'subcat_' . $subcat_id, TRUE);
					$eachPath[] = $scpath;
				}
			}
		}
		$subcatPath = @join('/', $eachPath);
		return $subcatPath . '/';
	}

	function getParents($subid)
	{
		$subcat_id = intval($subid);
		$query = 'SELECT scatid, parentid FROM %s WHERE scatid = %d';
		$res = sql_query(sprintf($query, _C_SUBCAT_TABLE, $subcat_id));
		list($sid, $parent) = mysql_fetch_row($res);
		if ($parent != 0) {
			$r = $this->getParents($parent) . '/' . $sid;
		} else {
			$r = $sid;
		}
		return $r;
	}

	function _generateCategoryLink($cid)
	{
		global $CONF;
		$cat_id = intval($cid);
		$path = $this->getCategoryOption($cat_id, 'customurl_cname');
		if ($path) {
			return $path . '/';
		} else {
			if (!$this->_isValid(array('category', 'catid', $cat_id))) {
				return $url = _NOT_VALID_CAT;
			} else {
				$cpath = $this->getOption('customurl_dfcat') . '_' . $cat_id;
				$this->RegistPath($cat_id, $cpath, getBlogIDFromCatID($cat_id), 'category', 'catid_'.$cat_id, TRUE);
				return $cpath . '/';
			}
		}
	}

	function _generateBlogLink($bid)
	{
		global $manager, $CONF;
		$blog_id = intval($bid);
		if ($this->getBlogOption($blog_id, 'use_customurl') == 'no') {
			$b =& $manager->getBlog($blog_id);
			$burl = $b->getURL();
		} else {
			if ($blog_id == $CONF['DefaultBlog']) {
				$burl = trim($CONF['IndexURL'], '/');
			} else {
				$query = 'SELECT burl as result FROM %s WHERE bnumber = %d';
				$burl = quickQuery(sprintf($query, sql_table('blog'), $blog_id));
				if ($burl) {
					if (substr($burl, -4, 4) == '.php') {
						$path = $this->getBlogOption($blog_id, 'customurl_bname');
						if ($path) {
							$burl = $CONF['IndexURL'] . $path;
						} else {
							$query = 'SELECT bshortname as result FROM %s WHERE bnumber = %d';
							$bpath = quickQuery(sprintf($query, sql_table('blog'), $blog_id));
							$this->RegistPath($blog_id, $bpath, 0, 'blog', $bpath, TRUE);
							$burl = $CONF['IndexURL'] . $bpath;
						}
						$burl_update = 'UPDATE %s SET burl = "%s" WHERE bnumber = %d';
						sql_query(sprintf($burl_update, sql_table('blog'), $this->quote_smart($burl), $blog_id));
					}
				} else {
					$burl = _NOT_VALID_BLOG;
				}
			}
		}
		return trim($burl, '/');
	}

	function _addLinkParams($link, $params)
	{
		global $CONF, $manager, $catid;
		$isArchives  = ((preg_match('/' . $this->getOption('customurl_archives') . '/', $link)) || (preg_match('/' . $this->getOption('customurl_archive') . '/', $link)));
		$mcategories = $this->pluginCheck('MultipleCategories');
		if ($mcategories) {
			if (method_exists($mcategories,"getRequestName")) {
				$mcategories->event_PreSkinParse(array());
				global $subcatid;
				$subrequest = $mcategories->getRequestName();
			}
		}
		if (!$subrequest) $subrequest = 'subcatid';
		if (is_array($params)) {
			foreach ($params as $param => $value) {
				switch ($param) {
					case 'catid':
						$catlink = $this->_generateCategoryLink(intval($value));
					break;
					case $subrequest:
						if ($mcategories) {
							$sublink = $this->_createSubCategoryLink(intval($value));
						}
					break;
				}
			}
			$tagparam = (preg_match('/^tag\//', $link));
			if (substr($link, -5, 5) == '.html' || $tagparam || $isArchives) {
				$link = $catlink . $sublink . $link;
			} else {
				$link .= $catlink . $sublink;
			}
		}
		if (strstr ($link, '//')) {
			$link = preg_replace("/([^:])\/\//", "$1/", $link);
		}
		return $link;
	}

	function doSkinVar($skinType, $link_type = '', $target = '', $title = '')
	{
		global $blogid;
		if ($skinType == 'item' && $link_type == 'trackback') {
			global $itemid, $CONF;
			if ($this->getBlogOption($blogid, 'use_customurl') == 'yes') {
				$que = 'SELECT obj_name as result FROM %s WHERE obj_param = "item" AND obj_id = %d';
				$itempath = quickQuery(sprintf($que, _CUSTOMURL_TABLE, $itemid));
				$uri = $CONF['BlogURL'] . '/trackback/' . $itempath;
// /item_123.trackback
//				$itempath = substr($itempath, 0, -5) . '.trackback';
//				$uri = $CONF['BlogURL'] . '/' . $itempath;
			} else {
				$uri = $CONF['ActionURL'] . '?action=plugin&amp;name=TrackBack&amp;tb_id='.$itemid;
			}
			echo $uri;
			return;
		}
		// $data == type / id || name / 'i'd || 'n'ame
		// ex. =>	[(b)log / blogid [|| shortname / 'i'd || 'n'ame]]
		//			(c)at  / catid [|| cname / 'i'd || 'n'ame]
		//			(s)cat / subcatid [|| sname / 'i'd || 'n'ame]
		//			[(i)tem /] itemid [/ 'path']
		//			(m)ember / mnumber [|| mname / 'i'd || 'n'ame]
		//
		// if second param is null, third param is id
		// if param is null, generate blog link
		if (!$link_type) {
			$link_params = '0, b/' . intval($blogid) . '/i,' . $target . ',' . $title;
		} else {
			$l_params = explode("/", $link_type);
			if (count($l_params) == 1) {
				$link_params = array(0, 'b/' . intval($link_type) . '/i,' . $target . ',' . $title);
			} else {
				$link_params = array(0, $link_type . ',' . $target . ',' . $title);
			}
		}
		echo $this->URL_Callback($link_params);
	}

	function doTemplateVar(&$item, $link_type = '', $target = '', $title = '')
	{
		if ($link_type == 'trackback') {
			global $CONF;
			if ($this->getBlogOption(getBlogIDFromItemID(intval($item->itemid)), 'use_customurl') == 'yes') {
				$que = 'SELECT obj_name as result FROM %s WHERE obj_param = "item" AND obj_id = %d';
				$itempath = quickQuery(sprintf($que, _CUSTOMURL_TABLE, intval($item->itemid)));
				$uri = $CONF['BlogURL'] . '/trackback/' . $itempath;
// /item_123.trackback
//				$itempath = substr($itempath, 0, -5) . '.trackback';
//				$uri = $CONF['BlogURL'] . '/' . $itempath;
			} else {
				$uri = $CONF['ActionURL'] . '?action=plugin&amp;name=TrackBack&amp;tb_id='.intval($item->itemid);
			}
			echo $uri;
			return;
		}
		if (!$link_type) {
			$link_params = array(0, 'i/' . intval($item->itemid) . '/path,' . $target . ',' . $title);
		} else {
			$link_params = array(0, $link_type . ',' . $target . ',' . $title);
		}
		echo $this->URL_Callback($link_params);
	}

	function URL_Callback($data)
	{
		$l_data		= explode(",", $data[1]);
		$l_type		= $l_data[0];
		$target		= $l_data[1];
		$title		= $l_data[2];
		$item_id	= intval($this->currentItem->itemid);
		if (!$l_type) {
			$link_params = array ('i', $item_id, 'i');
		} else {
			$link_data = explode("/", $l_type);
			if (count($link_data) == 1) {
				$link_params = array ('i', intval($l_type), 'i');
			} elseif (count($link_data) == 2) {
				if ($link_data[1] == 'path') {
					$link_params = array ('i', $link_data[0], 'path');
				} else {
					$link_params = array ($link_data[0], intval($link_data[1]), 'i');
				}
			} else {
				$link_params = array ($link_data[0], $link_data[1], $link_data[2]);
			}
		}
		$url = $this->_genarateObjectLink($link_params);
		if ($target) {
			if ($title) {
				$ObjLink = '<a href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($title) . '">' . htmlspecialchars($target) . '</a>';
			} else {
				$ObjLink = '<a href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($target) . '">' . htmlspecialchars($target) . '</a>';
			}
		} else {
			$ObjLink = htmlspecialchars($url);
		}
		return $ObjLink;
	}

	function _isValid($data)
	{
		$query = 'SELECT * FROM %s WHERE %s = %d';
		$res = sql_query(sprintf($query, sql_table($data[0]), $data[1], $this->quote_smart($data[2])));
		return (mysql_num_rows($res) != 0);
	}

	function _genarateObjectLink($data)
	{
		global $CONF, $manager, $blog;
		$ext = substr(serverVar('REQUEST_URI'), -4);
		if ($ext == '.rdf' || $ext == '.xml') $CONF['URLMode'] = 'pathinfo';
		if ($CONF['URLMode'] != 'pathinfo') return;
		$query = 'SELECT %s as result FROM %s WHERE %s = "%s"';
		switch ($data[0]) {
			case 'b':
				if ($data[2] == 'n') {
					$bid = getBlogIDFromName($data[1]);
				} else {
					$bid = $data[1];
				}
				$blog_id = intval($bid);
				if (!$this->_isValid(array('blog', 'bnumber', $blog_id))) {
					$url = _NOT_VALID_BLOG;
				} else {
					$url = $this->_generateBlogLink($blog_id) . '/';
				}
			break;
			case 'c':
				if ($data[2] == 'n') {
					$cid = getCatIDFromName($data[1]);
				} else {
					$cid = $data[1];
				}
				$cat_id = intval($cid);
				if (!$this->_isValid(array('category', 'catid', $cat_id))) {
					$url = _NOT_VALID_CAT;
				} else {
					$bid = getBlogIDFromCatID($cat_id);
					$blink = $this->_generateBlogLink(intval($bid));
					$url = $blink . '/' . $this->_generateCategoryLink($cat_id, '');
				}
			break;
			case 's':
				$mcategories = $this->pluginCheck('MultipleCategories');
				if ($mcategories) {
					if (method_exists($mcategories, "getRequestName")) {
						if ($data[2] == 'n') {
							$scid = quickQuery(sprintf($query, 'scatid', _C_SUBCAT_TABLE, 'sname', $this->quote_smart($data[1])));
						} else {
							$scid = $data[1];
						}
						$sub_id = intval($scid);
						if (!$this->_isValid(array('plug_multiple_categories_sub', 'scatid', $sub_id))) {
							$url = _NOT_VALID_SUBCAT;
						} else {
							$cid = quickQuery(sprintf($query, 'catid', _C_SUBCAT_TABLE, 'scatid', intVal($sub_id)));
							$subrequest = $mcategories->getRequestName();
							$url = createCategoryLink(intval($cid), array($subrequest => $sub_id));
						}
					}
				}
			break;
			case 'i':
				if (!$this->_isValid(array('item', 'inumber', intval($data[1])))) {
					$url = _NOT_VALID_ITEM;
				} else {
					$blink = $this->_generateBlogLink(getBlogIDFromItemID(intval($data[1])));
					$i_query = 'SELECT obj_name as result FROM %s WHERE obj_param = "item" AND obj_id = %d';
					$path = quickQuery(sprintf($i_query, _CUSTOMURL_TABLE, intval($data[1])));
					if ($path) {
						if ($data[2] == 'path') {
							$url = $path;
						} else {
							$url = $blink . '/' . $path;
						}
					} else {
						if ($data[2] == 'path') {
							$url = $CONF['ItemKey'] . '/' . $data[1];
						} else {
							$url = $blink . '/' . $CONF['ItemKey'] . '/' . $data[1];
						}
					}
				}
			break;
			case 'm':
				if ($data[2] == 'n') {
					$mid = quickQuery(sprintf($query, 'mnumber', sql_table('member'), 'mname', $this->quote_smart($data[1])));
				} else {
					$mid = $data[1];
				}
				$member_id = intval($mid);
				if (!$this->_isValid(array('member', 'mnumber', $member_id))) {
					$url = _NOT_VALID_MEMBER;
				} else {
					$url = createMemberLink($member_id, '');
				}
			break;
		}
		return $url;
	}

	function event_PreSendContentType($data)
	{
		global $blogid, $CONF;

		$ext = substr(serverVar('REQUEST_URI'), -4);
		if ($ext == '.rdf' || $ext == '.xml') {
			$p_info = trim(serverVar('PATH_INFO'), '/');
			$path_arr = explode('/', $p_info);
			switch (end($path_arr)) {
				case 'rss1.xml':
				case 'index.rdf':
				case 'rss2.xml':
				case 'atom.xml':
					$data['contentType'] = 'application/xml';
					break;
				default:
					break;
			}
		}
	}

	function event_InitSkinParse($data)
	{
		global $blogid, $CONF;
		$ext = substr(serverVar('PATH_INFO'), -4);
		if ($ext != '.rdf' && $ext != '.xml') {
			return;
		} else {
			$p_info = trim(serverVar('PATH_INFO'), '/');
			$path_arr = explode('/', $p_info);
			switch (end($path_arr)) {
				case 'rss1.xml':
				case 'index.rdf':
					$skinName = 'feeds/rss10';
					break;
				case 'rss2.xml':
					$skinName = 'feeds/rss20';
					break;
				case 'atom.xml':
					$skinName = 'feeds/atom';
					break;
			}
			if (SKIN::exists($skinName)) {
				ob_start();
				$skin =& SKIN::createFromName($skinName);
				$data['skin']->SKIN($skin->getID());
				$feed = ob_get_contents();
				ob_end_clean();
				$eTag = '"'.md5($feed).'"';
				header('Etag: '.$eTag);
				if ($eTag == serverVar('HTTP_IF_NONE_MATCH')) {	
					header("HTTP/1.0 304 Not Modified");
					header('Content-Length: 0');
				}
			}
		}
	}

// merge NP_RightURL
	function event_PreSkinParse($data)
	{
		global $CONF, $manager, $blog, $catid, $itemid, $subcatid;
		global $memberid;

/*		$mcategories = $this->pluginCheck('MultipleCategories');
		if ($mcategories) {
			if (method_exists($mcategories, "getRequestName")) {
				$mcategories->event_PreSkinParse(array());
				global $subcatid;
				if ($subcatid && !$catid) {
					$catid = intval($mcategories->_getParentCatID(intval($subcatid)));
					if (!$catid) {
						$subcatid = null;
						$catid = null;
					}
				} elseif ($subcatid) {
					$pcatid = intval($mcategories->_getParentCatID(intval($subcatid)));
					if ($pcatid != $catid) $subcatid = null;
				}
			}
		}*/
		if (!$blog) {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		} else {
			$b =& $blog;
		}
		$blogurl = $b->getURL();
		
		if (!$blogurl) {
			if($blog) {
				$b_tmp =& $manager->getBlog($CONF['DefaultBlog']);
				$blogurl = $b_tmp->getURL();
			}
			if (!$blogurl) {
				$blogurl = $CONF['IndexURL'];
				if ($CONF['URLMode'] != 'pathinfo'){
					if ($data['type'] == 'pageparser') {
						$blogurl .= 'index.php';
					} else {
						$blogurl = $CONF['Self'];
					}
				}
			}
		}
		if ($CONF['URLMode'] == 'pathinfo'){
			if(substr($blogurl, -1) == '/')  $blogurl = substr($blogurl,0,-1);
		}
		$CONF['BlogURL'] = $blogurl;
		$CONF['ItemURL'] = $blogurl;
		$CONF['CategoryURL'] = $blogurl;
		$CONF['ArchiveURL'] = $blogurl;
		$CONF['ArchiveListURL'] = $blogurl;
		$CONF['SearchURL'] = $blogurl;
//		$CONF['MemberURL'] = $blogurl;
	}

	function event_PreItem($data)
	{
		global $CONF, $manager;

		$this->currentItem = &$data['item']; 
		$pattern = '/<%CustomURL\((.*)\)%>/';
		$data['item']->body = preg_replace_callback ($pattern, array (&$this, 'URL_Callback'), $data['item']->body);
		if ($data['item']->more) {
			$data['item']->more = preg_replace_callback ($pattern, array (&$this, 'URL_Callback'), $data['item']->more);
		}

		$itemid = intval($data['item']->itemid);
		$itemblog =& $manager->getBlog(getBlogIDFromItemID($itemid));
		$blogurl = $itemblog->getURL();
		if (!$blogurl) {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
			if (!($blogurl = $b->getURL())) {
				$blogurl = $CONF['IndexURL'];
				if ($CONF['URLMode'] != 'pathinfo'){
					if ($data['type'] == 'pageparser') {
						$blogurl .= 'index.php';
					} else {
						$blogurl = $CONF['Self'];
					}
				}
			}
		}
		if ($CONF['URLMode'] == 'pathinfo'){
			if(substr($blogurl, -1) == '/')  $blogurl = substr($blogurl,0,-1);
		}
		$CONF['BlogURL'] = $blogurl;
		$CONF['ItemURL'] = $blogurl;
		$CONF['CategoryURL'] = $blogurl;
		$CONF['ArchiveURL'] = $blogurl;
		$CONF['ArchiveListURL'] = $blogurl;
//		$CONF['MemberURL'] = $blogurl;
	}

	function event_PostItem($data)
	{
		global $CONF, $manager, $blog;
		if (!$blog) {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		} else {
			$b =& $blog;
		}
		$blogurl = $b->getURL();
		if (!$blogurl) {
			if($blog) {
				$b_tmp =& $manager->getBlog($CONF['DefaultBlog']);
				$blogurl = $b_tmp->getURL();
			}
			if (!$blogurl) {
				$blogurl = $CONF['IndexURL'];
				if ($CONF['URLMode'] != 'pathinfo'){
					if ($data['type'] == 'pageparser') {
						$blogurl .= 'index.php';
					} else {
						$blogurl = $CONF['Self'];
					}
				}
			}
		}
		if ($CONF['URLMode'] == 'pathinfo'){
			if(substr($blogurl, -1) == '/')  $blogurl = substr($blogurl,0,-1);
		}
		$CONF['BlogURL'] = $blogurl;
		$CONF['ItemURL'] = $blogurl;
		$CONF['CategoryURL'] = $blogurl;
		$CONF['ArchiveURL'] = $blogurl;
		$CONF['ArchiveListURL'] = $blogurl;
//		$CONF['MemberURL'] = $CONF['Self'];
	}
// merge NP_RightURL end

	function event_PostDeleteBlog ($data)
	{
		$query = 'DELETE FROM %s WHERE obj_id = %d AND obj_param = "%s"';
		$pquery = 'DELETE FROM %s WHERE obj_bid = %d AND obj_param= "%s"';
		sql_query(sprintf($query, _CUSTOMURL_TABLE, $data['blogid'], 'blog'));
		sql_query(sprintf($pquery, _CUSTOMURL_TABLE, $data['blogid'], 'item'));
		$cnm = sql_query(sprintf('SELECT catid FROM %s WHERE cblog = %d', sql_table('category'), intval($data['blogid'])));
		while ($c = mysql_fetch_object($cnm)) {
			sql_query(sprintf($pquery, _CUSTOMURL_TABLE, intval($c->catid), 'subcategory'));
			sql_query(sprintf($query, _CUSTOMURL_TABLE, intval($c->catid), 'category'));
		}
	}

	function event_PostDeleteCategory ($data)
	{
		$query = 'DELETE FROM %s WHERE obj_id = %d AND obj_param = "%s"';
		$squery = 'DELETE FROM %s WHERE obj_bid = %d AND obj_param = "%s"';
		sql_query(sprintf($query, _CUSTOMURL_TABLE, intval($data['catid']), 'category'));
		sql_query(sprintf($squery, _CUSTOMURL_TABLE, intval($data['catid']), 'subcategory'));
	}

	function event_PostDeleteItem ($data)
	{
		$query = 'DELETE FROM %s WHERE obj_id = %d AND obj_param = "%s"';
		sql_query(sprintf($query, _CUSTOMURL_TABLE, intval($data['itemid']), 'item'));
	}

	function event_PostDeleteMember ($data)
	{
		$query = 'DELETE FROM %s WHERE obj_id = %d AND obj_param = "%s"';
		sql_query(sprintf($query, _CUSTOMURL_TABLE, intval($data['member']->id), 'member'));
	}

	function event_PostAddBlog ($data)
	{
		$this->RegistPath(intval($data['blog']->blogid), $data['blog']->settings['bshortname'], 0, 'blog', $data['blog']->settings['bshortname'], TRUE);
		$this->setBlogOption(intval($data['blog']->blogid), 'customurl_bname', $data['blog']->settings['bshortname']);
	}

	function event_PostAddCategory ($data)
	{
		global $CONF;
		if (!$data['blog']->blogid) {
			$query = 'SELECT cblog as result FROM %s WHERE catid = %d';
			$bid = quickQuery(sprintf($query, sql_table('category'), intval($data['catid'])));
		} else {
			$bid = $data['blog']->blogid;
		}
		if (!$data['name']) {
			$query = 'SELECT cname as result FROM %s WHERE catid = %d';
			$name = quickQuery(sprintf($query, sql_table('category'), intval($data['catid'])));
		} else {
			$name = $data['name'];
		}
		$this->RegistPath(intval($data['catid']), $this->getOption('customurl_dfcat').'_'.$data['catid'], intval($bid), 'category', $name, TRUE);
		$this->setCategoryOption(intval($data['catid']), 'customurl_cname', $CONF['CategoryKey'].'_'.$data['catid']);
	}

	function event_PostAddItem ($data)
	{
		$tpath = requestVar('plug_custom_url_path');
		$tque = 'SELECT itime as result FROM %s WHERE inumber = %d';
		$itime = quickQuery( sprintf($tque ,sql_table('item'), intval($data['itemid']) ) );
//		$itimestamp = strtotime($itime);
//		$tt = explode(',', date('Y,m,d', $itimestamp));
		$y = $m = $d = $temp = '';
		sscanf($itime, '"%d-%d-%d %s"', $y, $m, $d, $temp);
		$ipath = TEMPLATE::fill($tpath, array ('year' => $y, 'month' => $m, 'day' => $d));
		$query = 'SELECT ititle as result FROM %s WHERE inumber = %d';
		$iname = quickQuery(sprintf($query, sql_table('item'), intval($data['itemid'])));
		$this->RegistPath(intval($data['itemid']), $ipath, intval(getBlogIDFromItemID(intval($data['itemid']))), 'item', $iname, TRUE);
	}

	function event_PostRegister ($data)
	{
		$this->RegistPath(intval($data['member']->id), $data['member']->displayname, 0, 'member', $data['member']->displayname, TRUE);
		$this->setMemberOption(intval($data['member']->id), 'customurl_mname', $data['member']->displayname);
	}

	function event_AddItemFormExtras(&$data)
	{
		$this->createItemForm();
	}

	function event_EditItemFormExtras(&$data)
	{
		$this->createItemForm(intval($data['itemid']));
	}

	function event_PostUpdateItem($data)
	{
		$tpath = requestVar('plug_custom_url_path');
		$tque = 'SELECT itime as result FROM %s WHERE inumber = %d';
		$itime = quickQuery( sprintf($tque ,sql_table('item'), intval($data['itemid']) ) );
//		$itimestamp = strtotime($itime);
//		$tt = explode(',', date('Y,m,d', $itimestamp));
		$y = $m = $d = $temp = '';
		sscanf($itime, '"%d-%d-%d %s"', $y, $m, $d, $temp);
		$ipath = TEMPLATE::fill($tpath, array ('year' => $y, 'month' => $m, 'day' => $d));
		$query = 'SELECT ititle as result FROM %s WHERE inumber = %d';
		$iname = quickQuery(sprintf($query, sql_table('item'), intval($data['itemid'])));
		$this->RegistPath(intval($data['itemid']), $ipath, intval(getBlogIDFromItemID(intval($data['itemid']))), 'item', $iname);
	}

	function createItemForm($item_id = 0)
	{
		global $CONF;
		if ($item_id) {
			$query = 'SELECT obj_name as result FROM %s WHERE obj_param = "item" AND obj_id = %d';
			$res = quickQuery(sprintf($query, _CUSTOMURL_TABLE, intval($item_id)));
			$ipath = substr($res, 0, strlen($res)-5);
		} else {
			$ipath = $this->getOption('customurl_dfitem');
		}
		echo <<<OUTPUT
<h3>Custom URL</h3>
<p>
<label for="plug_custom_url">Custom Path:</label>
<input id="plug_custom_url" name="plug_custom_url_path" value="{$ipath}" />
</p>
OUTPUT;
	}

	function event_PrePluginOptionsUpdate($data)
	{
		$blog_option = ($data['optionname'] == 'customurl_bname');
		$cate_option = ($data['optionname'] == 'customurl_cname');
		$memb_option = ($data['optionname'] == 'customurl_mname');
		$arch_option = ($data['optionname'] == 'customurl_archive');
		$arvs_option = ($data['optionname'] == 'customurl_archives');
		$memd_option = ($data['optionname'] == 'customurl_member');
		if ($blog_option || $cate_option || $memb_option) {
			if ($data['context'] == 'member' ) {
				$blogid = 0;
				$query = 'SELECT mname as result FROM %s WHERE mnumber = %d';
				$name = quickQuery(sprintf($query, sql_table('member'), intval($data['contextid'])));
			} elseif ($data['context'] == 'category') {
				$blogid = getBlogIDFromCatID(intval($data['contextid']));
				$query = 'SELECT cname as result FROM %s WHERE catid = %d';
				$name = quickQuery(sprintf($query, sql_table('category'), intval($data['contextid'])));
			} else {
				$blogid = 0;
				$query = 'SELECT bname as result FROM %s WHERE bnumber = %d';
				$name = quickQuery(sprintf($query, sql_table('blog'), intval($data['contextid'])));
			}
			$msg = $this->RegistPath(intval($data['contextid']), $data['value'], intval($blogid), $data['context'], $name);
			if ($msg) {
				$this->error($msg);
				exit;
			}
		} elseif ($arch_option || $arvs_option || $memd_option) {
			if (!ereg("^[-_a-zA-Z0-9]+$", $data['value'])) {
				$name = substr($data['optionname'], 8);
				$msg = array (1, _INVALID_ERROR, $name, _INVALID_MSG);
				$this->error($msg);
				exit;
			} else {
				return;
			}
		}
		return;
	}

	function event_PostMoveItem($data)
	{
		$query = 'UPDATE %s SET obj_bid = %d WHERE obj_param = "%s" AND obj_id = %d';
		sql_query(sprintf($query, _CUSTOMURL_TABLE, intval($data['destblogid']), 'item', intval($data['itemid'])));
	}

	function event_PostMoveCategory($data)
	{
		$query = 'UPDATE %s SET obj_bid = %d WHERE obj_param = "%s" AND obj_id = %d';
		sql_query(sprintf($query, _CUSTOMURL_TABLE, intval($data['destblog']->blogid), 'category', intval($data['catid'])));
	}

	function RegistPath($objID, $path, $bid, $oParam, $name, $new = FALSE )
	{
		global $CONF;
		switch($oParam) {
			case 'item':
			case 'member':
				if (preg_match('/.html$/', $path))
					$path = substr($path, 0, -5);
			break;
			case 'blog':
			case 'category':
			case 'subcategory':
				break;
			default :
				return;
				break;
		}
		$bid = intval($bid);
		$objID = intval($objID);
		$name = rawurlencode($name);

		if ($new && $oParam == 'item') {
			$tque = 'SELECT itime as result FROM %s WHERE inumber = %d';
			$itime = quickQuery( sprintf($tque ,sql_table('item'), intval($objID) ) );
//			$itimestamp = strtotime($itime);
//			$tt = explode(',', date('Y,m,d', $itimestamp));
			$y = $m = $d = $temp = '';
			sscanf($itime, '"%d-%d-%d %s"', $y, $m, $d, $temp);
			$ikey = TEMPLATE::fill($this->getOption('customurl_dfitem'), array ('year' => $y, 'month' => $m, 'day' => $d));
				if ($path == $ikey) {
					$path = $ikey . '_' . $objID;
				}
		} elseif (!$new && strlen($path) == 0) {
			$del_que = 'DELETE FROM %s WHERE obj_id = %d AND obj_param = "%s"';
			sql_query(sprintf($del_que, _CUSTOMURL_TABLE, intval($objID), $oParam));
			$msg = array (0, _DELETE_PATH, $name, _DELETE_MSG);
			return $msg;
			exit;
		}

		$dotslash = array ('.', '/');
		$path = str_replace ($dotslash, '_', $path);
		if (!ereg("^[-_a-zA-Z0-9]+$", $path)) {
			$msg = array (1, _INVALID_ERROR, $name, _INVALID_MSG);
			return $msg;
			exit;
		}

		$tempPath = $path;
		if ($oParam == 'item' || $oParam == 'member') $tempPath .= '.html';
		$conf_que = 'SELECT obj_id FROM %s WHERE obj_name = "%s" AND obj_bid = %d AND obj_param = "%s" AND obj_id != %d';
		$res = sql_query(sprintf($conf_que, _CUSTOMURL_TABLE, $tempPath, $bid, $oParam, $objID));
		if ($res && mysql_num_rows($res)) {
			$msg = array (0, _CONFLICT_ERROR, $name, _CONFLICT_MSG);
			$path .= '_'.$objID;
		}
		if ($oParam == 'category' && !$msg) {
			$conf_cat = 'SELECT obj_id FROM %s WHERE obj_name = "%s" AND obj_param = "blog"';
			$res = sql_query(sprintf($conf_cat, _CUSTOMURL_TABLE, $tempPath));
			if ($res && mysql_num_rows($res)) {
				$msg = array (0, _CONFLICT_ERROR, $name, _CONFLICT_MSG);
				$path .= '_'.$objID;
			}
		}
		if ($oParam == 'blog' && !$msg) {
			$conf_blg = 'SELECT obj_id FROM %s WHERE obj_name = "%s" AND obj_param = "category"';
			$res = sql_query(sprintf($conf_blg, _CUSTOMURL_TABLE, $tempPath));
			if ($res && mysql_num_rows($res)) {
				$msg = array (0, _CONFLICT_ERROR, $name, _CONFLICT_MSG);
				$path .= '_'.$objID;
			}
		}

		$newPath = $path;
		if ($oParam == 'item' || $oParam == 'member') $newPath .= '.html';
		$query = 'SELECT * FROM %s WHERE obj_id = %d AND obj_param = "%s"';
		$res = sql_query(sprintf($query, _CUSTOMURL_TABLE, $objID, $oParam));
		$row = mysql_fetch_object($res);
		$pathID = $row->id;
		if ($pathID) {
			$query = 'UPDATE %s SET obj_name = "%s" WHERE id = %d';
			sql_query(sprintf($query, _CUSTOMURL_TABLE, $newPath, $pathID));
		} else {
			$query = 'INSERT INTO %s (obj_param, obj_name, obj_id, obj_bid) VALUES ("%s", "%s", %d, %d)';
			sql_query(sprintf($query, _CUSTOMURL_TABLE, $oParam, $newPath, $objID, $bid));
		}
		switch($oParam) {
			case 'blog':
				$this->setBlogOption($objID, 'customurl_bname', $path);
			break;
			case 'category':
				$this->setCategoryOption($objID, 'customurl_cname', $path);
			break;
			case 'member':
				$this->setMemberOption($objID, 'customurl_mname', $path);
			break;
			default :
			break;
		}
		return $msg;
	}

	function error($msg = '')
	{
		global $admin;

		$admin->pagehead();
		echo $msg[1].' : '.$msg[2].'<br />';
		echo $msg[3].'<br />';
		echo '<a href="index.php" onclick="history.back()">'._BACK.'</a>';
		$admin->pagefoot();
		return;
	}

	function quote_smart($value)
	{
		if (get_magic_quotes_gpc()) $value = stripslashes($value);
		if (!is_numeric($value)) {
			$value = mysql_real_escape_string($value);
		} elseif (is_numeric($value)) {
			$value = intval($value);
		}
		return $value;
	}
}
?>