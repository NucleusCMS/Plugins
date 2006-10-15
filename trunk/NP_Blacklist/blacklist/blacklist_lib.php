<?php
// Pivot-Blacklist version 0.4 (with Nucleus Support!)
//
// A simple (but effective) spam blocker based on the MT-Blacklist
// available at: http://www.jayallen.org/comment_spam/
//
// Includes realtime blacklist check functions by
// John Sinteur (http://weblog.sinteur.com/)
//
// This code (c) 2004 by Marco van Hylckama Vlieg
//                    adapted and extended by Appie Verschoor
// License is GPL, just like Pivot / Nucleus
//
// http://www.i-marco.nl/
// marco@i-marco.nl
//
// http://xiffy.nl/
// blacklist@xiffy.nl

define('__WEBLOG_ROOT', dirname(dirname(realpath(__FILE__))));
define('__EXT', '/blacklist');

define('NP_BLACKLIST_CACHE_DIR', dirname(__FILE__).'/cache');
define('NP_BLACKLIST_CACHE_LIFE', 86400);
define('NP_BLACKLIST_CACHE_GC_INTERVAL', NP_BLACKLIST_CACHE_LIFE/8);
define('NP_BLACKLIST_CACHE_GC_TIMESTAMP', 'gctime');
define('NP_BLACKLIST_CACHE_GC_TIMESTAMP_LIFE', NP_BLACKLIST_CACHE_LIFE*3);
//require_once(dirname(__FILE__).'/cache_file.php');
require_once(dirname(__FILE__).'/cache_eaccelerator.php');

function pbl_getconfig()  {
    global $pbl_config;
   	$pbl_config = array();
    $pbl_config['enabled']  = getPluginOption('enabled');
    $pbl_config['redirect'] = getPluginOption('redirect');
    //$pbl_config['update']   = getPluginOption('update');
    // convert 'yes' into '1'
    if ($pbl_config['enabled'] == 'yes') {$pbl_config['enabled'] = 1;}
	return $pbl_config;
}

function pbl_checkforspam($text, $ipblock = false, $ipthreshold = 10, $logrule = true)  {
	// check whether a string contains spam
	// if it does, we return the rule that was matched first
	//$text = strtolower($text);
	$text = trim($text);

    // first line of defense; block notorious spammers
    if ($ipblock) {
        if (pbl_blockIP()) {
            return "<b>IP Blocked</b>: ".serverVar('REMOTE_ADDR')." (".serverVar('REMOTE_HOST').")";
        }
    }
	// second line of defense: Check whether our poster is using
	// an open proxy
	//if(check_for_open_proxy())  {
    //    if ($ipblock == 'yes') {
    //        pbl_suspectIP ($ipthreshold);
    //    }
	//	return "open spam proxy";
	//}

	// third line of defense: Check whether our poster promotes
	// known spamsite url's listed at www.surbl.org
	//if(check_for_surbl($text))	{
    //    if ($ipblock == 'yes') {
    //        pbl_suspectIP ($ipthreshold);
    //    }
	//	return("url(s) listed on www.surbl.org found");
	//}

	// fourth line of defense: Run the MT-Blacklist check
	if( $text && file_exists(__WEBLOG_ROOT.__EXT."/settings/blacklist.pbl") ){	
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/blacklist.pbl", "r");
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$splitbuffer = explode("####", $buffer);
			$expression = $splitbuffer[0];
			$explodedSplitBuffer = explode("/", $expression);
			$expression = $explodedSplitBuffer[0];
			if (strlen($expression) > 0)  {
				if(preg_match("/".trim($expression)."/im", $text))  {
	                if ($ipblock) {
	                    pbl_suspectIP ($ipthreshold);
	                }
	                if ($logrule) {
	                    pbl_logRule($expression);
	                }
					return $expression;
				}
			}
		}
		fclose($handle);
	}

	// fifth line of defense: run the personal blacklist entries
	if ($text &&file_exists(__WEBLOG_ROOT.__EXT.'/settings/personal_blacklist.pbl'))  {
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl", "r");
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$splitbuffer = explode("####", $buffer);
			$expression = $splitbuffer[0];
		    if (strlen($expression) > 0)  {
//    		    if(is_domain($expression))  {
//	    		    $expression = str_replace(".","\.",$expression);
//		        }
			    if(preg_match("/".trim($expression)."/im", $text))  {
                    if ($ipblock) {
                        pbl_suspectIP ($ipthreshold);
                    }
                    if ($logrule) {
                        pbl_logRule($expression);
                    }
					fclose($handle);
				    return $expression;
				}
			}
		}
		fclose($handle);
	}

	if( $ipblock && $listedrbl = check_for_iprbl() )  {
		pbl_suspectIP ($ipthreshold);
		$ref = serverVar('HTTP_REFERER');
		return "ip listed on {$listedrbl[0]} found (Referer:{$ref})";
	}

	if( $text && ($listedrbl = check_for_domainrbl($text)) ) {
        if ($ipblock) {
            pbl_suspectIP ($ipthreshold);
        }
		return("url(s) listed on {$listedrbl[0]} ({$listedrbl[1]}) found");
	}

	// w00t! it's probably not spam!
	return "";
}

