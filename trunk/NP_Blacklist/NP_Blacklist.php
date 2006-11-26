<?php

/*                                                                                */
/* NP_Blacklist                                                                   */
/* ------------------------------------                                           */
/* version information ----------------                                           */
/* 0.90 initial release                                                           */
/* 0.91 issues with xhtml compliance. sloppy coding removed                       */
/* 0.92 added user, userid and host to check for spam                             */
/* 0.93 bug in fetching fresh blacklist solved                                    */
/* 0.94 code cleanup,no more pivot specific functions and files                   */
/* 0.952 added the posibility to block on the referrerfield against the same lists
         added the option to ip-ban the commenting machine (commented out! with //ip
         remove all '//ip' if you want to checkout this functionality.
         personally i don't like it -xiffy-
*/
/* 0.95b2 removed ip-ban option.
          added the yet non-existent event PreActioAddComment to kick in at the right moment
          and not 'too late'. Solves emailnotification problem on adding comments
*/
/* 0.95 final
        removed the option to have a different url for referrer spamming. This will grow wild
        if more spam-blocking types (like trackback) will be introduced.
        So 1 url to serve them all.
        the function blacklist is from now on the 1 function to call from other plugins
        to call blacklist from inside your plugin add the following code:
--deleted obsolete call for blacklist --
*/
/* 0.96 Beta
        added ip-based blocking. This option differs from earlier attempts to add the ip to the nucleus ip-ban
        Now, wehn a machine spam your website above the ip-block-threshold (default 10) the machine will be added
        to the blocked ip addresses table. This way, newly undiscovered spamming domains won't be showing up
        easily since most spamming is done by a subset of machines (zombies)
        added menu item to maintain blocked ip-addresses.
*/
/* 0.96 Beta 2
        .htaccess snippets work. Thanks to Karma for his regexp reworke
        there are two modes, one for blocked IP's and one for matched rules, each give a different kind of output
        Once you've generated the rules and incorporated the finished result into your .htaccess you should Reset the file.
        Otherwise you would end up with doubles inside your .htaccess, this should be avoided, but is completly acceptable for apache.
*/
/* 0.96 Beta 3
        Plugins calling plugins. Rakaz and I think we made it happen on a way that is future prove and a proof of concept for
        other plugin writers. This plugin listens to the event SpamCheck, which is unknown inside nucleus-core.
        NP_MailToAFriend, NP_Trackback and Referrer2 call this plugin if it is installed. It handles redirection itself.
        The easy way:
// check for spam attempts, you never knnow !
    $spamcheck = array ('type'  => 'MailtoaFriend',
                        'data'  => $extra."\n".$toEmail."\n".$fromEmail);
    $manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck));
// done
        The hard way and Total Control!
    $spamcheck = array (
        'type'  => 'Referer',
        'data'  => 'data that needs to be checked',
        'return'  => true
    );

    $manager->notify('SpamCheck',
        array ('spamcheck' => & $spamcheck)
    );

    if (isset($spamcheck['result']) &&
        $spamcheck['result'] == true)
    {
        // Handle spam
    }
*/
/* 0.97 Added eventHandler for the new ValidateForm event (nucleus 3.2)
*/
/* 0.98 Solved naar.be bug
*/

include_once(dirname(__FILE__)."/blacklist/blacklist_lib.php");

