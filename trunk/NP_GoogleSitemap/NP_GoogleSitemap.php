<?php

class NP_GoogleSitemap extends NucleusPlugin
{

   /* ==========================================================================================
	* GoogleSitemap for Nucleus
	*
	* Copyright 2005 by Niels Leenheer
	* ==========================================================================================
	* This program is free software and open source software; you can redistribute
	* it and/or modify it under the terms of the GNU General Public License as
	* published by the Free Software Foundation; either version 2 of the License,
	* or (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful, but WITHOUT
	* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
	* FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
	* more details.
	*
	* You should have received a copy of the GNU General Public License along
	* with this program; if not, write to the Free Software Foundation, Inc.,
	* 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA  or visit
	* http://www.gnu.org/licenses/gpl.html
	* ==========================================================================================
	*/


	function getName()
	{
		return 'GoogleSitemap';
	}

	function getAuthor()
	{
		return 'Niels Leenheer + shizuki';
	}

	function getURL()
	{
		return 'http://japan.nucleuscms.org/wiki/plugins:googlesitemap';
	}

	function getVersion()
	{
		return '0.7';
	}

	function getDescription()
	{
		return _G_SITEMAP_DESC;
	}
	
	function getEventList()
	{
		return array(
					'PostAddItem',
					'PreSendContentType'
					);
	}
	
	function supportsFeature($feature)
	{
    	switch($feature) {
	        case 'SqlTablePrefix':
	        	return 1;
	        default:
	    		return 0;
		}
	}