function pbl_updateblacklist($url, $force=false)  {
/*
	$listAge = time() - @filemtime(__WEBLOG_ROOT.__EXT.'/settings/blacklist.txt');
	// 86400 is 24hours (24*60*60)
	if ((($listAge > 86400 ) || (!file_exists(__WEBLOG_ROOT.__EXT.'/settings/blacklist.txt'))) || ($force))  {
		$handle = @fopen($url, "r");
		if ($handle) {
		    while (!feof($handle)) {
			    $buffer = fgets($handle, 4096);
    			$newBlackList .= $buffer;
    		}
    		fclose($handle);
    	}

		// Check whether we really have the file
		// if not we keep the old one because we don't want to break
		// the engine with a bad or missing file

		if(strstr($newBlackList, "MT-Blacklist Master Copy"))  {
			$newFile = fopen(__WEBLOG_ROOT.__EXT.'/settings/blacklist.txt', 'w');
			fwrite($newFile, $newBlackList);
			fclose($newFile);
			pbl_processblacklist();
		}
	}
*/
	return true;
}

/*
function pbl_processblacklist()  {
	// reformat the list to match our own format
	$listString = "";
	$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/blacklist.txt", "r")  or die ("could not open: ".__WEBLOG_ROOT.__EXT."/settings/blacklist.txt");
	while (!feof($handle)) {
		$buffer = fgets($handle, 4096);
		$splitbuffer = explode("#", $buffer);
		$expression = $splitbuffer[0];
		$explodedSplitBuffer = explode("/", $expression);
		$expression = $explodedSplitBuffer[0];
		if (strlen($expression) > 0)  {
			$listString .= preg_replace("/([^\\\|^_]|^)\./",'$1\\.',trim($expression));
			if(strlen($splitbuffer[1]) > 5)  {
				$listString .= " #### ".trim($splitbuffer[1]);
			}
			$listString .= "\n";
		}
	}
	fclose($handle);
	if(file_exists(__WEBLOG_ROOT.__EXT.'/settings/blacklist.pbl'))  {
	}
	$newhandle = fopen(__WEBLOG_ROOT.__EXT."/settings/blacklist.pbl", "w");
	fwrite($newhandle, $listString);
	fclose($newhandle);
}
*/

function is_domain($stheDomain) {
	return ( (strpos($stheDomain,"\\")==0) && (strpos($stheDomain,"[")==0) && (strpos($stheDomain, "(")==0) );
}


