<?php
// vim: tabstop=2:shiftwidth=2 

   /* ==========================================================================================
	* Trackback 2.0 for Nucleus CMS 
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

	// Compatiblity with Nucleus < = 2.0
	if (!function_exists('sql_table')) { function sql_table($name) { return 'nucleus_' . $name; } }

	
	class NP_TrackBack extends NucleusPlugin {

		var $useCurl = 1; // use curl? 2:precheck+read by curl, 1: read by curl 0: fread

//modify start+++++++++
		function _createItemLink($itemid, $b){
			global $CONF, $manager;
			$blogurl = $b->getURL();
		
			if (!$blogurl) {
				if($blog) {
					$b_tmp =& $manager->getBlog($CONF['DefaultBlog']);
					$blogurl = $b_tmp->getURL();
				}
				if (!$blogurl) {
					$blogurl = $CONF['IndexURL'];
					if ($CONF['URLMode'] != 'pathinfo'){
						$blogurl = $CONF['Self'];
					}
				}
			}
			if ($CONF['URLMode'] == 'pathinfo'){
				if(substr($blogurl, -1) == '/')  $blogurl = substr($blogurl,0,-1);
			}
			
			$itemUrlOrg = $CONF['ItemURL'];
			$CONF['ItemURL'] = $blogurl;
			$itemLink = createItemLink($itemid,'');
			$CONF['ItemURL'] = $itemUrlOrg;
			
			return $itemLink;
		}
//modify end+++++++++

    	/**************************************************************************************
    	 * SKIN VARS, TEMPLATE VARS AND ACTIONS
		 */

		/*
		 * TrackBack data can be inserted using skinvars (or templatevars)
		 */
		function doSkinVar($skinType, $what = '', $tb_id = '', $amount = 'limit-1') {

			global $itemid, $manager, $CONF;

//modify start+++++++++
			if(eregi('limit', $tb_id)){
				$amount = $tb_id;
				$tb_id = '';
			}
			$amount = eregi_replace("limit", "", $amount);
			$amount = intval($amount);
//modify end+++++++++

			if ($tb_id == '') $tb_id = intval($itemid);
	
//mod by cles
			if( $this->getBlogOption(getBlogIDFromItemID($tb_id), "AllowTrackBack") == 'yes' )
				$isAcceptPing = ( $this->getItemOption(intval($tb_id), 'ItemAcceptPing') == 'yes' ) ? true : false ;
			else
				$isAcceptPing = false;
				
			//if( $skinType == 'template' && (! $isAcceptPing ) ){
			//	return;
			//}
//mod by cles end
			switch ($what) {
			
				// Insert Auto-discovery RDF code
				case 'tbcode':
				case 'code':
//mod by cles
//					if($skinType == 'item')

					$spamcheck = array (
						'type'  	=> 'tbcode',
						'id'        	=> -1,
						'title'		=> '',
						'excerpt'	=> '',
						'blogname'  	=> '',
						'url'		=> '',
						'return'	=> true,
						'live'   	=> true,
						
						/* Backwards compatibility with SpamCheck API 1*/
						'data'		=> '',
						'ipblock'   => true,
					);
					global $manager;
					$manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck));
					$spam = false;
					if (isset($spamcheck['result']) && $spamcheck['result'] == true){
						$spam = true;
					}

					if( ($skinType == 'item') && (!$spam) && $isAcceptPing  )
//mod by cles end
						$this->insertCode($tb_id);
					break;
					
				// Insert TrackBack URL
				case 'tburl':
				case 'url':
//mod by cles
//					echo $this->getTrackBackUrl($tb_id);
					if($isAcceptPing)
						echo $this->getTrackBackUrl($tb_id);
					else
						echo 'Sorry, no trackback pings are accepted.';
//mod by cles end
					break;
				
				// Insert manual ping URL
				case 'form':
				case 'manualpingformlink':
					echo $this->getManualPingUrl($tb_id);
					break;
				
				case 'sendpinglink':
					echo $manager->addTicketToUrl($CONF['PluginURL'] . 'trackback/index.php?action=ping&amp;id=' . intval($tb_id));
					break;
	
				// Insert TrackBack count
				case 'count':
					$count = $this->getTrackBackCount($tb_id);
					switch ($count) {
						case 0: 	echo TEMPLATE::fill($this->getOption('tplTbNone'), array('number' => $count)); break;
						case 1: 	echo TEMPLATE::fill($this->getOption('tplTbOne'),  array('number' => $count)); break;
						default: 	echo TEMPLATE::fill($this->getOption('tplTbMore'), array('number' => $count)); break;
					}
					break;

				// Shows the TrackBack list
				case 'list':
				case '':
//modify start+++++++++
//					$this->showList($tb_id);
					$this->showList($tb_id, $amount);
//modify end+++++++++
					break;
//mod by cles
				// show requred URL
				case 'required':
					echo  $this->getRequiredURL($tb_id);
					break;
					
				// shows the Local list
				case 'locallist':
					$this->showLocalList($tb_id);
					break;					
