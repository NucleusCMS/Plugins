<?php
/*	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
	
	NP_Benchmark by yu (http://nucleus.datoka.jp/)
	
	Usage
	-----
	<%Benchmark(stylesheet)%> //output embedded stylesheet for head block
	
	<%Benchmark(foo)%> //benchmark label "foo"
	<%Benchmark(bar)%> //benchmark label "bar"
	
	
	History
	-------
	Ver0.4  2008/12/12 Add more options for output. Add embedding styles. (yu)
	Ver0.31 2008/06/18 Add labels to calc diff time. Add CSS "level" class. (yu)
	Ver0.3  2007/07/05 Add average time (use session) and memory usage. Memory usage is disabled in default. (yu)
	Ver0.2  2006/05/01 Improved and add some features. (sato(na))
	Ver0.1  2004/03/08 Initial release. (yu)
*/

class NP_Benchmark extends NucleusPlugin { 
	function getName()             { return 'Benchmark'; } 
	function getAuthor()           { return 'yu + sato(na)'; } 
	function getURL()              { return 'http://works.datoka.jp/plugins/224.html'; } 
	function getVersion()          { return '0.4'; } 
	function getMinNucleusVersion(){ return 330; }
	function getTableList()        { return array(); }
	function supportsFeature($w)   { return ($w == 'SqlTablePrefix') ? 1 : 0; }
	function getEventList()        { return array( 'PreSkinParse' ); }
	function getDescription()      { return 'Benchmark'; }
	
	function install()             {
		$this->createOption("EnableThis", "Enable benchmarking", "yesno", "yes");
		$this->createOption("EnabledFor", "Benchmark is enabled for:", "select", "admin", "Admin|admin|Team Member|team|All|all");
		$this->createOption("AutoStart",  "Start benchmark automatically on PreSkinParse", "yesno", "yes");
		$this->createOption("UseComment", "Use comment tag for output", "yesno", "no");
		$this->createOption("ShowSQL", "Show SQL count", "yesno", "yes");
		$this->createOption("ShowMem", "Show memory usage", "yesno", "no");
		$this->createOption("ShowDiff", "Show diffs of several benchmark points", "yesno", "no");
		$this->createOption("ShowAverage", "Show average of diffs", "yesno", "no");
	}
	
	var $flg_enabled;
	var $flg_comment;
	var $flg_sql;
	var $flg_mem;
	var $flg_diff;
	var $flg_average;
	var $member;
	
	function init() {
		global $member, $blogid;
		
		if (!$blogid) {
			$this->flg_enabled = false;
			return;
		}
		
		$this->member = $this->getOption('EnabledFor');
		$this->flg_enabled = ($this->getOption('EnableThis') == 'yes');
		$this->flg_comment = ($this->getOption('UseComment') == 'yes');
		$this->flg_sql     = ($this->getOption('ShowSQL') == 'yes');
		$this->flg_mem     = ($this->getOption('ShowMem') == 'yes');
		$this->flg_diff    = ($this->getOption('ShowDiff') == 'yes');
		$this->flg_average = ($this->getOption('ShowAverage') == 'yes' and $this->member != 'all');
		
		switch ($this->member) {
		case 'admin':
			if (!$member->isAdmin($blogid)) $this->flg_enabled = false;
			else if($this->flg_average) session_start();
			break;
		case 'team':
			if (!$member->isTeamMember($blogid)) $this->flg_enabled = false;
			else if($this->flg_average) session_start();
			break;
		}
	}
	
	function event_PreSkinParse($data) {
		if (!$this->flg_enabled) return;
		
		global $benchmark_start;
		if ($this->getOption('AutoStart') == 'yes') $benchmark_start = microtime();
	}
	
	function doAction($type) {
		global $member;
		
		switch ($type) {
		case 'reset':
			if (!$member->isLoggedIn()) return;
			session_start();
			$_SESSION = array();
			if (isset($_COOKIE[session_name()])) {
				setcookie(session_name(), '', time()-42000, '/');
			}
			session_destroy();
			break;
		}
		redirect( serverVar('HTTP_REFERER') );
	}
	
	function doTemplateVar(&$item, $label=''){ $this->benchmark($label); }
	function doSkinVar($skinType, $label='') {
		if ($label == 'stylesheet') $this->embedStylesheet();
		else $this->benchmark($label);
	}
	