function pbl_nucmenu() {
   	echo "<h2>Blacklist menu</h2>\n";
	echo "<ul>\n";
	echo "<li><a href=\"".serverVar('PHP_SELF')."?page=blacklist\"><img src=\"".dirname(serverVar('PHP_SELF'))."/icons/i_edit.gif\" /> Blacklist Editor</a></li>\n";
	echo "<li><a href=\"".serverVar('PHP_SELF')."?page=log\"><img src=\"".dirname(serverVar('PHP_SELF'))."/icons/i_log.gif\" /> Blacklist Log</a></li>\n";
	echo "<li><a href=\"".dirname(serverVar('PHP_SELF'))."/../../index.php?action=pluginoptions&amp;plugid=".getPlugid()."\"><img src=\"".dirname(serverVar('PHP_SELF'))."/icons/i_prefs.gif\" /> Blacklist options</a></li>\n";
	echo "<li><a href=\"".serverVar('PHP_SELF')."?page=testpage\"><img src=\"".dirname(serverVar('PHP_SELF'))."/icons/i_edit.gif\" /> Test Blacklist</a></li>\n";
	echo "<li><a href=\"".serverVar('PHP_SELF')."?page=showipblock\"><img src=\"".dirname(serverVar('PHP_SELF'))."/icons/i_log.gif\" /> Show blocked ip addresses</a></li>\n";
	echo "<li><a href=\"".serverVar('PHP_SELF')."?page=htaccess\"><img src=\"".dirname(serverVar('PHP_SELF'))."/icons/i_edit.gif\" /> Generate .htaccess snippets</a></li>\n";
	echo "<li><a href=\"".serverVar('PHP_SELF')."?page=spamsubmission\"><img src=\"".dirname(serverVar('PHP_SELF'))."/icons/i_edit.gif\" /> Spam submission (Bulkfeeds)</a></li>\n";
	echo "</ul>\n";
}