//mod by cles end
					
				default:
					return;
			}
		}
	
		/*
		 * When used in templates, the tb_id will be determined by the itemid there
		 */
		function doTemplateVar(&$item, $what = '') {
			$this->doSkinVar('template', $what, $item->itemid);
		}
		
		/*
		* A trackback ping is to be received on the URL
		* http://yourdomain.com/item/1234.trackback
		* Extra variables to be passed along are url, title, excerpt, blog_name
		*/
		function event_InitSkinParse(&$data) {
			global $CONF, $itemid;
			$format = requestVar('format');
			
			if ($CONF['URLMode'] == 'pathinfo') {
				if (preg_match('/(\/|\.)(trackback)(\/|$)/', serverVar('PATH_INFO'), $matches)) {
					$format = $matches[2];
				}
			}
			
			if ($format == 'trackback' && $data['type'] == 'item')
			{
				$errorMsg = $this->handlePing(intval($itemid));
				
				if ($errorMsg != '')
				$this->xmlResponse($errorMsg);
				else
				$this->xmlResponse();
				
				exit;
			}
		}

		/*
		 * A trackback ping is to be received on the URL
		 * http://yourdomain.com/action.php?action=plugin&name=TrackBack&tb_id=1234
		 * Extra variables to be passed along are url, title, excerpt, blog_name
		 */
		function doAction($type)
		{
			global $CONF,$manager;
			$aActionsNotToCheck = array(
				'',
				'ping',
				'form',
				'redirect',
				'left',
			);
			if (!in_array($type, $aActionsNotToCheck)) {
				if (!$manager->checkTicket()) return _ERROR_BADTICKET;
			}
			
			switch ($type) {
	
				// When no action type is given, assume it's a ping
				case '':
					$errorMsg = $this->handlePing();
					
					if ($errorMsg != '')
						$this->xmlResponse($errorMsg);
					else
						$this->xmlResponse();
					break; 
					
				// Manual ping
				case 'ping':
					$errorMsg = $this->handlePing();
					if ($errorMsg != '')
						$this->showManualPingError(intRequestVar('tb_id'), $errorMsg);
					else
						$this->showManualPingSuccess(intRequestVar('tb_id'));
					break; 
	
				// Show manual ping form
				case 'form':
//mod by cles
//					$this->showManualPingForm(intRequestVar('tb_id'));
					$tb_id = intRequestVar('tb_id');
					if( $this->getBlogOption(getBlogIDFromItemID($tb_id), "AllowTrackBack") == 'yes' )
						$isAcceptPing = ( $this->getItemOption($tb_id, 'ItemAcceptPing') == 'yes' ) ? true : false ;
					else
						$isAcceptPing = false;
					if( $isAcceptPing )	
						$this->showManualPingForm($tb_id);
					else
						echo 'Sorry, no trackback pings are accepted.';
//mod by cles end
					break;
	
				// Detect trackback
				case 'detect':
					list($url, $title) = 
						$this->getURIfromLink(html_entity_decode(requestVar('tb_link')));

					$url = addslashes($url);
					$url = $this->_utf8_to_javascript($url);

					$title = addslashes($title);
					$title = $this->_utf8_to_javascript($title);
				
					echo "tbDone('" . requestVar('tb_link') . "', '" . $url . "', '" . $title . "');";

					break;
//mod by cles
				// redirect 
				case 'redirect':
					return $this->redirect(intRequestVar('tb_id'), requestVar('urlHash'));
					break;
//mod by cles end
				case 'left':
					echo $this->showLeftList(intRequestVar('tb_id'), intRequestVar('amount'));
					break;
				
				// delete a trackback(local)
				case 'deletelc':
					$err = $this->deleteLocal(intRequestVar('tb_id'), intRequestVar('from_id'));
					if( $err )
						return $err;
					header('Location: ' . serverVar('HTTP_REFERER'));
					break;
			} 

			exit;
		} 



    	/**************************************************************************************
    	 * OUTPUT
		 */

		/*
		 * Show a list of left trackbacks for this ID
		 */
		function showLeftList($tb_id, $offset = 0, $amount = 99999999) {
			global $manager, $blog, $CONF;

			$out = array();
			$query = '
				SELECT 
					url, 
					md5(url) as urlHash,
					blog_name, 
					excerpt, 
					title, 
					UNIX_TIMESTAMP(timestamp) AS timestamp 
				FROM 
					'.sql_table('plugin_tb').' 
				WHERE 
					tb_id = '.intval($tb_id).' AND
					block = 0
				ORDER BY 
					timestamp DESC
			';
			if($offset)
				$query .= ' LIMIT '.intval($offset).', ' .intval($amount);
			$res = mysql_query($query);
			while ($row = mysql_fetch_array($res))
			{

				$row['blog_name'] 	= htmlspecialchars($row['blog_name'], ENT_QUOTES);
				$row['title']  		= htmlspecialchars($row['title'], ENT_QUOTES);
				$row['excerpt']  	= htmlspecialchars($row['excerpt'], ENT_QUOTES);
				if (_CHARSET != 'UTF-8') {
//modify start+++++++++
					$row['blog_name'] 	= $this->_restore_to_utf8($row['blog_name']);
					$row['title'] 		= $this->_restore_to_utf8($row['title']);
					$row['excerpt'] 	= $this->_restore_to_utf8($row['excerpt']);
//modify end+++++++++
					$row['blog_name'] 	= $this->_utf8_to_entities($row['blog_name']);
					$row['title'] 		= $this->_utf8_to_entities($row['title']);
					$row['excerpt'] 	= $this->_utf8_to_entities($row['excerpt']);
				}				
				$iVars = array(
					'action' 	=> $this->getTrackBackUrl($tb_id),
					'form' 	 	=> $this->getManualPingUrl($tb_id),
					'name'  	=> $row['blog_name'],
					'title' 	=> $row['title'],
					'excerpt'	=> $this->_cut_string($row['excerpt'], 400),
					'url'		=> htmlspecialchars($row['url'], ENT_QUOTES),
					'date'	   	=> htmlspecialchars(strftime($this->getOption('dateFormat'), $row['timestamp']), ENT_QUOTES)
				);

//mod by cles
				if( $this->getOption('HideUrl') == 'yes' )
					$iVars['url'] = $CONF['ActionURL'] . '?action=plugin&amp;name=TrackBack&amp;type=redirect&amp;tb_id=' . $tb_id . '&amp;urlHash=' . $row['urlHash'];
				else
					$iVars['url'] = $row['url'];
//mod by cles end

				$out[] = TEMPLATE::fill($this->getOption('tplItem'), $iVars);
			}
			mysql_free_result($res);
			
			return @join("\n",$out);
		}

		/*
		 * Show a list of all trackbacks for this ID
		 */
		function showList($tb_id, $amount = 0) {
			$tb_id = intval($tb_id);
			global $manager, $blog, $CONF, $member;
//mod by cles
			$enableHideurl = true;
			// for TB LinkLookup
			if( 
				   strstr(serverVar('HTTP_USER_AGENT'),'Hatena Diary Track Forward Agent')
				|| strstr(serverVar('HTTP_USER_AGENT'),'NP_TrackBack')
				|| strstr(serverVar('HTTP_USER_AGENT'),'TBPingLinkLookup')
				|| strstr(serverVar('HTTP_USER_AGENT'),'MT::Plugin::BanNoReferTb')
				|| strstr(serverVar('HTTP_USER_AGENT'),'livedoorBlog')
			){
				$enableHideurl = false;
				$amount = '-1';
			}
//mod by cles end

/*
			$res = mysql_query('
				SELECT 
					url, 
					md5(url) as urlHash,
					blog_name, 
					excerpt, 
					title, 
					UNIX_TIMESTAMP(timestamp) AS timestamp 
				FROM 
					'.sql_table('plugin_tb').' 
				WHERE 
					tb_id = '.$tb_id .' AND
					block = 0
				ORDER BY 
					timestamp ASC
			');
*/
			$query = '
				SELECT 
					url, 
					md5(url) as urlHash,
					blog_name, 
					excerpt, 
					title, 
					UNIX_TIMESTAMP(timestamp) AS timestamp 
				FROM 
					'.sql_table('plugin_tb').' 
				WHERE 
					tb_id = '.intval($tb_id) .' AND
					block = 0
				ORDER BY 
					timestamp DESC
			';
			if( $amount == '-1' )
				$query .= ' LIMIT 9999999';
			elseif( $amount )
				$query .= ' LIMIT '.intval($amount);
			
			if( $amount != 0)
				$res = mysql_query($query);

			$gVars = array(
				'action' => $this->getTrackBackUrl(intval($tb_id)),
				'form' 	 => $this->getManualPingUrl(intval($tb_id)),
				'required' => $this->getRequiredURL(intval($tb_id)),
			);
			
			if ($member->isLoggedIn() && $member->isAdmin()){
				$adminurl = $manager->addTicketToUrl($CONF['PluginURL'] . 'trackback/index.php?action=list&amp;id=' . intval($tb_id));
				$pingformurl = $manager->addTicketToUrl($CONF['PluginURL'] . 'trackback/index.php?action=ping&amp;id=' . intval($tb_id));
				$gVars['admin'] = '<a href="' . $adminurl . '" target="_blank">[admin]</a>';
				$gVars['pingform'] = '<a href="' . $pingformurl . '" target="_blank">[pingform]</a>';
			}

			echo TEMPLATE::fill($this->getOption('tplHeader'), $gVars);


			while ($amount != 0 && $row = mysql_fetch_array($res))
			{

				$row['blog_name'] 	= htmlspecialchars($row['blog_name'], ENT_QUOTES);
				$row['title']  		= htmlspecialchars($row['title'], ENT_QUOTES);
				$row['excerpt']  	= htmlspecialchars($row['excerpt'], ENT_QUOTES);

/*
*/
				if (_CHARSET != 'UTF-8') {
//modify start+++++++++
/*
					$row['blog_name'] 	= $this->_utf8_to_entities($row['blog_name']);
					$row['title'] 		= $this->_utf8_to_entities($row['title']);
					$row['excerpt'] 	= $this->_utf8_to_entities($row['excerpt']);
*/
					$row['blog_name'] 	= $this->_restore_to_utf8($row['blog_name']);
					$row['title'] 		= $this->_restore_to_utf8($row['title']);
					$row['excerpt'] 	= $this->_restore_to_utf8($row['excerpt']);

					$row['blog_name'] 	= mb_convert_encoding($row['blog_name'], _CHARSET, 'UTF-8');
					$row['title'] 		= mb_convert_encoding($row['title'], _CHARSET, 'UTF-8');
					$row['excerpt'] 	= mb_convert_encoding($row['excerpt'], _CHARSET, 'UTF-8');
//modify end+++++++++
				}				

//modify start+++++++++
/*
				$iVars = array(
					'action' 	=> $this->getTrackBackUrl($tb_id),
					'form' 	 	=> $this->getManualPingUrl($tb_id),
					'name'  	=> $row['blog_name'],
					'title' 	=> $row['title'],
					'excerpt'	=> $row['excerpt'],
					'url'		=> htmlspecialchars($row['url'], ENT_QUOTES),
					'date'	   	=> htmlspecialchars(strftime($this->getOption('dateFormat'), $row['timestamp'] + ($blog->getTimeOffset() * 3600)), ENT_QUOTES)
				);
*/
				$iVars = array(
					'action' 	=> $this->getTrackBackUrl($tb_id),
					'form' 	 	=> $this->getManualPingUrl($tb_id),
					'name'  	=> htmlspecialchars($row['blog_name'], ENT_QUOTES),
					'title' 	=> htmlspecialchars($row['title'], ENT_QUOTES),
					'excerpt'	=> htmlspecialchars($this->_cut_string($row['excerpt'], 400), ENT_QUOTES),
					'url'		=> htmlspecialchars($row['url'], ENT_QUOTES),
					'date'	   	=> htmlspecialchars(strftime($this->getOption('dateFormat'), $row['timestamp']), ENT_QUOTES)
				);

//mod by cles
				if( $enableHideurl && $this->getOption('HideUrl') == 'yes' )
					$iVars['url'] = $CONF['ActionURL'] . '?action=plugin&amp;name=TrackBack&amp;type=redirect&amp;tb_id=' . intval($tb_id) . '&amp;urlHash=' . $row['urlHash'];
				else
					$iVars['url'] = $row['url'];
//mod by cles end

//modify end+++++++++
				echo TEMPLATE::fill($this->getOption('tplItem'), $iVars);
				
			}

//modify start+++++++++
			$q = '
				SELECT 
					count(*) 
				FROM 
					'.sql_table('plugin_tb').' 
				WHERE 
					tb_id = '.intval($tb_id) .' AND
					block = 0
				ORDER BY 
					timestamp DESC
			';
			$result = mysql_query($q);
			$total = mysql_result($result,0,0);

			if($amount != -1 && $total > $amount){
				$leftcount = $total - $amount;

				echo '<script type="text/javascript" src="' . $this->getAdminURL() . 'detectlist.php?tb_id='.intval($tb_id).'&amp;amount='.intval($amount).'"></script>';

?>

<a name="restoftrackback" id="restoftrackback"></a>
<div id="tbshownavi"><a href="#restoftrackback" onclick="resttbStart(); return false;" id="tbshow">Show left <?php echo $leftcount;?> Trackbacks</a></div>
<div id="tbhidenavi" style="display: none;"><a href="#restoftrackback" onclick="hideresttb(); return false;">Hide <?php echo $leftcount;?> Trackbacks</a></div>
<div id="resttb"></div>

<?php
			}
//modify end+++++++++

			if (mysql_num_rows($res) == 0) 
			{
				echo TEMPLATE::fill($this->getOption('tplEmpty'), $gVars);
			}
			mysql_free_result($res);
			
			echo TEMPLATE::fill($this->getOption('tplFooter'), $gVars);

		}
			
		/*
		 * Returns the TrackBack count for a TrackBack item
		 */
		function getTrackBackCount($tb_id) {
			return quickQuery('SELECT COUNT(*) as result FROM ' . sql_table('plugin_tb') . ' WHERE tb_id='.intval($tb_id).' AND block = 0');
		}
		
		/**
		  * Returns the manual ping URL
		  */
		function getManualPingUrl($itemid) {
			global $CONF;
			return $CONF['ActionURL'] . '?action=plugin&amp;name=TrackBack&amp;type=form&amp;tb_id='.$itemid;
		}

		/**
		  * Show the manual ping form
		  */
		function showManualPingError($itemid, $status = '') {
			global $CONF;

			$form = true; $error = true; $success = false;
			sendContentType('text/html', 'admin-trackback', _CHARSET);	
//modify start+++++++++
//			include ($this->getDirectory() . '/templates/form.html');
			require_once($this->getDirectory() . '/template.php');
			$mTemplate = new Trackback_Template(null, $this->getDirectory());
			$mTemplate->set ('CONF', $CONF);
			$mTemplate->set ('itemid', $itemid);
			$mTemplate->set ('form', $form);
			$mTemplate->set ('error', $error);
			$mTemplate->set ('success', $success);
			$mTemplate->set ('status', $status);
			$mTemplate->template('templates/form.html');
			echo $mTemplate->fetch();
//modify end+++++++++
		}
		
		function showManualPingSuccess($itemid, $status = '') {
			global $CONF;

			$form = false; $error = false; $success = true;
			sendContentType('text/html', 'admin-trackback', _CHARSET);	
//modify start+++++++++
			//include ($this->getDirectory() . '/templates/form.html');
			require_once($this->getDirectory() . '/template.php');
			$mTemplate = new Trackback_Template(null, $this->getDirectory());
			$mTemplate->set ('CONF', $CONF);
			$mTemplate->set ('itemid', $itemid);
			$mTemplate->set ('form', $form);
			$mTemplate->set ('error', $error);
			$mTemplate->set ('success', $success);
			$mTemplate->set ('status', $status);
			$mTemplate->template('templates/form.html');
			echo $mTemplate->fetch();
//modify end+++++++++
		}
		
		function showManualPingForm($itemid, $text = '') {
			global $CONF;

			$form = true; $error = false; $success = false;

			// Check if we are allowed to accept pings
			if ($this->getOption('AcceptPing') == 'no') {
				$text = 'Sorry, no trackback pings are accepted';
				$form = false; $error = true;
			}
			
			sendContentType('text/html', 'admin-trackback', _CHARSET);	
//modify start+++++++++
			//include ($this->getDirectory() . '/templates/form.html');
			require_once($this->getDirectory() . '/template.php');
			$mTemplate = new Trackback_Template(null, $this->getDirectory());
			$mTemplate->set ('CONF', $CONF);
			$mTemplate->set ('itemid', $itemid);
			$mTemplate->set ('form', $form);
			$mTemplate->set ('error', $error);
			$mTemplate->set ('success', $success);
			$mTemplate->set ('status', $status);
			$mTemplate->template('templates/form.html');
			echo $mTemplate->fetch();
//modify end+++++++++
		}
	
		/**
		  * Returns the trackback URL
		  */
		function getTrackBackUrl($itemid) {
			global $CONF, $manager;
			return $CONF['ActionURL'] . '?action=plugin&amp;name=TrackBack&amp;tb_id='.$itemid;
		}		

		/*
		 * Insert RDF code for item
		 */
		function insertCode($itemid) {
			$itemid = intval($itemid);
			global $manager, $CONF;

			$item = & $manager->getItem($itemid, 0, 0);
			$blog = & $manager->getBlog(getBlogIDFromItemID($item['itemid']));
				
/*
			$CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());   
			$uri 	= createItemLink($item['itemid'],'');	
*/
			$uri 	= $this->_createItemLink($item['itemid'],$blog);	
					
			$title  = strip_tags($item['title']);
			$desc  	= strip_tags($item['body']);
			$desc   = $this->_cut_string($desc, 200);
			$desc   = htmlspecialchars($desc, ENT_QUOTES);
			
			$timestamp = time();
			$sourceaddr = ip2long(serverVar('REMOTE_ADDR'));
			$key = md5( sprintf("%u %u %u %s", $timestamp, $sourceaddr, $itemid, __FILE__));
			?>
			<!--
			<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
					 xmlns:dc="http://purl.org/dc/elements/1.1/"
					 xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
			<rdf:Description
					 rdf:about="<?php echo $uri; ?>"
					 dc:identifier="<?php echo $uri; ?>"
					 dc:title="<?php echo $title; ?>"
					 dc:description="<?php echo $desc; ?>"
					 trackback:ping="<?php echo $this->getTrackBackUrl($itemid)?>"
					 dc:date="<?php echo strftime('%Y-%m-%dT%H:%M:%S')?>" />
			</rdf:RDF>
			-->
			<?php
		}

		/**
		 * Retrieving TrackBack Pings (when __mode=rss)
		 */
		function rssResponse($tb_id) {
			$itemid = intval($itemid);
			global $manager, $CONF;
			$item =& $manager->getItem($tb_id, 0, 0);
	
			if($item)
			{
				$blog =& $manager->getBlog(getBlogIDFromItemID($item['itemid']));
				
				$blog_name  = $blog->getName();
				$title      = $item['title'];
				$excerpt    = $item['body'];

//modify start+++++++++
/*
				if (_CHARSET != 'UTF-8')
				{
					$title  	= $this->_convert_to_utf8($title, $encoding);
					$excerpt    = $this->_convert_to_utf8($excerpt, $encoding);
					$blog_name  = $this->_convert_to_utf8($blog_name, $encoding);
				}

				$title      = $this->_decode_entities(strip_tags($title));
				$excerpt    = $this->_decode_entities(strip_tags($excerpt));
				$blog_name  = $this->_decode_entities(strip_tags($blog_name));
*/

				$title      = $this->_restore_to_utf8($title);
				$excerpt    = $this->_restore_to_utf8($excerpt);
				$blog_name  = $this->_restore_to_utf8($blog_name);
//modify end+++++++++

				$excerpt    = $this->_cut_string($excerpt, 200);

				
//modify start+++++++++
/*
				$CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());   
				$url 	= createItemLink($item['itemid'],'');	
*/
				$url 	= $this->_createItemLink($item['itemid'],$blog);	
//modify end+++++++++
	
				// Use UTF-8 charset for output
				header('Content-Type: text/xml');
				echo "<","?xml version='1.0' encoding='UTF-8'?",">\n";
				
				echo "<response>\n";
				echo "\t<error>0</error>\n";
				echo "\t<rss version='0.91'>\n";
				echo "\t\t<channel>\n";
				echo "\t\t\t<title>".htmlspecialchars($title, ENT_QUOTES)."</title>\n";
				echo "\t\t\t<link>".htmlspecialchars($url, ENT_QUOTES)."</link>\n";
				echo "\t\t\t<description>".htmlspecialchars($excerpt, ENT_QUOTES)."</description>\n";
	
				$query = 'SELECT url, blog_name, excerpt, title, UNIX_TIMESTAMP(timestamp) as timestamp FROM '.sql_table('plugin_tb').' WHERE tb_id='.intval($tb_id).' AND block = 0 ORDER BY timestamp DESC';
				$res = mysql_query($query);
				while ($o = mysql_fetch_object($res)) 
				{
					// No need to do conversion, because it is already UTF-8
					$data = array (
						'url' 		=> htmlspecialchars($o->url, ENT_QUOTES),
						'blogname' 	=> htmlspecialchars($this->_restore_to_utf8($o->blog_name), ENT_QUOTES),
						'timestamp' => strftime('%Y-%m-%d',$o->timestamp),
						'title' 	=> htmlspecialchars($this->_restore_to_utf8($o->title), ENT_QUOTES),
						'excerpt' 	=> htmlspecialchars($this->_restore_to_utf8($o->excerpt), ENT_QUOTES),
						'tburl' 	=> $this->getTrackBackUrl($tb_id)
					);
					
					echo "\n";
					echo "\t\t\t<item>\n";
					echo "\t\t\t\t<title>".$data['title']."</title>\n";
					echo "\t\t\t\t<link>".$data['url']."</link>\n";
					echo "\t\t\t\t<description>".$data['excerpt']."</description>\n";
					echo "\t\t\t</item>\n";
				}
				echo "\t\t</channel>\n";
				echo "\t</rss>\n";
				echo "</response>";
				exit;
			}
			else
			{
				$this->xmlResponse(_ERROR_NOSUCHITEM);
			}
	
		}



    	/**************************************************************************************
    	 * SENDING AND RECEIVING TRACKBACK PINGS
		 */

		/* 
		 *  Send a Trackback ping to another website
		 */
		function sendPing($itemid, $title, $url, $excerpt, $blog_name, $ping_url, $utf8flag=0) 
		{
			$tempEncording = ($utf8flag)? 'UTF-8': _CHARSET;
			
			// 1. Check some basic things
			if (!$this->canSendPing()) {
				return 'You\'re not allowed to send pings';
			}
			
			if ($this->getOption('SendPings') == 'no') {
				return 'Sending trackback pings is disabled';
			}
			
			if ($ping_url == '') {
				return 'No ping URL';
			}
	
			// 2. Check if protocol is correct http URL
			$parsed_url = parse_url($ping_url);

			if ($parsed_url['scheme'] != 'http' || $parsed_url['host'] == '')
				return 'Bad ping URL';
	
			$port = ($parsed_url['port']) ? $parsed_url['port'] : 80;
	
			// 3. Create contents
			if($tempEncording != _CHARSET){
				$title = mb_convert_encoding($title, $tempEncording, _CHARSET);
				$excerpt = mb_convert_encoding($excerpt, $tempEncording, _CHARSET);
				$blog_name = mb_convert_encoding($blog_name, $tempEncording, _CHARSET);
			}
			
			
			$content  = 'title=' . 	urlencode( $title );
			$content .= '&url=' . 		urlencode( $url );
			$content .= '&excerpt=' . 	urlencode( $excerpt );
			$content .= '&blog_name=' . urlencode( $blog_name );
	
#			$user_agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)';
			$user_agent = 'NP_TrackBack/'. $this->getVersion();
	
			// 4. Prepare HTTP request
			$request  = 'POST ' . $parsed_url['path'];

			if ($parsed_url['query'] != '')
				$request .= '?' . $parsed_url['query'];
				
			$request .= " HTTP/1.1\r\n";
			$request .= "Accept: */*\r\n";
			$request .= "User-Agent: " . $user_agent . "\r\n";
			$request .= ( $port == 80 )?
									"Host: " . $parsed_url['host'] . "\r\n":
									"Host: " . $parsed_url['host'] . ":" . $port . "\r\n";
			$request .= "Connection: Keep-Alive\r\n";
			$request .= "Cache-Control: no-cache\r\n";
			$request .= "Connection: Close\r\n";
			$request .= "Content-Length: " . strlen( $content ) . "\r\n";
			$request .= ($utf8flag)? 
				"Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\n":
				"Content-Type: application/x-www-form-urlencoded; charset="._CHARSET."\r\n";
			$request .= "\r\n";
			$request .= $content;
	
			$socket = fsockopen( $parsed_url['host'], $port, $errno, $errstr );
			if ( ! $socket )
				return 'Could not send ping: '.$errstr.' ('.$errno.')';
	
			// 5. Execute HTTP request
			fputs($socket, $request);
	
			// 6. Receive response
			$result = '';
			while (!feof($socket)) {
				$result .= fgets($socket, 4096);
			}
			
			fclose($socket);
	
			// instead of parsing the XML, just check for the error string
			// [TODO] extract real error message and return that
//modify start+++++++++

			$DATA = split("\r\n\r\n", $result, 2);
			preg_match("/HTTP\/1\.[0-1] ([0-9]+) ([^\r\n]*)\r?\n/",$DATA[0],$httpresp);
			$respCd = $httpresp[1];
			$respMsg = $httpresp[2];

			if( $respCd != 200 ){
				return 'An error occurred: HTTP Error: [' . $respCd . '] ' . $respMsg;
			}
			if ( strstr($DATA[1],'<error>0</error>') === false ){
				preg_match("/<message>(.*?)<\/message>/",$DATA[1],$error_message);
				if( $error_message[1] )
					return 'An error occurred: '.htmlspecialchars($error_message[1], ENT_QUOTES);
				else
					return 'An error occurred: fatal error.';
			}
		} 
