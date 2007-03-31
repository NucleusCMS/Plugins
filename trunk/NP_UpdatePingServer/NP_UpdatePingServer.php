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
		return '1.7';
	}

	function getDescription()
	{
		return 'Receive "weblogUpdates.ping"';
	}

	function install()
	{
		$this->createOption('flg_erase',  'Erase data on uninstall.',      'yesno', 'no');
		$this->createOption('desclen',    'Entry description\'s width.',   'text',  '250');
		$this->createOption('addstr',     'Entry description\'s add str.', 'text',  '...');
		$this->createOption('dateformat', 'Format Entry updatetime.',      'text',  '%Y-%m-%d %H:%M:%S');
		$this->createOption('header',     'Header of new entry list.',     'text',  '<ul class="latestupdate">');
		$this->createOption('footer',     'Footer of new entry list.',     'text',  '</ul>');
		$this->createOption('body',       'Body of new entry list.',       'textarea',
										  '<li>BlogName:'
										. '<a href="<%blogurl%>" title="<%blogtitle%>"><%blogtitle%></a>'
										. '<ul><li>Latest Entry:'
										. '<a href="<%entryurl%>" title="<%entrytitle%>"><%entrytitle%></a>'
										. '@<%datetime%>'
										. '<ul><li>Description:<small><%entrydesc%></small>'
										. '</li></ul></li></ul></li>');
		$this->createOption('addlog',     'Add log when received ping.',    'yesno', 'yes');
		$this->createOption('maxhold',    'Maximum quantity holding data.', 'text',  '1000');
//		$this->createOption('reserved',   'reserved option.', 'text',  '...');
//		$this->createOption('reserved',   'reserved option.', 'text',  '...');
		$table_q = 'CREATE TABLE IF NOT EXISTS ' . sql_table('plug_pingserver') . ' ('
				 . '    pingid     INT(11)       NOT NULL AUTO_INCREMENT, '
				 . '    pingtime   TIMESTAMP     NOT NULL, '
				 . '    updatetime INT(12)       NOT NULL, '
				 . '    btitle     VARCHAR(255)  NOT NULL, '
				 . '    burl       VARCHAR(255)  NOT NULL, '
				 . '    feedurl    VARCHAR(255)  NOT NULL, '
				 . '    etitle     VARCHAR(255)  NOT NULL, '
				 . '    eurl       VARCHAR(255)  NOT NULL, '
				 . '    edesc      TEXT          NOT NULL, '
				 . ' UNIQUE  KEY   eurl (eurl), '
				 . ' PRIMARY KEY        (pingid) '
				 . ' )';
		sql_query($table_q);
		$table_q = 'CREATE TABLE IF NOT EXISTS ' . sql_table('plug_pingserver_changes') . ' ('
				 . '    id          INT(11)    NOT NULL AUTO_INCREMENT, '
				 . '    updatetime  TIMESTAMP  NOT NULL, '
				 . '    changestext VARCHAR(7) NOT NULL, '
				 . ' UNIQUE  KEY changestext (changestext), '
				 . ' PRIMARY KEY             (id) '
				 . ' )';
		sql_query($table_q);
	}

	function uninstall()
	{
		if ($this->getOption('flg_erase') == 'yes') {
			sql_query('DROP TABLE IF EXISTS ' . sql_table('plug_pingserver'));
			sql_query('DROP TABLE IF EXISTS ' . sql_table('plug_pingserver_changes'));
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
		if ($furl && @fopen($furl)) {
			$furl = $this->getAbsoluteFeedURL($furl, $url);
		} else {
			$furl = '';
		}
		if (!$furl) {
			$furl = $this->getFeedURL($url);
		}
		if ($furl) {
			$feeds = $this->getFeedData($furl, $title);
		} else {
			$feeds = FALSE;
		}

		if ($feeds) {
			$this->updateTable($feeds);
			if ($this->getOption('addlog') == 'yes') {
				addToLog(INFO, 'Updateping Server Received ping. from:' . $feeds['siteTitle']);
			}
			$flerror = new xmlrpcval(0, 'boolean');
			$message = new xmlrpcval('Thanks for your update ping.');
			$values  = array(
							 'flerror' => $flerror,
							 'message' => $message
							);
		    $value   = new xmlrpcval($values, 'struct');
		    return new xmlrpcresp($value);
		} else {
			global $xmlrpcerruser;
			$eCode   = $xmlrpcerruser + 21;
			$message = "Can't read your XML feed. Please prepare RSS1.0, RSS2.0, or ATOM feeds.";
			return new xmlrpcresp(0, $eCode, $message);
		}
	}

	function getFeedURL($url)
	{
		$url = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $url);
		ini_set('user_agent', 'NP' . $this->getName());
		$fp  = @fopen($url, 'r');
		if ($fp) {
//				addToLog(INFO, 'Updateping Server Received ping. from:' . $url);
			$sources = fread($fp, 4096);
			if (strpos(substr($sources, 0, 250), '<rdf:RDF') !== FALSE) {
				$rssLink = $url;
			} elseif (strpos(substr($sources, 0, 250), '<rss version="') !== FALSE) {
				$rssLink = $url;
			} elseif (strpos(substr($sources, 0, 250), '<feed ') !== FALSE) {
				$atomLink = $url;
			} else {
				preg_match_all('/<link\s+(.*?)\s*\/?>/si', $sources, $match);
				for ($i = 0; count($match[1]) > $i; $i++) {
					if (strpos($match[1][$i], 'alternate')) {
						if (strpos($match[1][$i], 'application/atom+xml')) {
							$temp = explode('href=', $match[1][$i], 2);
							$herf = explode(' ', $temp[1], 2);
							$atomLink = trim($herf[0], '\'"');
						} elseif (strpos($match[1][$i], 'application/rss+xml')) {
							$temp = explode('href=', $match[1][$i], 2);
							$herf = explode(' ', $temp[1], 2);
							$rssLink  = trim($herf[0], '\'"');
						}
					}
				}
			}
		}
		if ($atomLink) {
			$atomLink = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $atomLink);
			return $this->getAbsoluteFeedURL($atomLink, $url);
		} elseif ($rssLink) {
			$rssLink = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $rssLink);
			return $this->getAbsoluteFeedURL($rssLink, $url);
		} else {
			return FALSE;
		}
	}

	function getAbsoluteFeedURL($feedURL, $url)
	{
		$feedURL = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $feedURL);
		if (strpos($feedURL, 'http://') !== false) {
			$AbsoluteFeedURL = $feedURL;
		} else {
			$parts = parse_url($url);
			$AbsoluteFeedURL = 'http://' . $parts['host'];
			if (isset($parts['port'])) {
				$AbsoluteFeedURL .= ':' . $parts['port'];
			}
			if ($feedURL{0} != '/') {
				if (isset($parts['path'])) {
					$patharr = explode('/', trim($parts['path'], '/'));
					foreach ($patharr as $val) {
						if (!strpos(strtolower($val), '.htm') 
							&& !strpos(strtolower($val), '.xml')
							&& !strpos(strtolower($val), '.aspx')) {
								$AbsoluteFeedURL .= '/' . $val;
						}
					}
				}
				$AbsoluteFeedURL .= '/';
			}
			$AbsoluteFeedURL .= $feedURL;
		}
		$AbsoluteFeedURL = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $AbsoluteFeedURL);
		$AbsoluteFeedURL = preg_replace("|([^:])//|", "$1", $AbsoluteFeedURL);
		return $AbsoluteFeedURL;
	}

	function getFeedData($url, $title)
	{
		$url = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $url);
		require_once($this->getDirectory() . 'simplepie.inc');
		$feed = new SimplePie();
		$feed->feed_url($url);
		$feed->enable_caching(false);
		$feed->output_encoding(_CHARSET);
		$feed->init();
		$feed->handle_content_type();
		if ($feed->data) {
			$item = $feed->get_item(0);
			$data = array(
						  'siteTitle'  => stringStripTags($feed->get_feed_title()),
						  'siteURL'    => preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $feed->get_feed_link()),
						  'siteURL'    => preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $feed->get_feed_link()),
						  'entryTitle' => stringStripTags($item->get_title()),
						  'entryDesc'  => stringStripTags($item->get_description()),
						  'entryDate'  => $item->get_date('U'),
						  'entryURL'   => preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $item->get_permalink()),
						  'feedURL'    => preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $feed->subscribe_url()),
						 );
		}
		return $data;
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

				if (!version_compare(phpversion(), "5.0.1", "=<")) {
					$GLOBALS['xmlrpc_valid_parents'] = array(
							'BOOLEAN' => array('VALUE'),
							'I4' => array('VALUE'),
							'INT' => array('VALUE'),
							'STRING' => array('VALUE'),
							'DOUBLE' => array('VALUE'),
							'DATETIME.ISO8601' => array('VALUE'),
							'BASE64' => array('VALUE'),
							'ARRAY' => array('VALUE'),
							'STRUCT' => array('VALUE'),
							'PARAM' => array('PARAMS'),
							'METHODNAME' => array('METHODCALL'),		
							'PARAMS' => array('METHODCALL', 'METHODRESPONSE'),
							'MEMBER' => array('STRUCT'),
							'NAME' => array('MEMBER'),
							'DATA' => array('ARRAY'),
							'FAULT' => array('METHODRESPONSE'),
							'VALUE' => array('MEMBER', 'DATA', 'PARAM', 'FAULT'),
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

	function changesOutput()
	{
		$last  = intGetVar('last');
		$upd = date('r');
		if ($last > 300) {
			$last = 300;
		}
		$query = 'SELECT '
			   . '    updatetime, '
			   . '    btitle, '
			   . '    burl, '
			   . '    feedurl '
			   . ' FROM '
			   .      sql_table('plug_pingserver');
		if ($last > 0) {
			$query .= ' WHERE '
					. '      updatetime > ' . (strtotime($upd) - $last);
		}
		$query .= ' ORDER BY updatetime DESC';
		$res = sql_query($query);
		if (mysql_num_rows($res)) {
			$out = '';
			while ($list = mysql_fetch_assoc($res)) {
				$when = (strtotime($upd) - $list['updatetime']);
				$list = array_map(array(&$this, '_hsc'), $list);
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
				 .        sql_table('plug_pingserver_changes');
			$ver = quickQuery($que);
			$ver += 1;
			$out = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . "\n"
				 . '<weblogUpdates version="2" updated="' . $upd . '" count="' . $ver . '">' . "\n"
				 . $out
				 . '</weblogUpdates>';
			header("Content-Type: text/xml");
			echo $out;
			$query = 'REPLACE ' . sql_table('plug_pingserver_changes')
				   . ' SET '
				   . '     changestext = "changes"';
			sql_query($query);
		} else {
			echo 'No Data.';
		}

	}

	function updateTable($data)
	{
		$query = 'SELECT '
			   . '      updatetime as result'
			   . ' FROM '
			   .        sql_table('plug_pingserver')
			   . ' WHERE '
			   . '      eurl = ' . $this->quoteSmart($data['entryURL']);
		$olden = quickQuery($query);
		if (!$olden || $olden < intval($data['entryDate'])) {
			$query = 'REPLACE '
				   .        sql_table('plug_pingserver')
				   . ' ('
				   . '      updatetime, '
				   . '      btitle, '
				   . '      burl, '
				   . '      feedurl, '
				   . '      etitle, '
				   . '      eurl, '
				   . '      edesc '
				   . ' ) '
				   . 'VALUES '
				   . ' ('
				   .        $this->quoteSmart($data['entryDate'])  . ', '
				   .        $this->quoteSmart($data['siteTitle'])  . ', '
				   .        $this->quoteSmart($data['siteURL'])    . ', '
				   .        $this->quoteSmart($data['feedURL'])    . ', '
				   .        $this->quoteSmart($data['entryTitle']) . ', '
				   .        $this->quoteSmart($data['entryURL'])   . ', '
				   .        $this->quoteSmart($data['entryDesc'])
				   . ' )';
			sql_query($query);
			$query = 'SELECT '
				   . '      COUNT(pingid) as result '
				   . 'FROM '
				   .        sql_table('plug_pingserver');
			if (quickQuery($query) > $this->getOption('maxhold')) {
				$query = 'DELETE FROM '
					   .        sql_table('plug_pingserver')
					   . ' ORDER BY '
					   . '      pingid ASC '
					   . ' LIMIT 1'; 
				sql_query($query);
			}
		}
	}

	function doSkinVar($skinType, $amount = 5)
	{
		if (empty($amount)) $amount = 5;
		echo $this->getOption('header');
		$query = 'SELECT '
			   . '               updatetime  as tstamp, '
			   . '               btitle      as blogtitle, '
			   . '               burl        as blogurl, '
			   . '               etitle      as entrytitle, '
			   . '               eurl        as entryurl, '
			   . '               edesc       as entrydesc '
			   . 'FROM '
			   .        sql_table('plug_pingserver')
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
			echo TEMPLATE::fill($this->getOption('body'), $values);
		}
		echo $this->getOption('footer');
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

	function quoteSmart($value)
	{
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
		return $value;
	}

	function _hsc($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, _CHARSET);
	}

}
