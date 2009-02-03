<?php

global $DIR_PLUGINS, $DIR_LIBS;
if (version_compare(phpversion(), "5.0.1", ">=")) {
	require_once($DIR_PLUGINS . "updatepingserver/xmlrpc.inc");
	require_once($DIR_PLUGINS . "updatepingserver/xmlrpcs.inc");
} else {
	include_once($DIR_LIBS . "xmlrpc.inc.php");
	include_once($DIR_LIBS . "xmlrpcs.inc.php");
}

class NP_UpdatePingServer extends NucleusPlugin
{

var $moduleAdmin;

	function getName()
	{
		return 'XML-RPC Updateping Server';
	}

	function getAuthor()
	{
		return 'shizuki';
	}

	function getURL()
	{
		return 'http://shizuki.kinezumi.net';
	}

	function getVersion()
	{
		return '1.10';
	}

	function getDescription()
	{
		return _NP_PINGSERVER_DESCRIPTION;
	}

	function getEventList()
	{
		 return array(
		 			  'QuickMenu'
		 			 );
	}

	function install()
	{
		$this->createOption('desclen',     _NP_PINGSERVER_GLOBALOPTION_DESCLEN,     'text',
										   '250');
		$this->createOption('addstr',      _NP_PINGSERVER_GLOBALOPTION_ADDSTR,      'text',
										   '...');
		$this->createOption('dateformat',  _NP_PINGSERVER_GLOBALOPTION_DATEFORMAT,  'text',
										   '%Y-%m-%d %H:%M:%S');
		$this->createOption('listHeader',  _NP_PINGSERVER_GLOBALOPTION_LISTHEADER,  'text',
										   _NP_PINGSERVER_GLOBALOPTION_LISTHEADER_VALUE);
		$this->createOption('listFooter',  _NP_PINGSERVER_GLOBALOPTION_LISTFOOTER,  'text',
										   _NP_PINGSERVER_GLOBALOPTION_LISTFOOTER_VALUE);
		$this->createOption('listBody',    _NP_PINGSERVER_GLOBALOPTION_LISTBODY,    'textarea',
										   _NP_PINGSERVER_GLOBALOPTION_LISTBODY_VALUE);
		$this->createOption('logFlag',     _NP_PINGSERVER_GLOBALOPTION_LOGFLAG,     'yesno',
										   'yes');
		$this->createOption('dataMaxHold', _NP_PINGSERVER_GLOBALOPTION_DATAMAXHOLD, 'text',
										   '1000');
		$this->createOption('quickmenu',   _NP_PINGSERVER_GLOBALOPTION_QUICKMENU,   'yesno',
										   'yes');
		$this->createOption('dbFlag',      _NP_PINGSERVER_GLOBALOPTION_DBFLAG,      'yesno',
										   'no');
//		$this->createOption('reserved',    _NP_PINGSERVER_GLOBALOPTION_RESERVED,    'text',
//										   _NP_PINGSERVER_GLOBALOPTION_RESERVED_VALUE);
//		$this->createOption('reserved',    _NP_PINGSERVER_GLOBALOPTION_RESERVED,    'text',
//										   _NP_PINGSERVER_GLOBALOPTION_RESERVED_VALUE);
		$table_q = 'CREATE TABLE IF NOT EXISTS ' . sql_table('plug_updatepingserver_sitedata') . ' ('
				 . '    pingid     INT(11)       NOT NULL AUTO_INCREMENT, '
				 . '    updatetime INT(12)       NOT NULL, '
				 . '    btitle     VARCHAR(255)  NOT NULL, '
				 . '    burl       VARCHAR(255)  NOT NULL, '
				 . '    feedurl    VARCHAR(255)  NOT NULL, '
				 . ' PRIMARY KEY        (pingid) '
				 . ' )';
		sql_query($table_q);
		$table_q = 'CREATE TABLE IF NOT EXISTS ' . sql_table('plug_updatepingserver_changes') . ' ('
				 . '    id          INT(11)    NOT NULL AUTO_INCREMENT, '
				 . '    updatetime  TIMESTAMP  NOT NULL, '
				 . '    changestext VARCHAR(7) NOT NULL, '
				 . ' UNIQUE  KEY changestext (changestext), '
				 . ' PRIMARY KEY             (id) '
				 . ' )';
		sql_query($table_q);
		$table_q = 'CREATE TABLE IF NOT EXISTS ' . sql_table('plug_updatepingserver_modules') . ' ('
				 . '    moduleid    INT(11)       NOT NULL AUTO_INCREMENT, '
				 . '    modulename  VARCHAR(255)  NOT NULL, '
				 . '    moduleorder INT(11)       NOT NULL, '
				 . ' UNIQUE  KEY modulename (modulename), '
				 . ' PRIMARY KEY            (moduleid) '
				 . ' )';
		sql_query($table_q);
		$this->moduleAdmin =& new moduleAdmin('UpdatePingServer');
		$moduleNames = array();
		$handle = @fopen($this->getDirectory() . "preinstallmodules.txt", "r");
		if ($handle) {
			while (!feof($handle)) {
				$moduleNames[] = fgets($handle);
			}
			fclose($handle);
		}
		if (!empty($moduleNames)) {
			foreach ($moduleNames as $moduleName) {
				if (!empty($moduleName)) {
					$this->moduleAdmin->_moduleInstall($moduleName);
				}
			}
		}
	}