//modify end+++++++++

		/* 
		 *  Handle a Trackback ping sent to this website
		 */
		function handlePing($tb_id = 0) {
			global $manager;
			
			// Check if we are allowed to accept pings
			if ($this->getOption('AcceptPing') == 'no') {
				return 'Sorry, no trackback pings are accepted';
			}
						
			// Defaults
			$spam       = false;
			$link       = false;
//modify start+++++++++
//			$block 	    = true;
			$block 	    = false;
//modify end+++++++++
			if ($tb_id == 0)
			$tb_id 		= intRequestVar('tb_id');
			
			$rss 		= requestVar('__mode') == 'rss'; 
//mod by cles
			$enableLinkCheck = $this->isEnableLinkCheck($tb_id);
			$block = ( $enableLinkCheck ) ? true : false ;
//mod by cles end

			if (!$tb_id) {
				return 'TrackBack ID is missing (tb_id)';
			}
			
			if ((!$manager->existsItem($tb_id,0,0)) && ($this->getOption('CheckIDs') == 'yes')) {
				return _ERROR_NOSUCHITEM;
			}

			// 0. Check if we need to output the list as rss
			if ($rss) {
				$this->rssResponse($tb_id);
				return;
			}
//mod by cles
			// check: accept pings.
			if( $this->getBlogOption(getBlogIDFromItemID($tb_id), "AllowTrackBack") == 'yes' )
				$isAcceptPing = ( $this->getItemOption($tb_id, 'ItemAcceptPing') == 'yes' ) ? true : false ;
			else
				$isAcceptPing = false;
				
			if (! $isAcceptPing)
				return 'Sorry, no trackback pings are accepted.';
//mod by cles end

			// 1. Get attributes
//modify start+++++++++
			$b =& $manager->getBlog(getBlogIDFromItemID($tb_id));
//modify end+++++++++
			$url 		= requestVar('url');
			$title 		= requestVar('title');
			$excerpt 	= requestVar('excerpt');
			$blog_name 	= requestVar('blog_name');
			
			if (!$url) {
				return 'URL is missing (url)';
			}

			// 2. Conversion of encoding...
//modify start+++++++++
/*			if (preg_match ("/;\s*charset=([^\n]+)/is", $_SERVER["CONTENT_TYPE"], $regs))
				$encoding = strtoupper(trim($regs[1]));
			else
				$encoding = $this->_detect_encoding($excerpt);
*/
			$encoding = $this->_detect_encoding($excerpt);
//modify end+++++++++
			
//modify start+++++++++
			if (_CHARSET != 'UTF-8'){
				$title = $this->_strip_controlchar(strip_tags(mb_convert_encoding($title, _CHARSET, $encoding)));
				$excerpt = $this->_strip_controlchar(strip_tags(mb_convert_encoding($excerpt, _CHARSET, $encoding)));
				$blog_name = $this->_strip_controlchar(strip_tags(mb_convert_encoding($blog_name, _CHARSET, $encoding)));
			}else{
				$title      = $this->_strip_controlchar($this->_convert_to_utf8($title, $encoding));
				$excerpt    = $this->_strip_controlchar($this->_convert_to_utf8($excerpt, $encoding));
				$blog_name  = $this->_strip_controlchar($this->_convert_to_utf8($blog_name, $encoding));

				$title      = $this->_decode_entities(strip_tags($title));
				$excerpt    = $this->_decode_entities(strip_tags($excerpt));
				$blog_name  = $this->_decode_entities(strip_tags($blog_name));
			}
//modify end+++++++++

			// 4. Save data in the DB
			$res = @mysql_query('
				SELECT 
					tb_id, block, spam
				FROM 
					'.sql_table('plugin_tb').' 
				WHERE 
					url   = \''.mysql_real_escape_string($url).'\' AND 
					tb_id = \''.intval($tb_id).'\'
			');
			
			if (mysql_num_rows($res) != 0) 
			{
				// Existing TB, update it
/*
				$res = @mysql_query('
					UPDATE
						'.sql_table('plugin_tb').'
					SET 
						title     = "'.mysql_real_escape_string($title).'", 
						excerpt   = "'.mysql_real_escape_string($excerpt).'", 
						blog_name = "'.mysql_real_escape_string($blog_name).'", 
						timestamp = '.mysqldate(time()).'
					WHERE 
						url       = "'.mysql_real_escape_string($url).'" AND 
						tb_id     = "'.$tb_id.'"
				');
*/
//modify start+++++++++
				$rows = mysql_fetch_assoc($res);
				$spam = ( $rows['block'] || $rows['spam'] ) ? true : false;
				$res = @mysql_query('
					UPDATE
						'.sql_table('plugin_tb').'
					SET 
						title     = \''.mysql_real_escape_string($title).'\', 
						excerpt   = \''.mysql_real_escape_string($excerpt).'\', 
						blog_name = \''.mysql_real_escape_string($blog_name).'\', 
						timestamp = '.mysqldate($b->getCorrectTime()).'
					WHERE 
						url       = \''.mysql_real_escape_string($url).'\' AND 
						tb_id     = \''.mysql_real_escape_string(intval($tb_id)).'\'
				');
//modify end+++++++++

				if (!$res) {
					return 'Could not update trackback data: '.mysql_error();
				}
			} 
			else 
			{
				// 4. SPAM check (for SpamCheck API 2 /w compat. API 1)
				$spamcheck = array (
					'type'  	=> 'trackback',
					'id'        	=> $tb_id,
					'title'		=> $title,
					'excerpt'	=> $excerpt,
					'blogname'  	=> $blog_name,
					'url'		=> $url,
					'return'	=> true,
					'live'   	=> true,
					
					/* Backwards compatibility with SpamCheck API 1*/
					'data'		=> $url . "\n" . $title . "\n" . $excerpt . "\n" . $blog_name . "\n" . serverVar('HTTP_USER_AGENT'),
					'ipblock'   => true,
				);
				
				$manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck));
				
				if (isset($spamcheck['result']) && $spamcheck['result'] == true) 
				{
					$spam = true;
				}
				
				// 5. Content check (TO DO)
				if($spam == false || $enableLinkCheck == 'ignore' )	//modify
				{
//mod by cles
//					$contents = $this->retrieveUrl ($url);
//				
//					if (preg_match("/(".preg_quote($_SERVER["REQUEST_URI"], '/').")|(".preg_quote($_SERVER["SERVER_NAME"], '/').")/i", $contents)) {	
//						$link = true;
//					}
					if( $enableLinkCheck ){
						$contents = $this->retrieveUrl($url);
						
						$linkArray = $this->getPermaLinksFromText($contents);
						$itemLink = $this->_createItemLink($tb_id, $b);
						$itemLinkPat = '{^' . preg_quote($itemLink) .'}i';
						$itemLinkPat = str_replace('&','&(amp;)?', $itemLinkPat);
						
						foreach($linkArray as $l) {
							if(preg_match($itemLinkPat, $l)){
								ACTIONLOG :: add(INFO, "Trackback: LinkCheck OK. (link: $l pat: $itemLinkPat )");
								$link = true;
								break;
							}
						}
						if( ! $link ){
							$cnt = @count($linkArray);
							if( $enableLinkCheck == 'ignore' ){
								ACTIONLOG :: add(INFO, "Trackback: LinkCheck NG. [ignore] (itemid:$tb_id from: $url cnt: $cnt pat: $itemLinkPat)");
								return 'Sorry, trackback ping is not accepted.';
							} else {
								ACTIONLOG :: add(INFO, "Trackback: LinkCheck NG. [block] (itemid:$tb_id from: $url cnt: $cnt pat: $itemLinkPat");
							}
						}
					}
//mod by cles end
				}

				// 6. Determine if Trackback is safe...
//modify start+++++++++
//				$block = $spam == true || $link == false;
//				$block = $spam == true ;
//modify end+++++++++
//mod by cles
				if ( $enableLinkCheck )
					$block = ($spam == true || $link == false);
				else
					$block = $spam == true ;
//mod by cles end
				// New TB, insert it
/*
				$query = '
					INSERT INTO 
						'.sql_table('plugin_tb').' 
					SET
						tb_id     = "'.$tb_id.'",
						block     = "'.($block ? '1' : '0').'",
						spam      = "'.($spam ? '1' : '0').'",
						link      = "'.($link ? '1' : '0').'",
						url       = "'.mysql_real_escape_string($url).'",
						title     = "'.mysql_real_escape_string($title).'",
						excerpt   = "'.mysql_real_escape_string($excerpt).'",
						blog_name = "'.mysql_real_escape_string($blog_name).'",
						timestamp = '.mysqldate(time()).'
				';
*/
//modify start+++++++++
				$query = '
					INSERT INTO 
						'.sql_table('plugin_tb').' 
					SET
						tb_id     = \''.mysql_real_escape_string(intval($tb_id)).'\',
						block     = \''.($block ? '1' : '0').'\',
						spam      = \''.($spam ? '1' : '0').'\',
						link      = \''.($link ? '1' : '0').'\',
						url       = \''.mysql_real_escape_string($url).'\',
						title     = \''.mysql_real_escape_string($title).'\',
						excerpt   = \''.mysql_real_escape_string($excerpt).'\',
						blog_name = \''.mysql_real_escape_string($blog_name).'\',
						timestamp = '.mysqldate($b->getCorrectTime()).'
				';
//modify end+++++++++
				
				$res = @mysql_query($query);

				if (!$res) {
					return 'Could not save trackback data, possibly because of a double entry: ' . mysql_error() . $query;
				}
			}
	
			// 7. Send notification e-mail if needed
			if ($this->getOption('Notify') == 'yes' && $spam == false) 
			{
				$destAddress = $this->getOption('NotifyEmail');

				
				$vars = array (
					'tb_id'    => $tb_id,
					'url'      => $url,
					'title'    => $title,
					'excerpt'  => $excerpt,
					'blogname' => $blog_name
				);
				
//modify start+++++++++
/*
				$vars = array (
					'tb_id'    => $tb_id,
					'url'      => $url,
					'title'    => mb_convert_encoding($title, 'ISO-2022-JP', _CHARSET),
					'excerpt'  => mb_convert_encoding($excerpt, 'ISO-2022-JP', _CHARSET),
					'blogname' => mb_convert_encoding($blog_name, 'ISO-2022-JP', _CHARSET)
				);
*/				
//maybe not needed because japanese version has "mb_send_mail" in function notify
//modify end+++++++++
				
				$mailto_title = TEMPLATE::fill($this->notificationMailTitle, $vars);
				$mailto_msg   = TEMPLATE::fill($this->notificationMail, $vars);
	
				global $CONF, $DIR_LIBS;
				
				// make sure notification class is loaded
				if (!class_exists('notification'))
					include($DIR_LIBS . 'NOTIFICATION.php');
				
				$notify = new NOTIFICATION($destAddress);
				$notify->notify($mailto_title, $mailto_msg , $CONF['AdminEmail']);
//mod by cles+++++++++++	
				if ($manager->pluginInstalled('NP_Cache')){
					$p =& $manager->getPlugin('NP_Cache');
					$p->setCurrentBlog($tb_id);
					$p->cleanItem($tb_id);
					$p->cleanArray(array('index'));
				}
//mod by cles end +++++++++++	
			}
			return '';
		}	

		function xmlResponse($errorMessage = '') 
		{
			header('Content-Type: text/xml');

			echo "<","?xml version='1.0' encoding='UTF-8'?",">\n";
			echo "<response>\n";

			if ($errorMessage) 
				echo "\t<error>1</error>\n\t<message>",htmlspecialchars($errorMessage, ENT_QUOTES),"</message>\n";
			else
				echo "\t<error>0</error>\n";

			echo "</response>";
			exit;
		}
		
		/*
		 * Check if member may send ping (check if logged in)
		 */
		function canSendPing() {
			global $member;
			return $member->isLoggedIn() || $this->xmlrpc;
		}


