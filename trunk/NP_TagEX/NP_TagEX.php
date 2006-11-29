<?php
/**
 *
 * TAGGING PLUG-IN FOR NucleusCMS
 * PHP versions 4 and 5
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * (see nucleus/documentation/index.html#license for more info)
 * 
 * 
 * @author     Original Author nakahara21
 * @copyright  2005-2006 nakahara21
 * @license    http://www.gnu.org/licenses/gpl.txt
 *             GNU GENERAL PUBLIC LICENSE Version 2, June 1991
 * @version    0.41
 * @link       http://nakahara21.com
 *
 * 0.51 typo fix
 * 0.5  TAG sort modified
 * 0.43 fix URL generate
 * 0.42 add URL selected tag check
 * 0.41 security fix and add some trick
 * 0.4  fixed bug: numlic only
 * 0.3  fixed bug: delete action
 * 0.2  supports and/or query
 *
 * 
 * THESE PLUG-INS ARE DEDICATED TO ALL THOSE NucleusCMS USERS
 * WHO FIGHT CORRUPTION AND IRRATIONAL IN EVERY DAY OF THEIR LIVES.
 *
 **/

if (!defined('_TAGEX_TABLE_DEFINED')) {
	define('_TAGEX_TABLE_DEFINED', 1);
	define('_TAGEX_TABLE',         sql_table('plug_tagex'));
	define('_TAGEX_KLIST_TABLE',   sql_table('plug_tagex_klist'));
}

class NP_TagEX extends NucleusPlugin
{

	function getName()
	{
		return 'Tags Extension';
	}
	function getAuthor()
	{
		return 'nakahara21 + shizuki';
	}
	function getURL()
	{
		return 'http://nakahara21.com';
	}
	function getVersion()
	{
		return '0.51';
	}
	function getDescription()
	{
		return 'Tags Extension (for Japanese users)';
	}