function pbl_blacklisteditor()  {

	global $pblmessage;

	if(strlen($pblmessage) > 0)  {
		echo "<div class=\"pblmessage\">$pblmessage</div>\n";
	}

/*
	echo "<div id=\"jayallen\">\n";
	echo "<div class=\"pbldescription\">";
	if(!file_exists(__WEBLOG_ROOT.__EXT."/settings/blacklist.pbl"))  {
		echo "You don't have a blacklist file yet!<br />";
		echo "Click the button below to get the latest MT-Blacklist from Jay Allen's site.";
		echo "</div>";
		echo "<div class=\"pbform\">\n";
		echo "<form action=\"index.php\" method=\"get\">\n";
		echo "<input type=\"hidden\" name=\"page\" value=\"getblacklist\" />\n";
		echo "<input type=\"submit\" value=\"Download and install\" />\n";
		echo "</form>\n";
		echo "</div>\n";
	}
	else  {
		$updatetime = @filemtime(__WEBLOG_ROOT.__EXT."/settings/blacklist.txt");
		echo "Your MT-Blacklist file was last updated at: ";
		echo date("Y/m/d H:i:s", $updatetime)." <br />";
#		echo date("F d Y H:i", $updatetime)." <br />";
		echo "It's updated automatically every day but you can click below to update it immediately";
		echo "</div>\n";
		echo "<div class=\"pbform\" style=\"margin-left:10px;\">\n";
		echo "<form action=\"".serverVar('PHP_SELF')."\" method=\"get\">\n";
		echo "<input type=\"hidden\" name=\"page\" value=\"getblacklist\" />\n";
		echo "<input type=\"submit\" value=\"Update now\" />\n";
		echo "</form>\n";
		echo "</div>\n";
	}
	echo "</div>\n";
*/
	echo "<div id=\"personal\">\n";
	echo "<div class=\"pbldescription\">";
	echo "You can add url's, regular expressions or words to your personal blacklist below.";
	echo "</div>\n";
	echo "<div class=\"pbform\">\n";
	echo "<form action=\"".serverVar('PHP_SELF')."\" method=\"get\">\n";
	echo "<input type=\"hidden\" name=\"page\" value=\"addpersonal\" />\n";
	echo "<table class=\"pblform\">\n";
	echo "<tr>\n";
	echo "<td>expression</td>\n";
	echo "<td><input class=\"pbltextinput\" type=\"text\" name=\"expression\" /></td>\n";
	echo "</tr>\n";
	echo "<tr>";
	echo "  <td>comment</td>\n";
	echo "  <td><input class=\"pbltextinput\" type=\"text\" name=\"comment\" /></td>\n";
	echo "</tr>\n";
	echo "<tr>";
	echo "  <td>enable regular expressions ?</td>\n";
	echo "  <td><input class=\"pbltextinput\" type=\"checkbox\" name=\"enable_regex\" value=\"1\" /></td>\n";
	echo "</tr>\n";
	echo "<tr><td colspan=\"2\" style=\"border:none;\"><input type=\"submit\" value=\"Add\" /></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</form>\n";
	echo "</div>\n";
	echo "<div class=\"pbldescription\">Below is your personal blacklist</div>\n";
	if (file_exists(__WEBLOG_ROOT.__EXT.'/settings/personal_blacklist.pbl'))  {
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl", "r");
		echo "<table>\n";
		echo "<tr>\n";
		echo "<th>expression</th>\n";
		echo "<th>comment</th>\n";
		echo "<th>deletion</th>\n";
		echo "</tr>\n";
		$line = 0;
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$line++;
			$configParam = explode("####", $buffer);
			$key = $configParam[0];
			$value = $configParam[1];
			if(strlen($key) > 0)  {
				echo "<tr>\n";
				echo "<td>".htmlspecialchars($key,ENT_QUOTES)."</td>\n";
				echo "<td>".htmlspecialchars($value,ENT_QUOTES)."</td>\n";
				echo "<td>";
				echo "<a href=\"".serverVar('PHP_SELF')."?page=deleteexpression&amp;line=".$line."\">delete</a>";
				echo "</td>";
				echo "</tr>\n";
			}
		}
		echo "</table>\n";
	}
}
function pbl_deleteexpression()  {
	if(isset($_GET["line"]))  {
		if( ! is_writable(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl") ){
			echo "Error: personal_blacklist.pbl is not writable. ";
		}
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl", "r");
		$line = 0;
		$newFile = "";
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$line++;
			if($line != getVar("line"))  {
				$newFile .= $buffer;
			}
		}
		fclose($handle);
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl", "w");
		fwrite($handle, $newFile);
		fclose($handle);
	}
}
function pbl_addexpression($expression, $comment)  {
	if(strlen($expression) > 0)  {
		if( ! is_writable(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl") ){
			echo "Error: personal_blacklist.pbl is not writable. ";
		}
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl", "a");
		if(strlen($comment) > 0)  {
				$expression = $expression." #### ".$comment;
		}
		fwrite($handle, $expression."\n");
		fclose($handle);
	}
}

$g_reOk = false;
function _hdl($errno, $errstr) {
	global $g_reOk;
	$g_reOk = false;
}

function pbl_checkregexp($re) {
	// Thanks to 'OneOfBorg' on Gathering Of Tweakers
	// http://gathering.tweakers.net/forum/user_profile/109376
	global $g_reOk;
	$g_reOk = true;
	set_error_handler("_hdl");
	preg_match("/".trim($re)."/im", "");
	restore_error_handler();
	return $g_reOk;
}

function pbl_addpersonal()  {
	if(isset($_GET["expression"]))  {
		$expression = getVar("expression");
		if( getVar('comment') ){
			$comment = getVar('comment');
		}
		if($expression != "")  {
			$enable_regex = true;
			if( ! getVar('enable_regex') ){
				$enable_regex = false;
				$expression = preg_quote($expression,'/');
			} 
			
			if($enable_regex && (!pbl_checkregexp($expression)))  {
				echo "<div class=\"pblmessage\">Your expression contained errors and couldn't be added: <b>".htmlspecialchars($expression,ENT_QUOTES)."</b></div>\n";
			}
			else  {
				$existTest = pbl_checkforspam($expression);

				if (strlen($existTest) > 0)  {
					echo "<div class=\"pblmessage\">Expression <b>".htmlspecialchars($expression,ENT_QUOTES)."</b> already matched by the following rule in your system:<br/> <b>$existTest</b></div>\n";
				}
				else  {
					pbl_addexpression($expression,$comment);
					echo "<div class=\"pblmessage\">New entry added to your list: <b>".htmlspecialchars($expression,ENT_QUOTES)."</b></div>";
				}
			}
		}
		else  {
			echo "<div class=\"pblmessage\">There's no use in adding empty expressions.<b>".htmlspecialchars($expression,ENT_QUOTES)."</b></div>";
		}
	}
}

function pbl_logspammer($spam)  {
	$spam = trim($spam);
	if( ! is_writable(__WEBLOG_ROOT.__EXT."/settings/blacklist.log") ){
		echo "Error: blacklist.log is not writable. ";
	}
	$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/blacklist.log", "a");
	$lastVisit = cookieVar($CONF['CookiePrefix'] .'lastVisit');
	if( $lastVisit ){
		//$lastVisit = $this->getCorrectTime($lastVisit);
		$logline = date("Y/m/d H:i:s")." #### ".serverVar("REMOTE_ADDR")." #### ".$spam. ' [lastVisit ' .date("Y/m/d H:i:s", $lastVisit). "]\n";
	} else {
		$logline = date("Y/m/d H:i:s")." #### ".serverVar("REMOTE_ADDR")." #### ".$spam."\n";
	}
	fwrite($handle, $logline);
	fclose($handle);
}

function pbl_log($text)  {
	$text = trim($text);
	if( ! is_writable(__WEBLOG_ROOT.__EXT."/settings/blacklist.log") ){
		echo "Error: blacklist.log is not writable. ";
	}
	$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/blacklist.log", "a");
	$logline = date("Y/m/d H:i:s")." #### localhost #### ".$text."\n";
	fwrite($handle, $logline);
	fclose($handle);
}


function pbl_logtable()  {
	if (file_exists(__WEBLOG_ROOT.__EXT."/settings/blacklist.log"))  {
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/blacklist.log", "r");
		$logrows = "";
		$numb=0;
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$thisline = explode("####", $buffer);
			if($thisline[0] != "")  {
				$logrows .= "<tr>";
				$logrows .= "<td class=\"log$numb\" >$thisline[0]</td>";
				if( getPluginOption('SkipNameResolve') == 'no' )
					$logrows .= "<td class=\"log$numb\" >$thisline[1]<br />(" . gethostbyaddr( trim($thisline[1]) ) .  ")</td>";
				else
					$logrows .= "<td class=\"log$numb\" >$thisline[1]</td>";
				$logrows .= "<td class=\"log$numb\" >$thisline[2]</td>";
				$logrows .= "</tr>\n";
			}
			if($numb == 0)
			$numb=1;
			else
			$numb=0;
		}
		fclose($handle);
		echo "<table class=\"pbllog\">\n";
		echo "<tr><th>Date/Time</th><th>IP</th><th>Rule Matched</th></tr>\n";
		echo $logrows;
		echo "</table>\n";
	}
	if(strlen($logrows) < 10)  {
		echo "<div class=\"pbldescription\">Your log is empty.</div>\n";
	}
	echo "<div class=\"pbform\" style=\"margin-left:10px;\">\n";
	echo "<form action=\"".serverVar('PHP_SELF')."\" method=\"get\">\n";
	echo "<input type=\"hidden\" name=\"page\" value=\"resetlog\" />\n";
	echo "<input type=\"submit\" value=\"Reset log\" />\n";
	echo "</form>\n";
	echo "</div>\n";
}