//mod by cles
		function redirect($tb_id, $urlHash){
			global $CONF;
			$query = 'SELECT url FROM '.sql_table('plugin_tb').' WHERE tb_id='.intval($tb_id).' and md5(url)="'.$urlHash.'"';
			$res = mysql_query($query);
			
			$url = $CONF['SiteURL'];
			
			if ($o = mysql_fetch_object($res)) {
				$url = htmlspecialchars($o->url, ENT_QUOTES);
			}
			
			$url = stripslashes($url);
			$url = str_replace('&amp;','&',$url);
			$url = str_replace('&lt;','<',$url);
			$url = str_replace('&gt;','>',$url);
			$url = str_replace('&quot;','"',$url);
			
			header('Location: '.$url);
		}
				
		function getRequiredURL($itemid){
			global $manager;
			$blog = & $manager->getBlog(getBlogIDFromItemID($item['itemid']));
			if( $this->isEnableLinkCheck($itemid) )
				return $this->_createItemLink($itemid, $blog);
			return null;
		}
		
		function isEnableLinkCheck($itemid){
			$blogid = getBlogIDFromItemID($itemid);
			
//			if( ! $this->getBlogOption($blogid, 'enableLinkCheck') == 'yes' )
//				return false;
			
			$val = $this->getItemOption($itemid, 'isAcceptW/OLink');
			switch( $val ){
				case 'default':
					if($this->getBlogOption($blogid, 'isAcceptW/OLinkDef') == 'yes')
						return false;
					break; 
				case 'yes':
					return false;
					break;
				case 'no':
					break;
				default :
					ACTIONLOG :: add(INFO, "Trackback: Unknown Option (itemid:$itemid, value:$val)");
					return false;
			}
			return $this->getBlogOption($blogid, 'isAcceptW/OLinkDef');
		}
