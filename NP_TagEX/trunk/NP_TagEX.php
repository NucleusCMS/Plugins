<?php
/**
 * NP_TagEX
 * TAGGING PLUG-IN FOR NucleusCMS
 * PHP versions 4 and 5
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * (see nucleus/documentation/index.html#license for more info)
 *
 * @author     Original Author nakahara21
 * @copyright  2005-2008 nakahara21
 * @copyright  2006-2009 shizuki
 * @license    http://www.gnu.org/licenses/gpl.txt
 *             GNU GENERAL PUBLIC LICENSE Version 2, June 1991
 * @version    0.73
 * @version    $Id$
 * @link       http://nakahara21.com
 */
/**
 * HISTORY
 *
 * 0.73 TemplateExtraFields for v3.40
 * 0.72 clean up
 * 0.71 fix flat TAG level
 * 0.70 add Tag lebel css
 * 0.67 fix style
 * 0.65 clean up
 * 0.64 modified createTagLink function
 * 0.63 fix Magical code
 * 0.62 add function doIf() for NucleusCMS version3.3
 * 0.6  fix delete tags when item update(425)
 *      mod scanExistTags function
 *      mod get tagLink title
 * 0.51 typo fix
 * 0.5  TAG sort modified
 * 0.43 fix URL generate
 * 0.42 add URL selected tag check
 * 0.41 security fix and add some trick
 * 0.4  fixed bug: numlic only
 * 0.3  fixed bug: delete action
 * 0.2  supports and/or query
 */

/**
 * define table names using plugin
 */
if (!defined('_TAGEX_TABLE_DEFINED')) {
	define('_TAGEX_TABLE_DEFINED', 1);
	define('_TAGEX_TABLE',         sql_table('plug_tagex'));
	define('_TAGEX_KLIST_TABLE',   sql_table('plug_tagex_klist'));
}

/**
 * NP_TagEX class
 */
class NP_TagEX extends NucleusPlugin
{
/**
 * NP_MultipleCategories installed ?
 * @var bool
 */
public $maURL;
/**
 * NP_CustomURL installed ?
 * @var bool
 */
public $cuURL;

// {{{ getName()

	/**
	 * get Plugin name
	 * @return string
	 */
	function getName()
	{
		return 'Tags Extension';
	}

	// }}}
	// {{{ getAuthor()

	/**
	 * get Plugin author
	 * @return string
	 */
	function getAuthor()
	{
		return 'nakahara21 + shizuki + Tucker + Cacher';
	}

	// }}}
	// {{{ getURL()

	/**
	 * get Plugin getting URL
	 * @return string
	 */
	function getURL()
	{
		return 'http://japan.nucleuscms.org/wiki/plugins:tagex';
	}

	// }}}
	// {{{ getVersion()

	/**
	 * get Plugin version
	 * @return string
	 */
	function getVersion()
	{
		return '0.72';
	}

	// }}}
	// {{{ getDescription()

	/**
	 * get Plugin Description
	 * @return string
	 */
	function getDescription()
	{
		return 'Tags Extension (for Japanese users)';
	}

	// }}}
	// {{{ supportsFeature()