	function supportsFeature($what)
	{
		switch ($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function install()
	{
		$this->createOption('flg_erase',         'Erase data on uninstall.',				'yesno',	'no');
// <editable template mod by shizuki>
		$this->createOption('editTagOrder',      'editform tag order',                 'select',
							'1', 'amount(desc)|1|amount(asc)|2|tag\'s order|3|random|4');
		$this->createOption('and',               'template for \'and\'',               'textarea',
		                    '<span style="font-family:tahoma;font-size:smaller;"> '
		                  . ' <a href="<%andurl%>" title="narrow">&amp;</a>.');
		$this->createOption('or',                'template for \'or\'',                'textarea',
							'<a href="<%orurl%>" title="expand">or</a> </span>');
		$this->createOption('tagIndex',          'template for \'tagIndex\'',          'textarea',
							'<%and%><%or%><span style="font-size:<%fontlevel%>em" '
						  . 'title="<%tagamount%> post(s)! <%tagitems%>"><a href="<%taglinkurl%>"><%tag%></a></span>');
		$this->createOption('tagItemHeader',     'template for \'tagItemHeader\'',     'textarea',
							'');
		$this->createOption('tagItem',           'template for \'tagItem\'',           'textarea',
							'<%itemid%>:<%itemtitle%>');
		$this->createOption('tagItemSeparator',  'template for \'tagItemSeparator\'',  'text',
							' , ');
		$this->createOption('tagItemFooter',     'template for \'tagItemFooter\'',     'textarea',
							'');
		$this->createOption('tagIndexSeparator', 'template for \'tagIndexSeparator\'', 'text',
							' | ');
		$this->createOption('tagsonlycurrent',   'show tags only current blog have',   'yesno',
							'no');
		$this->createOption('colorfulhighlight', 'colorful highlight mode ?',          'yesno',
							'no');
		$this->createOption('highlight',         'template for normal highlightmode',  'text',
							'<span class="highlight">\0</span>');
//</mod by shizuki>*/
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

	function uninstall()
	{
		if ($this->getOption('flg_erase') == 'yes') {
			sql_query('DROP TABLE IF EXISTS ' . _TAGEX_TABLE);
			sql_query('DROP TABLE IF EXISTS ' . _TAGEX_KLIST_TABLE);
		}
	}

	function getTableList()
	{
		return array(
			_TAGEX_TABLE,
			_TAGEX_KLIST_TABLE
		);
	}

	function getEventList()
	{
		return array(
					 'PostAddItem',
					 'AddItemFormExtras',
					 'PreUpdateItem',
					 'EditItemFormExtras',
					 'PreItem',
					 'PreDeleteItem'
					);
	}

/**
 *
 * Nucleus event functions
 *
 */
	function quote_smart($value)
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

	function event_PreItem($data)
	{
// Hightlight tags
		global $currentTemplateName;
//		$currentTemplateName = $this->quote_smart($currentTemplateName);
		$currTemplateName    = $this->quote_smart($currentTemplateName);
		$templateDescTable   = sql_table('template_desc');
		$q_query             = 'SELECT tddesc as result '
							 . 'FROM %s '
							 . 'WHERE tdname = %s';
//		$q_query             = sprintf($q_query, $templateDescTable, $currentTemplateName);
		$q_query             = sprintf($q_query, $templateDescTable, $currTemplateName);
		$currentTemplateDesc = quickQuery($q_query);
		if (eregi('<highlightTagsAll>', $currentTemplateDesc)) {
			$tags = $this->scanExistTags(0, 99999999);
			if (empty($tags)) {
				return;
			} else {
				$highlightKeys = array_keys($tags);
			}
		} elseif (eregi('<highlightTags>', $currentTemplateDesc)) {
// <mod for Fancy mode by shizuki>
//			if (!requestVar('tag')) {
//				return;
//			} else {
// </mod by shizuki>
//			$highlightKeys = explode(' ',requestVar('tag'));
//			$highlightKeys = array_map(array(&$this, "_rawdecode"), $highlightKeys);
			$requestT = $this->getNoDecodeQuery('tag');
			if (empty($requestT)) {
				return;
			}
// </mod by shizuki>
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
//			}	<mod for Fancy mode by shizuki />
		} else {
			return;
		}
//		$template['highlight'] = '<span class="highlight">\0</span>';// original code
		$template['highlight'] =  $this->getOption('highlight');// <editable template mod by shizuki />
		$curItem               =& $data['item'];
		if ($this->getOption('colorfulhighlight') == 'no') {// original mode
//			$curItem->title = highlight($curItem->title, $highlightKeys, $template['highlight']);
			$curItem->body  = highlight($curItem->body, $highlightKeys, $template['highlight']);
			$curItem->more  = highlight($curItem->more, $highlightKeys, $template['highlight']);
		} else {
/**
 *
 * use other color for each tags
 * mod by shizuki
 *
 */
//
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
// </mod by shizuki>*/
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
	function _ItemFormExtras($oldforj = '', $itags = '', $tagrows, $tagcols, $blogid = 0)
	{
		$blogid = intval($blogid);
// Exstra form for add or update Item
		if (strstr(serverVar('HTTP_USER_AGENT'), 'Gecko')) {
			$divStyles = 'height: 24em;'
					   . 'overflow: auto;'
					   . 'border:1px solid lightblue;'
					   . 'margin-top:3.8em;'
					   . 'padding-left:0.5em;'
					   . '-moz-column-count: 3;'
					   . '-moz-column-gap: 0.5em;';
			$txAStyles = 'width:10em;'
					   . 'height: 200px;'
					   . 'width: 120px;';
		} else {
			$divStyles = 'height: 200px;'
					   . 'overflow: auto;';
			$txAStyles = 'width:60%;';
		}
		$printData = "\t\t"
				   . "<h3>TagEX</h3>\n\t\t"
				   . '<p style="float:left">' . "\n\t\t\t"
				   . '<label for="tagex">Tag(s):</label>' . "\n\t\t\t"
				   . '<a href="javascript:resetOlder'
				   . "('" . $oldforj . "')"
				   . '">[Reset]</a><br />' . "\n\t\t\t"
				   . '<textarea id="tagex" name="itags" rows="' . intval($tagrows)
				   . '" cols="' . intval($tagcols) . '" style="' . $txAStyles . '">'
				   . htmlspecialchars($itags) . '</textarea>' . "\n\t\t"
				   . '</p>'
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
				   . '<div style="' . $divStyles . '">' . "\n";
//		echo '<div style="height: 200px;overflow: auto;">' . "\n";
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
//			echo '<li><a href="javascript:insertag(' . "'" . htmlspecialchars($existTags[$i]) . "'" . ')">';
//			echo htmlspecialchars($existTags[$i]) . '</a></li>' . "\n";
		}
		echo '</div><br style="clear:all;" />' . "\n";
	}

	function event_AddItemFormExtras($data)
	{
// <current blog onry mod by shizuki>
		global $CONF, $blogid;
		if (is_numeric($blogid)) {
			$blogid = intval($blogid);
		} else {
			$blogid = intval(getBlogIDFromName($blogid));
		}
		if (empty($blogid)) {
			$blogid = intval($CONF['DefaultBlog']);
		}
// </mod by shizuki>*/
// Call exstra form
		$oldforj    = $itags = '';
		$this->_ItemFormExtras($oldforj, $itags, 3, 40, $blogid);// <current blog only />
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
// <current blog onry mod by shizuki>
		$blogid  = getBlogIDFromItemID($item_id);
		$blogid  = intval($blogid);
// </mod by shizuki>*/
// Call exstra form
// <current blog onry mod by shizuki />
		$this->_ItemFormExtras($oldforj, $itags, 5, 20, $blogid);
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
			return;
		}
		$query = 'DELETE FROM %s WHERE inum = %d';
		sql_query(sprintf($query, _TAGEX_TABLE, $inum));
		if (isset($itags)) {
			$query  = 'INSERT INTO %s (inum, itags) VALUES (%d, %s)';
			$query  = sprintf($query, _TAGEX_TABLE, $inum, $this->quote_smart($itags));
			sql_query($query);
		}
		$old_tags_array = $this->getTags($oldTags);
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
			return;
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
		if ($temp_inums == $inum) {
			$query = 'DELETE FROM %s WHERE tag = %s';
			sql_query(sprintf($query, _TAGEX_KLIST_TABLE, $tag));
			return;
		}
		$inums_array = explode(',', $temp_inums);
		$trans       = array_flip($inums_array);
		unset($trans[$inum]);
		$inums_array = array_flip($trans);
		$inums_count = count($inums_array);
		$inums       = @implode(",", $inums_array);
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
		
		if (isset($inums)) {
			$q_query    = 'UPDATE %s SET inums = %s, inums_count = %d WHERE tag = %s';
			$iCount     = intval($inums_count);
			$quoteInums = $this->quote_smart($inums);
			sql_query(sprintf($q_query, _TAGEX_KLIST_TABLE, $quoteInums, $iCount, $tag));
		}
	}

	function scanExistItem($narrowMode = 0, $blogid = 0)
	{
// Select Items when Categories or Sub-categorie or Archive selected
		global $manager, $CONF, $blog, $catid, $archive;
		if ($blogid > 0) {
			$b =& $manager->getBlog($blogid);
		} elseif ($blog) {
			$b =& $blog; 
		} else {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		$where = '';
		if ($narrowMode>0) {
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
					if ($Children[1]) {
						for ($i=0; $i < count($Children); $i++) {
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
		}else{
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
	function sortTags($tags, $sortmode = 0)
	{
		// sortMode 0:none
		// sortMode 1:max first
		// sortMode 2:min first
		// sortMode 3:tag's order
		// sortMode 4:random
		$sortmode = intval($sortmode);
		if (!$tags || $sortmode == 0) {
			return $tags;
		}
		foreach ($tags as $tag => $inums) {
			$tagCount[$tag] = count($inums);
		}
		switch ($sortmode) {
			case 1:
				arsort($tagCount);
				break;
			case 2:
				asort($tagCount);
				break;
// <for sortmode = 3 or 4 mod by shizuki>
			case 3:
/*// <http://blog.uribou.net/>
				$idx = 0;
				foreach ($tagCount as $tag => $cnt) {
					$tagtbl[$idx]  = $tag;
					$tmpcnts[$idx] = $cnt;
					$tmptags[$idx] = strtolower($tag);
					$idx++;
				}
				asort($tmptags);
				unset($tagCount);
				$tagCount = array();
				foreach ($tmptags as $idx => $tag) {
					$tagCount[$tagtbl[$idx]] = $tmpcnts[$idx];
				}
// </ http://blog.uribou.net/>*/
				uksort($tagCount, array(&$this, 'sortTagOrder'));
				break;
			case 4:
/// <http://blog.uribou.net/>
				srand ((float) microtime() * 10000000);
				$tmp_key  = array_rand($tagCount, count($tagCount));
				unset($tagCount);
				$tagCount = array();
				foreach ($tmp_key as $k => $v) {
					$tagCount[$v] = 0;
				}
// </ http://blog.uribou.net/>*/
				break;
// </mod by shizuki>*/
			default:
				break;
		}
//		$result = array_merge_recursive($tagCount, $tags);
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
// </mod by shizuki>
//		$existInumsIn = ($existInums = $this->scanExistItem($narrowMode, $blogid)) ?
//						 ' WHERE inum in (' . @implode(',', $existInums) . ')' : '';
		$existInumsIn = '';
		if ($existInums = $this->scanExistItem($narrowMode, $blogid)) {
			$existInumsIn = ' WHERE inum in (' . @implode(',', $existInums) . ')';
		}
		$q   = 'SELECT * FROM ' . _TAGEX_TABLE . $existInumsIn;
		$res = sql_query($q);
		while ($o = mysql_fetch_object($res)) {
			$temp_tags_array = preg_split("/[\n,]+/", $o->itags);
			for ($i=0; $i < count($temp_tags_array); $i++){
				$tag = trim($temp_tags_array[$i]);
				$tags[$tag][] = $o->inum;
			}
		}
		$tags = $this->sortTags($tags, $sortmode);
		if (count($tags) > $amount) {
			$tags = array_slice($tags, 0, $amount);
		}

		switch ($sortmode) {
			case 1:
				$sortq = ' ORDER by inums_count DESC';
				break;
			case 2:
				$sortq = ' ORDER by inums_count ASC';
				break;
//* <for sortmode = 3 or 4 mod by shizuki>
			default:
				$sortq = '';
				break;
// </mod by shizuki>*/
		}
//		$q = 'SELECT * FROM ' . _TAGEX_KLIST_TABLE . $sortq . ' LIMIT ' . $amount;
		$q   = 'SELECT * FROM %s%s LIMIT %d';
		$res = sql_query(sprintf($q, _TAGEX_KLIST_TABLE, $sortq, $amount));
		while ($o = mysql_fetch_object($res)) {
			$tagsk[$o->tag] = explode(',', $o->inums);
			if ($existInums) {
				$tagsk[$o->tag] = array_intersect($tagsk[$o->tag], $existInums);
				$tagsk[$o->tag] = array_values($tagsk[$o->tag]);
			}
			if ($tagsk[$o->tag] == array()) {
				unset($tagsk[$o->tag]);
			}
		}
//		print_r($tagsk);
/*
		if($tags && $tagsk){
			if($c = array_diff_assoc($tagsk,$tags)){
				echo '<h1>';
				print_r($c);
				echo '</h1>';
			}
		}
*/
		return $tags;
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
		global $CONF;
// FancyURL
		if ($CONF['URLMode'] == 'pathinfo') {
			$urlq  = serverVar('REQUEST_URI');
			$tempq = explode($q . '/', $urlq, 2);
			if ($tempq[1]) {
				$tagq = explode('/', $tempq[1]);
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
		if (!strstr($q, '+') && !strstr($q, ':')) {
			$res['and'][0] = $q;
			return $res;
		}
		$res     = array('and'=>array(), 'or'=>array());
		$tempAnd = explode('+', $q);
		$andCnt  = count($tempAnd);
		for ($i=0; $i<$andCnt; $i++) {
			$temp         = explode(':', $tempAnd[$i]);
			$res['and'][] = array_shift($temp);
			if ($temp != array()) {
				$res['or'] = array_merge($res['or'], $temp);
			}
		}
		return $res;
	}

	function doSkinVar($skinType, $type='list20/1/0/1/4')
	{
		// type[0]: type ( + amount (int))
		// type[1]: $narrowMode (0/1/2)
		// type[2]: sortMode (1/2/3/4)
		// type[3]: Minimum font-sizem(em) 0.5/1/1.5/2...
		// type[4]: Maximum font-sizem(em)
// default
		if (empty($type)) {
			$type = 'list20/2/1/1/4';
		}
		$type = explode('/',$type);
		if (eregi('list', $type[0])) {
			$amount  = eregi_replace("list", "", $type[0]);
			$type[0] = 'list';
// < meta keywords="TAG" mod by shizuki>
		} elseif (eregi('meta', $type[0])) {
			$amount  = eregi_replace("meta", "", $type[0]);
			$type[0] = 'meta';
// </mod by shizuki>*/
		}
// default amount
		$amount = ($amount) ?  $amount:  99999999;
		$amount = intval($amount);

		$defaultType = array('list', '1', '0', '1', '4');
		$type        = $type + $defaultType;
		$type[0]     = htmlspecialchars($type[0], ENT_QUOTES, _CHARSET);
		$type[1]     = intval($type[1]);
		$type[2]     = intval($type[2]);
		$type[3]     = (float)$type[3];
		$type[4]     = (float)$type[4];
// <for FancyURL mod by shizuki>
//		if (requestVar('tag')) {
		$requestT = $this->getNoDecodeQuery('tag');
		if (!empty($requestT)) {
			$requestTarray = $this->splitRequestTags($requestT);
			$reqAND        = array_map(array(&$this, "_rawdecode"), $requestTarray['and']);
			if ($requestTarray['or']) {
				$reqOR = array_map(array(&$this, "_rawdecode"), $requestTarray['or']);
			}
		}
//		}
// </mod by shizuki>
		switch($type[0]){ 

			case 'tag':
				if ($requestTarray) {
					foreach ($reqAND as $val) {
						$reqAndLink[] = '<a href="'
									  . $this->creatTagLink($val)
									  . '" title="' . $val . '">'
									  . $val . '</a>';
					}
					$reqANDp = @implode('" + "', $reqAndLink);
					if ($reqOR) {
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

// < meta keywords="TAG" mod by shizuki>
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
					if ($tags = $this->scanExistTags($type[1], $amount, $type[2])) {
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
// </mod by shizuki>*/
// TAG list
			case 'list':
// template
/*
	tagIndexHeader
		tagIndex
			tagItemHeader
					tagItem
				tagItemSeparator
					tagItem
			tagItemFooter
	tagIndexFooter
tagIndexSeparator
	tagIndexHeader .....
*/


/*
		$template['tagIndex']          = '<h3><a href="<%taglinkurl%>"><%tag%>'
									   . '(<%tagamount%>)</a></h3>';
		$template['tagItemHeader']     = '<ul>';
		$template['tagItem']           = '<li><%itemid%></li>';
		$template['tagItemSeparator']  = '';
		$template['tagItemFooter']     = '</ul>';
*//*
		$template['and']               = '<span style="font-family:tahoma;font-size:smaller;">'
									   . ' <a href="<%andurl%>" title="narrow">&amp;</a>.';
		$template['or']                = '<a href="<%orurl%>" title="expand">or</a> </span>';
		$template['tagIndex']          = '<%and%><%or%>'
									   . '<span style="font-size:<%fontlevel%>em" '
									   . 'title="<%tagamount%> post(s)! <%tagitems%>">'
									   . '<a href="<%taglinkurl%>"><%tag%></a></span>';
		$template['tagItemHeader']     = '';
		$template['tagItem']           = "<%itemid%>:<%itemtitle%>";
		$template['tagItemSeparator']  = ' , ';
		$template['tagItemFooter']     = '';
		$template['tagIndexSeparator'] = ' | ';
*//*
		$template['tagIndex']          = '<span style="font-size:<%fontlevel%>em"'
									   . ' title="<%tagamount%> post(s)!">'
									   . '<a href="<%taglinkurl%>"><%tag%>'
									   . '(<%tagamount%>)</a></span>';
		$template['tagItemHeader']     = '';
		$template['tagItem']           = "\n<%itemid%>:<%itemtitle%>";
		$template['tagItemSeparator']  = ' / ';
		$template['tagItemFooter']     = '';
		$template['tagIndexSeparator'] = ' | ';
*/
//		print_r($tags);
// <editable template mod by shizuki>
				$template['and']               = $this->getOption('and');
				$template['or']                = $this->getOption('or');
				$template['tagIndex']          = $this->getOption('tagIndex');
				$template['tagItemHeader']     = $this->getOption('tagItemHeader');
				$template['tagItem']           = $this->getOption('tagItem');
				$template['tagItemSeparator']  = $this->getOption('tagItemSeparator');
				$template['tagItemFooter']     = $this->getOption('tagItemFooter');
				$template['tagIndexSeparator'] = $this->getOption('tagIndexSeparator');
// </mod by shizuki>*/
//				if($tags = $this->scanExistTags($type[1], $amount, $type[2])){// original mode
				if ($tags = $this->scanExistTags($type[1])) {// <nodisplay selected TAGs mod by shizuki />
					if ($type[3] != $type[4]) {
						$minFontSize               = min($type[3], $type[4]) - 0.5;
						$maxFontSize               = max($type[3], $type[4]);
						$levelsum                  = ($maxFontSize - $minFontSize) / 0.5;
						list($maxCount, $minCount) = $this->scanCount($tags);
						$eachCount                 = ceil(($maxCount - $minCount) / $levelsum);
					}
// <nodisplay selected TAGs mod by shizuki>
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
							if (!in_array($tag, $req)) {// && !array_diff($tags[$tag], $selected)) {// shiborikomi
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
							$tags = $this->sortTags($r, $type[2]);
						}
					} else {
						echo 'No Tags';
						return;
					}
// </mod by shizuki>*/
					$eachTag = array();
					$t       = 0;
					foreach ($tags as $tag => $inums) {
						$tagitems  = array();
						$tagAmount = count($inums);
//$fontlevel = $eachCount ? ceil($tagAmount / $eachCount) * 0.5 + $minFontSize: 1;
						if ($eachCount) {
							$fontlevel = ceil($tagAmount / $eachCount) * 0.5 + $minFontSize;
						} else {
							$fontlevel = 1;
						}

// Item's name had TAGs 
						for ($i=0;$i<$tagAmount;$i++) {
							$qQuery               = 'SELECT ititle as result '
												  . 'FROM %s WHERE inumber = %d';
							$qQuery               = sprintf($qQuery, sql_table('item'), intval($inums[$i]));
							$itemtitle            = quickQuery($qQuery);
							$itemtitle            = htmlspecialchars($itemtitle, ENT_QUOTES, _CHARSET);
							$shortTitle           = shorten(strip_tags($itemtitle), 10, '...');
							$shortTitle           = htmlspecialchars($shortTitle, ENT_QUOTES, _CHARSET);
							$printData['tagItem'] = array(
														  'itemid'    => intval($inums[$i]),
														  'itemtitle' => $shortTitle,
														 );
							$tagitems[] = TEMPLATE::fill($template['tagItem'], $printData['tagItem']);
						}
						$tagitem = implode($template['tagItemSeparator'], $tagitems);

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
							'taglinkurl' => $this->creatTagLink($tag, $type[1]),
							'tagitems'   => $tagitem
													  );
						$eachTag[$t] = TEMPLATE::fill($template['tagIndex'], $printData['tagIndex']);

// format outputdata and data output
						$eachTag[$t] .= $template['tagItemHeader'];
						if (!ereg('<%tagitems%>', $template['tagIndex'])) {
							$eachTag[$t] .= $tagitem;
						}
						$eachTag[$t] .= $template['tagItemFooter'];
						$t++;
					}
					echo implode($template['tagIndexSeparator'], $eachTag);
				}
				break;

// <show selected TAGs for <title></title> mod by shizuki>
			case 'title':
				if ($reqAND) {
					$req = ($reqOR) ? array_merge($reqAND, $reqOR) : $reqAND;
					echo ' : Selected Tag(s) &raquo; "' . implode('|', $req) . '"';
				}
				break;
// </mod by shizuki>*/
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
// </mod by shizuki>*/
		$iid = intval($item->itemid);
		$q   = 'SELECT * FROM %s WHERE inum = %d';
		$res = sql_query(sprintf($q, _TAGEX_TABLE, $iid));
		while ($o = mysql_fetch_object($res)) {
			$temp_tags_array = preg_split("/[\n,]+/", trim($o->itags));
			for ($i=0; $i < count($temp_tags_array); $i++) {
				$tag     = trim($temp_tags_array[$i]);
				$taglink = $this->creatTagLink($tag, 0);
// <highlight selected TAGs mod by shizuki>
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
// </mod by shizuki>*/
					$taglist[$i] = '<a href="'
								 . $this->creatTagLink($tag, 0)
								 . '" rel="tag">'
								 . htmlspecialchars($tag, ENT_QUOTES, _CHARSET) . '</a>';
				}	// <highlight selected TAGs mod by shizuki />
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
			// <mod by shizuki />
		return $str;
	}

	function _rawdecode($str)
	{
		$str = rawurldecode($str);
		if (_CHERSET != 'UTF-8') {
			$str = mb_convert_encoding($str, _CHARSET, "UTF-8");
		}
		$str = htmlspecialchars($str);
			// <mod by shizuki />
		return $str;
	}

	function getChildren($subcat_id)
	{
		$subcat_id = intval($subcat_id);	// <mod by shizuki />
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
//			if ($blogid) {
//				$linkparams['blogid'] = intval($blogid);
//			}
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

// <mod by shizuki>
		if (!empty($ready)) {
			$requestTagArray = $this->splitRequestTags($ready);
			foreach ($requestTagArray['and'] as $key => $val) {
				if (!$this->_isValidTag($val)) {
					$trush = array_splice($requestTagArray['and'], $key, 1);
				}
			}
			$reqAnd = implode('+', $requestTagArray['and']);
			if (!empty($requestTagArray['or'])) {
				foreach ($requestTagArray['or'] as $key => $val) {
					if (!$this->_isValidTag($val)) {
						$trush = array_splice($requestTagArray['and'], $key, 1);
					}
				}
				$reqOr = ':' . implode(':', $requestTagArray['or']);
			}
			$ready = $reqAnd . $reqOr;
		}
// </mod by shizuki>*/

		if (!$ready) {
			$sep = '';
		}
//	<mod by shizuki>
/// <Original URL Generate code>
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
		if ($manager->pluginInstalled('NP_CustomURL')) {
			$linkparams['tag'] = $ready . $sep . $this->_rawencode($tag);
			$uri               = createBlogidLink($blogid, $linkparams);
			if (strstr ($uri, '//')) {
				$uri = preg_replace("/([^:])\/\//", "$1/", $uri);
			}
			return $uri;
		}
// </mod by shizuki>*/

		return addLinkParams($link, $linkparams);
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