class NP_Blacklist extends NucleusPlugin {
	function getName() 		  { return 'Blacklist'; }
	function getAuthor()  	  { return 'xiffy + cles'; }
	function getURL()  		  { return 'http://blog.cles.jp/np_cles/category/31/subcatid/11'; }
	function getVersion() 	  { return '0.98 jp9'; }
	function getDescription() { return 'Blacklist for commentspammers (SpamCheck API 2.0 compatible)';	}
	function supportsFeature($what) {
		switch($what) {
		    case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

    function install() {
        // create some options
        $this->createOption('enabled','Blacklist engine enabled?','yesno','yes');
        $this->createOption('redirect','To which URL should spammers be redireted?','text','');
//        $this->createOption('update','From which URL should we get a fresh blacklist copy?', 'text','');
        $this->createOption('referrerblock','Enable referrer based blocking?','yesno','no');
        $this->createOption('ipblock','Enable ip based blocking?','yesno','yes');
        $this->createOption('ipthreshold','','text','10');
		$this->createOption('BulkfeedsKey', 'Bulkfeeds API Key', 'text', '');
		$this->createOption('SkipNameResolve', 'Skip reverse DNS lookup ?', 'yesno','yes');
		
		$this->_initSettings();
    }

	function unInstall() {}

    function getPluginOption ($name) {
        return $this->getOption($name);
    }

	function getEventList() {
		$this->_initSettings();
		return array('QuickMenu','PreAddComment','ValidateForm', 'SpamCheck');
	}

	function hasAdminArea() {
		return 1;
	}
	
	function init(){
		$this->resultCache = false;
	}

	function event_QuickMenu(&$data) {
		global $member, $nucleus, $blogid;
		// only show to admins
		if (preg_match("/MD$/", $nucleus['version'])) {
			$isblogadmin = $member->isBlogAdmin(-1);
		} else {
			$isblogadmin = $member->isBlogAdmin($blogid);
		}
		if (!($member->isLoggedIn() && ($member->isAdmin() | $isblogadmin))) return;
		array_push(
			$data['options'],
			array(
				'title' => 'Blacklist',
				'url' => $this->getAdminURL(),
				'tooltip' => 'Manage your blacklist'
			)
		);
	}

    // for other plugin writers ...
    function event_SpamCheck (&$data) {
        global $DIR_PLUGINS;
//        $fp  = fopen ($DIR_PLUGINS."blacklist/settings/debug.txt", 'a');
//        fwrite($fp,"==called ==\n");
//        fwrite($fp,'type : ' .$data['spamcheck']['type']."\n");
//        fwrite($fp,'data : ' .$data['spamcheck']['data']."\n");
//        fclose($fp);
        if (isset($data['spamcheck']['result']) && $data['spamcheck']['result'] == true){
            // Already checked... and is spam
            return;
        }
		
		if( ! isset($data['spamcheck']['return']) ){
			$data['spamcheck']['return'] = true;
		}
		
		// for SpamCheck API 2.0 compatibility
		if( ! $data['spamcheck']['data'] ){
			switch( strtolower($data['spamcheck']['type']) ){
				case 'comment':
					$data['spamcheck']['data']  = $data['spamcheck']['body'] . "\n";
					$data['spamcheck']['data'] .= $data['spamcheck']['author'] . "\n";
					$data['spamcheck']['data'] .= $data['spamcheck']['url'] . "\n"; 
					break;
				case 'trackback':
					$data['spamcheck']['data']  = $data['spamcheck']['title']. "\n"; 
					$data['spamcheck']['data'] .= $data['spamcheck']['excerpt']. "\n";
					$data['spamcheck']['data'] .= $data['spamcheck']['blogname']. "\n";
					$data['spamcheck']['data'] .= $data['spamcheck']['url'];
					break;
				case 'referer':
					$data['spamcheck']['data'] = $data['spamcheck']['url'];
					break;
			}
		}
		$ipblock = ( $data['spamcheck']['ipblock'] ) || ($data['spamcheck']['live']);
		
        // Check for spam
        $result = $this->blacklist($data['spamcheck']['type'], $data['spamcheck']['data'], $ipblock);

        if ($result) {
            // Spam found
            // logging !
            pbl_logspammer($data['spamcheck']['type'].': '.$result);
            if  (isset($data['spamcheck']['return']) && $data['spamcheck']['return'] == true) {
                // Return to caller
                $data['spamcheck']['result'] = true;
                return;
            } else {
                $this->_redirect($this->getOption('redirect'));
            }
        }
    }

    // will become obsolete when nucleus is patched ...
	function event_PreAddComment(&$data) {
	    $comment = $data['comment'];
		$result = $this->blacklist('comment',postVar('body')."\n".$comment['host']."\n".$comment['user']."\n".$comment['userid']);
        if ($result) {
            pbl_logspammer('comment: '.$result);
            $this->_redirect($this->getOption('redirect'));
        }
    }

	function event_ValidateForm(&$data) {
		if( $data['type'] == 'comment' ){
		    $comment = $data['comment'];
			$result = $this->blacklist('comment',postVar('body')."\n".$comment['host']."\n".$comment['user']."\n".$comment['userid']);
	        if ($result) {
	            pbl_logspammer('comment: '.$result);
	            $this->_redirect($this->getOption('redirect'));
	        }
		} else if( $data['type'] == 'membermail' ){
			$result = $this->blacklist('membermail',postVar('frommail')."\n".postVar('message'));
			if ($result) {
				pbl_logspammer('membermail: '.$result);
				$this->_redirect($this->getOption('redirect'));
			}
		}
    }

	// preskinparse will check the referrer for spamming attempts
	// only when option enabled !
	// logging also only when option enabled ...
	function event_PreSkinParse(&$data) {
        $result = $this->blacklist('PreSkinParse','');
        if ($result) {
            pbl_logspammer('PreSkinParse: '.$result);
            $this->_redirect($this->getOption('redirect'));
        }
	}

	function blacklist($type, $testString, $ipblock = true) {
        global $DIR_PLUGINS, $member;
		if( $this->resultCache )
			return $this->resultCache . '[Cached]';
		
		if( $member->isLoggedIn() ){
			return '';
		}
		
	    if ($this->getOption('enabled') == 'yes') {
            // update the blacklist first file
            //pbl_updateblacklist($this->getOption('update'),false);
            if ($ipblock) {
                $ipblock = ( $this->getOption('ipblock') == 'yes' ) ? true : false ;
            }
			
			$result = '';
            if ($this->getOption('referrerblock') == 'yes')  {
				$refer = parse_url(serverVar('HTTP_REFERER'));
                $result = pbl_checkforspam($refer['host']."\n".$testString, $ipblock , $this->getOption('ipthreshold'), true);
            } elseif ($ipblock || $testString != '') {
                $result = pbl_checkforspam($testString, $ipblock, $this->getOption('ipthreshold'), true);
            }
			
			if( $result ){
				$this->resultCache = $result;
			}
			
			return $result;
        }
    }
	
	function submitSpamToBulkfeeds($url) {
		if( is_array($url) ) $url = implode("\n", $url);
		
		$postData['apikey'] = $this->getOption('BulkfeedsKey');
		if( ! $postData['apikey'] ) return "BulkfeedsKey not found. see http://bulkfeeds.net/app/register_api.html";
		$postData['url'] = $url;
		
		$data = $this->_http('http://bulkfeeds.net:80/app/submit_spam.xml', 'POST', '', $postData);
		//preg_match('#<result>([^<]*)</result>#mi', $data, $matches);
		//$result = trim($matches[1]);
		
		return $data;
	}
	
	function _http($url, $method = "GET", $headers = "", $post = array ("")) {
		$URL = parse_url($url);

		if (isset ($URL['query'])) {
			$URL['query'] = "?".$URL['query'];
		} else {
			$URL['query'] = "";
		}

		if (!isset ($URL['port']))
			$URL['port'] = 80;

		$request = $method." ".$URL['path'].$URL['query']." HTTP/1.0\r\n";

		$request .= "Host: ".$URL['host']."\r\n";
		$request .= "User-Agent: PHP/".phpversion()."\r\n";

		if (isset ($URL['user']) && isset ($URL['pass'])) {
			$request .= "Authorization: Basic ".base64_encode($URL['user'].":".$URL['pass'])."\r\n";
		}

		$request .= $headers;

		if (strtoupper($method) == "POST") {
			while (list ($name, $value) = each($post)) {
				$POST[] = $name."=".urlencode($value);
			}
			$postdata = implode("&", $POST);
			$request .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$request .= "Content-Length: ".strlen($postdata)."\r\n";
			$request .= "\r\n";
			$request .= $postdata;
		} else {
			$request .= "\r\n";
		}

		$fp = fsockopen($URL['host'], $URL['port'], $errno, $errstr, 20);

		if ($fp) {
			socket_set_timeout($fp, 20);
			fputs($fp, $request);
			$response = "";
			while (!feof($fp)) {
				$response .= fgets($fp, 4096);
			}
			fclose($fp);
			$DATA = split("\r\n\r\n", $response, 2);
			return $DATA[1];
		} else {
			$host = $URL['host'];
			$port = $URL['port'];
			ACTIONLOG :: add(WARNING, $this->getName().':'."[$errno]($host:$port) $errstr");
			return "";
		}
	}

	function _spamMark($word){
		$_GET["expression"] = preg_quote($word, '/');
		$_GET["comment"] = 'SpamMark [' . date("Y/m/d H:i:s") . ']';

		$existTest = pbl_checkforspam(getVar("expression"));
		if (! (strlen($existTest) > 0))  {
			pbl_addexpression();
		}
	}
	
	function _redirect($url) {
		if( !$url ){
			header("HTTP/1.0 403 Forbidden");
			header("Status: 403 Forbidden");
			
			include(dirname(__FILE__).'/blacklist/blocked.txt');
		} else {
			$url = preg_replace('|[^a-z0-9-~+_.?#=&;,/:@%]|i', '', $url);
			header('Location: ' . $url);
		}
		exit;
	}
	
	function _initSettings(){
		$settingsDir = dirname(__FILE__).'/blacklist/settings/';
		$settings = array(
			'blacklist.log',
			'blockip.pbl',
			'matched.pbl',
			'blacklist.pbl',
			'blacklist.txt',
			'suspects.pbl',
		);
		$personalBlacklist = $settingsDir . 'personal_blacklist.pbl';
		$personalBlacklistDist = $settingsDir . 'personal_blacklist.pbl.dist';

		// setup settings
		if( $this->_is_writable($settingsDir) ){
			foreach($settings as $setting ){
				touch($settingsDir.$setting);
			}
			// setup personal blacklist
			if( ! file_exists($personalBlacklist) ){
				if( copy( $personalBlacklistDist , $personalBlacklist ) ){
					$this->_warn("'$personalBlacklist' created.");
				} else {
					$this->_warn("'$personalBlacklist' cannot create.");
				}
			}
		}
	
		// check settings	
		foreach($settings as $setting ){
			$this->_is_writable($settingsDir.$setting);
		}			
		$this->_is_writable($personalBlacklist);
		
		// setup and check cache dir
		$cacheDir = NP_BLACKLIST_CACHE_DIR;
		$this->_is_writable($cacheDir);
	}
	
	function _is_writable($file){
		$ret = is_writable($file);
		if( ! $ret ){
			$this->_warn("'$file' is not writable.");
		}
		return $ret;
	}
	
	function _warn($msg) {
		ACTIONLOG :: add(WARNING, 'Blacklist: '.$msg);
	}
	
}
?>