	function event_PreSendContentType($data)
	{
		global $CONF, $manager, $blogid;

		if (!$blogid) {
			$blogid = $CONF['DefaultBlog'];
		} else {
			$blogid = (is_numeric($blogid)) ? intval($blogid) : intval(getBlogIDFromName($blogid));
		}

		$b =& $manager->getBlog($blogid);
		$BlogURL = $b->getURL();

		if (substr($BlogURL, -1) != '/' && substr($BlogURL, -4) != '.php') {
			$BlogURL .= '/';
		}

		if (getVar('virtualpath')) {
			$info = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', getVar('virtualpath'));
		} elseif (serverVar('PATH_INFO')) {
			$info = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', serverVar('PATH_INFO'));
		} else {
			return;
		}

		$path_arr = explode('/', $info);
		$PcMap = $this->getBlogOption($blogid, 'PcSitemap');
		$MobileMap = $this->getBlogOption($blogid, 'MobileSitemap');
		if (end($path_arr) == $PcMap || (!empty($MobileMap) && end($path_arr) == $MobileMap)) {
			$sitemap = array();
			if ($this->getOption('AllBlogMap') == 'yes' && $blogid == $CONF['DefaultBlog']) {
				$blog_query = 'SELECT * FROM %s ORDER BY bnumber';
				$blog_res = sql_query(sprintf($blog_query, sql_table('blog')));
			} else {
				$blog_query = 'SELECT * FROM %s WHERE bnumber = %d';
				$blog_res = sql_query(sprintf($blog_query, sql_table('blog'), $blogid));
				$current_blog = TRUE;
			}
			while ($blogs = mysql_fetch_array($blog_res)) {
				$blog_id = intval($blogs['bnumber']);
				if ($this->getBlogOption($blog_id, 'IncludeSitemap') == 'yes' || !empty($current_blog)) {
					$temp_b =& $manager->getBlog($blog_id);
					$TempURL = $temp_b->getURL();
					$SelfURL = $TempURL;

					$URLMode = $CONF['URLMode'];
					if (substr($TempURL, -4) == '.php') $CONF['URLMode'] = 'normal';

					$usePathInfo = ($CONF['URLMode'] == 'pathinfo');

					if (substr($SelfURL, -1) == '/') {
						$SelfURL = ($usePathInfo) ? substr($SelfURL, 0, -1) : $SelfURL . 'index.php';
					} elseif (substr($SelfURL, -4) != '.php') {
						$SelfURL = ($usePathInfo) ? $SelfURL : $SelfURL . '/index.php';
					}

					$CONF['ItemURL'] = $SelfURL;
					$CONF['CategoryURL'] = $SelfURL;

					if (substr($TempURL, -1) != '/' && substr($TempURL, -4) != '.php') {
						$TempURL .= '/';
					}

					$patternURL = '/^' . preg_replace('/\//', '\/', $BlogURL) . '/';

					if (preg_match($patternURL, $TempURL)) {
						$sitemap[] = array(
							'loc'   => $this->_prepareLink($SelfURL, $TempURL),
							'priority' => '1.0',
							'changefreq' => 'daily'
						);
						
						$cat_query = 'SELECT * FROM %s WHERE cblog = %d ORDER BY catid';
						$cat_res = sql_query(sprintf($cat_query, sql_table('category'), $blog_id));
						$mcategories = $this->pluginCheck('MultipleCategories');
						while ($cat = mysql_fetch_array($cat_res)) {
							$cat_id = intval($cat['catid']);
							$Link = createCategoryLink($cat_id);
							$sitemap[] = array(
								'loc' => $this->_prepareLink($SelfURL, $Link),
								'priority' => '1.0',
								'changefreq' => 'daily'
							);
							if ($mcategories) {
								$subrequest = $mcategories->getRequestName();
								$scat_query = 'SELECT * FROM %s WHERE catid = %d ORDER BY scatid';
								$scat_table = sql_table('plug_multiple_categories_sub');
								$scat_res = sql_query(sprintf($scat_query, $scat_table, $cat_id));
								while ($scat = mysql_fetch_array($scat_res)) {
									$scat_id = intval($scat['scatid']);
									$params = array($subrequest => $scat_id);
									$Link = createCategoryLink($cat_id, $params);
									$sitemap[] = array(
										'loc' => $this->_prepareLink($SelfURL, $Link),
										'priority' => '1.0',
										'changefreq' => 'daily'
									);
								}
							}
						}
						
						$item_query = 'SELECT *, UNIX_TIMESTAMP(itime) AS timestamp ' .
									'FROM %s WHERE iblog = %d AND idraft = 0 ORDER BY itime DESC';
						$item_res = sql_query(sprintf($item_query, sql_table('item'), $blog_id));
						
						while ($item = mysql_fetch_array($item_res)) {
							$item_id = intval($item['inumber']);
							$tz = date('O', $item['timestamp']);
							$tz = substr($tz, 0, 3) . ':' . substr($tz, 3, 2);	
							
							if (time() - $item['timestamp'] < 86400 * 2) {
								$fq = 'hourly';
							} elseif (time() - $item['timestamp'] < 86400 * 14) {
								$fq = 'daily'; 
							} elseif (time() - $item['timestamp'] < 86400 * 62) {
								$fq = 'weekly';
							} else {
								$fq = 'monthly';
							}
							$sitemap[] = array(
								'loc' => $this->_prepareLink($SelfURL, createItemLink($item_id)),
								'lastmod' => gmdate('Y-m-d\TH:i:s', $item['timestamp']) . $tz,
								'priority' => '1.0',
								'changefreq' => $fq
							);
						}
					}
				}
				if ($CONF['URLMode'] != $URLMode) $CONF['URLMode'] = $URLMode;
			}
			
			$manager->notify('SiteMap', array ('sitemap' => & $sitemap));
			
			header ("Content-type: application/xml");
			echo "<" . "?xml version='1.0' encoding='UTF-8'?" . ">\n\n";
			echo "\t<urlset" . ' xmlns="http://www.google.com/schemas/sitemap/0.84"' . "\n";
			echo "\t" . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
			echo "\t" . 'xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.84' . "\n";
			echo "\t" . '        http://www.google.com/schemas/sitemap/0.84/sitemap.xsd">' . "\n";
			
			while (list(,$url) = each($sitemap)) {
				echo "\t<url>\n";
				
				while (list($key,$value) = each($url)) {
					if ($key == 'loc') {
						$value = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $value);
						echo "\t\t<" . $key . ">" . htmlspecialchars($value, ENT_QUOTES) . "</" . $key . ">\n";
					} else {
						echo "\t\t<" . $key . ">" . htmlspecialchars($value, ENT_QUOTES) . "</" . $key . ">\n";
					}
				}
				
				echo "\t</url>\n";
			}
			
			echo "</urlset>\n";
			exit;
		}
	}

	function pluginCheck($pluginName)
	{
		global $manager;
		if (!$manager->pluginInstalled('NP_'.$pluginName)) return;
		$plugin =& $manager->getPlugin('NP_'.$pluginName);
		return $plugin;
	}

	function _prepareLink($base, $url) {
		if (substr($url, 0, 7) == 'http://')
			return $url;
		else
			return $base . $url;
	}

	function event_PostAddItem(&$data)
	{
		global $manager, $CONF;
		$blog_id = getBlogIDFromItemID($data['itemid']);
		if ($this->getBlogOption($blog_id, 'PingGoogle') == 'yes') {
			$b =& $manager->getBlog($blog_id);
			$b_url = $b->getURL();

			if (substr($b_url, -4) == '.php') $CONF['URLMode'] = 'normal';
			$usePathInfo = ($CONF['URLMode'] == 'pathinfo');

			if (substr($b_url, -1) == '/') {
				if (!$usePathInfo) $b_url .= 'index.php?virtualpath=';
			} elseif (substr($b_url, -4) == '.php') {
				$b_url .= '?virtualpath=';
			} else {
				$b_url = ($usePathInfo) ? $b_url . '/' : $b_url . '/index.php?virtualpath=';
			}

			$siteMap = $this->getBlogOption($blog_id, 'PcSitemap');
			$url = 'http://www.google.com/webmasters/sitemaps/ping?sitemap=' .
				   urlencode($b_url . $siteMap);
			$url = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $url);
			$fp = @fopen($url, 'r');
			@fclose($fp);
			$MobileMap = $this->getBlogOption($blog_id, 'MobileSitemap');
			if (!empty($MobileMap)) {
				$url = 'http://www.google.com/webmasters/sitemaps/ping?sitemap=' .
					   urlencode($b_url . $MobileMap);
				$url = preg_replace('|[^a-zA-Z0-9-~+_.?#=&;,/:@%]|i', '', $url);
				$fp = @fopen($url, 'r');
				@fclose($fp);
			}
		}
	}

	function init()
	{
		global $admin;
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory() . $language.'.php')) {
			include_once($this->getDirectory() . $language.'.php');
		}else {
			include_once($this->getDirectory() . 'english.php');
		}
	}

	function install()
	{
		$this->createOption('AllBlogMap',			_G_SITEMAP_ALLB,	'yesno',	'yes');
		$this->createBlogOption('PingGoogle',		_G_SITEMAP_PING,	'yesno',	'yes');
		$this->createBlogOption('IncludeSitemap',	_G_SITEMAP_INC,		'yesno',	'yes');
		$this->createBlogOption('PcSitemap',		_G_SITEMAP_PCSM,	'text',		'sitemap.xml');
		$this->createBlogOption('MobileSitemap',	_G_SITEMAP_MBSM,	'text',		'');
	}
}
?>