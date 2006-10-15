<?php

define('NP_RETAIN_OPTIONS_LIFETIME', 86400);

class NP_RetainOptions extends NucleusPlugin {

 function getNAME() { return 'Retain Options';  }
 function getAuthor()  { return 'Andy';  }
 function getURL() {  return ''; }
 function getVersion() { return '0.6'; }
 function getDescription() { 
  return 'Retain plugin options while you update(uninstall and reinstall) plugins. Keep up to one day';
 }
 
	function install() {
		$this->createOption("disable", "Disable this plugin", "yesno", "no");
		$query =  'CREATE TABLE IF NOT EXISTS '. sql_table('plug_retainoptions_plugin'). ' ('
				. 'id int(11) not null auto_increment, '
				. 'pluginname VARCHAR(40) NOT NULL, '
				. 'storetime DATETIME, '
				. 'PRIMARY KEY (id))';
		sql_query($query);
		$query =  'CREATE TABLE IF NOT EXISTS '. sql_table('plug_retainoptions_options'). ' ('
				. 'id int(11) not null, '
				. 'optionid int(11) not null auto_increment, '
				. 'optionname VARCHAR(20) NOT NULL, '
				. 'optioncontext VARCHAR(20), '
				. 'PRIMARY KEY (optionid))';
		sql_query($query);
		$query =  'CREATE TABLE IF NOT EXISTS '. sql_table('plug_retainoptions'). ' ('
				. 'optionid int(11) NOT NULL,'
				. 'contextid int(11),'
				. 'optionvalue VARCHAR(255)'
				. ') ';
		sql_query($query);
	}
 
	function unInstall() { 
		sql_query('DROP TABLE ' .sql_table('plug_retainoptions_plugin'));
		sql_query('DROP TABLE ' .sql_table('plug_retainoptions_options'));
		sql_query('DROP TABLE ' .sql_table('plug_retainoptions'));
	}

	function supportsFeature($what) {
		switch($what)
		{
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	function getTableList() {
		return array(sql_table('plug_retainoptions_plugin'), sql_table('plug_retainoptions'));
	}
	function getEventList() {
		return array('PreDeletePlugin','PostAddPlugin');
	}

	function event_PreDeletePlugin(&$data) {
		if ($this->getOption('disable') == 'yes') return;
		$plugid = $data['plugid'];
		$result = sql_query('SELECT pfile FROM '.sql_table('plugin'). ' WHERE pid='.intval($plugid));
		$plugin = mysql_fetch_array($result);
		$pname = strtolower($plugin['pfile']);
		mysql_free_result($result);
		if ($pname == get_class($this)) return; // don't retain this plugin
		$currenttime = mysqldate(time());
		sql_query('INSERT INTO '.sql_table('plug_retainoptions_plugin')
				. " (pluginname, storetime) VALUES ('" . mysql_real_escape_string($pname) . "', " . intval($currenttime) . ")");
		$id = mysql_insert_id();
		$descs = sql_query('SELECT oid, oname, ocontext FROM '.sql_table('plugin_option_desc')
						. ' WHERE opid='.intval($plugid));
		while ($desc = mysql_fetch_array($descs)) {
			sql_query('INSERT INTO '.sql_table('plug_retainoptions_options'). ' SET '
					. "id=" . intval($id)
					. ", optionname='".mysql_real_escape_string($desc['oname'])."'"
					. ", optioncontext='".mysql_real_escape_string($desc['ocontext'])."'");
			$optionid = mysql_insert_id();
			$options = sql_query('SELECT ovalue, ocontextid FROM '.sql_table('plugin_option')
							  . ' WHERE oid='.$desc['oid']);
			while ($option = mysql_fetch_array($options)) {
				sql_query('INSERT INTO '.sql_table('plug_retainoptions'). ' SET '
						. "optionid=". intval($optionid)
						. ', contextid='.intval($option['ocontextid'])
						. ", optionvalue='".mysql_real_escape_string($option['ovalue'])."'");
			}
			mysql_free_result($options);
		}
		mysql_free_result($descs);
	}

	function event_PostAddPlugin(&$data) {
		if ($this->getOption('disable') == 'yes') return;
		$plugin = & $data['plugin'];
		$pname = get_class($plugin);
		$oldesttimestamp = mysqldate(time() - NP_RETAIN_OPTIONS_LIFETIME);
		$result = sql_query('SELECT id FROM '.sql_table('plug_retainoptions_plugin')
							." WHERE pluginname='". mysql_real_escape_string($pname) . "' AND STORETIME>=". intval($oldesttimestamp));
		$nums = mysql_num_rows($result);
		if (!$nums) { $this->cleanup(); return; }
		while ($nums--) $row = mysql_fetch_array($result);
		mysql_free_result($result);
		$id = $row['id'];
		$options = sql_query('SELECT optionid, optionname, optioncontext FROM '
							. sql_table('plug_retainoptions_options')
							. " WHERE id=".intval($id));
		while ($option = mysql_fetch_array($options)) {
			$optionname = $option['optionname'];
			$contextname = $option['optioncontext'];
			$odescs = sql_query('SELECT oid FROM '.sql_table('plugin_option_desc')
						. ' WHERE opid='.intval($plugin->plugid)
						. " AND oname='".mysql_real_escape_string($optionname)."'"
						. " AND ocontext='".mysql_real_escape_string($contextname)."'");
			// restore values only when option name and option context are same
			if ($odesc = mysql_fetch_array($odescs)) {
				$values = sql_query('SELECT contextid, optionvalue FROM '
								. sql_table('plug_retainoptions')
								. ' WHERE optionid='.intval($option['optionid']));
				while ($value = mysql_fetch_array($values)) {
					// call plugin function instead of directly store in DB
					// because some items/blogs/categories might not exist
					$plugin->_setOption($contextname,$value['contextid'],
										$optionname, $value['optionvalue']);
				}
				mysql_free_result($values);
			}
			mysql_free_result($odescs);
		}
		mysql_free_result($options);
		$this->cleanup();
	}

	function cleanup() {
		$oldesttimestamp = time() - NP_RETAIN_OPTIONS_LIFETIME;
		$result = sql_query('SELECT id FROM '.sql_table('plug_retainoptions_plugin')
							." WHERE STORETIME < " . intval($oldesttimestamp));
		while ($row = mysql_fetch_array($result)) {
			$options = sql_query('SELECT optionid FROM '
								. sql_table('plug_retainoptions_options')
								. ' WHERE id='.intval($row['id']));
			while ($option = mysql_fetch_array($options)) {
				sql_query('DELETE FROM '.sql_table('plug_retainoptions')
						. ' WHERE optionid='.intval($option['optionid']));
			}
			mysql_free_result($options);
			sql_query('DELETE FROM '. sql_table('plug_retainoptions_options')
					. ' WHERE id='.intval($row['id']));
		}
		mysql_free_result($result);
		sql_query('DELETE FROM '.sql_table('plug_retainoptions_plugin')
				." WHERE STORETIME < " . intval($oldesttimestamp));
	}

}
?>