function check_for_open_proxy()	{
	$spammer_ip = serverVar('REMOTE_ADDR');
	list($a, $b, $c, $d) = split('.', $spammer_ip);
	if( gethostbyname("$d.$c.$b.$a.list.dsbl.org") != "$d.$c.$b.$a.list.dsbl.org") {
		return true;
	}
	return false;
}

function check_for_surbl ( $comment_text ) {
	/*  for a full explanation, see http://www.surbl.org
	summary: blocks comment if it contains an url that's on a known spammers list.
	*/
	//get site names found in body of comment.
	$regex_url   = "/(www\.)([^\/\"<\s]*)/i";
	$mk_regex_array = array();
	preg_match_all($regex_url, $comment_text, $mk_regex_array);

	for( $cnt=0; $cnt < count($mk_regex_array[2]); $cnt++ ) {
		$domain_to_test = rtrim($mk_regex_array[2][$cnt],"\\");

		if (strlen($domain_to_test) > 3)
		{
			$domain_to_test = $domain_to_test . ".multi.surbl.org";
			if( strstr(gethostbyname($domain_to_test),'127.0.0')) {
				return true;
			}
		}
	}
	return false;
}

//add hsur +++++++++++++

function check_for_iprbl () {
	if( pbl_ipcache_read() ) return false;
	
	//$iprbl = array('sc.surbl.org', 'bsb.spamlookup.net', 'opm.blitzed.org', 'list.dsbl.org');
	$iprbl = array('niku.2ch.net', 'list.dsbl.org', 'bsb.spamlookup.net');

	$spammer_ip = serverVar('REMOTE_ADDR');
	list($a, $b, $c, $d) = explode('.', $spammer_ip);
		
	foreach($iprbl as $rbl ){
		if( strstr( gethostbyname( "$d.$c.$b.$a.$rbl" ),'127.0.0') ) {
			return array($rbl, $spammer_ip);
		}
	}
	pbl_ipcache_write();
	return false;
}

