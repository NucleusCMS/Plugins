<?php

//history
//	0.80:	Fixed bug (by nakahara21)
//	0.72:	Internationalize.
//			Fixed typo.
//	0.71:	Fixed security issue.
//			Fixed typo.

class NP_UpdateTime extends NucleusPlugin
{
	function getName()
	{
		return 'UpdateTime';
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
		return '0.80';
	}

	function getDescription()
	{
		return _UPDATETIME_DESCRIPTION;
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

	function getTableList()
	{
		return array (
					  sql_table('plugin_rectime')
					 );
	}

	function getEventList()
	{
		return array (
					  'EditItemFormExtras',
					  'PreUpdateItem'
					 );
	}

	function install()
	{
		$query = 'CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_rectime') . ' ('
			   . ' up_id      INT(11)  not null,'
			   . ' updatetime DATETIME,'
			   . ' PRIMARY KEY (up_id)'
			   . ')';
		sql_query($query);
		$this->createOption('DefautMode', _UPDATETIME_DEFAULT_MODE, 'select', '1', _UPDATETIME_DEFAULT_MODE_VALUE);
		$this->createOption('BeforeTime', _UPDATETIME_BEFORE_TIME,  'text',        _UPDATETIME_BEFORE_TIME_VALUE);
		$this->createOption('AfterTime',  _UPDATETIME_AFTER_TIME,   'text',        _UPDATETIME_AFTER_TIME_VALUE);
		$this->createOption('Locale',     _UPDATETIME_DATE_LOCALE,  'text',        'ja_JP.' . _CHARSET);
		$this->createOption('DateFormat', _UPDATETIME_DATE_FORMAT,  'text',        '%Y-%m-%d %H:%M:%S');
		$this->createOption('sLists',     _UPDATETIME_S_LISTS,      'text',        '<ul class="nobullets">');
		$this->createOption('eLists',     _UPDATETIME_E_LISTS,      'text',        '</ul>');
		$this->createOption('sItems',     _UPDATETIME_S_ITEMS,      'text',        '<li>');
		$this->createOption('eItems',     _UPDATETIME_E_ITEMS,      'text',        '</li>');
		$this->createOption('uninstFlag', _UPDATETIME_UNINST_FLAG,  'yesno',      'no');
	}

	function unInstall()
	{ 
		if ($this->getOption('uninstFlag') == 'yes') {
			sql_query ('DROP TABLE IF EXISTS ' . sql_table('plugin_rectime'));
		}
	}