//mod by cles end

    	/**************************************************************************************
    	 * EVENTS
		 */

		function event_SendTrackback($data) {
			global $manager;
			
			// Enable sending trackbacks for the XML-RPC API, otherwise we would 
			// get an error because the current user is not exactly logged in.
			$this->xmlrpc = true;
			
			$itemid = $data['tb_id'];
			$item = &$manager->getItem($itemid, 0, 0);
			if (!$item) return; // don't ping for draft & future
			if ($item['draft']) return;   // don't ping on draft items
			
			// gather some more information, needed to send the ping (blog name, etc)      
			$blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
			$blog_name 	= $blog->getName();
			
			$title      = $data['title'] != '' ? $data['title'] : $item['title'];
			$title 		= strip_tags($title);
			
			$excerpt    = $data['body'] != '' ? $data['body'] : $item['body'];
			$excerpt 	= strip_tags($excerpt);
			$excerpt    = $this->_cut_string($excerpt, 200);
			
			$CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());
			//$url = createItemLink($itemid);
			$url = $this->_createItemLink($itemid, $blog);
			
			while (list(,$url) = each($data['urls'])) {
				$res = $this->sendPing($itemid, $title, $url, $excerpt, $blog_name, $url);
				if ($res) ACTIONLOG::add(WARNING, 'TrackBack Error:' . $res . ' (' . $url . ')');
			}
		}
		
		function event_RetrieveTrackback($data) {
			
			$res = mysql_query('
			SELECT 
			url, 
			title, 
			UNIX_TIMESTAMP(timestamp) AS timestamp 
			FROM 
			'.sql_table('plugin_tb').' 
			WHERE 
			tb_id = '.intval($data['tb_id']).' AND
			block = 0
			ORDER BY 
			timestamp ASC
			');
			
			while ($row = mysql_fetch_array($res)) {
				
				$trackback = array(
				'title' => $row['title'],
				'url'   => $row['url'],
				'ip'    => ''
				);
				
				$data['trackbacks'][] = $trackback;
			}
		}
/*
		function event_BookmarkletExtraHead($data) {
			global $NP_TB_URL;
			list ($NP_TB_URL,) = $this->getURIfromLink(requestVar('loglink'));
		} 
*/
		function event_PrepareItemForEdit($data) {
//			if (!$this->getOption('AutoXMLHttp'))
			if ($this->getOption('AutoXMLHttp') == 'no')
			{
				// The space between body and more is to make sure we didn't join 2 words accidently....
				$this->larray = $this->autoDiscovery($data['item']['body'].' '.$data['item']['more']);
			}
		} 

		/*
		 * After an item has been added to the database, send out a ping if requested
		 * (trackback_ping_url variable in request)
		 */
		function event_PostAddItem($data) {
			$this->pingTrackback($data);
		}
	
		function event_PreUpdateItem($data) {
			$this->pingTrackback($data);
		}

		/**
		 * Add trackback options to add item form/bookmarklet
		 */
		function event_AddItemFormExtras($data) {
		
//			global $NP_TB_URL;
			
			?>
				<h3>TrackBack</h3>
				<p>
<!--modify start+++++++++-->
<!--					<label for="plug_tb_url">TrackBack Ping URL:</label>
					<input type="text" value="<?php if (isSet($NP_TB_URL)) {echo $NP_TB_URL;} ?>" id="plug_tb_url" name="trackback_ping_url" size="60" />
-->
<!--modify end+++++++++-->
					<label for="plug_tb_url">TrackBack URL:</label><br />
					<textarea id="plug_tb_url" name="trackback_ping_url" cols="60" rows="5" style="font:normal xx-small Tahoma, Arial, verdana ;"></textarea>
<input type="button" name="btnAdd" value="<?php echo _TB_LIST_IT?>" onClick="AddStart()" />

		<br />
	
			<?php
//				if ($this->getOption('AutoXMLHttp'))
				if ($this->getOption('AutoXMLHttp') == 'yes')
				{
			?>
					<div id="tb_auto">
<input type="button" name="discoverit" value="Auto Discover" onclick="tbSetup();" />
						<img id='tb_busy' src='<?php echo $this->getAdminURL(); ?>busy.gif' style="display:none;" /><br />
					<div id="tb_auto_title"></div>
						<table border="1"><tbody id="tb_ping_list"></tbody></table>
						<input type="hidden" id="tb_url_amount" name="tb_url_amount" value="0" /> 
					</div>
					
			<?php
					$this->jsautodiscovery();
				}
			?>
				</p>
			<?php
		}

		/**
		 * Add trackback options to edit item form/bookmarklet
		 */
		function event_EditItemFormExtras($data) {
			global $CONF;
			?>
<!--					<input type="text" value="" id="plug_tb_url" name="trackback_ping_url" size="60" /><br />-->
				<h3>TrackBack</h3>
				<p>
					<label for="plug_tb_url">TrackBack URL:</label><br />
					<textarea id="plug_tb_url" name="trackback_ping_url" cols="60" rows="5" style="font:normal xx-small Tahoma, Arial, verdana ;"></textarea>
<input type="button" name="btnAdd" value="<?php echo _TB_LIST_IT?>" onClick="AddStart()" />
	
			<?php
//				if ($this->getOption('AutoXMLHttp'))
				if ($this->getOption('AutoXMLHttp') == 'yes')
				{
			?>


					<div id="tb_auto">
<input type="button" name="discoverit" value="Auto Discover" onclick="tbSetup();" />
						<img id='tb_busy' src='<?php echo $this->getAdminURL(); ?>busy.gif' style="display:none;" /><br />
					<div id="tb_auto_title"></div>
						<table border="1"><tbody id="tb_ping_list"></tbody></table>
						<input type="hidden" id="tb_url_amount" name="tb_url_amount" value="0" /> 
					</div>

			<?php
					$this->jsautodiscovery();
				}
				else
				{
					if (count($this->larray) > 0) 
					{
			?>
					Auto Discovered Ping URL's:<br />
			<?php
						echo '<input type="hidden" name="tb_url_amount" value="'.count($this->larray).'" />';
	
						$i = 0;
						
						while (list($url, $title) = each ($this->larray))
						{
//modify start+++++++++
							if (_CHARSET != 'UTF-8') {
								$title = $this->_utf8_to_entities($title);
								$title = mb_convert_encoding($title, _CHARSET, 'UTF-8');
							}
//modify end+++++++++

							echo '<input type="checkbox" name="tb_url_'.$i.
								 '" value="'.$url.'" id="tb_url_'.$i.
								 '" /><label for="tb_url_'.$i.'" title="'.$url.'">'.$title.'</label><br />';
							
							$i++;
						}
					}
				}		
			?>
				</p>
			<?php
		}

		/**
		 * Insert Javascript AutoDiscovery routines
		 */
		function jsautodiscovery() 
		{
			global $CONF;
		
			?>
				<script type='text/javascript' src='<?php echo $this->getAdminURL(); ?>autodetect.php'></script>	
			<?php
		}

		/**
		 * Ping all URLs
		 */
		function pingTrackback($data) {
			global $manager, $CONF;
			
			$ping_urls_count = 0;
			$ping_urls = array();
			$utf8flag = array();
			$localflag = array();
			
			$ping_url = requestVar('trackback_ping_url');
//modify start+++++++++
/*
			if ($ping_url) {
				$ping_urls[0] = $ping_url;
				$ping_urls_count++;
			}
*/
			if (trim($ping_url)) {
				$ping_urlsTemp = array();
				$ping_urlsTemp = preg_split("/[\s,]+/", trim($ping_url));
				for($i=0;$i<count($ping_urlsTemp);$i++){
					$ping_urls[] = trim($ping_urlsTemp[$i]);
					$ping_urls_count++;
				}
			}
//modify end+++++++++
	
			$tb_url_amount = requestVar('tb_url_amount');
			for ($i=0;$i<$tb_url_amount;$i++) {
				$tb_temp_url = requestVar('tb_url_'.$i);
				if ($tb_temp_url) {
					$ping_urls[$ping_urls_count] = $tb_temp_url;
					$utf8flag[$ping_urls_count] = (requestVar('tb_url_'.$i.'_utf8') == 'on')? 1: 0;
					$localflag[$ping_urls_count] = (requestVar('tb_url_'.$i.'_local') == 'on')? 1: 0;
					$ping_urls_count++;
				}
			}
	
			if ($ping_urls_count <= 0) {
				return;
			}
	
			$itemid = $data['itemid'];
			$item = &$manager->getItem($itemid, 0, 0);
			if (!$item) return; // don't ping for draft & future
			if ($item['draft']) return;   // don't ping on draft items
	
			// gather some more information, needed to send the ping (blog name, etc)      
			$blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
			$blog_name 	= $blog->getName();

			$title      = $data['title'] != '' ? $data['title'] : $item['title'];
			$title 		= strip_tags($title);

			$excerpt    = $data['body'] != '' ? $data['body'] : $item['body'];
			$excerpt 	= strip_tags($excerpt);
			$excerpt    = $this->_cut_string($excerpt, 200);
	
/*
			$CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());   
			$url = createItemLink($itemid);
*/
			$url 	= $this->_createItemLink($item['itemid'],$blog);	
	
			// send the ping(s) (add errors to actionlog)
			for ($i=0; $i<count($ping_urls); $i++) {
				if( ! $localflag[$i] )
					$res = $this->sendPing($itemid, $title, $url, $excerpt, $blog_name, $ping_urls[$i], $utf8flag[$i]);
				else
					$res = $this->handleLocalPing($itemid, $title, $excerpt, $blog_name, $ping_urls[$i]);
				if ($res) ACTIONLOG::add(WARNING, 'TrackBack Error:' . $res . ' (' . $ping_urls[$i] . ')');
			}
		}

	
	
	
    	/**************************************************************************************
    	 * AUTO-DISCOVERY
		 */

		/*
		 * Auto-Discovery of TrackBack Ping URLs based on HTML story
		 */
		function autoDiscovery($text) 
		{
			$links  = $this->getPermaLinksFromText($text);
			$result = array();
	
			for ($i = 0; $i < count($links); $i++)
			{
				list ($url, $title) = $this->getURIfromLink($links[$i]);
				
				if ($url != '')
					$result[$url] = $title;
			}
			
			return $result;
		}
		
		/*
		 * Auto-Discovery of TrackBack Ping URLs based on single link
		 */
		function getURIfromLink($link) 
		{
			
			// Check to see if the cache contains this link
			$res = mysql_query('SELECT url, title FROM '.sql_table('plugin_tb_lookup').' WHERE link=\''.mysql_real_escape_string($link).'\'');

			if ($row = mysql_fetch_array($res)) 
			{
				if ($row['title'] != '')
				{
//modify start+++++++++
					if (_CHARSET != 'UTF-8'){
						$row['title'] = mb_convert_encoding($row['title'], 'UTF-8', _CHARSET);
						$row['title'] = $this->_decode_entities($row['title']);
					}
//modify end+++++++++
					return array (
						$row['url'], $row['title']
					);
				}
				else
				{
					return array (
						$row['url'], $row['url']
					);
				}
			}
			
			// Retrieve RDF
			if (($rdf = $this->getRDFFromLink($link)) !== false) 
			{
				// Get PING attribute
				if (($uri = $this->getAttributeFromRDF($rdf, 'trackback:ping')) !== false) 
				{
					// Get TITLE attribute
					if (($title = $this->getAttributeFromRDF($rdf, 'dc:title')) !== false) 
					{
						// Get CREATOR attribute
						if (($author = $this->getAttributeFromRDF($rdf, 'dc:creator')) !== false) 
						{
							$title = $author. ": " . $title;
						}
	
						$uri   = $this->_decode_entities($uri);
//modify start+++++++++
						if (_CHARSET != 'UTF-8')
							$convertedTitle = mb_convert_encoding($title, _CHARSET, 'UTF-8');
						else
							$convertedTitle = $title;
/*
						// Store in cache
						$res = mysql_query("INSERT INTO ".sql_table('plugin_tb_lookup')." (link, url, title) VALUES ('".mysql_real_escape_string($link)."','".mysql_real_escape_string($uri)."','".mysql_real_escape_string($title)."')");
*/
						// Store in cache
						$res = mysql_query("INSERT INTO ".sql_table('plugin_tb_lookup')." (link, url, title) VALUES ('".mysql_real_escape_string($link)."','".mysql_real_escape_string($uri)."','".mysql_real_escape_string($convertedTitle)."')");
//modify end+++++++++
						$title = $this->_decode_entities($title);

						return array (
							$uri, $title
						);
					}
					else
					{
						$uri = html_entity_decode($uri, ENT_COMPAT);
	
						// Store in cache
						$res = mysql_query("INSERT INTO ".sql_table('plugin_tb_lookup')." (link, url, title) VALUES ('".mysql_real_escape_string($link)."','".mysql_real_escape_string($uri)."','')");
	
						return array (
							$uri, $uri
						);
					}
				}
			}
			
			// Store in cache
			$res = mysql_query("INSERT INTO ".sql_table('plugin_tb_lookup')." (link, url, title) VALUES ('".mysql_real_escape_string($link)."','','')");
	
			return array ('', '');
		}
	
		/*
		 * Detect links used in HTML code
		 */
		function getPermaLinksFromText($text)
		{
			$links = array();
			
			if (preg_match_all('/<a ([^>]+)>/', $text, $array, PREG_SET_ORDER))
			{
				for ($i = 0; $i < count($array); $i++)
				{
					preg_match('/href="http:\/\/(.+?)"/', $array[$i][1], $matches);
					$links['http://'.$matches[1]] = 1;
				}
			}
			
			return array_keys($links);
		}
	
		/*
		 * Retrieve RDF code from external link
		 */
		function getRDFFromLink($link) 
		{
			if ($content = $this->getContents($link))
			{
				preg_match_all('/(<rdf:RDF.*?<\/rdf:RDF>)/sm', $content, $rdfs, PREG_SET_ORDER);
				
				if (count($rdfs) > 1)
				{
					for ($i = 0; $i < count($rdfs); $i++)
					{
						if (preg_match('|dc:identifier="'.preg_quote($link).'"|ms',$rdfs[$i][1])) 
						{
							return $rdfs[$i][1];
						}
					}
				}
				else
				{
					// No need to check the identifier
					return $rdfs[0][1];
				}
			}
			
			return false;
		}
	
		/**
		 * Retrieve the contents of an external (X)HTML document
		 */
		function getContents($link) {
		
			// Use cURL extention if available
			if (function_exists("curl_init") && $this->useCurl == 2)
			{
				// Make HEAD request
				$ch = curl_init();
				@curl_setopt($ch, CURLOPT_URL, $link);
				@curl_setopt($ch, CURLOPT_HEADER, true);
				@curl_setopt($ch, CURLOPT_NOBODY, true);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				@curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
				@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				@curl_setopt($ch, CURLOPT_TIMEOUT, 20);
				@curl_setopt($ch, CURLOPT_USERAGENT, 'NP_TrackBack/'. $this->getVersion());

				$headers = curl_exec($ch);
				curl_close($ch);
				
				// Check if the link points to a (X)HTML document
				if (preg_match('/Content-Type: (text\/html|application\/xhtml+xml)/i', $headers))
				{
					return $this->retrieveUrl ($link);
				}
				
				return false;
			}
			else
			{
				return $this->retrieveUrl ($link);
			}
		}
	
		/*
		 * Get a single attribute from RDF
		 */
		function getAttributeFromRDF($rdf, $attribute)
		{
			if (preg_match('/'.$attribute.'="([^"]+)"/', $rdf, $matches)) 
			{
				return $matches[1];
			}
			
			return false;
		}






		/**************************************************************************************/
		/* Internal helper functions for dealing with external file retrieval                 */
	
		function retrieveUrl ($url) {
//mod by cles			$ua = ini_set('user_agent', 'NP_TrackBack/'. $this->getVersion());
//mod by cles end
			if (function_exists('curl_init') && $this->useCurl > 0)
			{
				// Set options
				$ch = curl_init();
				@curl_setopt($ch, CURLOPT_URL, $url);
				@curl_setopt($ch, CURLOPT_HEADER, 1);
				@curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				@curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				@curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
				@curl_setopt($ch, CURLOPT_TIMEOUT, 20);
				@curl_setopt($ch, CURLOPT_USERAGENT, 'NP_TrackBack/'. $this->getVersion());
		
				// Retrieve response
				$raw  = curl_exec($ch);
				$info = curl_getinfo($ch);
			
				// Split into headers and contents
				$headers  = substr($raw, 0, $info['header_size']);
				$contents = substr($raw, $info['header_size']);

				curl_close($ch);
			}
			elseif ($fp = @fopen ($url, "r"))
			{
//mod by cles
//				$contents = fread($fp, 8192);
				$contents = '';
				while (!feof($fp)) {
					$contents .= fread($fp, 8192);
				}
//mod by cles end
				$headers  = '';
				
				fclose($fp);
			}		
//mod by cles
			ini_set('user_agent', $ua);
//mod by cles end
			
			// Next normalize the encoding to UTF8...
			$contents = $this->_convert_to_utf8_auto($contents, $headers);
	
			return $contents;
		}
		

		/**************************************************************************************/
		/* Internal helper functions for dealing with encodings and entities                  */
	
		var $entities_cp1251 = array (
			'&#128;' 		=> '&#8364;',
			'&#130;' 		=> '&#8218;',
			'&#131;' 		=> '&#402;',	
			'&#132;' 		=> '&#8222;',	
			'&#133;' 		=> '&#8230;',	
			'&#134;' 		=> '&#8224;',	
			'&#135;' 		=> '&#8225;',	
			'&#136;' 		=> '&#710;',	
			'&#137;' 		=> '&#8240;',	
			'&#138;' 		=> '&#352;',	
			'&#139;' 		=> '&#8249;',	
			'&#140;' 		=> '&#338;',	
			'&#142;' 		=> '&#381;',	
			'&#145;' 		=> '&#8216;',	
			'&#146;' 		=> '&#8217;',	
			'&#147;' 		=> '&#8220;',	
			'&#148;' 		=> '&#8221;',	
			'&#149;' 		=> '&#8226;',	
			'&#150;' 		=> '&#8211;',	
			'&#151;' 		=> '&#8212;',	
			'&#152;' 		=> '&#732;',	
			'&#153;' 		=> '&#8482;',	
			'&#154;' 		=> '&#353;',	
			'&#155;' 		=> '&#8250;',	
			'&#156;' 		=> '&#339;',	
			'&#158;' 		=> '&#382;',	
			'&#159;' 		=> '&#376;',	
		);
	
		var $entities_default = array (
			'&quot;'		=> '&#34;',		
			'&amp;'   		=> '&#38;',	  	
			'&apos;'  		=> '&#39;',		
			'&lt;'    		=> '&#60;',		
			'&gt;'    		=> '&#62;',		
		);
	
		var $entities_latin = array (
			'&nbsp;' 		=> '&#160;',	
			'&iexcl;'		=> '&#161;',	
			'&cent;' 		=> '&#162;',	
			'&pound;' 		=> '&#163;',	
			'&curren;'		=> '&#164;',	
			'&yen;' 		=> '&#165;',	
			'&brvbar;'		=> '&#166;', 	
			'&sect;' 		=> '&#167;',	
			'&uml;' 		=> '&#168;',	
			'&copy;' 		=> '&#169;',	
			'&ordf;' 		=> '&#170;',	
			'&laquo;' 		=> '&#171;',	
			'&not;' 		=> '&#172;',	
			'&shy;' 		=> '&#173;',	
			'&reg;' 		=> '&#174;',	
			'&macr;' 		=> '&#175;',	
			'&deg;' 		=> '&#176;',	
			'&plusmn;' 		=> '&#177;',	
			'&sup2;' 		=> '&#178;',	
			'&sup3;' 		=> '&#179;', 	
			'&acute;' 		=> '&#180;',	
			'&micro;' 		=> '&#181;', 	
			'&para;' 		=> '&#182;',	
			'&middot;' 		=> '&#183;',	
			'&cedil;' 		=> '&#184;', 	
			'&sup1;' 		=> '&#185;',	
			'&ordm;' 		=> '&#186;',	
			'&raquo;' 		=> '&#187;',	
			'&frac14;' 		=> '&#188;',	
			'&frac12;' 		=> '&#189;',	
			'&frac34;' 		=> '&#190;',	
			'&iquest;' 		=> '&#191;',	
			'&Agrave;' 		=> '&#192;',	
			'&Aacute;' 		=> '&#193;',	
			'&Acirc;' 		=> '&#194;',	
			'&Atilde;' 		=> '&#195;',	
			'&Auml;' 		=> '&#196;',	
			'&Aring;' 		=> '&#197;',	
			'&AElig;' 		=> '&#198;',	
			'&Ccedil;'		=> '&#199;', 	
			'&Egrave;' 		=> '&#200;',	
			'&Eacute;' 		=> '&#201;',	
			'&Ecirc;' 		=> '&#202;',	
			'&Euml;' 		=> '&#203;',	
			'&Igrave;' 		=> '&#204;',	
			'&Iacute;' 		=> '&#205;',	
			'&Icirc;' 		=> '&#206;',	
			'&Iuml;' 		=> '&#207;', 	
			'&ETH;' 		=> '&#208;',	
			'&Ntilde;' 		=> '&#209;',	
			'&Ograve;' 		=> '&#210;',	
			'&Oacute;'		=> '&#211;',	
			'&Ocirc;' 		=> '&#212;',	
			'&Otilde;' 		=> '&#213;',	
			'&Ouml;' 		=> '&#214;',	
			'&times;' 		=> '&#215;',	
			'&Oslash;' 		=> '&#216;',	
			'&Ugrave;' 		=> '&#217;',	
			'&Uacute;' 		=> '&#218;',	
			'&Ucirc;' 		=> '&#219;',	
			'&Uuml;' 		=> '&#220;',	
			'&Yacute;' 		=> '&#221;',	
			'&THORN;' 		=> '&#222;',	
			'&szlig;' 		=> '&#223;',	
			'&agrave;' 		=> '&#224;',	
			'&aacute;' 		=> '&#225;',	
			'&acirc;' 		=> '&#226;',	
			'&atilde;' 		=> '&#227;',	
			'&auml;' 		=> '&#228;',	
			'&aring;' 		=> '&#229;',	
			'&aelig;' 		=> '&#230;',	
			'&ccedil;' 		=> '&#231;',	
			'&egrave;' 		=> '&#232;',	
			'&eacute;' 		=> '&#233;',	
			'&ecirc;' 		=> '&#234;',	
			'&euml;' 		=> '&#235;',	
			'&igrave;' 		=> '&#236;',	
			'&iacute;' 		=> '&#237;',	
			'&icirc;' 		=> '&#238;',	
			'&iuml;' 		=> '&#239;',	
			'&eth;' 		=> '&#240;',	
			'&ntilde;' 		=> '&#241;',	
			'&ograve;' 		=> '&#242;',	
			'&oacute;' 		=> '&#243;',	
			'&ocirc;' 		=> '&#244;',	
			'&otilde;' 		=> '&#245;',	
			'&ouml;' 		=> '&#246;',	
			'&divide;' 		=> '&#247;',	
			'&oslash;' 		=> '&#248;',	
			'&ugrave;' 		=> '&#249;',	
			'&uacute;' 		=> '&#250;',	
			'&ucirc;' 		=> '&#251;',	
			'&uuml;' 		=> '&#252;',	
			'&yacute;' 		=> '&#253;',	
			'&thorn;' 		=> '&#254;',	
			'&yuml;' 		=> '&#255;',	
		);	
	
		var $entities_extended = array (
			'&OElig;'		=> '&#338;',	
			'&oelig;'		=> '&#229;',	
			'&Scaron;'		=> '&#352;',	
			'&scaron;'		=> '&#353;',	
			'&Yuml;'		=> '&#376;',	
			'&circ;'		=> '&#710;',	
			'&tilde;'		=> '&#732;', 	
			'&esnp;'		=> '&#8194;',	
			'&emsp;'		=> '&#8195;',	
			'&thinsp;'		=> '&#8201;',	
			'&zwnj;'		=> '&#8204;',	
			'&zwj;'			=> '&#8205;',	
			'&lrm;'			=> '&#8206;',	
			'&rlm;'			=> '&#8207;', 	
			'&ndash;'		=> '&#8211;', 	
			'&mdash;'		=> '&#8212;',	
			'&lsquo;'		=> '&#8216;',	
			'&rsquo;'		=> '&#8217;', 	
			'&sbquo;'		=> '&#8218;',	
			'&ldquo;'		=> '&#8220;', 	
			'&rdquo;'		=> '&#8221;',	
			'&bdquo;'		=> '&#8222;',	
			'&dagger;'		=> '&#8224;',	
			'&Dagger;'		=> '&#8225;',	
			'&permil;'		=> '&#8240;',	
			'&lsaquo;'		=> '&#8249;',
			'&rsaquo;'		=> '&#8250;',
			'&euro;'		=> '&#8364;',
			'&fnof;'		=> '&#402;',	
			'&Alpha;'		=> '&#913;',	
			'&Beta;'		=> '&#914;',	
			'&Gamma;'		=> '&#915;',	
			'&Delta;'		=> '&#916;',	
			'&Epsilon;'		=> '&#917;',	
			'&Zeta;'		=> '&#918;',	
			'&Eta;'			=> '&#919;',	
			'&Theta;'		=> '&#920;',	
			'&Iota;'		=> '&#921;',	
			'&Kappa;'		=> '&#922;',	
			'&Lambda;'		=> '&#923;',	
			'&Mu;'			=> '&#924;',	
			'&Nu;'			=> '&#925;',	
			'&Xi;'			=> '&#926;',	
			'&Omicron;'		=> '&#927;',	
			'&Pi;'			=> '&#928;',	
			'&Rho;'			=> '&#929;',	
			'&Sigma;'		=> '&#931;',	
			'&Tau;'			=> '&#932;',	
			'&Upsilon;'		=> '&#933;', 	
			'&Phi;'			=> '&#934;',	
			'&Chi;'			=> '&#935;',	
			'&Psi;'			=> '&#936;',	
			'&Omega;'		=> '&#937;',	
			'&alpha;'		=> '&#945;',	
			'&beta;'		=> '&#946;',	
			'&gamma;'		=> '&#947;',	
			'&delta;'		=> '&#948;',	
			'&epsilon;'		=> '&#949;',	
			'&zeta;'		=> '&#950;',	
			'&eta;'			=> '&#951;',	
			'&theta;'		=> '&#952;',	
			'&iota;'		=> '&#953;',	
			'&kappa;'		=> '&#954;',	
			'&lambda;'		=> '&#955;',	
			'&mu;'			=> '&#956;',	
			'&nu;'			=> '&#957;',	
			'&xi;'			=> '&#958;',	
			'&omicron;'		=> '&#959;',	
			'&pi;'			=> '&#960;',	
			'&rho;'			=> '&#961;',	
			'&sigmaf;'		=> '&#962;',	
			'&sigma;'		=> '&#963;',	
			'&tau;'			=> '&#964;',	
			'&upsilon;'		=> '&#965;', 	
			'&phi;'			=> '&#966;',	
			'&chi;'			=> '&#967;',	
			'&psi;'			=> '&#968;',	
			'&omega;'		=> '&#969;',	
			'&thetasym;'	=> '&#977;',	
			'&upsih;'		=> '&#978;',	
			'&piv;'			=> '&#982;',	
			'&bull;'		=> '&#8226;',	
			'&hellip;'		=> '&#8230;',	
			'&prime;'		=> '&#8242;',	
			'&Prime;'		=> '&#8243;',	
			'&oline;'		=> '&#8254;', 	
			'&frasl;'		=> '&#8260;',	
			'&weierp;'		=> '&#8472;', 	
			'&image;'		=> '&#8465;', 	
			'&real;'		=> '&#8476;',	
			'&trade;'		=> '&#8482;', 	
			'&alefsym;' 	=> '&#8501;', 	
			'&larr;'		=> '&#8592;', 	
			'&uarr;'		=> '&#8593;', 	
			'&rarr;'		=> '&#8594;',	
			'&darr;'		=> '&#8595;', 	
			'&harr;'		=> '&#8596;',	
			'&crarr;'		=> '&#8629;',	
			'&lArr;'		=> '&#8656;',	
			'&uArr;'		=> '&#8657;', 	
			'&rArr;'		=> '&#8658;', 	
			'&dArr;'		=> '&#8659;', 	
			'&hArr;'		=> '&#8660;', 	
			'&forall;'		=> '&#8704;', 	
			'&part;'		=> '&#8706;', 	
			'&exist;'		=> '&#8707;', 	
			'&empty;'		=> '&#8709;', 	
			'&nabla;'		=> '&#8711;', 	
			'&isin;'		=> '&#8712;', 	
			'&notin;'		=> '&#8713;', 	
			'&ni;'			=> '&#8715;', 	
			'&prod;'		=> '&#8719;', 	
			'&sum;'			=> '&#8721;', 	
			'&minus;'		=> '&#8722;', 	
			'&lowast;'		=> '&#8727;', 	
			'&radic;'		=> '&#8730;', 	
			'&prop;'		=> '&#8733;', 	
			'&infin;'		=> '&#8734;', 	
			'&ang;'			=> '&#8736;', 	
			'&and;'			=> '&#8743;', 	
			'&or;'			=> '&#8744;', 	
			'&cap;'			=> '&#8745;', 	
			'&cup;'			=> '&#8746;', 	
			'&int;'			=> '&#8747;', 	
			'&there4;'		=> '&#8756;', 	
			'&sim;'			=> '&#8764;', 	
			'&cong;'		=> '&#8773;', 	
			'&asymp;'		=> '&#8776;', 	
			'&ne;'			=> '&#8800;', 	
			'&equiv;'		=> '&#8801;', 	
			'&le;'			=> '&#8804;', 	
			'&ge;'			=> '&#8805;', 	
			'&sub;'			=> '&#8834;', 	
			'&sup;'			=> '&#8835;', 	
			'&nsub;'		=> '&#8836;', 	
			'&sube;'		=> '&#8838;', 	
			'&supe;'		=> '&#8839;', 	
			'&oplus;'		=> '&#8853;', 	
			'&otimes;'  	=> '&#8855;', 	
			'&perp;'		=> '&#8869;', 	
			'&sdot;'		=> '&#8901;', 	
			'&lceil;'		=> '&#8968;', 	
			'&rceil;'		=> '&#8969;', 	
			'&lfloor;'		=> '&#8970;', 	
			'&rfloor;'		=> '&#8971;', 	
			'&lang;'		=> '&#9001;', 	
			'&rang;'		=> '&#9002;', 	
			'&loz;'			=> '&#9674;', 	
			'&spades;'		=> '&#9824;', 	
			'&clubs;'		=> '&#9827;', 	
			'&hearts;'		=> '&#9829;', 	
			'&diams;'		=> '&#9830;', 	
		);
	
	
//modify start+++++++++
		function _restore_to_utf8($contents)
		{
			if (_CHARSET != 'UTF-8')
			{
				$contents = mb_convert_encoding($contents, 'UTF-8', _CHARSET);
			}
			$contents = $this->_decode_entities(strip_tags($contents));
			return $contents;
		}
//modify end+++++++++
		function _detect_encoding($string)
		{
//modify start+++++++++
			if (function_exists('mb_convert_encoding')) {
				$encoding = (preg_match ("/;\s*charset=([^\n]+)/is", serverVar("CONTENT_TYPE"), $regs))? 
					strtoupper(trim($regs[1])):
					'';

				if ( ($encoding !="") && ((mb_http_input("P") == "") || ( strtolower( ini_get("mbstring.http_input") ) == "pass")) ) {
					return $encoding;
				} else { 
					$encoding = mb_detect_encoding($string, 'UTF-8,EUC-JP,SJIS,ISO-8859-1,ASCII,JIS');
				}
				return ( $encoding ) ? $encoding : 'ISO-8859-1';
			}
//modify end+++++++++
			if (!ereg("[\x80-\xFF]", $string) && !ereg("\x1B", $string))
			return 'US-ASCII';
			
			if (!ereg("[\x80-\xFF]", $string) && ereg("\x1B", $string))
			return 'ISO-2022-JP';
			
			if (preg_match("/^([\x01-\x7F]|[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF][\x80-\xBF])+$/", $string) == 1)
			return 'UTF-8';
			
			if (preg_match("/^([\x01-\x7F]|\x8E[\xA0-\xDF]|\x8F[xA1-\xFE][\xA1-\xFE]|[\xA1-\xFE][\xA1-\xFE])+$/", $string) == 1)
			return 'EUC-JP';
			
			if (preg_match("/^([\x01-\x7F]|[\xA0-\xDF]|[\x81-\xFC][\x40-\xFC])+$/", $string) == 1)
			return 'Shift_JIS';
			
			return 'ISO-8859-1';
		}

		function _convert_to_utf8($contents, $encoding)
		{
			$done = false;
			
//modify start+++++++++
//			if (!$done && function_exists('iconv'))  
//			{
//			
//				$result = @iconv($encoding, 'UTF-8//IGNORE', $contents);
//	
//				if ($result) 
//				{
//					$contents = $result;
//					$done = true;
//				}
//			}
			
			if(!$done && function_exists('mb_convert_encoding')) 
			{
				
				if( function_exists('mb_substitute_character') ){
					@mb_substitute_character('none');
				}
				$result = @mb_convert_encoding($contents, 'UTF-8', $encoding );
	
				if ($result) 
				{
					$contents = $result;
					$done = true;
				}
			}

			if (!$done && function_exists('iconv'))  
			{
			
				$result = @iconv($encoding, 'UTF-8//IGNORE', $contents);
	
				if ($result) 
				{
					$contents = $result;
					$done = true;
				}
			}
//modify end+++++++++
			return $contents;
		}
		
		function _convert_to_utf8_auto($contents, $headers = '')
		{
			/* IN:  string in unknown encoding, headers received during transfer
			 * OUT: string in UTF-8 encoding
			 */
	
			$str = substr($contents, 0, 4096);
			$len = strlen($str);
			$pos = 0;
			$out = '';
			
			while ($pos < $len)
			{
				$ord = ord($str[$pos]);
				
				if ($ord > 32 && $ord < 128)
					$out .= $str[$pos];
					
				$pos++;
			}
	
			// Detection of encoding, check headers
			if (preg_match ("/;\s*charset=([^\n]+)/is", $headers, $regs))
				$encoding = strtoupper(trim($regs[1]));
	
			// Then check meta inside document
			if (preg_match ("/;\s*charset=([^\"']+)/is", $out, $regs))
				$encoding = strtoupper(trim($regs[1]));
				
			// Then check xml declaration
			if (preg_match("/<\?xml.+encoding\s*=\s*[\"|']([^\"']+)[\"|']\s*\?>/i", $out, $regs))
				$encoding = strtoupper(trim($regs[1]));		
	
			// Converts
			return $this->_convert_to_utf8($contents, $encoding);
		}
		
		function _decode_entities($string)
		{
			/* IN:  string in UTF-8 containing entities
			 * OUT: string in UTF-8 without entities
			 */
			 
			/// Convert all hexadecimal entities to decimal entities
			$string = preg_replace('/&#[Xx]([0-9A-Fa-f]+);/e', "'&#'.hexdec('\\1').';'", $string);		

			// Deal with invalid cp1251 numeric entities
			$string = strtr($string, $this->entities_cp1251);

			// Convert all named entities to numeric entities
			$string = strtr($string, $this->entities_default);
			$string = strtr($string, $this->entities_latin);
			$string = strtr($string, $this->entities_extended);

			// Convert all numeric entities to UTF-8
			$string = preg_replace('/&#([0-9]+);/e', "'&#x'.dechex('\\1').';'", $string);
			$string = preg_replace('/&#[Xx]([0-9A-Fa-f]+);/e', "NP_TrackBack::_hex_to_utf8('\\1')", $string);		

			return $string;
		}
	
		function _hex_to_utf8($s)
		{
			/* IN:  string containing one hexadecimal Unicode character
			 * OUT: string containing one binary UTF-8 character
			 */
			 
			$c = hexdec($s);
		
			if ($c < 0x80) {
				$str = chr($c);
			}
			else if ($c < 0x800) {
				$str = chr(0xC0 | $c>>6) . chr(0x80 | $c & 0x3F);
			}
			else if ($c < 0x10000) {
				$str = chr(0xE0 | $c>>12) . chr(0x80 | $c>>6 & 0x3F) . chr(0x80 | $c & 0x3F);
			}
			else if ($c < 0x200000) {
				$str = chr(0xF0 | $c>>18) . chr(0x80 | $c>>12 & 0x3F) . chr(0x80 | $c>>6 & 0x3F) . chr(0x80 | $c & 0x3F);
			}
			
			return $str;
		} 		

		function _utf8_to_entities($string)
		{
			/* IN:  string in UTF-8 encoding
			 * OUT: string consisting of only characters ranging from 0x00 to 0x7f, 
			 *      using numeric entities to represent the other characters 
			 */
			 
			$len = strlen ($string);
			$pos = 0;
			$out = '';
				
			while ($pos < $len) 
			{
				$ascii = ord (substr ($string, $pos, 1));
				
				if ($ascii >= 0xF0) 
				{
					$byte[1] = ord(substr ($string, $pos, 1)) - 0xF0;
					$byte[2] = ord(substr ($string, $pos + 1, 1)) - 0x80;
					$byte[3] = ord(substr ($string, $pos + 2, 1)) - 0x80;
					$byte[4] = ord(substr ($string, $pos + 3, 1)) - 0x80;
	
					$char_code = ($byte[1] << 18) + ($byte[2] << 12) + ($byte[3] << 6) + $byte[4];
					$pos += 4;
				}
				elseif (($ascii >= 0xE0) && ($ascii < 0xF0)) 
				{
					$byte[1] = ord(substr ($string, $pos, 1)) - 0xE0;
					$byte[2] = ord(substr ($string, $pos + 1, 1)) - 0x80;
					$byte[3] = ord(substr ($string, $pos + 2, 1)) - 0x80;
	
					$char_code = ($byte[1] << 12) + ($byte[2] << 6) + $byte[3];
					$pos += 3;
				}
				elseif (($ascii >= 0xC0) && ($ascii < 0xE0)) 
				{
					$byte[1] = ord(substr ($string, $pos, 1)) - 0xC0;
					$byte[2] = ord(substr ($string, $pos + 1, 1)) - 0x80;
	
					$char_code = ($byte[1] << 6) + $byte[2];
					$pos += 2;
				}
				else 
				{
					$char_code = ord(substr ($string, $pos, 1));
					$pos += 1;
				}
	
				if ($char_code < 0x80)
					$out .= chr($char_code);
				else
					$out .=  '&#'. str_pad($char_code, 5, '0', STR_PAD_LEFT) . ';';
			}
	
			return $out;	
		}			

		function _utf8_to_javascript($string)
		{
			/* IN:  string in UTF-8 encoding
			 * OUT: string consisting of only characters ranging from 0x00 to 0x7f, 
			 *      using javascript escapes to represent the other characters 
			 */
			 
			$len = strlen ($string);
			$pos = 0;
			$out = '';
				
			while ($pos < $len) 
			{
				$ascii = ord (substr ($string, $pos, 1));
				
				if ($ascii >= 0xF0) 
				{
					$byte[1] = ord(substr ($string, $pos, 1)) - 0xF0;
					$byte[2] = ord(substr ($string, $pos + 1, 1)) - 0x80;
					$byte[3] = ord(substr ($string, $pos + 2, 1)) - 0x80;
					$byte[4] = ord(substr ($string, $pos + 3, 1)) - 0x80;
	
					$char_code = ($byte[1] << 18) + ($byte[2] << 12) + ($byte[3] << 6) + $byte[4];
					$pos += 4;
				}
				elseif (($ascii >= 0xE0) && ($ascii < 0xF0)) 
				{
					$byte[1] = ord(substr ($string, $pos, 1)) - 0xE0;
					$byte[2] = ord(substr ($string, $pos + 1, 1)) - 0x80;
					$byte[3] = ord(substr ($string, $pos + 2, 1)) - 0x80;
	
					$char_code = ($byte[1] << 12) + ($byte[2] << 6) + $byte[3];
					$pos += 3;
				}
				elseif (($ascii >= 0xC0) && ($ascii < 0xE0)) 
				{
					$byte[1] = ord(substr ($string, $pos, 1)) - 0xC0;
					$byte[2] = ord(substr ($string, $pos + 1, 1)) - 0x80;
	
					$char_code = ($byte[1] << 6) + $byte[2];
					$pos += 2;
				}
				else 
				{
					$char_code = ord(substr ($string, $pos, 1));
					$pos += 1;
				}
	
				if ($char_code < 0x80)
					$out .= chr($char_code);
				else
					$out .=  '\\u'. str_pad(dechex($char_code), 4, '0', STR_PAD_LEFT);
			}
	
			return $out;	
		}			
/*		
		function _cut_string($string, $dl = 0) {
		
			$defaultLength = $dl > 0 ? $dl : $this->getOption('defaultLength');
			
			if ($defaultLength < 1)
				return $string;
	
			$border    = 6;
			$count     = 0;
			$lastvalue = 0;
	
  			for ($i = 0; $i < strlen($string); $i++)
       		{
       			$value = ord($string[$i]);
	   
	   			if ($value > 127)
           		{
           			if ($value >= 192 && $value <= 223)
               			$i++;
           			elseif ($value >= 224 && $value <= 239)
               			$i = $i + 2;
           			elseif ($value >= 240 && $value <= 247)
               			$i = $i + 3;
					
					if ($lastvalue <= 223 && $value >= 223 && 
						$count >= $defaultLength - $border)
					{
						return substr($string, 0, $i) . '...';
					}

					// Chinese and Japanese characters are
					// wider than Latin characters
					if ($value >= 224)
						$count++;
					
           		}
				elseif ($string[$i] == '/' || $string[$i] == '?' ||
						$string[$i] == '-' || $string[$i] == ':' ||
						$string[$i] == ',' || $string[$i] == ';')
				{
					if ($count >= $defaultLength - $border)
						return substr($string, 0, $i) . '...';
				}
				elseif ($string[$i] == ' ')
				{
					if ($count >= $defaultLength - $border)
						return substr($string, 0, $i) . '...';
				}
				
				if ($count == $defaultLength)
					return substr($string, 0, $i + 1) . '...';
      
	  			$lastvalue = $value;
       			$count++;
       		}

			return $string;
		}
*/

function _cut_string($string, $dl = 0) {
	$maxLength = $dl > 0 ? $dl : $this->getOption('defaultLength');
	
	if ($maxLength < 1)
		return $string;
	if (strlen($string) > $maxLength)
		$string = mb_strimwidth($string, 0, $maxLength, '...', _CHARSET);

	return $string;
}

function _strip_controlchar($string){	$string = preg_replace("/[\x01-\x08\x0b\x0c\x0e-\x1f\x7f]+/","",$string);
	$string = str_replace("\0","",$string);
	return $string;
}

//modify start+++++++++
	function checkTableVersion(){
				$res = sql_query("SHOW FIELDS from ".sql_table('plugin_tb') );
				$fieldnames = array();
				while ($co = mysql_fetch_assoc($res)) {
					if($co['Field'] == 'block') return true;
				}
				return false;
	}
//modify end+++++++++

/*---------------------------------------------------------------------------------- */
/*   LOCAL                                                                           */
/*---------------------------------------------------------------------------------- */
	/**
	  * Handle an incoming TrackBack ping and save the data in the database
	  */
	function handleLocalPing($itemid, $title, $excerpt, $blog_name, $ping_url){
		global $manager;
		$ping_url = trim($ping_url);
		
		if( preg_match("/^.+tb_id=([0-9]+)$/",$ping_url,$idnum) ){
			$tb_id = intval($idnum[1]);
		} elseif ( preg_match("/([0-9]+)\.trackback/",$ping_url,$idnum) ){
			$tb_id = intval($idnum[1]);
		} elseif ( preg_match("/itemid=([0-9]+)/",$ping_url,$idnum) ){
			$tb_id = intval($idnum[1]);
		}

		if ((!$manager->existsItem($tb_id,0,0)) && ($this->getOption('CheckIDs') == 'yes'))
			return _ERROR_NOSUCHITEM . "[ $tb_id ]";
			
		// save data in the DB
		$query = 'INSERT INTO ' . sql_table('plugin_tb_lc') . " (tb_id, from_id) VALUES ('".intval($tb_id)."','".intval($itemid)."')";
		$res = @mysql_query($query);
		if (!$res) 
			return 'Could not save trackback data, possibly because of a double entry: ' . mysql_error();
	}
	
	/**
	  * Show the list of TrackBack pings for a certain Trackback ID
	  */
	function showLocalList($tb_id) {
		global $CONF, $manager;
		
		// create SQL query
		$query = 'SELECT t.from_id as from_id , i.ititle as ititle, i.ibody as ibody, i.itime as itime, i.iblog as iblog FROM '.sql_table('plugin_tb_lc').' as t, '.sql_table('item').' as i WHERE t.tb_id='.intval($tb_id) .' and i.inumber=t.from_id ORDER BY i.itime DESC';
		$res = mysql_query($query);
		
		$vars = array(
			'tburl' => $this->getTrackBackUrl($tb_id)
		);

		// when no TrackBack pings are found
		if (!$res || mysql_num_rows($res) == 0) {
			echo TEMPLATE::fill($this->getOption('tplLocalEmpty'), $vars);
			return;
		}
		
		// when TrackBack pings are found
		echo TEMPLATE::fill($this->getOption('tplLocalHeader'), $vars);
		
		while ($o = mysql_fetch_object($res)) {
			$canDelete = $this->canDelete($tb_id);
			$data = array(
				'url' => createItemLink($o->from_id),
				'blogname' => htmlspecialchars(getBlogNameFromID($o->iblog)),
				'timestamp' => strftime('%Y-%m-%d',strtotime($o->itime)),
				'title' => htmlspecialchars($o->ititle),
				'excerpt' => htmlspecialchars(shorten(strip_tags($o->ibody),200,'...')),
				'delete' => $canDelete?'<a href="'. $manager->addTicketToUrl($CONF['ActionURL'].'?action=plugin&amp;name=TrackBack&amp;type=deletelc&amp;tb_id='.intval($tb_id).'&amp;from_id='.intval($o->from_id)).'">[delete]</a>':'',
				'tburl' => $this->getTrackBackUrl($tb_id),
				'commentcount'=> quickQuery('SELECT COUNT(*) as result FROM '.sql_table('comment').' WHERE citem=' . intval($o->from_id))
			);
			echo TEMPLATE::fill($this->getOption('tplLocalItem'), $data);
		}
		echo TEMPLATE::fill($this->getOption('tplLocalFooter'), $vars);
	}
	
	/**
	  * Delete a TrackBack item, redirect to referer
	  */
	function deleteLocal($tb_id, $from_id) {
		if (!$this->canDelete($tb_id))
			return 'You\'re not allowed to delete this trackback item';
		$query = 'DELETE FROM ' . sql_table('plugin_tb_lc') . " WHERE tb_id='" . intval($tb_id) . "' and from_id='" . intval($from_id) ."'";
		mysql_query($query);
		return '';
	}
	
	function canDelete($tb_id) {
		global $member, $manager;
		
		if ( ! $member->isLoggedIn() ) return 0;
		
		$checkIDs = $this->getOption('CheckIDs');
		$itemExists =& $manager->existsItem($tb_id,0,0);
		
		// if CheckIDs option is set, check if member canEdit($tb_id)
		// if CheckIDs option is not set, and item exists, check if member canEdit($tb_id)
		// if CheckIDs option is not set, and item does not exists, check if member isAdmin()
		
		if (($checkIDs == 'yes') || ($itemExists))
			return $member->canAlterItem($tb_id);
		else
			return $member->isAdmin();
	}

		/**************************************************************************************/
		/* Plugin API calls, for installation, configuration and setup                        */
	
		function getName()   	  { 		return 'TrackBack';   }
		function getAuthor() 	  { 		return 'rakaz + nakahara21 + hsur'; }
		function getURL()    	  { 		return 'http://blog.cles.jp/np_cles/category/31/subcatid/3'; }
		function getVersion()	  { 		return '2.0.3 jp7'; }
		function getDescription() { 		return _TB_DESCRIPTION; }
	
//modify start+++++++++
/*
		function getTableList()   { 		return array(sql_table("plugin_tb"), sql_table("plugin_tb_lookup")); }
		function getEventList()   { 		return array('QuickMenu','PostAddItem','AddItemFormExtras','EditItemFormExtras','PreUpdateItem','PrepareItemForEdit', 'BookmarkletExtraHead'); }
*/
		function getTableList()   { 		return array(sql_table("plugin_tb"), sql_table("plugin_tb_lookup"), sql_table('plugin_tb_lc')); }

		function getEventList()   { 		return array('QuickMenu','PostAddItem','AddItemFormExtras','EditItemFormExtras','PreUpdateItem','PrepareItemForEdit', 'BookmarkletExtraHead', 'RetrieveTrackback', 'SendTrackback', 'InitSkinParse'); }
//modify end+++++++++
		function getMinNucleusVersion() {	return 200; }
	
		function supportsFeature($feature) {
			switch($feature) {
				case 'SqlTablePrefix':
					return 1;
//modify start+++++++++
//				case 'HelpPage':
//					return 1;
//modify end+++++++++
				default:
					return 0;
			}
		}

	
		function hasAdminArea() { 			return 1; }

		function event_QuickMenu(&$data) {
			global $member, $nucleus, $blogid;
			
			// only show to admins
			if (!$member->isLoggedIn() || !$member->isAdmin()) return;

			array_push(
				$data['options'],
				array(
					'title' => 'Trackback',
					'url' => $this->getAdminURL(),
					'tooltip' => 'Manage your trackbacks'
				)
			);
		}
			
		function install() {
			$this->createOption('AcceptPing',  _TB_AcceptPing,'yesno','yes');
			$this->createOption('SendPings',   _TB_SendPings,'yesno','yes');
			$this->createOption('AutoXMLHttp', _TB_AutoXMLHttp, 'yesno', 'yes');
			$this->createOption('CheckIDs',	   _TB_CheckIDs,'yesno','yes');

			$this->createOption('tplHeader',   _TB_tplHeader, 'textarea', _TB_tplHeader_VAL);
			$this->createOption('tplEmpty',	   _TB_tplEmpty, 'textarea', _TB_tplEmpty_VAL);
			$this->createOption('tplItem',	   _TB_tplItem, 'textarea', _TB_tplItem_VAL);
			$this->createOption('tplFooter',   _TB_tplFooter, 'textarea', _TB_tplFooter_VAL);
//mod by cles
			$this->createOption('tplLocalHeader',   _TB_tplLocalHeader, 'textarea', _TB_tplLocalHeader_VAL);
			$this->createOption('tplLocalEmpty',	   _TB_tplLocalEmpty, 'textarea', _TB_tplLocalEmpty_VAL);
			$this->createOption('tplLocalItem',	   _TB_tplLocalItem, 'textarea', _TB_tplLocalItem_VAL);
			$this->createOption('tplLocalFooter',   _TB_tplLocalFooter, 'textarea', _TB_tplLocalFooter_VAL);
//mod by cles end

			$this->createOption('tplTbNone',   _TB_tplTbNone, 'text', "No Trackbacks");
			$this->createOption('tplTbOne',    _TB_tplTbOne, 'text', "1 Trackback");
			$this->createOption('tplTbMore',   _TB_tplTbMore, 'text', "<%number%> Trackbacks");
			$this->createOption('dateFormat',  _TB_dateFormat, 'text', _TB_dateFormat_VAL);
	
			$this->createOption('Notify',	   _TB_Notify,'yesno','no');
			$this->createOption('NotifyEmail', _TB_NotifyEmail,'text','');	

			$this->createOption('DropTable',   _TB_DropTable,'yesno','no');
//mod by cles
			$this->createOption('HideUrl',_TB_HideUrl,'yesno','yes');
			$this->createItemOption('ItemAcceptPing',_TB_ItemAcceptPing,'yesno','yes');
			$this->createItemOption('isAcceptW/OLink',_TB_isAcceptWOLink,'select','default', _TB_isAcceptWOLink_VAL);

			$this->createBlogOption('isAcceptW/OLinkDef',_TB_isAcceptWOLinkDef,'select','block', _TB_isAcceptWOLinkDef_VAL);
			$this->createBlogOption('AllowTrackBack',_TB_AllowTrackBack,'yesno','yes');
//mod by cles end

			/* Create tables */
			mysql_query("
				CREATE TABLE IF NOT EXISTS 
					".sql_table('plugin_tb')."
				(
					`id`        INT(11)         NOT NULL       AUTO_INCREMENT,
					`tb_id`     INT(11)         NOT NULL, 
					`url`       TEXT            NOT NULL, 
					`block`     TINYINT(4)      NOT NULL, 
					`spam`      TINYINT(4)      NOT NULL, 
					`link`      TINYINT(4)      NOT NULL, 
					`title`     TEXT, 	
					`excerpt`   TEXT, 
					`blog_name` TEXT, 
					`timestamp` DATETIME, 
					
					PRIMARY KEY (`id`)
				)
			");
						
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
//modify start+++++++++
			@mysql_query('ALTER TABLE `' . sql_table('plugin_tb') . '` ADD INDEX `tb_id_block_timestamp_idx` ( `tb_id`, `block`, `timestamp` DESC )');
			@mysql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_tb_lc'). ' (tb_id int(11) not null, from_id int(11) not null, PRIMARY KEY (tb_id,from_id))');
//modify end+++++++++
		}
	
		function uninstall() {
			if ($this->getOption('DropTable') == 'yes') {
	 			mysql_query ('DROP TABLE '.sql_table('plugin_tb'));
				mysql_query ('DROP TABLE '.sql_table('plugin_tb_lookup'));
				mysql_query ("DROP table ".sql_table('plugin_tb_lc'));
			}
		}

		function init() {
      // include language file for this plugin 
      $language = ereg_replace( '[\\|/]', '', getLanguageName()); 
      if (file_exists($this->getDirectory().'language/'.$language.'.php')) 
         include_once($this->getDirectory().'language/'.$language.'.php'); 
      else 
         include_once($this->getDirectory().'language/'.'english.php'); 
		$this->notificationMail = _TB_NORTIFICATION_MAIL_BODY;
		$this->notificationMailTitle = _TB_NORTIFICATION_MAIL_TITLE;
         }
	}

?>
