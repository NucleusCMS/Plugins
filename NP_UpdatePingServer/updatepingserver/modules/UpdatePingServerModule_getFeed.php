<?php
class UpdatePingServerModule_getFeed// extends NP_UpdatePingServer
{

var $myPlugin;

	function initModule($plugin)
	{
		$this->myPlugin = $plugin;
//		print_r($this->myPlugin->getShortName());
	}

	function _getModuleTablePrefix()
	{
		return 'plug_' . strtolower($this->myPlugin->getShortName()) . '_';
	}

	function _getModuleTableName($name)
	{
		return sql_table($this->_getModuleTablePrefix() . $name);
	}

	function getModuleTableList()
	{
		$tables = array (
						 'feeddata',
						);

		$moduleTables = array();
		foreach ($tables as $table) {
			$moduleTables[] = $this->_getModuleTableName($table);
		}
		return $moduleTables;
	}

	function installModule()
	{
		$table_q = 'CREATE TABLE IF NOT EXISTS ' . $this->_getModuleTableName('feeddata') . ' ('
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
	}

	function getModuleName()
	{
		return 'Get Site data';
	}

	function getModuleDescription()
	{
		return 'Get Site data via Atom or RSS feeds.';
	}

	function np_updatePingServerEvent_changesOutput(&$data)
	{
		$query = 'SELECT '
			   . '               updatetime, '
			   . '               btitle, '
			   . '               burl, '
			   . '               feedurl '
			   . ' FROM '
			   .      $this->_getModuleTableName('feeddata');
		if ($last > 0) {
			$query .= ' WHERE '
					. '          updatetime > ' . (strtotime($data['nowTime']) - $data['last']);
		}
		$query .= ' ORDER BY updatetime DESC';

		$data['query'] = $query;
	}

	function np_updatePingServerEvent_receivePing(&$data)
	{
		global $manager;

		extract($data);
		extract($siteData);
		if (!$response) {
			return;
		}
		$plugName =  str_replace('NP_', '', (get_class($this->myPlugin)));
		if ($feedURL) {
			ini_set('user_agent', 'NP_' . $plugName());
			$fp  = @fopen($feedURL, 'r');
			if ($fp) {
				$feedURL = $this->getAbsoluteFeedURL($feedURL, $siteURL);
			}
		} else {
			$feedURL = '';
		}
		if (!$feedURL) {
			$feedURL = $this->getFeedURL($siteURL, $plugName);
		}
		if ($feedURL) {
			$feedData = $this->getFeedData($feedURL, $siteTitle);
		} else {
			$feedData = FALSE;
		}
		if ($feedData) {
			$this->updateTable($feedData);
			$data['siteTitle'] = $feedData['siteTitle'];
			$data['siteData']  = $feedData;
			$data['message']   = 'Thanks for your update ping.';
			$data['response']  = TRUE;
		} else {
			$data['message']   = 'Can not read your XML feed. Please prepare RSS1.0, RSS2.0, or ATOM feeds.';
			$data['response']  = FALSE;
		}
	}

	function updateTable($data)
	{
		$sharedFunctions = new sharedFunctions();

		$query = 'SELECT '
			   . '      updatetime as result'
			   . ' FROM '
			   .        $this->_getModuleTableName('feeddata')
			   . ' WHERE '
			   . '      eurl = ' . $sharedFunctions->quoteSmart($data['entryURL']);
		$olden = quickQuery($query);
		if (!$olden || $olden < intval($data['entryDate'])) {
			$query = 'REPLACE '
				   .        $this->_getModuleTableName('feeddata')
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
				   .        $sharedFunctions->quoteSmart($data['entryDate'])  . ', '
				   .        $sharedFunctions->quoteSmart($data['siteTitle'])  . ', '
				   .        $sharedFunctions->quoteSmart($data['siteURL'])    . ', '
				   .        $sharedFunctions->quoteSmart($data['feedURL'])    . ', '
				   .        $sharedFunctions->quoteSmart($data['entryTitle']) . ', '
				   .        $sharedFunctions->quoteSmart($data['entryURL'])   . ', '
				   .        $sharedFunctions->quoteSmart($data['entryDesc'])
				   . ' )';
			sql_query($query);
			$query = 'SELECT '
				   . '      COUNT(pingid) as result '
				   . 'FROM '
				   .        $this->_getModuleTableName('feeddata');
			if (quickQuery($query) > $this->myPlugin->getOption('dataMaxHold')) {
				$query = 'DELETE FROM '
					   .        $this->_getModuleTableName('feeddata')
					   . ' ORDER BY '
					   . '      pingid ASC '
					   . ' LIMIT 1'; 
				sql_query($query);
			}
		}
	}

	function getFeedURL($url, $plugName)
	{
		$url = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $url);
		ini_set('user_agent', $plugName);
		$fp  = @fopen($url, 'r');
		if ($fp) {
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
//		$AbsoluteFeedURL = preg_replace("|([^:])//|", "$1", $AbsoluteFeedURL);
		return $AbsoluteFeedURL;
	}

	function getFeedData($url, $title)
	{
		$url = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $url);
		require_once('sharedlibs/simplepie.inc');
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
}