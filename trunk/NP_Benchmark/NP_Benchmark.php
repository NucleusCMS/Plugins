<?php
/*	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
	
	History
	-------
	Ver0.3 2007/07/05 average, memory usage (commented out now)
	Ver0.2 2006/05/01 improved by sato(na)
	Ver0.1 2004/03/08 initial release.
*/

class NP_Benchmark extends NucleusPlugin { 
	function getName()             { return 'Benchmark'; } 
	function getAuthor()           { return 'yu + sato(na)'; } 
	function getURL()              { return 'http://works.datoka.jp/?itemid=224'; } 
	function getVersion()          { return '0.3'; } 
	function getMinNucleusVersion(){ return 330; }
	function getTableList()        { return array(); }
	function supportsFeature($w)   { return ($w == 'SqlTablePrefix') ? 1 : 0; }
	function getEventList()        { return array( 'PreSkinParse','PostSkinParse' ); }
	function getDescription()      { return 'Benchmark'; }
	function install()             {
		$this->createOption("TeamDisp", "Benchmark for blog team only", "yesno", "yes");
		$this->createOption("AutoStart", "Start benchmark automatically on PreSkinParse", "yesno", "yes");
		$this->createOption("PostSkinParseDisp", "Output on PostSkinParse", "yesno", "no");
		$this->createOption("UseComment", "Use comment tag for output", "yesno", "no");
		$this->createOption("UseSession", "Use session", "yesno", "no");
	}
	function init() {
		global $member, $blogid;
		$this->TeamDisp  = ($this->getOption('TeamDisp') == 'yes' and $member->isTeamMember($blogid)) ? TRUE : FALSE;
		if ($this->TeamDisp and $this->getOption('UseSession') == 'yes') session_start();
	}
	function doAction($type) {
		switch ($type) {
		case 'reset':
			session_unset();
			break;
		}
		redirect( serverVar('HTTP_REFERER') );
	}
	function doTemplateVar(&$item, $disp=FALSE){ $this->benchmark($disp); }
	function doSkinVar($skinType, $disp=FALSE) { $this->benchmark($disp); }
	function event_PreSkinParse($data) {
		global $benchmark_start;
		if ($this->getOption('AutoStart') == 'yes') $benchmark_start = microtime();
	}
	function event_PostSkinParse($data) {
		if ($this->getOption('PostSkinParseDisp') == 'yes') $this->benchmark(FALSE);
	}
	function benchmark($disp=FALSE){
		global $benchmark_start, $SQLCount;
		
		if ($this->getOption('TeamDisp') == 'yes' and $this->TeamDisp == FALSE) return;
		if (empty($benchmark_start)) {
			$benchmark_start = microtime();
			return;
		}
		
		list($msec1, $sec1) = explode(' ', $benchmark_start);
		list($msec2, $sec2) = explode(' ', microtime());
		$diff = (float) $sec2 + (float) $msec2 - (float) $sec1 - (float) $msec1;
		if ($this->getOption('UseComment') == 'yes') {
			$pre = '<!--';
			$post = '-->';
		}
		else {
			$pre = '[';
			$post = ']';
		}
		echo $pre;
		echo (int)$SQLCount.'q./'.
			//number_format(memory_get_usage()).'bites/'. // returns apache's process size on xampp/win ...
			number_format($diff, 3).'sec.';
		if ($this->getOption('UseSession') == 'yes') {
			if ( empty($disp) ) $disp = 'default';
			$_SESSION[ $disp ][] = $diff;
			$cnt = count($_SESSION[ $disp ]);
			$avr_sec = number_format(array_sum($_SESSION[ $disp ]) / $cnt, 3);
			echo '/<a href="'. $CONF['ActionURL'] .'?action=plugin&amp;name=Benchmark&amp;type=reset">AVR '.$avr_sec.'sec.('.$cnt.')</a>';
		}
		echo $post;
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