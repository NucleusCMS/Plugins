<?php 
class NP_OnlineUsers extends NucleusPlugin { 
	function getName() { return 'NP_OnlineUsers'; }
	function getMinNucleusVersion() { return 220; }
	function getAuthor()  { return 'Katsumi'; }
	function getVersion() { return '0.1.2'; }
	function getURL() {return 'http://hp.vector.co.jp/authors/VA016157/';}
	function getDescription() { return $this->getName().' plugin'; } 
	function supportsFeature($what) { return (int)($what=='SqlTablePrefix'); }
	function getEventList() { return array('PreSkinParse'); }
	function getTableList() { return $this->sql_query('list'); }
	function install() {
		$this->sql_query('create','(
			id int(11) not null auto_increment,
			ip varchar(16) not null default "000.000.000.000",
			mid int(11) not null default 0,
			itemid int(11) not null default 0,
			catid int(11) not null default 0,
			time datetime not null default "0000-00-00 00:00:00",
			PRIMARY KEY id(id)
			)');
		$this->createOption('howlong','How many minutes do you collect the data?','text','5','datatype=numerical');
		$this->createOption('siteadmin','Template for showing siteadmin:','textarea','<span style="color: rgb(255, 163, 79);"><%name%></span>');
		$this->createOption('moderator','Template for showing blogadmin:','textarea','<span style="color: rgb(0, 102, 0);"><%name%></span>');
		$this->createOption('member','Template for showing other member:','textarea','<%name%>');
		$this->createOption('maxonlinenum','Maximum online user number','text','0','access=hidden');
		$this->createOption('recorddate','When recorded (timestamp)?','text','0','access=hidden');
	}
	function unInstall() { $this->sql_query('drop'); }
	var $data=false;
	function doSkinVar($skinType,$type,$p1='') {
		global $manager, $itemid, $catid;
		if (!$this->data){
			$res=$this->sql_query('SELECT * FROM');
			$this->data=array();
			while($row=mysql_fetch_assoc($res)) $this->data[]=$row;
			mysql_free_result($res);
			if (count($this->data)>(int)$this->getOption('maxonlinenum')) {
				$this->setOption('maxonlinenum',count($this->data));
				$this->setOption('recorddate',$this->time);
			}
		}
		switch($type=strtolower($type)){
		case 'online':
		case 'member':
		case 'guest':
			$count=0;
			foreach($this->data as $row){
				if (0<$row[mid]){
					if ($type!='guest') $count++;
				} else {
					if ($type!='member') $count++;
				}
			}
			echo (int)$count;
			break;
		case 'recordtime':
			$template =& $manager->getTemplate($p1);
			echo strftime($template['FORMAT_TIME'],$this->getOption('recorddate'));
			break;
		case 'recorddate':
			$template =& $manager->getTemplate($p1);
			echo strftime($template['FORMAT_DATE'],$this->getOption('recorddate'));
			break;
		case 'howlong':
		case 'maxonlinenum':
			echo htmlspecialchars($this->getOption($type),ENT_QUOTES);
			break;
		case 'onlinelist':
			$moderators=array();
			$query='SELECT m.mnumber FROM '.sql_table('member').' as m, '.
				sql_table('team').' as t'.
				' WHERE m.mnumber=t.tmember AND t.tadmin>0';
			$res=sql_query($query);
			while($row=mysql_fetch_row($res)) $moderators[$row[0]]=$row[0];
			mysql_free_result($res);
			$list=array();
			$extra=strstr($p1,'category')?' AND o.catid='.(int)$catid:'';
			$extra.=strstr($p1,'item')?' AND o.itemid='.(int)$itemid:'';
			$query='SELECT m.mnumber as mid, m.mrealname as name, m.madmin as admin FROM '.
				sql_table('member').' as m, '.$this->sql_query('name').' as o'.
				' WHERE m.mnumber=o.mid'.$extra.' ORDER BY o.time DESC';
			$res=sql_query($query);
			while($row=mysql_fetch_assoc($res)) {
				$template=$this->getOption('member');
				if ($moderators[$row['mid']]) $template=$this->getOption('moderator');
				if ($row['admin']) $template=$this->getOption('siteadmin');
				$list[]=TEMPLATE::fill($template,array('name'=>htmlspecialchars($row['name'],ENT_QUOTES)));
			}
			mysql_free_result($res);
			echo implode(',',$list);
		default:
			break;
		}
	}
	function event_PreSkinParse(){
		global $blog,$member,$catid,$itemid;
		if ($blog) $this->time=$blog->getCorrectTime();
		else $this->time=time();
		$ip=addslashes(serverVar('REMOTE_ADDR'));
		$this->sql_query('DELETE FROM','WHERE ip="'.$ip.'"'.
			' OR ( mid='.(int)$member->getID().' AND mid>0 )'.
			' OR time<='.mysqldate(($this->time)-$this->getOption('howlong')*60));
		$this->sql_query('INSERT INTO','SET'.
			' ip="'.$ip.'"'.
			',mid='.(int)$member->getID().
			',itemid='.(int)$itemid.
			',catid='.(int)$catid.
			',time='.mysqldate($this->time));
	}
	function sql_query($mode='name',$p1=''){
		$tablename[0]=sql_table(strtolower('plugin_'.substr(get_class($this),3)));
		switch($mode){
		case 'create': return sql_query('CREATE TABLE IF NOT EXISTS '.$tablename[0].' '.$p1);
		case 'drop':   return sql_query('DROP TABLE IF EXISTS '.$tablename[0]);
		case 'list':   return $tablename;
		case 'name':   return $tablename[0];
		default:       return sql_query($mode.' '.$tablename[0].' '.$p1);
		}
	}
	function quickQuery($mode,$p1){
		$row=mysql_fetch_assoc($res=$this->sql_query($mode,$p1));
		mysql_free_result($res);
		return $row['result'];
	}
	//function getOption($name){ return $this->_getOption('global', 0, $name); }//required for Nucleus 3.24
}
?>