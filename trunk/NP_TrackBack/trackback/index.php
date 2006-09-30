<?php

	$strRel = '../../../'; 
	include($strRel . 'config.php');
	include($DIR_LIBS . 'PLUGINADMIN.php');
	include('template.php');
	
	
	// Send out Content-type
	sendContentType('application/xhtml+xml', 'admin-trackback', _CHARSET);	

	// Compatiblity with Nucleus < = 2.0
	if (!function_exists('sql_table')) { function sql_table($name) { return 'nucleus_' . $name; } }
	


	$oPluginAdmin = new PluginAdmin('TrackBack');

	if (!($member->isLoggedIn() && $member->isAdmin()))
	{
		$oPluginAdmin->start();
		echo '<p>' . _ERROR_DISALLOWED . '</p>';
		$oPluginAdmin->end();
		exit;
	}
	
	$oPluginAdmin->start();
	
//modify start+++++++++
		$plug =& $oPluginAdmin->plugin;
		$tableVersion = $plug->checkTableVersion();

		// include language file for this plugin 
		$language = ereg_replace( '[\\|/]', '', getLanguageName()); 
		if (file_exists($plug->getDirectory().'language/'.$language.'.php')) 
			include_once($plug->getDirectory().'language/'.$language.'.php'); 
		else 
			include_once($plug->getDirectory().'language/'.'english.php');
