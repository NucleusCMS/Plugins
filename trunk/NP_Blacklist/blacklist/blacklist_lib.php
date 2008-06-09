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
//
// Modified by hsur
// http://blog.cles.jp
// $Id: blacklist_lib.php,v 1.13 2008-06-09 10:33:40 hsur Exp $

define('__WEBLOG_ROOT', dirname(dirname(realpath(__FILE__))));
define('__EXT', '/blacklist');

define('NP_BLACKLIST_CACHE_DIR', dirname(__FILE__).'/cache');
define('NP_BLACKLIST_CACHE_LIFE', 86400);
define('NP_BLACKLIST_CACHE_GC_INTERVAL', NP_BLACKLIST_CACHE_LIFE / 8);
define('NP_BLACKLIST_CACHE_GC_TIMESTAMP', 'gctime');
define('NP_BLACKLIST_CACHE_GC_TIMESTAMP_LIFE', NP_BLACKLIST_CACHE_LIFE * 3);

define('NP_BLACKLIST_LOG_MAX_DAYS', 7);

require_once(dirname(__FILE__).'/cache_file.php');
//require_once(dirname(__FILE__).'/cache_eaccelerator.php');
//require_once(dirname(__FILE__).'/cache_xcache.php');
//require_once (dirname(__FILE__).'/cache_memcache.php');

function pbl_getconfig() {
	global $pbl_config;
	$pbl_config = array ();
	$pbl_config['enabled'] = getPluginOption('enabled');
	$pbl_config['redirect'] = getPluginOption('redirect');
	if ($pbl_config['enabled'] == 'yes') {
		$pbl_config['enabled'] = 1;
	}
	return $pbl_config;
}