	var $lastsec;
	var $bmcnt = 0;
	function benchmark($label=''){
		global $benchmark_start, $SQLCount, $CONF;
		
		if (!$this->flg_enabled) return;
		if (empty($benchmark_start)) {
			$benchmark_start = microtime();
			return;
		}
		
		$this->bmcnt++;
		list($msec1, $sec1) = explode(' ', $benchmark_start);
		list($msec2, $sec2) = explode(' ', microtime());
		$start = (float) $sec1 + (float) $msec1;
		$end   = (float) $sec2 + (float) $msec2;
		$diff = $end - $start;
		if (!$this->lastsec) $this->lastsec = $start;
		$diff2 = $end - $this->lastsec;
		
		if ($this->flg_comment) {
			$pre = '<!--';
			$post = '-->';
		}
		else {
			$lv = (int)($diff2 * 10); //unit:0.1sec.
			$lv = ($lv > 10) ? 10 : $lv;
			$pre  = '<span class="benchmark level'. $lv .'" title="['. $this->bmcnt.']'.$label .'">';
			$post = '</span>';
		}
		
		echo $pre;
		if ($this->flg_sql) echo (int)$SQLCount.'q. ';
		if ($this->flg_mem) echo number_format(memory_get_usage()).'b. '; // returns apache's process size on xampp/win ...
		echo number_format($diff, 2).'s. ';
		if ($this->flg_diff) echo '+'. number_format($diff2, 2) .'s. ';
		
		if ($this->flg_average) {
			if ( empty($label) ) $label = 'default';
			$_SESSION[ $label ][] = $diff2;
			$cnt = count($_SESSION[ $label ]);
			$avr_sec = number_format(array_sum($_SESSION[ $label ]) / $cnt, 2);
			echo '+'.$avr_sec.'s./avr.('.$cnt.') ';
			if ($this->bmcnt == 1) {
				echo '[<a href="'. $CONF['ActionURL'] .'?action=plugin&amp;name=Benchmark&amp;type=reset" title="session reset">x</a>]';
			}
		}
		echo $post;
		$this->lastsec = $end;
	}
	
	function embedStylesheet() {
		echo <<< EOH
<style type="text/css">
<!--
span.benchmark {
	border: 1px solid black;
	background-color: #000000;
	padding: 1px;
	color: white;
	font-size: 9px;
	line-height: 1em;
}
span.benchmark a {
	color: #ccc;
}
span.benchmark.level0  { background-color: #113333; }
span.benchmark.level1  { background-color: #336666; }
span.benchmark.level2  { background-color: #666633; }
span.benchmark.level3  { background-color: #aa9911; }
span.benchmark.level4  { background-color: #ddaa00; }
span.benchmark.level5  { background-color: #ffcc00; }
span.benchmark.level6  { background-color: #ff9900; }
span.benchmark.level7  { background-color: #ff6600; }
span.benchmark.level8  { background-color: #ff3311; }
span.benchmark.level9  { background-color: #ff0033; }
span.benchmark.level10 { background-color: #ff0066; }
-->
</style>
EOH;
	}

}

if( !function_exists('memory_get_usage') )
{
	function memory_get_usage()
	{
		//If its Windows
		//Tested on Win XP Pro SP2. Should work on Win 2003 Server too
		//Doesn't work for 2000
		//If you need it to work for 2000 look at http://us2.php.net/manual/en/function.memory-get-usage.php#54642
		if ( substr(PHP_OS,0,3) == 'WIN')
		{
				if ( substr( PHP_OS, 0, 3 ) == 'WIN' )
				{
					$output = array();
					exec( 'tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output );
					return preg_replace( '/[\D]/', '', $output[5] ) * 1024;
				}
		}else
		{
			//We now assume the OS is UNIX
			//Tested on Mac OS X 10.4.6 and Linux Red Hat Enterprise 4
			//This should work on most UNIX systems
			$pid = getmypid();
			exec("ps -eo%mem,rss,pid | grep $pid", $output);
			$output = explode("  ", $output[0]);
			//rss is given in 1024 byte units
			return $output[1] * 1024;
		}
	}
}

?>