//modify end+++++++++

	$mTemplate = new Trackback_Template();
	$mTemplate->set ('CONF', $CONF);
	$mTemplate->set ('plugid', $plug->getID());
	$mTemplate->template('templates/menu.html');
	echo $mTemplate->fetch();

	$oTemplate = new Trackback_Template();
	$oTemplate->set ('CONF', $CONF);

	// Actions
	$action = requestVar('action');

	switch($action) {

//modify start+++++++++
		case 'tableUpgrade':
			mysql_query("
				CREATE TABLE IF NOT EXISTS
					".sql_table('plugin_tb_lookup')."
				(
					`link`      TEXT            NOT NULL, 
					`url`       TEXT            NOT NULL, 
					`title`     TEXT, 
					
					PRIMARY KEY (`link` (100))
				)
			");
			echo $q = "ALTER TABLE ".sql_table('plugin_tb')."
				 ADD `block` TINYINT( 4 ) NOT NULL AFTER `url` ,
				 ADD `spam` TINYINT( 4 ) NOT NULL AFTER `block` ,
				 ADD `link` TINYINT( 4 ) NOT NULL AFTER `spam` ,
				 CHANGE `url` `url` TEXT NOT NULL,
				 CHANGE `title` `title` TEXT NOT NULL,
				 CHANGE `excerpt` `excerpt` TEXT NOT NULL,
				 CHANGE `blog_name` `blog_name` TEXT NOT NULL,
				 DROP PRIMARY KEY,
				 ADD `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;";
			$res = @mysql_query($q);
			if (!$res){
				echo 'Could not alter table: ' . mysql_error();
			}else{
				$tableVersion = 1;
				$oTemplate->template('templates/updatetablefinished.html');
			}
			@mysql_query('ALTER TABLE `' . sql_table('plugin_tb') . '` ADD INDEX `tb_id_block_timestamp_idx` ( `tb_id`, `block`, `timestamp` DESC )');
			break;
//modify end+++++++++

		case 'block':
			$tb = intRequestVar('tb');

			$res = mysql_query ("
				UPDATE
					".sql_table('plugin_tb')."
				SET
					block = 1
				WHERE
					id = '".$tb."'
			");

			$action = requestVar('next');
			break;
		case 'blocked_clear':
			$res = mysql_query ("DELETE FROM ".sql_table('plugin_tb')." WHERE block = 1");
			$action = requestVar('next');
			break;
			
		case 'blocked_spamclear':
			$res = mysql_query ("DELETE FROM ".sql_table('plugin_tb')." WHERE block = 1 and spam = 1");
			$action = requestVar('next');
			break;

		case 'unblock':
			$tb = intRequestVar('tb');

			$res = mysql_query ("
				UPDATE
					".sql_table('plugin_tb')."
				SET
					block = 0
				WHERE
					id = '".$tb."'
			");

			$action = requestVar('next');
			break;

		case 'delete':
			$tb = intRequestVar('tb');

			$res = mysql_query ("
				DELETE FROM
					".sql_table('plugin_tb')."
				WHERE
					id = '".$tb."'
			");

			$action = requestVar('next');
			break;

		case 'sendping':
			$title     = requestVar('title');
			$url       = requestVar('url');
			$excerpt   = requestVar('excerpt');
			$blog_name = requestVar('blog_name');
			$ping_url  = requestVar('ping_url');		

			// No charset conversion needs to be done here, because
			// the charset used to receive the info is used to send
			// it...

			if ($ping_url) {
				$error = $oPluginAdmin->plugin->sendPing(0, $title, $url, $excerpt, $blog_name, $ping_url);
				
				if ($error) {
					echo '<b>TrackBack Error:' . $error . '</b>';
				}
			} 		
			
			$action = requestVar('next');
			break;
		case 'ping':
			$id  = intRequestVar('id');
			
			$usePathInfo = ($CONF['URLMode'] == 'pathinfo');
			if ($usePathInfo)
			@ include($strRel . 'fancyurls.config.php');
			
			global $manager;
			$itemData = $manager->getItem($id, 0, 0);
			
			if(is_array($itemData)){
				$blog =& $manager->getBlog($itemData['blogid']);
				$CONF['ItemURL'] = ($usePathInfo)? preg_replace('/\/$/', '', $blog->getURL()): $blog->getURL();
				$itemData['url'] = createItemLink($id);
				$itemData['excerpt'] = shorten(strip_tags($itemData['body'].$itemData['more']), 250, '...');
				$itemData['blogname'] = $blog->getName();
			}else{
				$itemData = array();
				$itemData['url'] = $CONF['IndexURL'];
				$itemData['blogname'] = $CONF['SiteName'];
			}
			$oTemplate->set('item', $itemData);
			
			$oTemplate->template('templates/ping.html');
			break; 			
	}

	// Pages 
	switch($action) {
		
		case 'help':
			$oTemplate->template('help.html');			
			break;

		case 'ping':
			$oTemplate->template('templates/ping.html');			
			break;

		case 'blocked':
			$start  = intRequestVar('start') ? intRequestVar('start') : 0;
			$amount = intRequestVar('amount') ? intRequestVar('amount') : 25;

			$rres = mysql_query ("
				SELECT
					COUNT(*) AS count
				FROM
					".sql_table('plugin_tb')." AS t,
					".sql_table('item')." AS i
				WHERE
					t.tb_id = i.inumber AND
					t.block = 1
			");				
						
			if ($row = mysql_fetch_array($rres))
				$count = $row['count'];
			else
				$count = 0;
					
			$rres = mysql_query ("
				SELECT
					i.ititle AS story,
					i.inumber AS story_id,
					t.id AS id,
					t.title AS title,
					t.blog_name AS blog_name,
					t.excerpt AS excerpt,
					t.url AS url,
					-- UNIX_TIMESTAMP(t.timestamp) AS timestamp,
					t.timestamp AS timestamp,
					t.spam AS spam,
					t.link AS link
				FROM
					".sql_table('plugin_tb')." AS t,
					".sql_table('item')." AS i
				WHERE
					t.tb_id = i.inumber AND
					t.block = 1
				ORDER BY
					timestamp DESC
				LIMIT
					".$start.",".$amount."
			");				
			
			$items = array();

			while ($rrow = mysql_fetch_array($rres))
			{
				$rrow['title'] 		= $oPluginAdmin->plugin->_cut_string($rrow['title'], 50);
				$rrow['title'] 		= $oPluginAdmin->plugin->_strip_controlchar($rrow['title']);
				$rrow['title'] 		= htmlspecialchars($rrow['title']);
//				$rrow['title'] 		= _CHARSET == 'UTF-8' ? $rrow['title'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['title']);

				$rrow['blog_name'] 	= $oPluginAdmin->plugin->_cut_string($rrow['blog_name'], 50);
				$rrow['blog_name'] 	= $oPluginAdmin->plugin->_strip_controlchar($rrow['blog_name']);
				$rrow['blog_name'] 	= htmlspecialchars($rrow['blog_name']);
//				$rrow['blog_name'] 	= _CHARSET == 'UTF-8' ? $rrow['blog_name'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['blog_name']);

				$rrow['excerpt'] 	= $oPluginAdmin->plugin->_cut_string($rrow['excerpt'], 800);
				$rrow['excerpt'] 	= $oPluginAdmin->plugin->_strip_controlchar($rrow['excerpt']);
				$rrow['excerpt'] 	= htmlspecialchars($rrow['excerpt']);
//				$rrow['excerpt'] 	= _CHARSET == 'UTF-8' ? $rrow['excerpt'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['excerpt']);

				$rrow['url'] 		= htmlspecialchars($rrow['url'], ENT_QUOTES);
				$rrow['timestamp'] 		= htmlspecialchars($rrow['timestamp'], ENT_QUOTES);
				
				$blog = & $manager->getBlog(getBlogIDFromItemID($item['itemid']));
				$rrow['story_url'] = $oPluginAdmin->plugin->_createItemLink($rrow['story_id'], $blog);
				$rrow['story'] = htmlspecialchars(strip_tags($rrow['story']), ENT_QUOTES);

				$items[] = $rrow;
			}
			
			$oTemplate->set ('amount', $amount);
			$oTemplate->set ('count', $count);
			$oTemplate->set ('start', $start);
			$oTemplate->set ('items', $items);
			$oTemplate->template('templates/blocked.html');			
			break;

		case 'all':
			$start  = intRequestVar('start') ? intRequestVar('start') : 0;
			$amount = intRequestVar('amount') ? intRequestVar('amount') : 25;

			$rres = mysql_query ("
				SELECT
					COUNT(*) AS count
				FROM
					".sql_table('plugin_tb')." AS t,
					".sql_table('item')." AS i
				WHERE
					t.tb_id = i.inumber AND
					t.block = 0
			");				
						
			if ($row = mysql_fetch_array($rres))
				$count = $row['count'];
			else
				$count = 0;
					
			$rres = mysql_query ("
				SELECT
					i.ititle AS story,
					i.inumber AS story_id,
					t.id AS id,
					t.title AS title,
					t.blog_name AS blog_name,
					t.excerpt AS excerpt,
					t.url AS url,
					UNIX_TIMESTAMP(t.timestamp) AS timestamp
				FROM
					".sql_table('plugin_tb')." AS t,
					".sql_table('item')." AS i
				WHERE
					t.tb_id = i.inumber AND
					t.block = 0
				ORDER BY
					timestamp DESC
				LIMIT
					".$start.",".$amount."
			");				
			
			$items = array();

			while ($rrow = mysql_fetch_array($rres))
			{
				$rrow['title'] 		= $oPluginAdmin->plugin->_cut_string($rrow['title'], 50);
				$rrow['title'] 		= $oPluginAdmin->plugin->_strip_controlchar($rrow['title']);
				$rrow['title'] 		= htmlspecialchars($rrow['title']);
//				$rrow['title'] 		= _CHARSET == 'UTF-8' ? $rrow['title'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['title']);

				$rrow['blog_name'] 	= $oPluginAdmin->plugin->_cut_string($rrow['blog_name'], 50);
				$rrow['blog_name'] 	= $oPluginAdmin->plugin->_strip_controlchar($rrow['blog_name']);
				$rrow['blog_name'] 	= htmlspecialchars($rrow['blog_name']);
//				$rrow['blog_name'] 	= _CHARSET == 'UTF-8' ? $rrow['blog_name'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['blog_name']);

				$rrow['excerpt'] 	= $oPluginAdmin->plugin->_cut_string($rrow['excerpt'], 800);
				$rrow['excerpt'] 	= $oPluginAdmin->plugin->_strip_controlchar($rrow['excerpt']);
				$rrow['excerpt'] 	= htmlspecialchars($rrow['excerpt']);
//				$rrow['excerpt'] 	= _CHARSET == 'UTF-8' ? $rrow['excerpt'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['excerpt']);

				$rrow['url'] 		= htmlspecialchars($rrow['url'], ENT_QUOTES);

				$blog = & $manager->getBlog(getBlogIDFromItemID($item['itemid']));
				$rrow['story_url'] = $oPluginAdmin->plugin->_createItemLink($rrow['story_id'], $blog);
				$rrow['story'] = htmlspecialchars(strip_tags($rrow['story']), ENT_QUOTES);

				$items[] = $rrow;
			}
			
			$oTemplate->set ('amount', $amount);
			$oTemplate->set ('count', $count);
			$oTemplate->set ('start', $start);
			$oTemplate->set ('items', $items);
			$oTemplate->template('templates/all.html');			
			break;		
		
		case 'list':
			$id     = requestVar('id');
			$start  = intRequestVar('start') ? intRequestVar('start') : 0;
			$amount = intRequestVar('amount') ? intRequestVar('amount') : 25;

			$ires = mysql_query ("
				SELECT
					ititle,
					inumber
				FROM
					".sql_table('item')."
				WHERE
					inumber = '".$id."'
			");
			
			if ($irow = mysql_fetch_array($ires))
			{
				$story['id']    = $id;
				$story['title'] = $irow['ititle'];

				$rres = mysql_query ("
					SELECT
						COUNT(*) AS count
					FROM
						".sql_table('plugin_tb')." AS t
					WHERE
						t.tb_id = '".$id."' AND
						t.block = 0
				");				
							
				if ($row = mysql_fetch_array($rres))
					$count = $row['count'];
				else
					$count = 0;
					
				$rres = mysql_query ("
					SELECT
						t.id AS id,
						t.title AS title,
						t.blog_name AS blog_name,
						t.excerpt AS excerpt,
						t.url AS url,
				        UNIX_TIMESTAMP(t.timestamp) AS timestamp
					FROM
						".sql_table('plugin_tb')." AS t
					WHERE
						t.tb_id = '".$id."' AND
						t.block = 0
					ORDER BY
						timestamp DESC
					LIMIT
						".$start.",".$amount."
				");				
				
				$items = array();
	
				while ($rrow = mysql_fetch_array($rres))
				{
					$rrow['title'] 		= $oPluginAdmin->plugin->_cut_string($rrow['title'], 50);
					$rrow['title'] 		= $oPluginAdmin->plugin->_strip_controlchar($rrow['title']);
					$rrow['title'] 		= htmlspecialchars($rrow['title']);
//					$rrow['title'] 		= _CHARSET == 'UTF-8' ? $rrow['title'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['title']);
	
					$rrow['blog_name'] 	= $oPluginAdmin->plugin->_cut_string($rrow['blog_name'], 50);
					$rrow['blog_name'] 	= $oPluginAdmin->plugin->_strip_controlchar($rrow['blog_name']);
					$rrow['blog_name'] 	= htmlspecialchars($rrow['blog_name']);
//					$rrow['blog_name'] 	= _CHARSET == 'UTF-8' ? $rrow['blog_name'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['blog_name']);
	
					$rrow['excerpt'] 	= $oPluginAdmin->plugin->_cut_string($rrow['excerpt'], 800);
					$rrow['excerpt'] 	= $oPluginAdmin->plugin->_strip_controlchar($rrow['excerpt']);
					$rrow['excerpt'] 	= htmlspecialchars($rrow['excerpt']);
//					$rrow['excerpt'] 	= _CHARSET == 'UTF-8' ? $rrow['excerpt'] : $oPluginAdmin->plugin->_utf8_to_entities($rrow['excerpt']);
	
					$rrow['url'] 		= htmlspecialchars($rrow['url'], ENT_QUOTES);
					$rrow['story'] = htmlspecialchars(strip_tags($rrow['story']), ENT_QUOTES);
					$items[] = $rrow;
				}
				
				$oTemplate->set ('amount', $amount);
				$oTemplate->set ('count', $count);
				$oTemplate->set ('start', $start);
				$oTemplate->set ('items', $items);
				$oTemplate->set ('story', $story);
				$oTemplate->template('templates/list.html');			
			}
			
			break;
							
		
		case 'index':
			$bres = mysql_query ("
				SELECT
					bnumber AS bnumber,
					bname AS bname,
					burl AS burl
				FROM
					".sql_table('blog')."
				ORDER BY
					bname
			");
			
			$blogs = array();
			
			while ($brow = mysql_fetch_array($bres))
			{
				$ires = mysql_query ("
					SELECT
						i.inumber AS inumber,
					    i.ititle AS ititle,
					    COUNT(*) AS total
					FROM
						".sql_table('item')." AS i,
						".sql_table('plugin_tb')." AS t
					WHERE
						i.iblog = ".$brow['bnumber']." AND
						t.tb_id = i.inumber AND
						t.block = 0
					GROUP BY
						i.inumber
                    ORDER BY
                    	i.inumber DESC
				");				

				$items = array();

				while ($irow = mysql_fetch_array($ires))
				{
					$items[] = $irow;
				}

				$brow['items'] = $items;
				$blogs[] = $brow;
			}

			$oTemplate->set ('blogs', $blogs);
			$oTemplate->template('templates/index.html');
			break;

		default:
			//modify start+++++++++
			if(!$tableVersion){
				$oTemplate->template('templates/updatetable.html');
			}
			//modify end+++++++++
			break;
	}

	// Create the admin area page
	echo $oTemplate->fetch();
	$oPluginAdmin->end();	

?>