function pbl_checkforspam($text, $ipblock = false, $ipthreshold = 10, $logrule = true) {
	// check whether a string contains spam
	// if it does, we return the rule that was matched first

	// whitelist
	if (pbl_checkIp('white')) {
		return '';
	}
	
	// first line of defense; block notorious spammers
	if ($ipblock) {
		if (pbl_checkIp()){
			return "<b>".NP_BLACKLIST_ipBlocked."</b>: ".serverVar('REMOTE_ADDR')." (".serverVar('REMOTE_HOST').")";
		}
	}

	// fourth line of defense: Run the MT-Blacklist check
	if ($text && file_exists(__WEBLOG_ROOT.__EXT."/settings/blacklist.pbl")) {
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/blacklist.pbl", "r");
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$splitbuffer = explode("####", $buffer);
			$expression = $splitbuffer[0];
			$explodedSplitBuffer = explode("/", $expression);
			$expression = $explodedSplitBuffer[0];
			if (strlen($expression) > 0) {
				if (preg_match("/".trim($expression)."/i", $text)) {
					if ($ipblock) {
						pbl_suspectIp($ipthreshold);
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

	// fifth line of defense: run the personal blacklist entries
	if ($text && file_exists(__WEBLOG_ROOT.__EXT.'/settings/personal_blacklist.pbl')) {
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl", "r");
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$splitbuffer = explode("####", $buffer);
			$expression = $splitbuffer[0];
			if (strlen($expression) > 0) {
				if (preg_match("/".trim($expression)."/i", $text)) {
					if ($ipblock) {
						pbl_suspectIp($ipthreshold);
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

	if ($ipblock && $listedrbl = check_for_iprbl()) {
		pbl_suspectIP($ipthreshold);
		$ref = serverVar('HTTP_REFERER');
		return NP_BLACKLIST_ipListed." {$listedrbl[0]} ".NP_BLACKLIST_found." (Referer:{$ref})";
	}

	if ($text && $listedrbl = check_for_domainrbl($text)) {
		if ($ipblock) {
			pbl_suspectIP($ipthreshold);
		}
		return NP_BLACKLIST_urlListed." {$listedrbl[0]} ({$listedrbl[1]})".NP_BLACKLIST_found;
	}

	// w00t! it's probably not spam!
	return "";
}

function is_domain($stheDomain) {
	return ((strpos($stheDomain, "\\") == 0) && (strpos($stheDomain, "[") == 0) && (strpos($stheDomain, "(") == 0));
}

function pbl_blacklisteditor() {
	global $manager;
	if (file_exists(__WEBLOG_ROOT.__EXT.'/settings/personal_blacklist.pbl')) {
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl", "r");
		$line = 0;
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$line ++;
			$configParam = explode("####", $buffer);
			$key = $configParam[0];
			$value = $configParam[1];
			if (strlen($key) > 0) {
				echo "<tr>\n";
				echo "<td>".htmlspecialchars($key, ENT_QUOTES)."</td>\n";
				echo "<td>".htmlspecialchars($value, ENT_QUOTES)."</td>\n";
				echo "<td>";
				echo "<a href=\"".htmlspecialchars($manager->addTicketToUrl(serverVar('PHP_SELF')."?action=deleteexpression&line=".$line), ENT_QUOTES)."\">".NP_BLACKLIST_delete."</a>";
				echo "</td>";
				echo "</tr>\n";
			}
		}
	}
}

function pbl_deleteexpression() {
	if (isset ($_GET["line"])) {
		if (!is_writable(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl")) {
			return "Error: personal_blacklist.pbl ".NP_BLACKLIST_isNotWritable;
		}
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl", "r");
		$line = 0;
		$newFile = "";
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$line ++;
			if ($line != getVar("line")) {
				$newFile .= $buffer;
			}
		}
		fclose($handle);
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl", "w");
		fwrite($handle, $newFile);
		fclose($handle);
		return '<div class="pblmessage">'.NP_BLACKLIST_expressionDeleted.'</div>';
	}
	return '';
}

function pbl_addexpression($expression, $comment) {
	if (strlen($expression) > 0) {
		if (!is_writable(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl")) {
			echo "Error: personal_blacklist.pbl ".NP_BLACKLIST_isNotWritable;
		}
		$handle = fopen(__WEBLOG_ROOT.__EXT."/settings/personal_blacklist.pbl", "a");
		if (strlen($comment) > 0) {
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
	preg_match("/".trim($re)."/i", "");
	restore_error_handler();
	return $g_reOk;
}

function pbl_addpersonal() {
	if (isset ($_POST["expression"])) {
		$expression = postVar("expression");
		if (postVar('comment')) {
			$comment = postVar('comment');
		}
		if ($expression != "") {
			$enable_regex = true;
			if (!postVar('enable_regex')) {
				$enable_regex = false;
				$expression = preg_quote($expression, '/');
			}
			if ($enable_regex && (!pbl_checkregexp($expression))) {
				return "<div class=\"pblmessage\">".NP_BLACKLIST_containdError.": <b>".htmlspecialchars($expression, ENT_QUOTES)."</b></div>\n";
			} else {
				$existTest = pbl_checkforspam($expression);
				if (strlen($existTest) > 0) {
					return "<div class=\"pblmessage\"><b>".htmlspecialchars($expression, ENT_QUOTES)."</b> ".NP_BLACKLIST_alreadyMatched.": <b>$existTest</b></div>\n";
				} else {
					pbl_addexpression($expression, $comment);
					return "<div class=\"pblmessage\">".NP_BLACKLIST_newEntryAdded.": <b>".htmlspecialchars($expression, ENT_QUOTES)."</b></div>";
				}
			}
		} else {
			return "<div class=\"pblmessage\">".NP_BLACKLIST_emptyExpression.": <b>".htmlspecialchars($expression, ENT_QUOTES)."</b></div>";
		}
	}
	return '';
}

function pbl_logspammer($spam) {
	$spam = trim($spam);
	$filePath = __WEBLOG_ROOT.__EXT."/settings/blacklist.log";
	if (!is_writable($filePath)) {
		echo "Error: blacklist.log ".NP_BLACKLIST_isNotWritable;
	}

	$type = "a";
	$mtime = filemtime($filePath);
	if (date("Ymd") != date("Ymd", $mtime)) {
		if (@rename($filePath, $filePath.date(".Ymd", $mtime))) {
			$files = pbl_getlogfiles();
			foreach( array_slice($files, NP_BLACKLIST_LOG_MAX_DAYS-1) as $f )
				@ unlink($f);
		} else {
			$type = "w";
		}
	}

	$handle = fopen($filePath, $type);
	$lastVisit = cookieVar($CONF['CookiePrefix'].'lastVisit');
	if ($lastVisit) {
		$logline = date("Y/m/d H:i:s")." #### ".serverVar("REMOTE_ADDR")." #### ".$spam.' [lastVisit '.date("Y/m/d H:i:s", $lastVisit)."]\n";
	} else {
		$logline = date("Y/m/d H:i:s")." #### ".serverVar("REMOTE_ADDR")." #### ".$spam."\n";
	}
	fwrite($handle, $logline);
	fclose($handle);
	
	if($type == "w")
		@ chmod($filePath, 0666);
}

function pbl_log($text) {
	$text = trim($text);
	$filePath = __WEBLOG_ROOT.__EXT."/settings/blacklist.log";
	if (!is_writable($filePath)) {
		echo "Error: blacklist.log ".NP_BLACKLIST_isNotWritable;
	}

	$type = "a";
	$mtime = filemtime($filePath);
	if (date("Ymd") != date("Ymd", $mtime)) {
		if (@rename($filePath, $filePath.date(".Ymd", $mtime))) {
			$files = pbl_getlogfiles();
			foreach( array_slice($files, NP_BLACKLIST_LOG_MAX_DAYS-1) as $f )
				@ unlink($f);
		} else {
			$type = "w";
		}
	}

	$handle = fopen($filePath, $type);
	$logline = date("Y/m/d H:i:s")." #### localhost #### ".$text."\n";
	fwrite($handle, $logline);
	fclose($handle);
}


function pbl_getlogfiles(){
	$tmp = array();	
	foreach (glob(__WEBLOG_ROOT.__EXT."/settings/blacklist.log*") as $filename) {
		@chmod($filename, 0666);
		$tmp[$filename] = filemtime($filename);
	}
	arsort($tmp);
	$files = array();
	foreach($tmp as $key => $value ){
		$files[] = $key;
	}
	return $files;
}

function pbl_logtable($no) {
	global $manager;
	
	$files = pbl_getlogfiles();
	if( $file = $files[$no] ){
		$handle = fopen($file, "r");
		$numb = 0;
		$lines = 0;
		$skipNameResolve = ( getPluginOption('SkipNameResolve') == 'yes' ) ? true : false;
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$thisline = explode("####", $buffer);
			if ($thisline[0] != "") {
				echo "<tr>";
				echo "<td class=\"log$numb\" >$thisline[0]</td>";
				if ( $skipNameResolve )
					echo "<td class=\"log$numb\" >$thisline[1]</td>";
				else
					echo "<td class=\"log$numb\" >$thisline[1]<br />(".gethostbyaddr(trim($thisline[1])).")</td>";
				echo "<td class=\"log$numb\" >$thisline[2]</td>";
				echo "</tr>\n";
				$lines++;
			}
			$numb = ($numb == 0)?1:0;
		}
		fclose($handle);
	}
	if (!$lines) {
		echo '<tr><td colspan="3" class="log0">'.NP_BLACKLIST_logIsEmpty.'</td></tr>';
	}

}

function check_for_iprbl() {
	if (pbl_ipcache_read())
		return false;

	$spammer_ip = serverVar('REMOTE_ADDR');
	$iprbl = array ('niku.2ch.net', 'list.dsbl.org', 'bsb.spamlookup.net');
	list ($a, $b, $c, $d) = explode('.', $spammer_ip);

	foreach ($iprbl as $rbl) {
		if ( gethostbynamel("$d.$c.$b.$a.$rbl") !== false ) {
			return array ($rbl, $spammer_ip);
		}
	}
	pbl_ipcache_write();
	return false;
}

function check_for_domainrbl($comment_text) {
	$domainrbl = array ('url.rbl.jp', 'bsb.spamlookup.net');
	$regex_url = "{https?://(?:www\.)?([a-z0-9._-]{2,})(?::[0-9]+)?((?:/[_.!~*a-z0-9;@&=+$,%-]+){0,2})}i";

	$mk_regex_array = array ();
	preg_match_all($regex_url, $comment_text, $mk_regex_array);

	$mk_regex_array[1] = array_unique($mk_regex_array[1]);

	for ($cnt = 0; $cnt < count($mk_regex_array[1]); $cnt ++) {
		$domain_to_test = rtrim($mk_regex_array[1][$cnt], "\\");
		foreach ($domainrbl as $rbl) {
			if (strlen($domain_to_test) > 3) {
				//pbl_log('DNSBL Lookup: ' . $domain_to_test.'.'.$rbl);
				if ( gethostbynamel("$domain_to_test.$rbl") !== false ) {
					return array ($rbl, $domain_to_test);
				}
			}
		}
	}
	return false;
}

function pbl_checkIp($type = 'block') {
	$remote_ip = trim(serverVar('REMOTE_ADDR'));
	$filename = __WEBLOG_ROOT.__EXT.'/settings/'.$type.'ip.pbl';
	$match = false;
	// already in ipblock?
	if (file_exists($filename)) {
		$fp = fopen($filename, "r");
		while ($line = trim(fgets($fp, 255))) {
			if (pbl_netMatch($line, $remote_ip)) {
				$match = true;
			}
		}
		fclose($fp);
	} else {
		$fp = fopen($filename, "w");
		fwrite($fp, "");
		fclose($fp);
	}
	return $match;
}

function pbl_logRule($expression) {
	$filename = __WEBLOG_ROOT.__EXT."/settings/matched.pbl";
	$count = 0;
	$fp = fopen($filename, "r+");
	if ($fp) {
		while ($line = fgets($fp, 4096)) {
			if (!(strpos($line, $expression) === false)) {
				$count ++;
				break;
			}
		}
		fclose($fp);
	}
	if ($count == 0 && !trim($expression) == "") {
		$fp = fopen($filename, "a+");
		fwrite($fp, $expression."\n");
	}
}

// this function logs all ip-adresses in a 'suspected ip-list'
// if the ip of the currently catched spammer is above the ip-treshold (plugin option) then
// the spamming ipaddress is transfered to the blocked-ip list.
// this list is the first line of defense, so notorious spamming machine will be kicked of real fast
// improves blacklist performance
// possible danger: blacklisting real humans who post on-the-edge comments
function pbl_suspectIP($threshold, $remote_ip = '', $type = 'block') {
	if ($remote_ip == '') {
		$remote_ip = serverVar('REMOTE_ADDR');
	}
	if($type == 'white'){
		$threshold = false;
	}
	
	$blockfile = __WEBLOG_ROOT.__EXT.'/settings/'.$type.'ip.pbl';
	$filename = __WEBLOG_ROOT.__EXT.'/settings/suspects.pbl';
	$count = 0;

	if( $threshold ){
		// suspectfile ?
		if (!file_exists($filename)) {
			$fp = fopen($filename, "w");
			fwrite($fp, "");
			fclose($fp);
		}
	
		$fp = fopen($filename, "r");
		while ($line = fgets($fp, 255)) {
			if (strpos($line, $remote_ip) === 0) {
				$count ++;
			}
		}
		fclose($fp);
	}

	// not above threshold ? add ip to suspect ...
	if ($threshold !== false && $count < $threshold) {
		$fp = fopen($filename, 'a+');
		fwrite($fp, $remote_ip."\n");
		fclose($fp);
	} else {
		if($threshold !== false){
			// remove from suspect to ip-block
			$fp = fopen($filename, "r");
			$rewrite = "";
			while ($line = fgets($fp, 255)) {
				// keep all lines except the catched ip-address
				if (strpos($line, $remote_ip) === 0) {
					$rewrite .= $line;
				}
			}
			fclose($fp);
			$fp = fopen($filename, "w");
			fwrite($fp, $rewrite);
			fclose($fp);
		}
		// transfer to blocked-ip file
		$fp = fopen($blockfile, 'a+');
		fwrite($fp, $remote_ip."\n");
		fclose($fp);
	}
}

function pbl_showIp($type = 'block') {
	global $pblmessage, $manager;
	$filename = __WEBLOG_ROOT.__EXT.'/settings/'.$type.'ip.pbl';
	$line = 0;
	$fp = fopen($filename, 'r');
	
	while ($ip = fgets($fp, 255)) {
		$line++;
		if (getPluginOption('SkipNameResolve') == 'no')
			echo "<tr><td>".$ip."</td><td>[".gethostbyaddr(rtrim($ip))."]</td><td>";
		else
			echo "<tr><td>".$ip."</td><td>[<em>skipped</em>]</td><td>";
		echo "<a href=\"".htmlspecialchars($manager->addTicketToUrl(serverVar('PHP_SELF').'?action=deleteip'.$type.'&line='.$line), ENT_QUOTES)."\">".NP_BLACKLIST_delete."</a>";
		echo "</td></tr>";
	}
}

function pbl_addIp($type = 'block') {
	if (isset ($_POST["ipaddress"])) {
		pbl_suspectIP(0, postVar("ipaddress"), $type);
		return "<div class=\"pblmessage\">".NP_BLACKLIST_newEntryAdded.": <b>".htmlspecialchars(postVar("ipaddress"), ENT_QUOTES)."</b></div>";
	}
	return '';
}

function pbl_deleteIp($type = 'block') {
	global $pblmessage;
	$filename = __WEBLOG_ROOT.__EXT.'/settings/'.$type.'ip.pbl';
	if (isset ($_GET["line"])) {
		$handle = fopen($filename, "r");
		$line = 0;
		$newFile = "";
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$line ++;
			if ($line != getVar("line")) {
				$newFile .= $buffer;
			}
		}
		fclose($handle);
		$handle = fopen($filename, "w");
		fwrite($handle, $newFile);
		fclose($handle);
		return "<div class=\"pblmessage\">".NP_BLACKLIST_blockDeleted."</div>\n";
	}
	return '';
}

function pbl_htaccess($type) {
	$htaccess = "";
	switch ($type) {
		case "ip" :
			$filename = __WEBLOG_ROOT.__EXT."/settings/blockip.pbl";
			$htaccess = "# This htaccess snippet blocks machine based on IP Address. \n"."# these lines are generated by NP_Blackist\n";

			$htaccess .= "# You need to have the following line once in your .htaccess file\n# order allow,deny\n# allow from all\n";
			break;
		case "rules" :
			$filename = __WEBLOG_ROOT.__EXT."/settings/matched.pbl";
			$htaccess = "# This htaccess snippet blocks machine based on referrers. \n"."# these lines are generated by NP_Blackist\n"."# You need to have the following line once in your .htaccess file\n"."# RewriteEngine On\n";
			break;
		default :
			$htaccess = "Here you can generate two types of .htaccess snippets. The first part is based on blocked ip's. This is only relevant if you have IP blocking enabled in the options. \nThe other part is referrer based rewrite rules. Blacklist stores all rules matched in a different file. With this tool you convert these matched rules into .htaccess rewrite rules which you can incorporate into your existings .htaccess file (Apache only)\n After you've added the snippet to your .htaccess file it's safe and wise to reset the blocked ip list and/or matched rules file. That way you won't end up with double rules inside your .htaccess file\n";
			return $htaccess;
	}

	$fp = fopen($filename, 'r');
	$count = 0;

	while ($line = fgets($fp, 4096)) {
		if ($type == "ip") {
			$htaccess .= "deny from ".$line;
		} else {
			if (rtrim($line) != "") {
				if ($count > 0) {
					$htaccess .= "[NC,OR]\n";
				}
				// preg_replace does the magic of converting . into \. while keeping \. and _. intact
				$htaccess .= "RewriteCond %{HTTP_REFERER} ".preg_replace("/([^\\\|^_]|^)\./", '$1\\.', rtrim($line)).".*$ ";
				$count ++;
			}
		}
	}
	if ($type != "ip") {
		$htaccess .= "\nRewriteRule .* [F,L]\n";
	}
	return $htaccess;
}

function pbl_resetfile($type) {
	global $pblmessage;
	switch ($type) {
		case 'log' :
			$files = pbl_getlogfiles();
			foreach( $files as $file )
				@ unlink($file);
			@ touch(__WEBLOG_ROOT.__EXT."/settings/blacklist.log");
			@ chmod(__WEBLOG_ROOT.__EXT."/settings/blacklist.log", 0666);
			break;
		case 'ip' :
			$filename = __WEBLOG_ROOT.__EXT."/settings/blockip.pbl";
			break;
		case 'rules' :
			$filename = __WEBLOG_ROOT.__EXT."/settings/matched.pbl";
			break;
	}
	if (file_exists($filename)) {
		$fp = fopen($filename, "w");
		fwrite($fp, "");
		fclose($fp);
	}
	return '<b> '.$type.' '.NP_BLACKLIST_fileHasBeenReset.'</b><br />';
}

function pbl_test() {
	// test's user input, no loggin.
	if (isset ($_POST["expression"])) {
		if (postVar("expression") != "") {
			$pblmessage = NP_BLACKLIST_yourExpression.": <br /><pre>".htmlspecialchars(postVar("expression"), ENT_QUOTES).'</pre>';
			$return = pbl_checkforspam(postVar("expression"), false, 0, false);

			if (!$return == "") {
				return NP_BLACKLIST_matchedRule.": <strong>".$return."</strong>";
			} else {
				return NP_BLACKLIST_didNotMatch;
			}
		}
	}
	return '';
}

function pbl_netMatch($network, $ip) {
	if (strpos($network, '/') !== false) {
		list($network, $mask) = explode('/', $network);
		$network = ip2long($network);
		$mask = 0xffffffff << (32 - intval($mask));
		$ip = ip2long($ip);
		return ($ip & $mask) == ($network & $mask);
	}
	return strpos($ip, $network) === 0;
}