	/**
	 * get Plugin supports Nucleus CORE feature
	 * @param string Nucleus CORE feature
	 * @return bool
	 */
	function supportsFeature($what)
	{
		switch ($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	// }}}
	// {{{ install()

	/**
	 * plugin install script
	 *
	 * Create plugin options
	 *
	 * 'And' template's class 'tagex_and_or' CSS example
	 * tagex_and_or {
	 *     font-family:tahoma;
	 *     font-size:smaller;
	 * }
	 *
	 * 'TagIndex' template is 'taglevel' class ver. for default.
	 * But there use '<%fontlevel%>' template tag.
	 * For ex.
	 * <%and%><%or%>
	 * <span style="font-size:<%fontlevel%>em" title="<%tagamount%> post(s)! <%tagitems%>">
	 *     <a href="<%taglinkurl%>"><%tag%></a>
	 * </span>
	 */
	function install()
	{
		$tplAND = <<<__ANDTAGTPL__
<span class="tagex_and_or"> <a href="<%andurl%>" title="narrow">&amp;</a>.
__ANDTAGTPL__;
		$tplOR  = <<<__ORTAGTPL__
<a href="<%orurl%>" title="expand">or</a> </span>
__ORTAGTPL__;
		$tplIDX = <<<__ORTAGTPL__
<%and%><%or%>
<span class="level<%taglevel%>" title="<%tagamount%> post(s) <%tagitems%>">
	<a href="<%taglinkurl%>"><%tag%></a>
</span>
__ORTAGTPL__;
		$this->createOption('flg_erase',         _NPTAGEX_ERASE_FLG,    'yesno',    'no');
		$this->createOption('editTagOrder',      _NPTAGEX_EDT_TAGORDER, 'select',   '1', _NPTAGEX_ORDER_VALUE);
		$this->createOption('and',               _NPTAGEX_TPL_AND,      'textarea', $tplAND);
		$this->createOption('or',                _NPTAGEX_TPL_OR,       'textarea', $tplOR);
		$this->createOption('tagIndex',          _NPTAGEX_TPL_TAGIDX,   'textarea', $tplIDX);
		$this->createOption('tagItemHeader',     _NPTAGEX_TPL_ITEMHEAD, 'textarea', '');
		$this->createOption('tagItem',           _NPTAGEX_TPL_TAGITEMS, 'textarea', '<%itemid%>:<%itemtitle%>');
		$this->createOption('tagItemSeparator',  _NPTAGEX_TPL_ITEMSEPL, 'text',     ' , ');
		$this->createOption('tagItemFooter',     _NPTAGEX_TPL_ITEMFOOT, 'textarea', '');
		$this->createOption('tagIndexSeparator', _NPTAGEX_TPL_IDXSEP,   'text',     ' | ');
		$this->createOption('tagsonlycurrent',   _NPTAGEX_ONLY_CURRENT, 'yesno',    'no');
		$this->createOption('colorfulhighlight', _NPTAGEX_HILIGHT_MODE, 'yesno',    'no');
		$this->createOption('highlight',         _NPTAGEX_HILIGHT_NORM, 'text',     '<span class="highlight">\0</span>');
		$this->createOption('maxTagLevel',       _NPTAGEX_MAX_TAGLEBEL, 'text',     '6', 'datatype=numerical');
		$this->createOption('minTagLevel',       _NPTAGEX_MIN_TAGLEBEL, 'text',     '1', 'datatype=numerical');
		$table_q = 'CREATE TABLE IF NOT EXISTS ' . _TAGEX_TABLE . ' ('
				 . ' `inum`    INT(9)        NOT NULL default "0" PRIMARY KEY, '
				 . ' `itags`   TEXT          NOT NULL, '
				 . ' `itagreg` TIMESTAMP(14) NOT NULL'
				 . ' )';
		sql_query($table_q);
		$table_q = 'CREATE TABLE IF NOT EXISTS ' . _TAGEX_KLIST_TABLE . ' ('
				 . ' `listid`      INT(9)        NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				 . ' `tag`         VARCHAR(255)           default NULL, '
				 . ' `inums`       TEXT          NOT NULL, '
				 . ' `inums_count` INT(11)       NOT NULL default "0", '
				 . ' `ireg`        TIMESTAMP(14) NOT NULL'
				 . ' )';
		sql_query($table_q);
	}

	// }}}
	// {{{ defineMultilanguage()

	/**
	 * Multi language support
	 */
	function defineMultilanguage()
	{
		$multilang = array(
			'_NPTAGEX_ERASE_FLG'    => array(
				'Erase data when uninstall ?',
				'アンインストール時にデータを消去しますか？',
			),
			'_NPTAGEX_EDT_TAGORDER' => array(
				'editform tag order',
				'アイテム追加/編集時のタグの並び順',
			),
			'_NPTAGEX_ORDER_VALUE'  => array(
				"amount(desc)|1|amount(asc)|2|tag's order|3|random|4",
				'アイテムの多い順|1|アイテムの少ない順|2|タグ順(キャラクターコード順)|3|ランダム|4',
			),
			'_NPTAGEX_TPL_AND'      => array(
				"template for 'and'",
				"'and' リンクのテンプレート",
			),
			'_NPTAGEX_TPL_OR'       => array(
				"template for 'or'",
				"'or' リンクのテンプレート",
			),
			'_NPTAGEX_TPL_TAGIDX'   => array(
				"template for 'tagIndex'",
				'タグのリンクのテンプレート'
			),
			'_NPTAGEX_TPL_ITEMHEAD' => array(
				"template for 'tagItemHeader'",
				'タグを含むアイテムごとのヘッダ',
			),
			'_NPTAGEX_TPL_TAGITEMS' => array(
				"template for 'tagItem'",
				'タグを含むアイテム',
			),
			'_NPTAGEX_TPL_ITEMSEPL' => array(
				"template for 'tagItemSeparator'",
				'タグを含むアイテムのセパレータ',
			),
			'_NPTAGEX_TPL_ITEMFOOT' => array(
				"template for 'tagItemFooter'",
				'タグを含むアイテムごとのフッタ',
			),
			'_NPTAGEX_TPL_IDXSEP'   => array(
				"template for 'tagIndexSeparator'",
				'タグのリンクのセパレータ'
			),
			'_NPTAGEX_ONLY_CURRENT' => array(
				'show tags only current blog have',
				'表示中のブログのアイテムに登録してあるタグのみ表示'
			),
			'_NPTAGEX_HILIGHT_MODE' => array(
				'colorful highlight mode ?',
				'カラフルハイライトモードにしますか？'
			),
			'_NPTAGEX_HILIGHT_NORM' => array(
				'template for normal highlightmode',
				'ノーマルハイライトモードの時のテンプレート'
			),
			'_NPTAGEX_MAX_TAGLEBEL' => array(
				'MAX tag lebel',
				'タグレベルの最大値'
			),
			'_NPTAGEX_MIN_TAGLEBEL' => array(
				'MAX tag lebel',
				'タグレベルの最小値'
			),
		);
		switch (ereg_replace('[\\|/]', '', getLanguageName())) {
			case 'japanese-euc':
				foreach ($multilang as $key => $value) {
					define($key, mb_convert_encoding($value[1], 'EUC-JP', 'UTF-8'));
				}
				break;
			case 'japanese-utf8':
				foreach ($multilang as $key => $value) {
					define($key, $value[1]);
				}
				break;
			default:
				foreach ($multilang as $key => $value) {
					define($key, $value[0]);
				}
		}
	}

	// }}}
	// {{{ init()

	/**
	 * initialize plugin
	 */
	function init()
	{
		global $CONF;
		$this->defineMultilanguage;
		$usePathInfo = ($CONF['URLMode'] == 'pathinfo');
		$this->maURL = (($this->plugCheck('MagicalURL2') || $this->plugCheck('Magical')) && $usePathInfo);
		$this->cuURL = ($this->plugCheck('CustomURL') && $usePathInfo);
	}

	// }}}
	// {{{ uninstall()

	/**
	 * Plugin uninstall and clear plugin's all data if you want.
	 */
	function uninstall()
	{
		if ($this->getOption('flg_erase') == 'yes') {
			sql_query('DROP TABLE IF EXISTS ' . _TAGEX_TABLE);
			sql_query('DROP TABLE IF EXISTS ' . _TAGEX_KLIST_TABLE);
		}
	}

	// }}}
	// {{{ getTableList()

	/**
	 * Plugin has there.
	 */
	function getTableList()
	{
		return array(
			_TAGEX_TABLE,
			_TAGEX_KLIST_TABLE
		);
	}

	// }}}
	// {{{ getEventList()

	/**
	 * Plugin fook these API.
	 * @return array
	 */
	function getEventList()
	{
		return array(
			'PostAddItem',
			'AddItemFormExtras',
			'PreUpdateItem',
			'EditItemFormExtras',
			'PreItem',
			'PreDeleteItem',
			'TemplateExtraFields',
		);
	}

	// }}}

/**
 * Private functions
 */

	// {{{ plugCheck()

	/**
	 * other plugins installed ?
	 * @param string Plugin name
	 * @return bool
	 */
	private function plugCheck($name)
	{
		global $manager;
		return $manager->pluginInstalled('NP_' . $name);
	}

	// }}}
	// {{{ quote_smart()

	/**
	 * Quote string befor SQL.
	 * @param mix string, int, or array
	 * @return mix string or int
	 */
	private function quote_smart($value)
	{
// Escape SQL query strings
		if (is_array($value)) {
			if (get_magic_quotes_gpc()) {
				$value = array_map("stripslashes", $value);
			}
			if (!array_map("is_numeric",$value)) {
				if (version_compare(phpversion(),"4.3.0") == "-1") {
					$value = array_map("mysql_escape_string", $value);
				} else {
					$value = array_map("mysql_real_escape_string", $value);
				}
			} else {
				$value = intval($value);
			}
		} else {
			if (get_magic_quotes_gpc()) {
				$value = stripslashes($value);
			}
			if (!is_numeric($value)) {
				if (version_compare(phpversion(), "4.3.0") == "-1") {
					$value = "'" . mysql_escape_string($value) . "'";
				} else {
					$value = "'" . mysql_real_escape_string($value) . "'";
				}
			} else {
				$value     = intval($value);
			}
		}
		return $value;
	}

	// }}}

/**
 * Processing of the event fook held from here
 */

	// {{{ event_PreItem()

	/**
	 * Quote string befor SQL.
	 * @param mix string, int, or array
	 * @return mix string or int
	 */
	function event_PreItem($data)
	{
// Hightlight tags
		global $currentTemplateName;
		$currTemplateName    = $this->quote_smart($currentTemplateName);
		$templateDescTable   = sql_table('template_desc');
		$q_query             = 'SELECT tddesc as result '
							 . 'FROM %s '
							 . 'WHERE tdname = %s';
		$q_query             = sprintf($q_query, $templateDescTable, $currTemplateName);
		$currentTemplateDesc = quickQuery($q_query);
		if (eregi('<highlightTagsAll>', $currentTemplateDesc)) {
			$tags = $this->scanExistTags(0, 99999999);
			if (empty($tags)) {
				return false;
			} else {
				$highlightKeys = array_keys($tags);
			}
		} elseif (eregi('<highlightTags>', $currentTemplateDesc)) {
			$requestT = $this->getNoDecodeQuery('tag');
			if (empty($requestT)) {
				return false;
			}
			$requestTarray = $this->splitRequestTags($requestT);
			$reqAND        = array_map(array(&$this, "_rawdecode"), $requestTarray['and']);
			if ($requestTarray['or']) {
				$reqOR     = array_map(array(&$this, "_rawdecode"), $requestTarray['or']);
			}
			if (isset($reqOR)) {
				$highlightKeys = array_merge($reqAND, $reqOR);
			} else {
				$highlightKeys = $reqAND;
			}
		} else {
			return false;
		}
		$template['highlight'] =  $this->getOption('highlight');
		$curItem               =& $data['item'];
		if ($this->getOption('colorfulhighlight') == 'no') {// original mode
			$curItem->body  = highlight($curItem->body, $highlightKeys, $template['highlight']);
			$curItem->more  = highlight($curItem->more, $highlightKeys, $template['highlight']);
		} else {
/**
 *
 * use other color for each tags
 * mod by shizuki
 *
 */
			$sh = 0;
			foreach ($highlightKeys as $qValue) {
				$pattern       = '<span class=\'highlight_' . $sh . '\'>\0</span>';
				$curItem->body = highlight($curItem->body, $qValue, $pattern);
				$sh++;
				if ($sh == 10) {
					$sh = 0;
				}
			}
			if ($curItem->more) {
				$sh = 0;
				foreach ($highlightKeys as $qValue) {
					$pattern       = '<span class=\'highlight_' . $sh . '\'>\0</span>';
					$curItem->more = highlight($curItem->more, $qValue, $pattern);
					$sh++;
					if ($sh == 10) {
						$sh = 0;
					}
				}
			}
		}
	}

	function event_TemplateExtraFields($data)
	{
		$data['fields']['NP_TagEX'] = array(
			'nptagex_and'               => _NPTAGEX_TPL_AND,
			'nptagex_or'                => _NPTAGEX_TPL_OR,
			'nptagex_tagIndex'          => _NPTAGEX_TPL_TAGIDX,
			'nptagex_tagItemHeader'     => _NPTAGEX_TPL_ITEMHEAD,
			'nptagex_tagItem'           => _NPTAGEX_TPL_TAGITEMS,
			'nptagex_tagItemSeparator'  => _NPTAGEX_TPL_ITEMSEPL,
			'nptagex_tagItemFooter'     => _NPTAGEX_TPL_ITEMFOOT,
			'nptagex_tagIndexSeparator' => _NPTAGEX_TPL_IDXSEP,
			'nptagex_highlight'         => _NPTAGEX_HILIGHT_NORM,
		);
	}
/**
 *
 * extra forms function
 * mod by shizuki
 *
 */
/**
 *
 * TAG list
 * Add or Edit Item
 * TAGs only current blog
 * written by shizuki
 * From http://blog.uribou.net/
 *
 */
	function _ItemFormExtras($tagrows, $tagcols, $blogid = 0, $oldforj = '', $itags = '')
	{
		$blogid = intval($blogid);
// Exstra form for add or update Item
		if (strstr(serverVar('HTTP_USER_AGENT'), 'Gecko')) {
			$divStyles = 'height: 24em;'
					   . 'width: 95%;'
					   . 'overflow: auto;'
					   . 'clear: both;'
					   . 'border:1px solid lightblue;'
					   . 'margin-top:3.8em;'
					   . 'padding-left:0.5em;'
					   . '-moz-column-count: 3;'
					   . '-moz-column-width: 200px;'
					   . '-moz-column-gap: 0.5em;';
			$txAStyles = ''//'width:10em;'
					   . 'width: 95%;'
					   . 'height: 200px;';
		} else {
			$divStyles = 'height: 200px;'
					   . 'clear: both;'
					   . 'overflow: auto;';
			$txAStyles = 'width:95%;';
		}
		$printData = "\t\t"
				   . "<h3>TagEX</h3>\n\t\t"
//				   . '<p style="float:left;width:95%;">' . "\n\t\t\t"
				   . '<label for="tagex">Tag(s):</label>' . "\n\t\t\t"
				   . '<a href="javascript:resetOlder'
				   . "('" . $oldforj . "')"
				   . '">[Reset]</a><br />' . "\n\t\t\t"
				   . '<textarea id="tagex" name="itags" rows="' . intval($tagrows)
				   . '" cols="' . intval($tagcols) . '" style="' . $txAStyles . '"'
				   . ' class="tagex">'
				   . htmlspecialchars($itags) . '</textarea>' . "\n\t\t"
//				   . '</p>'
				   . '<script language="JavaScript" type="text/javascript">' . "\n"
				   . '<!--' . "\n"
				   . 'function insertag(tag){' . "\n\t"
				   . "if(document.getElementById('tagex').value != '')\n\t\t"
				   . 'tag = "\n" + tag;' . "\n\t"
				   . "document.getElementById('tagex').value += tag;\n"
				   . "}\n"
				   . "function resetOlder(old){\n\t"
				   . "document.getElementById('tagex').value = old;\n"
				   . "}\n//-->\n"
				   . "</script>\n"
				   . '<div style="' . $divStyles . '" class="tagex"><ul>' . "\n";
		echo $printData;
		$tagOrder = intval($this->getOption('editTagOrder'));
		if ($this->getOption('tagsonlycurrent') == no) {
			$existTags = $this->scanExistTags(0, 99999999, $tagOrder);
		} else {
			$existTags = $this->scanExistTags(1, 99999999, $tagOrder, $blogid);
		}
		if ($existTags) {
			$existTags = array_keys($existTags);
		}
		for ($i=0; $i < count($existTags); $i++) {
			$exTags    = htmlspecialchars($existTags[$i]);
			$printData = '<li><a href="javascript:insertag'
					   . "('" . $exTags . "')" . '">'
					   . $exTags . '</a></li>' . "\n";
			echo $printData;
		}
		echo '</ul></div><br style="clear:all;" />' . "\n";
	}

	function event_AddItemFormExtras($data)
	{
/*		global $CONF, $blogid;
		if (is_numeric($blogid)) {
			$blogid = intval($blogid);
		} else {
			$blogid = intval(getBlogIDFromName($blogid));
		}
		if (empty($blogid)) {
			$blogid = intval($CONF['DefaultBlog']);
		}*/
		$blogid  = intval($data['blog']->blogid);
// Call exstra form
//		$oldforj    = $itags = '';
		$this->_ItemFormExtras(3, 40, $blogid);//, $oldforj, $itags);// <current blog only />
	}

	function event_EditItemFormExtras($data)
	{
// Initialize tags when it have
		$item_id = intval($data['variables']['itemid']);
		$query   = 'SELECT itags FROM %s WHERE inum = %d';
		$result  = sql_query(sprintf($query, _TAGEX_TABLE, $item_id));
		if (mysql_num_rows($result) > 0) {
			$itags  = mysql_result($result,0,0);
		}
		$oldforj = str_replace("\n", '\n', htmlspecialchars($itags));
//		$blogid  = getBlogIDFromItemID($item_id);
		$blogid  = intval($data['blog']->blogid);//$blogid);
// Call exstra form
// current blog onry mode
		$this->_ItemFormExtras(5, 20, $blogid, $oldforj, $itags);
	}

	function event_PostAddItem($data)
	{
// Add tags when it add for Item
		$itags  = trim(requestVar('itags'));
		if (!$itags) {
			return;
		}
		$inum   = intval($data['itemid']);
		$query  = 'INSERT INTO %s (inum, itags) VALUES (%d, %s)';
		$query  = sprintf($query, _TAGEX_TABLE, $inum, $this->quote_smart($itags));
		sql_query($query);
		$temp_tags_array = preg_split("/[\r\n,]+/", $itags);
		for ($i=0; $i < count($temp_tags_array); $i++) {
			$this->mergeTags(trim($temp_tags_array[$i]), $inum);
		}
	}

	function event_PreUpdateItem($data)
	{
// Add tags when it add for Item
		$itags   = trim(requestVar('itags'));
		$inum    = intval($data['itemid']);
		$query   = 'SELECT itags as result FROM %s WHERE inum = %d';
		$oldTags = quickQuery(sprintf($query, _TAGEX_TABLE, $inum));
		if ($itags == $oldTags) {
			return false;
		}
		$query = 'DELETE FROM %s WHERE inum = %d';
		sql_query(sprintf($query, _TAGEX_TABLE, $inum));
		if (!empty($itags)) {
			$query  = 'INSERT INTO %s (inum, itags) VALUES (%d, %s)';
			$query  = sprintf($query, _TAGEX_TABLE, $inum, $this->quote_smart($itags));
			sql_query($query);
		}
		$old_tags_array = $this->getTags($oldTags);
		if (!is_array($old_tags_array)) $old_tags_array = array($old_tags_array);
		$new_tags_array = $this->getTags($itags);
		$deleteTags     = $this->array_minus_array($old_tags_array, $new_tags_array);
		for ($i=0; $i < count($deleteTags); $i++) {
			$this->deleteTags($deleteTags[$i], $inum);
		}
		$addTags = $this->array_minus_array($new_tags_array, $old_tags_array);
		for ($i=0; $i < count($addTags); $i++) {
			$this->mergeTags($addTags[$i], $inum);
		}

	}

	function event_PreDeleteItem($data)
	{
// Delete tags when it for deleted Item
// or delete Itemid from TAG table
		$inum    = intval($data['itemid']);
		$query   = 'SELECT itags as result FROM %s WHERE inum = %d';
		$oldTags = quickQuery(sprintf($query, _TAGEX_TABLE, $inum));
		if (empty($oldTags)) {
			return false;
		} else {
			$query      = 'DELETE FROM %s WHERE inum = %d';
			sql_query(sprintf($query, _TAGEX_TABLE, $inum));
			$deleteTags = $this->getTags($oldTags);
			for ($i=0; $i < count($deleteTags); $i++) {
				$this->deleteTags($deleteTags[$i], $inum);
			}
		}
	}

//------------------------------------------------------

	function getTags($str)
	{
// extract Item's TAG for array
		if (!$str) return false;
		$tempArray   = preg_split("/[\r\n,]+/", $str);
		$returnArray = array_map('trim', $tempArray);
		return array_unique($returnArray);
	}

	function array_minus_array($a, $b)
	{
// update Item's TAGs
		$c = array_diff($a,$b);
		$c = array_intersect($c, $a);
		return array_values($c);
	}

	function deleteTags($tag, $inum)
	{
// Delete TAGs and TAG's Item
		$inum    = intval($inum);
		$tag     = $this->quote_smart($tag);
		$f_query = "SELECT inums FROM " . _TAGEX_KLIST_TABLE
				 . " WHERE tag = " . $tag
				 . '       AND inums REGEXP "(^|,)' . $inum . '(,|$)"'
				 . ' ORDER BY ireg DESC';
		$findres = sql_query($f_query);
		if (mysql_num_rows($findres) == 0) {
			return;
		}
		$temp_inums = mysql_result($findres, 0, 0);
		if (preg_match('/^\d+$/', $temp_inums) && $inum == $temp_inums) {
			$query = 'DELETE FROM %s WHERE tag = %s';
			sql_query(sprintf($query, _TAGEX_KLIST_TABLE, $tag));
			return;
		}
		$inums_array = array();
		$inums_array = explode(',', $temp_inums);
		$trans       = array_flip($inums_array);
		unset($trans[$inum]);
		$inums_array = array_flip($trans);
		$inums_count = count($inums_array);
//		$inums       = @implode(",", $inums_array);
		$inums       = implode(",", $inums_array);
		if (!empty($inums)) {
			$update_query = 'UPDATE %s '
						  . 'SET inums   = %s, '
						  . 'inums_count = %d '
						  . 'WHERE tag   = %s';
			$iCount       = intval($inums_count);
			$quoteInums   = $this->quote_smart($inums);
			sql_query(sprintf($update_query, _TAGEX_KLIST_TABLE, $quoteInums, $iCount, $tag));
		}
	}

	function mergeTags($tag, $inum)
	{
// Add TAG's Item
		if (empty($tag)) {
			return;
		}

		$inums_array = array();

		$inum    = intval($inum);
		$tag     = $this->quote_smart($tag);
		$f_query = 'SELECT inums'
				 . ' FROM ' . _TAGEX_KLIST_TABLE
				 . ' WHERE tag = ' . $tag
				 . ' ORDER BY ireg DESC';
		$findres = sql_query($f_query);
		if (mysql_num_rows($findres) > 0) {
			$temp_inums  = mysql_result($findres, 0, 0);
			$inums_array = explode(',', $temp_inums);
			if (!in_array($inum, $inums_array)) {
				$inums       = $temp_inums . ',' . $inum;
				$inums_count = count($inums_array) + 1;
			}
		} else {
			$q_query = 'INSERT INTO %s '
					 . '(tag, inums, inums_count) '
					 . 'VALUES (%s, %d, 1)';
			sql_query(sprintf($q_query, _TAGEX_KLIST_TABLE, $tag, intval($inum)));
		}

		if (!empty($inums)) {
			$q_query    = 'UPDATE %s SET inums = %s, inums_count = %d WHERE tag = %s';
			$iCount     = intval($inums_count);
			$quoteInums = $this->quote_smart($inums);
			sql_query(sprintf($q_query, _TAGEX_KLIST_TABLE, $quoteInums, $iCount, $tag));
		}
	}

	function scanExistItem($narrowMode = 0, $blogid = 0)
	{
/// Select Items when Categories or Sub-categories or Archive selected
		global $manager, $CONF, $blog, $catid, $archive;
		if (!$narrowMode) {
			return;
		}
		if ($blogid > 0) {
			$b =& $manager->getBlog($blogid);
		} elseif ($blog) {
			$b =& $blog;
		} else {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		$where = '';
		if ($narrowMode > 0) {
				$where .= ' and i.iblog = ' . intval($b->getID());
		}
		if ($catid && $narrowMode > 1) {
			$catid = intval($catid);
			if ($manager->pluginInstalled('NP_MultipleCategories')) {
				$where .= ' and ((i.inumber = p.item_id'
						. ' and (p.categories REGEXP "(^|,)' . $catid . '(,|$)"'
						. ' or  i.icat = ' . $catid . '))'
						. ' or (i.icat = ' . $catid
						. ' and p.item_id IS NULL))';
				$mtable = ' LEFT JOIN '
						. sql_table('plug_multiple_categories') . ' as p'
						. ' ON  i.inumber = p.item_id';
				$mplugin =& $manager->getPlugin('NP_MultipleCategories');
				global $subcatid;
				if ($subcatid && method_exists($mplugin, 'getRequestName')) {
//family
					$subcatid   = intval($subcatid);
					$scatTable  = sql_table('plug_multiple_categories_sub');
					$tres_query = 'SELECT * FROM %s WHERE scatid = %d';
					$tres_query = sprintf($tres_query, $scatTable, $subcatid);
					$tres       = sql_query($tres_query);
					$ra         = mysql_fetch_array($tres, MYSQL_ASSOC);
					if (array_key_exists('parentid', $ra)) {
						$Children = array();
						$Children = explode('/', $subcatid . $this->getChildren($subcatid));
					}
					if ($loop = count($Children) >= 2) {
						for ($i=0; $i < $loop; $i++) {
							$chidID     = intval($Children[$i]);
							$temp_whr[] = ' p.subcategories REGEXP "(^|,)' . $chidID . '(,|$)" ';
						}
						$where .= ' and ( '
								. implode (' or ', $temp_whr)
								. ' )';
					} else {
						$where .= ' and p.subcategories REGEXP "(^|,)' . $subcatid . '(,|$)"';
					}
//family end
				}
			} else {
				$where .= ' and i.icat = ' . $catid;
			}
		}

		if ($archive) {
			$y = $m = $d = '';
			sscanf($archive, '%d-%d-%d', $y, $m, $d);
			if ($d) {
				$timestamp_start = mktime(0, 0, 0, $m,   $d,   $y);
				$timestamp_end   = mktime(0, 0, 0, $m,   $d+1, $y);
			} elseif ($m) {
				$timestamp_start = mktime(0, 0, 0, $m,   1,    $y);
				$timestamp_end   = mktime(0, 0, 0, $m+1, 1,    $y);
			} else {
				$timestamp_start = mktime(0, 0, 0, 1,    1,    $y);
				$timestamp_end   = mktime(0, 0, 0, 1,    1,    $y+1);
			}
			$where .= ' and i.itime >= ' . mysqldate($timestamp_start)
			        . ' and i.itime < '  . mysqldate($timestamp_end);
		} else {
			$where .= ' and i.itime <= ' . mysqldate($b->getCorrectTime());
		}

		$iquery = 'SELECT i.inumber '
				. 'FROM %s as i'
				. $mtable
				. ' WHERE i.idraft = 0'
				. $where;
		$res    = sql_query(sprintf($iquery, sql_table('item')));
		while ($row = mysql_fetch_row($res)) {
			$existInums[] = $row[0];
		}
		return $existInums;
	}

/**
 *
 * TAG list sort
 * add TAG's order and Random sort
 * written by shizuki
 * From http://blog.uribou.net/
 *
 */
	function sortTags($tags, $sortMode = 0)
	{
		// sortMode 0:none
		// sortMode 1:max first
		// sortMode 2:min first
		// sortMode 3:tag's order
		// sortMode 4:random
		$sortMode = intval($sortMode);
		if (!$tags || $sortMode == 0) {
			return $tags;
		}
		foreach ($tags as $tag => $inums) {
			$tagCount[$tag] = count($inums);
		}
		switch ($sortMode) {
			case 1:
				arsort($tagCount);
				break;
			case 2:
				asort($tagCount);
				break;
			case 3:
				uksort($tagCount, array(&$this, 'sortTagOrder'));
				break;
			case 4:
				srand ((float) microtime() * 10000000);
				$tmp_key  = array_rand($tagCount, count($tagCount));
				unset($tagCount);
				$tagCount = array();
				foreach ($tmp_key as $k => $v) {
					$tagCount[$v] = 0;
				}
				break;
			default:
				break;
		}
		foreach ($tagCount as $k => $v) {
			$r[$k] = $tags[$k];
		}
		return $r;
	}

	function sortTagOrder($a, $b)
	{
		return strcasecmp($a, $b);
	}

	function scanExistTags($narrowMode = 0, $amount = 99999999, $sortmode = 0, $blogid = 0)
	{
// Select TAG's Item
		// $narrowMode = 0: all blogs
		// $narrowMode = 1: currentblog only
		// $narrowMode = 2: narrowed with catid/subcatid
		$narrowMode = intval($narrowMode);
		$amount     = intval($amount);
		$sortmode   = intval($sortmode);
// <mod by shizuki />
		if (is_numeric($blogid)) {
			$blogid = intval($blogid);
		} else {
			$blogid = intval(getBlogIDFromName($blogid));
		}
		$existInums = array();
		$existInums = $this->scanExistItem($narrowMode, $blogid);
		$res        = sql_query(sprintf('SELECT * FROM %s', _TAGEX_KLIST_TABLE));
		while ($o = mysql_fetch_object($res)) {
			$tagsk[$o->tag] = explode(',', $o->inums);
			if ($existInums) {
				$tagsk[$o->tag] = array_intersect($tagsk[$o->tag], $existInums);
				$tagsk[$o->tag] = array_values($tagsk[$o->tag]);
			}
			if (empty($tagsk[$o->tag])) {
				unset($tagsk[$o->tag]);
			}
		}
		$tagsk = $this->sortTags($tagsk, $sortmode);
		if (count($tagsk) > $amount) {
			$tagsk = array_slice($tagsk, 0, $amount);
		}
		return $tagsk;
	}

	function scanCount($tags)
	{
// ? count TAGs have Item ?
		$max = $min = 1;
		foreach ($tags as $tag) {
			$tempCount = count($tag);
			$max       = max($max, $tempCount);
			$min       = min($min, $tempCount);
		}
		return array($max, $min);
	}

	function getNoDecodeQuery($q)
	{
// Get urlencoded TAGs
		global $CONF, $manager;
// FancyURL
		if ($CONF['URLMode'] == 'pathinfo') {
			$urlq  = serverVar('REQUEST_URI');
			$tempq = explode($q . '/', $urlq, 2);
			if ($this->maURL) {//($manager->pluginInstalled('NP_MagicalURL2') || $manager->pluginInstalled('NP_Magical')) {
				$tempq = explode($q . '_', $urlq, 2);
			}
//			if ($tempq[1]) {
			if (!empty($tempq[1])) {
				$tagq = explode('/', $tempq[1]);
				if ($this->maURL) {//($manager->pluginInstalled('NP_MagicalURL2') || $manager->pluginInstalled('NP_Magical')) {
					$tagq = explode('_', $tempq[1]);
				}
				$str  = preg_replace('|[^a-z0-9-~+_.#;,:@%]|i', '', $tagq[0]);
				return $str;
			}
		} else {
// NormalURL
			$urlq = serverVar('QUERY_STRING');
			$urlq = str_replace('?', '', $urlq);
			$urlq = explode('&', $urlq);
			$qCnt = count($urlq);
			for ($i=0; $i<$qCnt; $i++) {
				$tempq = explode('=', $urlq[$i]);
				if ($tempq[0] == $q) {
					$str = preg_replace('|[^a-z0-9-~+_.#;,:@%]|i', '', $tempq[1]);
					return $str;
				}
			}
		}
		return FALSE;
	}

	function splitRequestTags($q)
	{
// extract TAGs to array
		if (!strpos($q, '+') && !strpos($q, ':')) {
			$res['and'][0] = $q;
			return $res;
		}
		$res     = array(
						 'and' => array(),
						 'or'  => array(),
						);
		$tempAnd = explode('+', $q);
		$andCnt  = count($tempAnd);
		for ($i=0; $i < $andCnt; $i++) {
			$temp         = explode(':', $tempAnd[$i]);
			$res['and'][] = array_shift($temp);
			if ($temp != array()) {
				$res['or'] = array_merge($res['or'], $temp);
			}
		}
		return $res;
	}

	function doIf($key, $value)
	{
		if ($key != 'tag') {
			return false;
		}
		$reqTags = $this->getNoDecodeQuery('tag');
		if (!empty($reqTags)) {
			$reqTagsArr = $this->splitRequestTags($reqTags);
			$reqAND     = array_map(array(&$this, "_rawdecode"), $reqTagsArr['and']);
			if ($requestTarray['or']) {
				$reqOR = array_map(array(&$this, "_rawdecode"), $reqTagsArr['or']);
			}
		} else {
			return false;
		}
		if (empty($value)) {
			return true;
		} else {
			$tagsArray = ($reqOR) ? array_merge($reqAND, $reqOR) : $reqAND;
			return in_array($value, $tagsArray);
		}
	}

	function doSkinVar($skinType, $type='list20/1/0/1/4')
	{
//	global $ecatid;
//	echo 'ecat='.$ecatid.'<br />';
		// type[0]: type ( + amount (int))
		// type[1]: $narrowMode (0/1/2)
		// type[2]: sortMode (1/2/3/4)
		// type[3]: Minimum font-sizem(em) 0.5/1/1.5/2...
		// type[4]: Maximum font-sizem(em)
		$maxtaglevel = $this->getOption('maxTagLevel');
		$mintaglevel = $this->getOption('minTagLevel');
		$taglevel    = 1;
// default
		if (empty($type)) {
			$type = 'list20/2/1/1/4';
		}
		$type = explode('/', $type);
		if (eregi('list', $type[0])) {
			$amount  = eregi_replace("list", "", $type[0]);
			$type[0] = 'list';
// keywords="TAG"
		} elseif (eregi('meta', $type[0])) {
			$amount  = eregi_replace("meta", "", $type[0]);
			$type[0] = 'meta';
		}
// default amount
		$amount = (!empty($amount)) ?  intval($amount):  99999999;

		$defaultType = array('list', '1', '0', '1', '4');
		$type        = $type + $defaultType;
		$requestT = $this->getNoDecodeQuery('tag');
		if (!empty($requestT)) {
			$requestTarray = $this->splitRequestTags($requestT);
			$reqAND        = array_map(array(&$this, "_rawdecode"), $requestTarray['and']);
			if ($requestTarray['or']) {
				$reqOR = array_map(array(&$this, "_rawdecode"), $requestTarray['or']);
			}
		}
		switch($type[0]){

			case 'tag':
				if ($requestTarray) {
					$reqAndLink = array();
					foreach ($reqAND as $val) {
						$reqAndLink[] = '<a href="'
									  . $this->creatTagLink($val)
									  . '" title="' . $val . '">'
									  . $val . '</a>';
					}
					$reqANDp = implode('" + "', $reqAndLink);
					if ($reqOR) {
						$reqOrLink = array();
						foreach ($reqOR as $val) {
							$reqOrLink[] = '<a href="'
										 . $this->creatTagLink($val)
										 . '" title="' . $val . '">'
										 . $val . '</a>';
						}
						$reqORp = '"</u> or <u>"'
								. implode('"</u> or <u>"', $reqOrLink);
					}
					echo '<h1> Tag for <u>"' . $reqANDp . $reqORp . '"</u></h1>';
				}
				break;

// meta keywords="TAG"
// and AWS keywords
			case 'meta':
				global $manager, $itemid;
				$itemid = intval($itemid);
				if ($type[3] != 'ad') {
					echo '<meta name="keywords" content="';
					$sep = ' ';
				} elseif ($type[3] == 'ad') {
					$sep = ' ';
				}
				if ($skinType == 'item') {
					$q   = 'SELECT * FROM %s WHERE inum = %d';
					$res = sql_query(sprintf($q, _TAGEX_TABLE, $itemid));
					while ($o = mysql_fetch_object($res)) {
						$temp_tags_array = preg_split("/[\n,]+/", trim($o->itags));
						$temp_tags_count = count($temp_tags_array);
						for ($i=0; $i < $temp_tags_count; $i++) {
							$tag         = trim($temp_tags_array[$i]);
							$taglist[$i] = htmlspecialchars($tag, ENT_QUOTES, _CHARSET);
						}
					}
					if ($taglist)
						echo implode(' ', $taglist);
				} else {
					if ($tags = $this->scanExistTags(intval($type[1]), $amount, intval($type[2]))) {
						$eachTag = array();
						$t       = 0;
						foreach ($tags as $tag => $inums) {
							$eachTag[$t] = htmlspecialchars($tag, ENT_QUOTES, _CHARSET);
							$t++;
						}
						if ($type[3] != 'ad') {
							echo implode($sep, $eachTag);
						} elseif ($type[3] == 'ad') {
							$tag_str = implode($sep, $eachTag);
						}
					}
				}
				if ($type[3] != 'ad') {
					echo '" />';
				} elseif ($type[3] == 'ad') {
//					$tag_str = mb_convert_encoding($tag_str, 'UTF-8', 'UTF-8');
					$tag_str = urlencode($tag_str);
					echo $tag_str;
				}
				break;
// TAG list(tag cloud)
			case 'list':
				$template['and']               = $this->getOption('and');
				$template['or']                = $this->getOption('or');
				$template['tagIndex']          = $this->getOption('tagIndex');
				$template['tagItemHeader']     = $this->getOption('tagItemHeader');
				$template['tagItem']           = $this->getOption('tagItem');
				$template['tagItemSeparator']  = $this->getOption('tagItemSeparator');
				$template['tagItemFooter']     = $this->getOption('tagItemFooter');
				$template['tagIndexSeparator'] = $this->getOption('tagIndexSeparator');
				if ($tags = $this->scanExistTags($type[1])) {
					if ($type[3] != $type[4]) {
						$minFontSize               = min((float)$type[3], (float)$type[4]) - 0.5;
						$maxFontSize               = max((float)$type[3], (float)$type[4]);
						$levelsum                  = ($maxFontSize - $minFontSize) / 0.5;
						$taglevelsum               = $maxtaglevel - $mintaglevel;
						list($maxCount, $minCount) = $this->scanCount($tags);
						$eachCount                 = ceil(($maxCount - $minCount) / $levelsum);
					}
					$select = array();
					if ($reqAND) {
						$req = ($reqOR) ? array_merge($reqAND, $reqOR) : $reqAND;
						foreach ($req as $tag) {
							if (array_key_exists($tag, $tags)) {
								$select   = array_merge($select, $tags[$tag]);
								$selected = array_unique($select);
							}
						}
					}
					foreach ($tags as $tag => $inums) {
						if ($selected) {
							if (!in_array($tag, $req)) {
// shiborikomi
//							if (!in_array($tag, $req) && !array_diff($tags[$tag], $selected)) {
								$tagCount[$tag] = count($inums);
							}
						} else {
							$tagCount[$tag] = count($inums);
						}
					}
					if ($tagCount) {
						arsort($tagCount);
						foreach ($tagCount as $k => $v) {
							$r[$k] = $tags[$k];
						}
						unset($tags);
						if (count($r) > $amount) {
							$r = array_slice($r, 0, $amount);
						}
						$tags = array();
						if (count($r) == 1) {
							$tags = $r;
						} else {
							$tags = $this->sortTags($r, intval($type[2]));
						}
					} else {
						echo 'No Tags';
						return;
					}
					$eachTag = array();
					$t       = 0;
					foreach ($tags as $tag => $inums) {
						$tagitems  = array();
						$tagAmount = count($inums);
						if ($eachCount) {
							$fontlevel = ceil($tagAmount / $eachCount) * 0.5 + $minFontSize;
						} else {
							$fontlevel = 1;
						}
						if ($maxCount == $minCount) {//2008-05-22 Cacher
							$taglevel = 1;
						}else{
							$taglevel = round(($tagAmount - $minCount) / ($maxCount - $minCount) * $taglevelsum + $mintaglevel);
						}
/// Item's name had TAGs
						$iids = array_slice($inums, 0, 4);
						sort($iids);
						$qQuery  = ' SELECT '
								 . '   SUBSTRING(ititle, 1, 12) as short_title'
								 . ' FROM '
								 .     sql_table('item')
								 . ' WHERE '
								 . '   inumber in (' . implode(',', $iids) . ') '
								 . 'ORDER BY '
								 . '   inumber';
						$sTitles = sql_query($qQuery);
						$i       = 0;
						while ($sTitle = mysql_fetch_assoc($sTitles)) {
							$shortTitle = mb_convert_encoding($sTitle['short_title'], _CHARSET, _CHARSET);
							$shortTitle = htmlspecialchars($shortTitle, ENT_QUOTES, _CHARSET);
							$printData['tagItem']
										= array(
												'itemid'    => intval($iids[$i]),
												'itemtitle' => $shortTitle . '..',
											   );
							$i++;
							$tagitems[] = TEMPLATE::fill($template['tagItem'], $printData['tagItem']);
						}
						$tagitem = implode($template['tagItemSeparator'], $tagitems) . '...etc.';

// Generate URL link to TAGs
						$and = $or = '';
/*********************
 * comment out this line when nodisplay selected TAGs */
//						$req = ($reqOR) ? array_merge($reqAND, $reqOR) : $reqAND;
/*********************/
						if ($req && !in_array($tag, $req)) {
							$printData['and'] = array(
								'andurl' => $this->creatTagLink($tag, $type[1], $requestT, '+')	//AND link
													 );
							$printData['or']  = array(
								'orurl'  => $this->creatTagLink($tag, $type[1], $requestT, ':')	//OR link
													 );
							$and = TEMPLATE::fill($template['and'], $printData['and']);	// insert URL to template
							$or  = TEMPLATE::fill($template['or'], $printData['or']);
						}

// insert data to template
						$printData['tagIndex'] = array(
							'and'        => $and,
							'or'         => $or,
							'tag'        => htmlspecialchars($tag, ENT_QUOTES, _CHARSET),
							'tagamount'  => $tagAmount,
							'fontlevel'  => $fontlevel,
							'taglevel'   => $taglevel,
							'taglinkurl' => $this->creatTagLink($tag, intval($type[1])),
							'tagitems'   => $tagitem
													  );
						$eachTag[$t]  = TEMPLATE::fill($template['tagIndex'], $printData['tagIndex']);

// format outputdata and data output
						$eachTag[$t] .= $template['tagItemHeader'];
/*
						if (!ereg('<%tagitems%>', $template['tagIndex'])) {//<%
							$eachTag[$t] .= $tagitem;
						}
*/
						$eachTag[$t] .= $template['tagItemFooter'];
						$t++;
					}
					echo implode($template['tagIndexSeparator'] . "\n", $eachTag);
				}
				break;

// show selected TAGs for <title></title>
			case 'title':
				if ($reqAND) {
					$req  = ($reqOR) ? array_merge($reqAND, $reqOR) : $reqAND;
					$data = htmlspecialchars(implode('|', $req), ENT_QUOTES, _CHARSET);
					echo ' : Selected Tag(s) &raquo; &quot;' . $data . '&quot;';
				}
				break;
			default:
				break;
		}
// end of switch(type)
	}

	function doTemplateVar(&$item, $type = '')
	{
// <highlight selected TAGs mod by shizuki>
		$requestT = $this->getNoDecodeQuery('tag');
		if (!empty($requestT)) {
			$requestTarray = $this->splitRequestTags($requestT);
			$reqAND        = array_map(array(&$this, "_rawdecode"), $requestTarray['and']);
			if($requestTarray['or']) {
				$reqOR = array_map(array(&$this, "_rawdecode"), $requestTarray['or']);
			}
			$words = ($reqOR)? array_merge($reqAND, $reqOR): $reqAND;
		} else {
			$words = array();
		}
		$iid = intval($item->itemid);
		$q   = 'SELECT * FROM %s WHERE inum = %d';
		$res = sql_query(sprintf($q, _TAGEX_TABLE, $iid));
		while ($o = mysql_fetch_object($res)) {
			$temp_tags_array = preg_split("/[\n,]+/", trim($o->itags));
			$temp_tags_count = count($temp_tags_array);
			for ($i=0; $i < $temp_tags_count; $i++) {
				$tag     = trim($temp_tags_array[$i]);
				$taglink = $this->creatTagLink($tag, 0);
// highlight selected TAGs
				$key     = array_search($tag, $words);
				if ($key >= 10) {
					$key = $key - 10;
				}
				if (in_array($tag, $words)) {
					$taglist[$i] = '<a href="'
								 . $this->creatTagLink($tag, 0)
								 . '" class="highlight_0" rel="tag">'
								 . htmlspecialchars($tag, ENT_QUOTES, _CHARSET) . '</a>';
				} else {
					$taglist[$i] = '<a href="'
								 . $this->creatTagLink($tag, 0)
								 . '" rel="tag">'
								 . htmlspecialchars($tag, ENT_QUOTES, _CHARSET) . '</a>';
				}
			}
		}
		if ($taglist) {
//			echo 'Tag: ' . implode(' / ', $taglist);
			echo implode(' / ', $taglist);
		}
	}

	function _rawencode($str)
	{

		if (_CHERSET != 'UTF-8') {
			$str = mb_convert_encoding($str, "UTF-8", _CHARSET);
		}
		$str = rawurlencode($str);
		$str = preg_replace('|[^a-z0-9-~+_.?#=&;,/:@%]|i', '', $str);
		return $str;
	}

	function _rawdecode($str)
	{
		$str = rawurldecode($str);
		if (_CHERSET != 'UTF-8') {
			$str = mb_convert_encoding($str, _CHARSET, "UTF-8");
		}
		$str = htmlspecialchars($str);
		return $str;
	}

	function getChildren($subcat_id)
	{
		$subcat_id = intval($subcat_id);
		$que       = 'SELECT'
				   . ' scatid,'
				   . ' parentid,'
				   . ' sname '
				   . 'FROM'
				   . ' %s '
				   . 'WHERE'
				   . ' parentid = %d';
		$mcatTable = sql_table('plug_multiple_categories_sub');
		$que       = sprintf($que, $mcatTable, $subcat_id);
		$res       = sql_query($que);
		while ($so =  mysql_fetch_object($res)) {
			$r .= $this->getChildren($so->scatid)
				. '/'
				. $so->scatid;
		}
		return $r;
	}

	function creatTagLink($tag, $narrowMode = 0, $ready = '', $sep = '')
	{
		global $manager, $CONF, $blogid, $catid;	//, $subcatid;
		$linkparams = array();
		if (is_numeric($blogid)) {
			$blogid = intval($blogid);
		} else {
			$blogid = intval(getBlogIDFromName($blogid));
		}
		if (!$blogid) {
			$blogid = $CONF['DefaultBlog'];
		}
		$b =& $manager->getBlog($blogid);
		if ($narrowMode == 2) {
			if ($catid) {
				$linkparams['catid'] = intval($catid);
			}
			if ($manager->pluginInstalled('NP_MultipleCategories')) {
				$mcategories =& $manager->getPlugin('NP_MultipleCategories');
				if (method_exists($mcategories, 'getRequestName')) {
					$subrequest = $mcategories->getRequestName();
				} else {
					$subrequest = 'subcatid';
				}
				$mcategories->event_PreSkinParse(array());
				global $subcatid;
				if ($subcatid) {
					$linkparams[$subrequest] = intval($subcatid);
				}
			}
		}

		if (!empty($ready)) {
			$requestTagsArray = $this->splitRequestTags($ready);
			foreach ($requestTagsArray['and'] as $key => $val) {
				if (!$this->_isValidTag($val)) {
					$trush = array_splice($requestTagsArray['and'], $key, 1);
				}
			}
			$reqAnd = implode('+', $requestTagsArray['and']);
			if (!empty($requestTagsArray['or'])) {
				foreach ($requestTagsArray['or'] as $key => $val) {
					if (!$this->_isValidTag($val)) {
						$trush = array_splice($requestTagsArray['and'], $key, 1);
					}
				}
				$reqOr = ':' . implode(':', $requestTagsArray['or']);
			}
			$ready = $reqAnd . $reqOr;
		}

		if (!$ready) {
			$sep = '';
		}
//	<mod by shizuki>
/*// <Original URL Generate code>
//		if ($CONF['URLMode'] == 'pathinfo')
//			$link = $CONF['IndexURL'] . '/tag/' . $ready . $sep . $this->_rawencode($tag);
//		else
//			$link = $CONF['IndexURL'] . '?tag=' . $ready . $sep . $this->_rawencode($tag);
		$link = $b->getURL();
		if (substr($link, -1) != '/') {
			if (substr($link, -4) != '.php') {
				$link .= '/';
			}
		}
		if ($CONF['URLMode'] == 'pathinfo') {
			$link .=  'tag/' . $ready . $sep . $this->_rawencode($tag);
		} else {
			$link .= '?tag=' . $ready . $sep . $this->_rawencode($tag);
		}
//  </ Original URL Generate code> */

/*// <test code>
        $CONF['BlogURL']   = $b->getURL();
		$linkparams['tag'] = $ready . $sep . $this->_rawencode($tag);
		$uri               = createBlogidLink($blogid, $linkparams);
		if (strstr ($uri, '//')) {
			$uri = preg_replace("/([^:])\/\//", "$1/", $uri);
		}
		return $uri;
// </test code>*/

// </mod by shizuki>*/

//	<mod by shizuki>
//		if ($manager->pluginInstalled('NP_CustomURL')) {
			$linkparams['tag'] = $ready . $sep . $this->_rawencode($tag);
			$uri               = createBlogidLink($blogid, $linkparams);
			if (strstr ($uri, '//')) {
				$uri = preg_replace("/([^:])\/\//", "$1/", $uri);
			}
			return $uri;
/*		} elseif ($manager->pluginInstalled('NP_MagicalURL2') || $manager->pluginInstalled('NP_Magical')) {
			$uri = createBlogidLink($blogid, $linkparams);
			if (strstr ($uri, '//')) {
				$uri = preg_replace("/([^:])\/\//", "$1/", $uri);
			}
			$uri = substr($uri, 0, -5) . '_tag' . $ready . $sep . $this->_rawencode($tag);
			return $uri;
		}
// </mod by shizuki>*/

//		return addLinkParams($link, $linkparams);
	}

/**
 * function Tag valid
 * add by shizuki
 */
	function _isValidTag($encodedTag)
	{
		$encodedTag = rawurldecode($encodedTag);
		if (_CHERSET != 'UTF-8') {
			$str = mb_convert_encoding($encodedTag, _CHARSET, "UTF-8");
		}
		$str  = $this->quote_smart($str);
		$q    = 'SELECT listid as result FROM %s WHERE tag = %s';
		$Vali = quickQuery(sprintf($q, _TAGEX_KLIST_TABLE, $str));
		if (!empty($Vali)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

}