	function init()
	{
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory() . $language . '.php')) {
			include_once($this->getDirectory() . $language . '.php');
		} else {
			include_once($this->getDirectory() . 'english.php');
		}
		$this->defMode = intval($this->getOption('DefautMode'));
		if ($this->defMode > 2) {
			$this->defMode = 0;
		}
	}

	function event_EditItemFormExtras($data)
	{
		$checkedFlag[$this->defMode] = ' checked="checked"';
		$updateMode = _UPDATETIME_MODE;
		$updateOver = _UPDATETIME_OVERWRITE;
		$recordOnly = _UPDATETIME_RECORDEONLY;
		$noAction   = _UPDATETIME_NOACTION;
		$printData  = '<h3 id="np_updatetime_ares" style="margin-bottom:0;">' . $updateMode . "</h3>\n"
					. '<input type="radio" name="updatetime" value="2" id="updatetime_2"' . $checkedFlag[2] . ' />'
					. '<label for="updatetime_2">' . $updateOver . "</label><br />\n"
					. '<input type="radio" name="updatetime" value="1" id="updatetime_1"' . $checkedFlag[1] . ' />'
					. '<label for="updatetime_1">' . $recordOnly . "</label><br />\n"
					. '<input type="radio" name="updatetime" value="0" id="updatetime_0"' . $checkedFlag[0] . ' />'
					. '<label for="updatetime_0">' . $noAction . "</label><br />\n";
		echo $printData;
	}

	function event_PreUpdateItem($data)
	{
		$recd = intRequestVar('updatetime');
		if (!$recd) {
			return;
		}
		if (postVar('actiontype') == 'adddraft') {
			return;
		}

		$updatetime = mysqldate($data['blog']->getCorrectTime());
		if ($recd == 2) {
			$upQuery    = 'UPDATE ' . sql_table('item')
						. ' SET   itime   = ' . $updatetime
						. ' WHERE inumber = ' . intval($data['itemid']);
			$upTimeQue  = 'SELECT itime as result '
						. 'FROM ' . sql_table('item')
						. ' WHERE inumber=' . intval($data['itemid']);
			$tmpTimeQue = 'SELECT updatetime as result '
						. 'FROM ' . sql_table('plugin_rectime')
						. ' WHERE up_id = ' . intval($data['itemid']);
			$updatetime = '"' . quickQuery($upTimeQue) . '"';
			$tmptime    = '"' . quickQuery($tmpTimeQue) . '"';
			if ($tmptime > $updatetime) {
				$updatetime = $tmptime;
			}
			sql_query($upQuery);
		}
		$delQuery = 'DELETE FROM ' . sql_table('plugin_rectime')
				  . ' WHERE up_id = ' . intval($data['itemid']);
		sql_query($delQuery);
		$query = 'INSERT INTO ' . sql_table('plugin_rectime')
			   . ' (up_id, updatetime) '
			   . 'VALUES'
			   . ' (' . intval($data['itemid']) . ', ' . $updatetime . ')';
		$res   = sql_query($query);
		if (strpos($res, 'mySQL')) {
			return '<p>Could not save data: ' . $res;
		}
		return '';
	}

	function doSkinVar($skinType, $maxtoshow = 5, $bmode = 'current')
	{
		global $manager, $CONF, $blogid;
		if (is_numeric($blogid)) {
			$blogid = intval($blogid);
		} else {
			$blogid = gttBlogIDFromName($blogid);
		}
		if (!$blogid) {
			$blogid = $CONF['DefaultBlog'];
		}

		$b                    =& $manager->getBlog($blogid);
		$this->defaultBlogURL = $b->getURL() ;
		if (!$this->defaultBlogURL) {
			$this->defaultBlogURL = $CONF['IndexURL'];
		}

		if ($maxtoshow == '') {
			$maxtoshow = 5;
		}
		if ($bmode == '') {
			$bmode = 'current';
		}

		echo $this->getOption('sLists') . "\n";
		$query = 'SELECT'
			   . ' r.up_id as up_id, '
			   . ' IF(INTERVAL(r.updatetime, i.itime), UNIX_TIMESTAMP(r.updatetime), UNIX_TIMESTAMP(i.itime)) as utime '
			   . 'FROM '
			   .   sql_table('plugin_rectime') . ' as r, '
			   .   sql_table('item') .           ' as i '
			   . 'WHERE'
			   . ' r.up_id=i.inumber';
		if ($bmode != 'all') {
			$query .= ' and i.iblog=' . intval($blogid);
		}
		$query .= ' ORDER BY utime DESC'
				. ' LIMIT 0, ' . intval($maxtoshow);
		$res    = sql_query($query);
		while ($row = mysql_fetch_object($res)) {
			$item =& $manager->getItem($row->up_id, 0, 0);
			if ($item) {
				$itemlink  = $this->createGlobalItemLink($item['itemid']);
				$itemtitle = strip_tags($item['title']);
				$itemtitle = shorten($itemtitle,26,'..');
				$itemdate  = date('m/d H:i',$row->utime);

				$printData = $this->getOption('sItems') . "\n"
						   . '<a href="' . $itemlink . '">'
						   . htmlspecialchars($itemtitle, ENT_QUOTES, _CHARSET)
						   .'</a> <small>' . $itemdate . "</small>\n"
						   . $this->getOption('eItems') . "\n";
				echo $printData;

			}
		}
		echo $this->getOption('eLists');
	}

	function doTemplateVar(&$item)
	{
		setlocale(LC_TIME, $this->getOption('Locale'));
		$query = 'SELECT'
			   . '   r.up_id,'
			   . '   UNIX_TIMESTAMP(r.updatetime) as updatetime,'
			   . '   UNIX_TIMESTAMP(i.itime)      as itemtime '
			   . 'FROM '
			   .     sql_table('plugin_rectime') . ' as r, '
			   .     sql_table('item') .           ' as i '
			   . 'WHERE'
			   . '     r.up_id = ' . intval($item->itemid)
			   . ' and r.up_id = i.inumber';
		$res   = sql_query($query);
		if ($row = mysql_fetch_assoc($res)) {
//			$data['utime'] = date($this->getOption('DateFormat'), $row['updatetime']);
			$data['utime'] = strftime($this->getOption('DateFormat'), $row['updatetime']);
			if ($row['updatetime'] > $row['itemtime']) {
				echo TEMPLATE::fill($this->getOption('AfterTime'), $data);
			} elseif ($row['updatetime'] < $row['itemtime']) {
				echo TEMPLATE::fill($this->getOption('BeforeTime'), $data);
			}
		}
	}

	function createGlobalItemLink($itemid, $extra = '')
	{
		global $CONF, $manager;
/*		if ($CONF['URLMode'] == 'pathinfo') {
			$link = $CONF['ItemURL'] . '/item/' . $itemid;
		}else{
			$blogid = getBlogIDFromItemID($itemid);
			$b_tmp =& $manager->getBlog($blogid);
			$blogurl = $b_tmp->getURL() ;
			if(!$blogurl){
				$blogurl = $this->defaultBlogURL;
			}
			if(substr($blogurl, -4) != '.php'){
				if(substr($blogurl, -1) != '/')
					$blogurl .= '/';
				$blogurl .= 'index.php';
			}
			$link = $blogurl . '?itemid=' . $itemid;
		}
		return addLinkParams($link, $extra);*/
		$blogid  =  getBlogIDFromItemID($itemid);
		$b_tmp   =& $manager->getBlog($blogid);
		$blogurl =  $b_tmp->getURL() ;
		if (!$blogurl) {
			$blogurl = $this->defaultBlogURL;
		}
		if (substr($blogurl, -4) != '.php') {
			if(substr($blogurl, -1) != '/')
				$blogurl .= '/';
			$blogurl .= 'index.php';
		}
		if (($CONF['URLMode'] == 'pathinfo') && (substr($blogurl, -4) == '.php')) {
			$originalURLMode = $CONF['URLMode'];
			$CONF['URLMode'] = 'normal';
		}
		$originalItemURL = $CONF['ItemURL'];
		$CONF['ItemURL'] = $blogurl;
		$link            = createItemLink($itemid, $extra);
		$CONF['ItemURL'] = $originalItemURL;
		if ($CONF['URLMode'] <> $originalURLMode) {
			$CONF['URLMode'] = $originalURLMode;
		}
		return $link;
	}
}
