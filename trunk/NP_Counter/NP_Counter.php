<?php
/**
  * NP_Counter - customized by yu (http://nucleus.datoka.jp/)
  *
  * History:
  *   v0.63: Fix Use correct server time. (2004/04/14)
  *          Fix Today's count, starting from "0" is changed to "1" (in IP check mode).
  *          Fix Change title property to alt property in IMG tag.
  *   v0.62: Improved Plugin Checker of "Show Plugin Link". (2004/04/14)
  *          Add Skinvar Parameter "image+sincetext";
  *   v0.61: Add Option "Show Plugin Link". (2004/04/02)
  *          This plugin link also works as version checker.
  *          Add Skinvar Parameter "image".
  *   v0.6 : Add Option "Show Count Mode". (2004/02/17)
  *          Support external language file.
  *
  *    Other New Feature after v0.2 is ...
  *    -----------------------------------
  *    Change save format(delete "count_begin" column). (2004/02/10)
  *    Add option "Erase count data on uninstall". (2004/01/07)
  *    Change default image format from jpg to gif. (2004/01/04)
  *    Add option "Image File Extension", "Init Value", "Show Detail". (2004/01/03)
  *    Add option "Count Mode" (data is now saved in 7days, multi-rows).
  *    Add skin parameter - change images on each skins (see plugin description).
  *    Support for tableprefix (Nucleus versions > 2.0). (2004/01/02)
  *    Add option "Figure", and "Show begin date".
  *
  *
  * -----
  * Original version is written by Qi Liangpei.
  * webmaster@barb.51.net
  * http://barb.51.net
  *
  * History:
  *   v0.2 : Add 2 options, so it now can be graphical counter.
  *   v0.1 : just finished, and worked on my blog.
  */

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table'))
{
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_Counter extends NucleusPlugin { 

	/* plugin info */

    function getName() { return 'Hit Counter'; } 
    function getAuthor()  { return 'Qi Liangpei + yu'; } 
    function getURL()  { return 'http://works.datoka.jp/index.php?itemid=166'; } 
    function getVersion() { return '0.63'; } 
	function getMinNucleusVersion() { return 200; }
	function getTableList () { return array( sql_table('plugin_counter') ); }
	function supportsFeature($what) {
		switch($what)
		{
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

    function getDescription() { 
		return $this->description;
	} 

	function init() {
		// multi-language support
		$this->setDefaultStrings();
		$this->setLanguageFile( strtolower( _CHARSET ) );
	}

	function setLanguageFile($charset) {
		if ($charset == 'utf-8') 
			$charset .= '_' . strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"]); //exception on UTF-8
		
		@(include ($this->getDirectory() . $charset .'.php') ); //read external file
	}

	function setDefaultStrings() {
		$this->description = 
			'Display how many vistors browse your blog. '.
			'You can change "Count Mode",etc. in plugin option. '.
			'Usage: &lt;%Counter%&gt; or &lt;%Counter(another)%&gt;. '.
			'The skin parameter is an extra path which added to Graphics Path in plugin option '.
			'(so you can change images on each skins).';
		
		// words in plugin option
		$this->opt['graphical_counter'] = 'Graphical Counter';
		$this->opt['graphics_path'] = 'Graphics Path. Ensure this path correct. Default is nucleus/plugins/counter/. And it must be ended with a /';
		$this->opt['ext'] = 'Image File Extention (default:gif).';
		$this->opt['init_val'] = 'Initial Value. The count is overwritten by this value if this is not zero.';
		$this->opt['figure'] = 'Minimam Figures of the count(zero-filled). Set to "0" is available for no zero-filled. "6/3" means 6 to "total" counter and 3 to "detail" counter.';
		$this->opt['flg_detail'] = 'Show Detail(7days/Week, Yesterday, Today).';
		$this->opt['flg_week'] = 'Use "Week"(count from sunday to saturday) on detail mode.';
		$this->opt['flg_bdate'] = 'Show Begin Date.';
		$this->opt['begin_date'] = 'Begin Date.';
		$this->opt['count_mode'] = 'Count Mode. [normal] Count every accesses. [ip1] IP check (with time limitation). [ip2] Only one count in a day by the same IP.';
		$this->opt['time_limit'] = 'Time Limitation, not count accesses from same IP in the specified period (if Count Mode is selected to "ip1(time-limit)"). Set to "0" means no time-limit. UNIT:minute';
		$this->opt['flg_showmode'] = 'Show count mode information.';
		$this->opt['flg_pluglink'] = 'Show plugin link (which works as version checker when logged in by admin).';
		$this->opt['flg_erase'] = 'Erase count data on uninstall.';
	}
	
	function install() {
		sql_query('CREATE TABLE IF NOT EXISTS '. sql_table('plugin_counter') .' (
			count_time    DATETIME NOT NULL default "2003-08-15 23:00:00", 
			count_num     INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
			count_ipcheck VARCHAR(15) NOT NULL default "255.255.255.255" 
			)' ); 
		//sql_query('CREATE INDEX key_count_time ON '. sql_table('plugin_counter') .' (count_time)');
		
		$check_query = "SELECT * FROM ". sql_table('plugin_counter');
		$check_rows = sql_query($check_query);
		$num_rows = mysql_num_rows($check_rows); 
		if ($num_rows < 1) {
			$install_date = date("Y-m-d H:i:s");
			$install_ip = $_SERVER['REMOTE_ADDR'];
			$query = "INSERT INTO ". sql_table('plugin_counter') 
			       ." VALUES ('$install_date','1','$install_ip')";
			sql_query($query);
		}
		
		//basic features
		$this->createOption('graphical_counter',$this->opt['graphical_counter'],'yesno','yes');
		$this->createOption('graphics_path',    $this->opt['graphics_path'],'text','nucleus/plugins/counter/');
		$this->createOption('ext',              $this->opt['ext'],'text','gif');
		
		$this->createOption('init_val',  $this->opt['init_val'],'text','0');
		$this->createOption('figure',    $this->opt['figure'],'text','6/3');
		$this->createOption('flg_detail',$this->opt['flg_detail'],'yesno','no');
		$this->createOption('flg_week',  $this->opt['flg_week'],'yesno','no');
		$this->createOption('flg_bdate', $this->opt['flg_bdate'],'yesno','yes');
		$this->createOption('begin_date',$this->opt['begin_date'],'text','Since '.date("Y-m-d"));
		
		//count mode
		if(getNucleusVersion() < 220) {
			$this->createOption('count_mode', $this->opt['count_mode'], 'text','ip1');
		}
		else {
			$this->createOption('count_mode', $this->opt['count_mode'], 'select','ip1','Normal|normal|IP Check1(time-limit available)|ip1|IP Check2(one count in a day)|ip2');
		}
		$this->createOption('time_limit',  $this->opt['time_limit'],'text','30');
		$this->createOption('flg_showmode',$this->opt['flg_showmode'],'yesno','yes');
		$this->createOption('flg_pluglink',$this->opt['flg_pluglink'],'yesno','no');
		$this->createOption('flg_erase',   $this->opt['flg_erase'],'yesno','no');
	}

	function unInstall() {
		if ($this->getOption('flg_erase') == 'yes')
			sql_query('DROP TABLE '. sql_table('plugin_counter')); 
	}

	function doSkinVar($skinType, $mode='', $expath='') {
		global $manager, $blog, $CONF;
		
		if ($mode == 'image+sincetext') {
			$mode = 'image';
			$is_sincetext = true;
		}
		if (!$mode and $this->getOption('graphical_counter') == "yes") $mode = 'image';
		
		$ext        = $this->getOption('ext');
		$is_bdate   = $this->getOption('flg_bdate');
		$is_detail  = $this->getOption('flg_detail');
		$is_week    = $this->getOption('flg_week');
		$is_showmode= $this->getOption('flg_showmode');
		$is_pluglink= $this->getOption('flg_pluglink');
		$count_mode = $this->getOption('count_mode');
		$tlimit   = (int)$this->getOption('time_limit');
		$init_val = (int)$this->getOption('init_val');
		
		$fig = $this->getOption(figure);
		list($fig, $fig_detail) = split('/', $fig); //separate figure settings
		$fig        = (int)$fig;
		$fig_detail = (int)$fig_detail;
		
		//get the latest count
		$query_number = sql_query("SELECT * FROM ". sql_table('plugin_counter') 
			." ORDER BY count_time DESC LIMIT 1");
		if ($the_number = mysql_fetch_array($query_number)){
			$old_number = $the_number["count_num"];
			$old_date   = $the_number["count_time"];
			$begin_date = $the_number["count_begin"];
			
			$old_ip = $the_number["count_ipcheck"];
			$new_ip = $_SERVER['REMOTE_ADDR'];
			
			$b =& $manager->getBlog($CONF['DefaultBlog']);
			$new_time = $b->getCorrectTime();
			$new_date = date("Y-m-d H:i:s", $new_time);
			
			//make begin-date strings
			if ($is_bdate == 'yes' and $this->getOption('begin_date') != '') {
				$str_bdate = $this->getOption('begin_date');
			}
			
			//check count in a span
			if ($count_mode == "ip1") {
				$limit_time = $new_time - $tlimit * 60;
				$chk_date = date('Y-m-d H:i:s', $limit_time);
				$query_ip1 = sql_query("SELECT COUNT(*) FROM ". sql_table('plugin_counter') 
					." WHERE count_time >= '$chk_date'" 
					." AND count_ipcheck = '$new_ip'"
					);
				$ip1_num = mysql_fetch_array($query_ip1);
			}
			
			//check count in a same day
			if ($count_mode == "ip2") {
				$chk_date = date('Y-m-d 00:00:00', $new_time);
				$query_ip2 = sql_query("SELECT COUNT(*) FROM ". sql_table('plugin_counter') 
					." WHERE count_time >= '$chk_date'" 
					." AND count_ipcheck = '$new_ip'"
					);
				$ip2_num = mysql_fetch_array($query_ip2);
			}
			
			// hit count
			if ( $count_mode == "normal" or 
				($count_mode == "ip1" and $tlimit == 0 and $new_ip != $old_ip) or 
				($count_mode == "ip1" and $tlimit >  0 and $ip1_num[0] == 0) or 
				($count_mode == "ip2" and $ip2_num[0] == 0)
			   ) {
				if ($init_val > 0) { //set value from 'initial value' option
					$insert_query = "INSERT INTO ". sql_table('plugin_counter') 
						." SET count_time='$new_date', count_num='$init_val', count_ipcheck='$new_ip'";
					sql_query($insert_query);
					
					$new_number = $init_val;
					$this->setOption('init_val', 0);
				}
				else {
					$insert_query = "INSERT INTO ". sql_table('plugin_counter') 
						." SET count_time='$new_date', count_ipcheck='$new_ip'";
					sql_query($insert_query);
					
					$new_number = $old_number + 1;
				}
				
				//delete old data (save only 7days)
				$del_date = date('Y-m-d 00:00:00', mktime(0,0,0,date("m"),date("d")-6,date("Y")) );
				sql_query("DELETE FROM ". sql_table('plugin_counter') ." WHERE count_time < '$del_date'" );
			}
			else {
				$new_number = $old_number;
			}
			
			if ($fig > 0) $new_number = sprintf('%0'.$fig.'d', $new_number);
			
			//get detail count
			if ($is_detail=='yes') {
				//today
				$t_date = date('Y-m-d', $new_time);
				$query_today = sql_query("SELECT COUNT(count_time) FROM ". sql_table('plugin_counter') 
					." WHERE SUBSTRING(count_time,1,10) = '$t_date'" );
				$today_num = mysql_fetch_array($query_today);
				if ($today_num == 0) $today_num = 1; //exception
				
				//yesterday
				$y_date = date('Y-m-d', mktime(0,0,0,date("m"),date("d")-1,date("Y")) );
				$query_yest = sql_query("SELECT COUNT(count_time) FROM ". sql_table('plugin_counter') 
					." WHERE SUBSTRING(count_time,1,10) = '$y_date'" );
				$yest_num = mysql_fetch_array($query_yest);
				
				if ($is_week != 'yes') {
					//7days (all data)
					$query_week = sql_query("SELECT COUNT(count_time) FROM ". sql_table('plugin_counter') );
					$week_num = mysql_fetch_array($query_week);
				}
				else {
					//week (from Sunday to Saturday)
					$d_of_week = date('w', $new_time);
					$week_date = date('Y-m-d', mktime(0,0,0,date("m"),date("d")-$d_of_week,date("Y")) );
					$query_week = sql_query("SELECT COUNT(count_time) FROM ". sql_table('plugin_counter') 
						." WHERE SUBSTRING(count_time,1,10) >= '$week_date'" );
					$week_num = mysql_fetch_array($query_week);
				} 
				
				if ($fig_detail > 0) {
					$today_num[0] = sprintf('%0'.$fig_detail.'d', $today_num[0]);
					$yest_num[0]  = sprintf('%0'.$fig_detail.'d', $yest_num[0]);
					$week_num[0]  = sprintf('%0'.$fig_detail.'d', $week_num[0]);
				}
			}
			
			//count mode
			if ($is_showmode=='yes') {
				switch ($count_mode) {
					case 'normal':
						$str_cmode = "Count for every access";
						break;
					case 'ip1':
						$str_cmode = "IP check in $tlimit min";
						break;
					case 'ip2':
						$str_cmode = "1count/day on each IP";
						break;
				}
			}
			
			//plugin link
			if ($is_pluglink == 'yes') {
				$pluglink_url = $this->getURL();
				$str_pluglink = "[PLink]";
				$pnotice = 0;
				
				//version check
				if ($this->canEdit()) {
					$chkver = $this->getLatestVersion($pluglink_url);
					if ($chkver > $this->getVersion()) {
						$pnotice = 1;
						$str_pluglink = "[Ver $chkver available]";
					}
				}
			}
			
			echo "<div id='counter'>";
			
			//graphical counter
			if ($mode == 'image') {
				$image_path  = $this->getOption('graphics_path');
				if (!empty($expath)) $image_path .= $expath.'/';
				$image_total = $this->getImageTags($new_number, $image_path, $ext);
				
				if ($is_detail=='yes') {
					$str_total = "<img src='{$image_path}total.$ext' class='icon' alt='Total' />";
					echo "{$str_total}$image_total";
					echo "\n<span class='counter-detail'>";
					
					$image_week  = $this->getImageTags($week_num[0], $image_path, $ext);
					$image_yest  = $this->getImageTags($yest_num[0], $image_path, $ext);
					$image_today = $this->getImageTags($today_num[0], $image_path, $ext);
					$str_t = "<img src='{$image_path}today.$ext' class='icon' alt='Today' />";
					$str_y = "<img src='{$image_path}yesterday.$ext' class='icon' alt='Yesterday' />";
					if ($is_week == 'yes') 
						$str_week = "<img src='{$image_path}week.$ext' class='icon' alt='Week' />";
					else 
						$str_week = "<img src='{$image_path}7days.$ext' class='icon' alt='7days' />";
					echo "{$str_week}$image_week {$str_y}$image_yest {$str_t}$image_today</span>\n";
				}
				else {
					echo $image_total;
				}
				//show count mode 
				if ($str_cmode) echo " <img src='{$image_path}mode.$ext' class='icon' alt='$str_cmode' />";
				
				//show plugin link
				if ($str_pluglink) {
					if ($pnotice) $str_pversion = $str_pluglink;
					else $str_pversion = '';
					echo " <a href='$pluglink_url' title='Jump to the site of this plugin $str_pversion'>";
					echo "<img src='{$image_path}plink$pnotice.$ext' /></a>";
				}
				
				//show begin date
				if ($str_bdate) {
					if ($is_sincetext) echo ' '.$str_bdate;
					else echo " <img src='{$image_path}since.$ext' class='icon' alt='$str_bdate' />";
				}
			}
			else { //text counter
				echo $new_number;
				if ($is_detail=='yes') {
					echo " <span class='counter-detail'>\n";
					
					if ($is_week == 'yes') $str_week = 'W';
					else $str_week = '7D';
					echo "($str_week:$week_num[0] Y:$yest_num[0] T:$today_num[0])</span>\n";
				}
				//show count mode 
				echo " <span title='$str_cmode'>[Mode]</span>";
				
				//show plugin link
				if ($str_pluglink) echo " <a href='$pluglink_url' title='Jump to the site of this plugin'>$str_pluglink</a>";
				
				//show begin date
				if ($str_bdate) echo ' '.$str_bdate;
			}
			echo "\n</div>";
		}
	}

	// helper function
	function getImageTags($nstr, $image_path, $ext){
		if (empty($ext)) $ext = 'gif';
		$image = '';
		$num = strlen($nstr);
		$i = 0;
		while ($i < $num) {
			$digit = substr ($nstr, $i, 1);
			$image .= "<img src='{$image_path}{$digit}.$ext' class='icon' alt='$digit' />";
			$i++;
		}
		return $image;
	}

	function canEdit() {
		global $member, $manager;
		if (!$member->isLoggedIn()) return 0;
		return $member->isAdmin();
	}
	
	function getLatestVersion($url) {
		
		$name = $this->getShortName();
		if ($_COOKIE[$name]) return 0;
		
		$fp = @fopen ($url, "r");
		if ($fp){
			$ref_str = fread($fp, 16384);
			if (eregi("<version>(.*)</version>", $ref_str, $out)) {
				setcookie($name,1,null,'/'); // set session cookie
				return $out[1];
			}
		}
		return 0;
	}

}

?>