function check_for_domainrbl ( $comment_text ) {
	$domainrbl = array('rbl.bulkfeeds.jp', 'url.rbl.jp', 'bsb.spamlookup.net');
	//$regex_url   = "/((http:\/\/)|(www\.))([^\/\"<\s]*)/i";
	$regex_url   = "{https?://(?:www\.)?([a-z0-9._-]{2,})(?::[0-9]+)?((?:/[_.!~*a-z0-9;@&=+$,%-]+){0,2})}m";
	$comment_text = mb_strtolower($comment_text);

	$mk_regex_array = array();
	preg_match_all($regex_url, $comment_text, $mk_regex_array);

	$mk_regex_array[1] = array_unique($mk_regex_array[1]);

	for( $cnt=0; $cnt < count($mk_regex_array[1]); $cnt++ ) {
		$domain_to_test = rtrim($mk_regex_array[1][$cnt],"\\");
		foreach($domainrbl as $rbl ){
			if (strlen($domain_to_test) > 3)
			{
				if( strstr(gethostbyname($domain_to_test.'.'.$rbl),'127.0.0')) {
					return array($rbl, $domain_to_test);
				}
			}
		}
	}
	return false;
}

//add hsur end ++++++++++++++

function pbl_blockIP() {
    $remote_ip = trim(serverVar('REMOTE_ADDR'));
	$filename  = __WEBLOG_ROOT.__EXT."/settings/blockip.pbl";
	$block     = false;
	// already in ipblock?
	if (file_exists($filename)) {
        $fp = fopen(__WEBLOG_ROOT.__EXT."/settings/blockip.pbl", "r");
        while ($line = trim(fgets($fp,255))) {
            if( strpos($remote_ip, $line) !== false){$block = true;}
        }
        fclose ($fp);
    } else {
        $fp = fopen(__WEBLOG_ROOT.__EXT."/settings/blockip.pbl", "w");
        fwrite($fp, "");
        fclose ($fp);
    }
    return $block;
}

function pbl_logRule($expression) {
    $filename  = __WEBLOG_ROOT.__EXT."/settings/matched.pbl";
    $count = 0;
    $fp = fopen($filename,"r+");
    if ($fp) {
        while ($line = fgets($fp, 4096)) {
            if (! (strpos($line, $expression) === false )) {
                $count++;
                break;
            }
        }
        fclose($fp);
    }
    if ($count == 0 && !trim($expression) == "" ) {
        $fp = fopen($filename,"a+");
        fwrite($fp,$expression."\n");
    }
}

// this function logs all ip-adresses in a 'suspected ip-list'
// if the ip of the currently catched spammer is above the ip-treshold (plugin option) then
// the spamming ipaddress is transfered to the blocked-ip list.
// this list is the first line of defense, so notorious spamming machine will be kicked of real fast
// improves blacklist performance
// possible danger: blacklisting real humans who post on-the-edge comments
function pbl_suspectIP($threshold, $remote_ip = '') {
	if ($remote_ip == '' ) {$remote_ip = serverVar('REMOTE_ADDR');}
	$filename  = __WEBLOG_ROOT.__EXT."/settings/suspects.pbl";
	$blockfile = __WEBLOG_ROOT.__EXT."/settings/blockip.pbl";
	$count     = 0;
    // suspectfile ?
	if (! file_exists($filename)) {
        $fp = fopen($filename, "w");
        fwrite($fp, "");
        fclose ($fp);
    }

    $fp = fopen($filename, "r");
    while ($line = fgets($fp,255)) {
        if ( strpos($line, $remote_ip) !== false ) {
            $count++;
        }
    }
    fclose ($fp);

    // not above threshold ? add ip to suspect ...
    if ($count < $threshold) {
        $fp = fopen($filename,'a+');
        fwrite($fp,$remote_ip."\n");
        fclose($fp);
    } else {
        // remove from suspect to ip-block
        $fp = fopen($filename, "r");
        $rewrite = "";
        while ($line = fgets($fp,255)) {
            // keep all lines except the catched ip-address
            if(strpos ($line, $remote_ip) !== false) {
                $rewrite .= $line;
            }
        }
        fclose($fp);
        $fp = fopen($filename, "w");
        fwrite($fp, $rewrite);
        fclose ($fp);
        // transfer to blocked-ip file
        $fp = fopen($blockfile,'a+');
        fwrite($fp,$remote_ip."\n");
        fclose($fp);
    }
}

