<?php
// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) { return 'nucleus_' . $name; }
}

class NP_UpdateTime extends NucleusPlugin {
	function getName() { return 'UpdateTime'; }
	function getAuthor()  { return 'nakahara21'; }
	function getURL() { return 'http://xx.nakahara21.net/'; }
	function getVersion() { return '0.7'; }
	function getDescription() { return 'Record updatetime when the item updated.'; }
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	function getTableList() {	return array( sql_table('plugin_rectime') ); }
	function getEventList() { return array('EditItemFormExtras','PreUpdateItem'); }
	function install() {
		sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_rectime'). ' (up_id int(11) not null, updatetime datetime, PRIMARY KEY (up_id))');
		$this->createOption('DefautMode','デフォルトのモードは？(0:何もしない, 1:更新日時記録のみ, 2:アイテム日時上書き)','text','1');
		$this->createOption('BeforeTime','アイテム日時上書きの場合の表示形式:','text','※ このアイテムは<%utime%>に保存されたものを再編集しています');
		$this->createOption('AfterTime','更新日時記録のみの場合の表示形式','text','最終更新日時:<%utime%>');
		$this->createOption('DateFormat','テンプレート内の日時表示形式(phpのdate関数 例 Y-m-d H:i:s):','text','Y-m-d H:i:s');
		$this->createOption('s_lists','最新更新リストの開始タグ','text','<ul class="nobullets">');
		$this->createOption('e_lists','最新更新リストの終了タグ','text','</ul>');
		$this->createOption('s_items','最新更新リストの各アイテムの開始タグ','text','<li>');
		$this->createOption('e_items','最新更新リストの各アイテムの終了タグ','text','</li>');
		$this->createOption("del_uninstall", "Delete tables on uninstall?", "yesno", "no");
	}
	function unInstall() { 
		if ($this->getOption('del_uninstall') == "yes") {
			mysql_query ("DROP TABLE IF EXISTS ".sql_table('plugin_rectime'));
		}
	}
	function init() {
		if(($this->def_mode = intval($this->getOption('DefautMode'))) > 2){
			$this->def_mode = 0;
		}
	}

	function event_EditItemFormExtras($data) {
		$checked_flag[$this->def_mode] = ' checked="checked"';
		echo '<h3 style="margin-bottom:0;">更新時刻の記録方法の選択</h3>';
		echo '<input type="radio" name="updatetime" value="2" id="updatetime_2"'.$checked_flag[2].' /><label for="updatetime_2">アイテム日時として上書きする</label><br />';
		echo '<input type="radio" name="updatetime" value="1" id="updatetime_1"'.$checked_flag[1].' /><label for="updatetime_1">更新日時を記録するのみ</label><br />';
		echo '<input type="radio" name="updatetime" value="0" id="updatetime_0"'.$checked_flag[0].' /><label for="updatetime_0">何もしない</label><br />';
	}

	function event_PreUpdateItem($data) {
		global $manager;

		$recd = intRequestVar('updatetime');
		if (!$recd) return;
		if (postVar('actiontype') == 'adddraft') return;

		$updatetime = mysqldate($data['blog']->getCorrectTime());
		if ($recd == 2){
			$up_query = 'UPDATE '.sql_table('item').' SET itime='.$updatetime.' WHERE inumber='.$data['itemid'];
			$updatetime = '"'.quickQuery('SELECT itime as result FROM '.sql_table('item').' WHERE inumber='.$data['itemid']).'"';
			$tmptime = '"'.quickQuery('SELECT updatetime as result FROM '.sql_table('plugin_rectime').' WHERE up_id='.$data['itemid']).'"';
			if($tmptime > $updatetime)
				$updatetime = $tmptime;
			sql_query($up_query);
		}
		sql_query('DELETE FROM '.sql_table('plugin_rectime')." WHERE up_id=".$data['itemid']);
		$query = 'INSERT INTO ' . sql_table('plugin_rectime') . " (up_id, updatetime) VALUES ('".$data['itemid']."',".$updatetime.")";
		$res = @mysql_query($query);
		if (!$res) 
			return 'Could not save data: ' . mysql_error();
	}

	function doSkinVar($skinType, $maxtoshow = 5, $bmode = 'current') {
		global $manager, $CONF, $blogid;
		$b =& $manager->getBlog($CONF['DefaultBlog']);
		$this->defaultblogurl = $b->getURL() ;
		if(!$this->defaultblogurl)
			$this->defaultblogurl = $CONF['IndexURL'] ;

		if($maxtoshow == ''){$maxtoshow = 5;}
		if($bmode == ''){$bmode = 'current';}

		echo $this->getOption(s_lists)."\n";
		$query = 'SELECT r.up_id as up_id, IF(INTERVAL(r.updatetime, i.itime), UNIX_TIMESTAMP(r.updatetime), UNIX_TIMESTAMP(i.itime) ) as utime FROM '.sql_table('plugin_rectime') . ' as r, '.sql_table('item') .' as i WHERE  r.up_id=i.inumber';
		if($bmode != 'all'){
			$query .= ' and i.iblog='.$blogid;
		}	
		$query .= ' ORDER BY utime DESC';
		$query .= ' LIMIT 0,'.intval($maxtoshow);
		$res = mysql_query($query);
		while($row = mysql_fetch_object($res)){
			$item =& $manager->getItem($row->up_id,0,0);
			if($item){
				$itemlink = $this->createGlobalItemLink($item['itemid'], '');
				$itemtitle = strip_tags($item['title']);
				$itemtitle = shorten($itemtitle,26,'..');
				$itemdate = date('m/d H:i',$row->utime);

				echo $this->getOption(s_items)."\n";
				echo '<a href="'.$itemlink.'">'.$itemtitle.'</a> <small>'.$itemdate."</small>\n";
				echo $this->getOption(e_items)."\n";
			}
		}
		echo $this->getOption(e_lists);
	}

	function doTemplateVar(&$item){
		$query = 'SELECT r.up_id, UNIX_TIMESTAMP(r.updatetime) as updatetime, UNIX_TIMESTAMP(i.itime) as itemtime FROM '.sql_table('plugin_rectime') . ' as r, '.sql_table('item') .' as i WHERE r.up_id='.$item->itemid.' and r.up_id=i.inumber';
		$res = sql_query($query);
		if($row = mysql_fetch_assoc($res)){
			$data['utime'] = date($this->getOption('DateFormat'),$row['updatetime']);
			if($row['updatetime'] > $row['itemtime']){
				echo TEMPLATE::fill($this->getOption('AfterTime'),$data);;
			}elseif($row['updatetime'] < $row['itemtime']){
				echo TEMPLATE::fill($this->getOption('BeforeTime'),$data);;
			}
		}
	}

	function createGlobalItemLink($itemid, $extra = '') {
		global $CONF, $manager;
		if ($CONF['URLMode'] == 'pathinfo'){
			$link = $CONF['ItemURL'] . '/item/' . $itemid;
		}else{
			$blogid = getBlogIDFromItemID($itemid);
			$b_tmp =& $manager->getBlog($blogid);
			$blogurl = $b_tmp->getURL() ;
			if(!$blogurl){
				$blogurl = $this->defaultblogurl;
			}
			if(substr($blogurl, -4) != '.php'){
				if(substr($blogurl, -1) != '/')
					$blogurl .= '/';
				$blogurl .= 'index.php';
			}
			$link = $blogurl . '?itemid=' . $itemid;
		}
		return addLinkParams($link, $extra);
	}
}
?>