	function uninstall()
	{
		if ($this->getOption('dbFlag') == 'yes') {
			$tables = $this->getTableList();
			foreach ($tables as $table) {
				sql_query('DROP TABLE IF EXISTS ' . $table);
			}
		}
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

	function event_QuickMenu(&$data)
	{
		// only show when option enabled
		if ($this->getOption('quickmenu') != 'yes') return;
		
		global $member;
		// only show to admins
		if (!($member->isLoggedIn() && $member->isAdmin())) return;
		
		array_push(
			$data['options'],
			array(
				'title'   => _NP_PINGSERVER_QUICKMENU_TITLE,
				'url'     => $this->getAdminURL(),
				'tooltip' => _NP_PINGSERVER_QUICKMENU_TOOLTIP
			)
		);
	}

	function getTableList()
	{ 
		$tables = array(
						sql_table('plug_updatepingserver_modules'),
						sql_table('plug_updatepingserver_changes'),
						sql_table('plug_updatepingserver_sitedata')
					   );
		if (!is_array($this->moduleAdmin->moduleList)) {
			$this->moduleAdmin->getModuleList();
		}
		$listEneries = $this->moduleAdmin->moduleList;
		if (is_array($listEneries)) {
			foreach($listEneries as $listEnery) {
				$this->moduleAdmin->_loadModule($listEnery);
				if (method_exists($this->moduleAdmin->modules[$listEnery], 'getModuleTableList')) {
					$moduleTables = $this->moduleAdmin->modules[$listEnery]->getModuleTableList();
				}
				$tables = array_merge($tables, $moduleTables);
			}
		}
		return $tables;
	}

	function hasAdminArea()
	{
		return 1;
	}

	function init()
	{
		global $CONF;
		$langfile = $this->getDirectory() . 'language/' . $CONF['Language'] . '.php';
		if (!file_exists($langfile)) {
			$langfile = $this->getDirectory() . 'language/english.php';
		}
		include_once($langfile);
		require_once($this->getDirectory() . 'modules/moduleAdmin.php');
		include_once($this->getDirectory() . 'sharedFunctions.php');
		if (@mysql_query('SELECT * FROM ' . sql_table('plug_updatepingserver_modules'))) {
			$this->moduleAdmin =& new moduleAdmin('UpdatePingServer');
		}
	}

	function updatepingEventNotify($eventName, $data)
	{
		if (!is_array($this->moduleAdmin->moduleList)) {
			$this->moduleAdmin->getModuleList();
		}
		$listEneries = $this->moduleAdmin->moduleList;
		if (is_array($listEneries)) {
			foreach($listEneries as $listEnery) {
				$this->moduleAdmin->_loadModule($listEnery);
				$eventMethod = 'np_updatePingServerEvent_' . $eventName;
				if (method_exists($this->moduleAdmin->modules[$listEnery], $eventMethod)) {
					$data['myPlugin'] = $this;
					call_user_func(array(&$this->moduleAdmin->modules[$listEnery], $eventMethod), $data);
				}
			}
		}
	}

	function weblogUpdates($msg)
	{

		for ($i = 0; $i < $msg->getNumParams(); $i++) {
			if ($i == 0) {
				$title = $msg->getParam($i);
				$title = $title->scalarval();
			} else if ($i == 1) {
				$url = $msg->getParam($i);
				$url = $url->scalarval();
			} else if ($i == 3) {
				$furl = $msg->getParam($i);
				$furl = $furl->scalarval();
				if (!preg_match("/^(http)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/", $furl)) {
					$furl = '';
				}
			}
		}

		$response   = TRUE;
		$message    = '';
		$siteData   = array(
							'siteTitle'  => $title,
							'siteURL'    => $url,
							'feedURL'    => $furl,
						   );
		$moduleData = array(
							'siteTitle' => &$title,
							'response'  => &$response,
							'message'   => &$message,
							'siteData'  => &$siteData,
						   );
		$this->updatepingEventNotify('receivePing', $moduleData);

		$GLOBALS['xmlrpc_defencoding'] = 'UTF-8';
		if ($response) {
			$this->updateTable($siteData);
			if ($this->getOption('logFlag') == 'yes') {
				addToLog(INFO, 'Updateping Server Received ping. from:' . $title);
			}
			$flerror = new xmlrpcval(0, 'boolean');
			if (!$message) {
				$message = new xmlrpcval('Thanks for your update ping.');
			} else {
				$message = new xmlrpcval($message);
			}
			$values = array(
							 'flerror' => $flerror,
							 'message' => $message
							);
		    $value  = new xmlrpcval($values, 'struct');
		    return new xmlrpcresp($value);
		} else {
			$eCode = $GLOBALS['xmlrpcerruser'] + 21;
			if (!$message) {
				$message = 'Your updateping can not received.';
			}
			return new xmlrpcresp(0, $eCode, $message);
		}
	}

	function changesOutput()
	{
		$last  = intGetVar('last');
		$upd   = date('r');
		if ($last > 300) {
			$last = 300;
		}
		$query = 'SELECT '
			   . '               updatetime, '
			   . '               btitle, '
			   . '               burl, '
			   . '               feedurl '
			   . ' FROM '
			   .      sql_table('plug_updatepingserver_sitedata');
		if ($last > 0) {
			$query .= ' WHERE '
					. '               updatetime > ' . (strtotime($upd) - $last);
		}
		$query .= ' ORDER BY updatetime DESC';

		$eventData = array (
							'nowTime' => $upd,
							'last'    => $last,
							'query'   => &$query
						   );
		$this->updatepingEventNotify('changesOutput', $eventData);

		$res = sql_query($query);
		if (mysql_num_rows($res)) {
			$out = '';
			while ($list = mysql_fetch_assoc($res)) {
				$when = (strtotime($upd) - $list['updatetime']);
				$list = array_map($sharedFunctions->_hsc, $list);
				$out .= '    '
					  . '<weblog'
					  . ' name="'   . $list['btitle']  . '"'
					  . ' url="'    . $list['burl']    . '"'
					  . ' rssUrl="' . $list['feedurl'] . '"'
					  . ' when="'   . $when            . '"'
					  . ' />' . "\n";
			}
			$que = 'SELECT '
				 . '      id as result '
				 . 'FROM '
				 .        sql_table('plug_updatepingserver_changes');
			$ver = quickQuery($que);
			$ver += 1;
			$out = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . "\n"
				 . '<weblogUpdates version="2" updated="' . $upd . '" count="' . $ver . '">' . "\n"
				 . $out
				 . '</weblogUpdates>';
			header("Content-Type: text/xml");
			echo $out;
			$query = 'REPLACE ' . sql_table('plug_updatepingserver_changes')
				   . ' SET '
				   . '     changestext = "changes"';
			sql_query($query);
		} else {
			echo 'No Data.';
		}

	}

	function updateTable($data)
	{
		$sharedFunctions = new sharedFunctions();

		$query = 'REPLACE '
			   .        sql_table('plug_updatepingserver_sitedata')
			   . ' ('
			   . '      updatetime, '
			   . '      btitle, '
			   . '      burl, '
			   . '      feedurl '
			   . ' ) '
			   . 'VALUES '
			   . ' ('
			   .        date('U')                                        . ', '
			   .        $sharedFunctions->quoteSmart($data['siteTitle']) . ', '
			   .        $sharedFunctions->quoteSmart($data['siteURL'])   . ', '
			   .        $sharedFunctions->quoteSmart($data['feedURL'])
			   . ' )';
		sql_query($query);
		$query = 'SELECT '
			   . '      COUNT(pingid) as result '
			   . 'FROM '
			   .        sql_table('plug_updatepingserver_sitedata');
		if (quickQuery($query) > $this->getOption('dataMaxHold')) {
			$query = 'DELETE FROM '
				   .        sql_table('plug_updatepingserver_sitedata')
				   . ' ORDER BY '
				   . '      pingid ASC '
				   . ' LIMIT 1'; 
			sql_query($query);
		}
	}

	function doSkinVar($skinType, $amount = 5)
	{
		if (empty($amount)) $amount = 5;
		echo $this->getOption('listHeader');
		$query = 'SELECT '
			   . '               updatetime  as tstamp, '
			   . '               btitle      as blogtitle, '
			   . '               burl        as blogurl, '
			   . '               etitle      as entrytitle, '
			   . '               eurl        as entryurl, '
			   . '               edesc       as entrydesc '
			   . 'FROM '
			   .        sql_table('plug_updatepingserver_feeddata')
			   . ' ORDER BY '
			   . '      updatetime DESC'
			   . ' LIMIT '
			   .        intval($amount);
		$res   = sql_query($query);
		$len   = $this->getOption('desclen');
		$add   = $this->getOption('addstr');
		while ($values = mysql_fetch_assoc($res)) {
			$values['formatdate'] = strftime($this->getOption('dateformat'), $values['tstamp']);
			$values               = array_map(array(&$this, '_hsc'), $values);
			$values['entrydesc']  = shorten($values['entrydesc'], $len, $add);
			echo TEMPLATE::fill($this->getOption('listBody'), $values);
		}
		echo $this->getOption('listFooter');
	}

	function doTemplateVar(&$item, $amount = 5)
	{
		if (empty($amount)) $amount = 5;
		$this->doSkinVar('template', $amount);
	}

	function doItemVar($item, $amount = 5)
	{
		if (empty($amount)) $amount = 5;
		$this->doSkinVar('item', $amount);
	}

	function doAction($type)
	{
		switch ($type) {
			case 'updateping':
/*
global $HTTP_RAW_POST_DATA;
$HTTP_RAW_POST_DATA = '<' . '?xml version="1.0"?' . '>
<methodCall>
  <methodName>weblogUpdates.ping</methodName>
  <params>
    <param>
      <value>YOUR WEBLOG NAME HERE</value>
    </param>
    <param>
      <value>http://shizuki.kinezumi.net</value>
    </param>
  </params>
</methodCall>';
//*/
				$parser = xml_parser_create();
				xml_parse_into_struct($parser, $GLOBALS['HTTP_RAW_POST_DATA'], $vals, $index);
				xml_parser_free($parser);
				$method = $vals[$index['METHODNAME'][0]]['value'];
				$GLOBALS['xmlrpc_internalencoding'] = _CHARSET;
				$GLOBALS['xmlrpc_defencoding']      = 'UTF-8';

				$fnc = array(
							 &$this,
							 'weblogUpdates'
							);
				$png = array(
							 'function' => $fnc
							);
				$map = array(
							 $method => $png
							);

				if (!version_compare(phpversion(), "5.0.0", "=<")) {// && !is_array($GLOBALS['xmlrpc_valid_parents'])) {
					$GLOBALS['xmlrpc_valid_parents'] = array(
							'BOOLEAN'          => array('VALUE'),
							'I4'               => array('VALUE'),
							'INT'              => array('VALUE'),
							'STRING'           => array('VALUE'),
							'DOUBLE'           => array('VALUE'),
							'DATETIME.ISO8601' => array('VALUE'),
							'BASE64'           => array('VALUE'),
							'ARRAY'            => array('VALUE'),
							'STRUCT'           => array('VALUE'),
							'PARAM'            => array('PARAMS'),
							'METHODNAME'       => array('METHODCALL'),		
							'PARAMS'           => array('METHODCALL', 'METHODRESPONSE'),
							'MEMBER'           => array('STRUCT'),
							'NAME'             => array('MEMBER'),
							'DATA'             => array('ARRAY'),
							'FAULT'            => array('METHODRESPONSE'),
							'VALUE'            => array('MEMBER', 'DATA', 'PARAM', 'FAULT'),
					);
				}

				$svr =& new xmlrpc_server($map);
				exit(0);
				break;
			case 'changes':
				$this->changesOutput();
				exit(0);
				break;
		}
	}

}