function pbl_showipblock() {
    global $pblmessage;
	$filename  = __WEBLOG_ROOT.__EXT."/settings/blockip.pbl";
	$line = 0;
	$fp = fopen($filename,'r');
	echo "<div class=\"pbform\">\n";
	echo "<form action=\"".serverVar('PHP_SELF')."\" method=\"get\">\n";
	echo "<input type=\"hidden\" name=\"page\" value=\"addip\" />\n";
	echo "Add IP to block: ";
	echo "<input class=\"pbltextinput\" type=\"text\" name=\"ipaddress\" />\n";
	echo "<input type=\"submit\" value=\"Add\" />\n";
	echo "</form>";
	echo "</div>\n";
	echo "<table>";
	echo "<tr>\n";
	echo "<th>IP Address</th>\n";
	echo "<th>reversed lookup</th>\n";
	echo "<th>deletion</th>\n";
	echo "</tr>\n";
	while ($ip = fgets($fp,255)) {
	    $line++;
		if( getPluginOption('SkipNameResolve') == 'no' )
			echo "<tr><td>".$ip."</td><td>[".gethostbyaddr(rtrim($ip))."]</td><td>";
		else
			echo "<tr><td>".$ip."</td><td>[<em>skipped</em>]</td><td>";
		echo "<a href=\"".serverVar('PHP_SELF')."?page=deleteipblock&amp;line=".$line."\">delete</a>";
		echo "</td></tr>";
	}
	echo "</table>";
}
function pbl_addipblock() {
   	if(isset($_GET["ipaddress"]))  {
   	    pbl_suspectIP(0,getVar("ipaddress"));
   	}
}

function pbl_deleteipblock() {
    global $pblmessage;
	$filename  = __WEBLOG_ROOT.__EXT."/settings/blockip.pbl";
	if(isset($_GET["line"]))  {
		$handle = fopen($filename, "r");
		$line = 0;
		$newFile = "";
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$line++;
			if($line != getVar("line"))  {
				$newFile .= $buffer;
			}
		}
		fclose($handle);
		$handle = fopen($filename, "w");
		fwrite($handle, $newFile);
		fclose($handle);
	}
}

function pbl_htaccess($type) {
    $htaccess = "";
    switch($type) {
        case "ip":
    	    $filename  = __WEBLOG_ROOT.__EXT."/settings/blockip.pbl";
            $htaccess  = "# This htaccess snippet blocks machine based on IP Address. \n"
                       . "# these lines are generated by NP_Blackist\n";
            break;
        case "rules":
            $filename  = __WEBLOG_ROOT.__EXT."/settings/matched.pbl";
            $htaccess  = "# This htaccess snippet blocks machine based on referrers. \n"
                       . "# these lines are generated by NP_Blackist\n"
                       . "# You need to have the following line once in your .htaccess file\n"
                       . "# RewriteEngine On\n";
            break;
        default:
            $htaccess = "Here you can generate two types of .htaccess snippets. The first part is based on blocked ip's. This is only relevant if you have IP blocking enabled in the options. \nThe other part is referrer based rewrite rules. Blacklist stores all rules matched in a different file. With this tool you convert these matched rules into .htaccess rewrite rules which you can incorporate into your existings .htaccess file (Apache only)\n After you've added the snippet to your .htaccess file it's safe and wise to reset the blocked ip list and/or matched rules file. That way you won't end up with double rules inside your .htaccess file\n";
            return $htaccess;
    }

    $fp = fopen($filename, 'r');
    $count = 0;
    while ($line = fgets($fp,4096)) {
        if ($type == "ip") {
            $htaccess .= "deny from ".$line;
        } else {
            if (rtrim($line) != "" ) {
                if ($count > 0) {$htaccess .= "[NC,OR]\n";}
                // preg_replace does the magic of converting . into \. while keeping \. and _. intact
                $htaccess .= "RewriteCond %{HTTP_REFERER} ". preg_replace("/([^\\\|^_]|^)\./",'$1\\.',rtrim($line)).".*$ ";
                $count++;
            }
        }
    }
    if ($type != "ip") {
        $htaccess .= "\nRewriteRule .* ?¿½ [F,L]\n";
    }
    return $htaccess;
}

function pbl_htaccesspage() {
	global $pblmessage;
	if(strlen($pblmessage) > 0)  {
		echo "<div class=\"pblmessage\">$pblmessage</div>\n";
	}

    if (isset($_POST["type"])) {
        if (strstr(postVar("type"),"blocked")) {
            $type = 'ip';
        } else {
            $type = 'rules';
        }
    }
	echo "<div class=\"pbform\" style=\"margin-left:10px;\">\n";
	echo "<form action=\"".serverVar('PHP_SELF')."\" method=\"post\">\n";
    echo "<input type=\"submit\" label=\"ip\" value=\"Generate blocked IP's\" name=\"type\" />\n";
    echo "<input type=\"submit\" label=\"ip\" value=\"Generate rewrite rules\" name=\"type\" />\n";
    echo "<br />";
    echo "<br />";
	echo "<input type=\"hidden\" name=\"page\" value=\"htaccess\" />\n";
    echo "<textarea class=\"pbltextinput\" cols=\"60\" rows=\"15\" name=\"snippet\" >". pbl_htaccess($type)."</textarea><br />";
    echo "<br />";
    echo "<input title=\"this will clean your block IP addresses file\" type=\"submit\" label=\"ip\" value=\"Reset blocked IP's\" name=\"type\" />\n";
    echo "<input title=\"This will clean your matched file\" type=\"submit\" label=\"ip\" value=\"Reset rewrite rules\" name=\"type\" />\n";
	echo "</form>\n";
	// if user asked for a reset, do it now
    if (stristr(postVar("type"),"reset")) {
        echo "restting file ...";
        pbl_resetfile($type);
    }
	echo "</div>\n";
} // pbl_htaccesspage()

function pbl_resetfile($type){
    global $pblmessage;
    switch ($type) {
        case 'log':
            $filename = __WEBLOG_ROOT.__EXT."/settings/blacklist.log";
            break;
        case 'ip':
            $filename  = __WEBLOG_ROOT.__EXT."/settings/blockip.pbl";
            break;
        case 'rules':
            $filename  = __WEBLOG_ROOT.__EXT."/settings/matched.pbl";
            break;
    }
   	if(file_exists($filename))	{
        $fp = fopen($filename, "w");
    	fwrite($fp, "");
	    fclose($fp);
	}
}

function pbl_test () {
    // test's user input, no loggin.
	global $pblmessage;
	if(isset($_GET["expression"]))  {
		if(getVar("expression") != "")  {
            $pblmessage = "Your expression: <br />".htmlspecialchars(getVar("expression"), ENT_QUOTES);
            $return = pbl_checkforspam(getVar("expression"),false,0,false);

            if (! $return == "" ) {
                $pblmessage .= "<br />matched rule: <strong>".$return."</strong>";
            } else {
                $pblmessage .= "<br /> did not match any rule.";
            }
        }
    }
}

function pbl_testpage () {
    // shows user testpage ...
	global $pblmessage;
	if(strlen($pblmessage) > 0)  {
		echo "<div class=\"pblmessage\">$pblmessage</div>\n";
	}
	echo "<div class=\"pbform\" style=\"margin-left:10px;\">\n";
	echo "<form action=\"".serverVar('PHP_SELF')."\" method=\"get\">\n";
	echo "<input type=\"hidden\" name=\"page\" value=\"test\" />\n";
    echo "<textarea class=\"pbltextinput\" cols=\"60\" rows=\"6\" name=\"expression\" ></textarea><br />";
	echo "<input type=\"submit\" value=\"Test this\" />\n";
	echo "</form>\n";
	echo "</div>\n";
}

function pbl_spamsubmission_form()  {
		// form 
		echo "<form action=\"".serverVar('PHP_SELF')."?page=spamsubmission&action=send\" method=\"post\">\n";

		// table
		echo "<table>\n";
		echo "<tr>\n";
		echo "<th>Report Spam</th>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td><textarea name=\"url\" rows=\"6\" cols=\"60\"></textarea></td>\n";
		echo "</tr>\n";

		echo '<tr><td><div align="right"><input type="submit" name="submit" value="submit" /></div></td></tr>';
	
		echo "</table>\n";
		echo "</form>\n";
}

?>
