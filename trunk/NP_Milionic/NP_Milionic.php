<?php

class NP_Milionic extends NucleusPlugin {

	function getName() 		{ return 'DHTML Tree Menu'; }
	function getAuthor()  	{ return 'nakahara21'; }
	function getURL()  		{ return 'http://nakahara21.com/'; }
	function getVersion() 	{ return '0.8'; }
	function getDescription() { return 'JavaScript DHTML navigation';	}

	function supportsFeature($what) {
		switch($what)
		{ case 'SqlTablePrefix':
				return 1;
			default:
				return 0; }
	}

	function install() {
	}
	
	function unInstall() {
	}


	function doSkinVar($skinType){
		global $CONF, $manager, $blogid, $catid, $subcatid;
		
		
		
		$fileName = $CONF['ActionURL'].'?action=plugin&name=Milionic&type=f&st='.$skinType;
		$fileName .= '&bid='.$blogid;
		if($catid) $fileName .= '&cid='.$catid;
		if($subcatid) $fileName .= '&sid='.$subcatid;


?>

<script type="text/javascript" src="<?php echo $this->getAdminURL(); ?>milonic_src.js"></script>
<div class=milonic><a href="http://www.milonic.com/">JavaScript Menu, DHTML Menu Powered By Milonic</a></div>
<script	type="text/javascript">
	<!--
	if(ns4)_d.write("<scr"+"ipt type=text/javascript src='<?php echo $this->getAdminURL(); ?>mmenuns4.js'><\/scr"+"ipt>");
	  else _d.write("<scr"+"ipt type=text/javascript src='<?php echo $this->getAdminURL(); ?>mmenudom.js'><\/scr"+"ipt>");
	-->
</script>
<script type="text/javascript" src="<?php echo $fileName; ?>"></script>
<table>
<td>
<script type="text/javascript">
	<!--
drawMenus();
	-->
</script>
<noscript>
Site navigation will open here if you enable JavaScript in your browser.
</noscript>
</td>
</table>


<?php
	}

	function doAction($type) {
		if($type == 'f'){
		global $manager, $CONF;
		$aurl = $this->getAdminURL().'icons/';
		$blogid = intRequestVar('bid');
		$catid = intRequestVar('cid');
		$subcatid = intRequestVar('sid');
		$skinType = requestVar('st');

//++++++++++++++++++++++++++++++++++++++
$pathToCodeFiles=$aurl;               // The www root to where the menu code files are located

/// The following is only changed if the name of the menu code files have been changed.
$menuVars=array();
$menuData="";

//++++++++++++++++++++++++++++++++++++++

echo <<<EOD

_menuCloseDelay=150;
_menuOpenDelay=10;
_subOffsetTop=0;
_subOffsetLeft=2;

with(submenuStyle=new mm_style()){
fontfamily="Verdana";
fontsize="80%";
fontstyle="normal";
high3dcolor="#ffffff";
low3dcolor="#336633";
offbgcolor="#DFDFBF";
offborder="1px solid #BDBDA2";
offcolor="#000000";
onbgcolor="#878774";
onborder="2px outset #CBCBAE";
oncolor="#ffffff";
onsubimage="{$aurl}white_arrow.gif";
padding=4;
separatorcolor="#a0c0a0";
separatorsize=1;
subimage="{$aurl}arrow.gif";
subimagepadding=4;
image='{$aurl}grey-bar.gif';
pageimage="{$aurl}grey-bar-select.gif";
pagematch="";
}

with(menuStyle=new mm_style()){
fontfamily="Verdana";
fontsize="80%";
fontstyle="normal";
high3dcolor="#ffffff";
low3dcolor="#336633";
offbgcolor="#DFDFBF";
offborder="1px solid #BDBDA2";
offcolor="000000";
onbgcolor="#878774";
onborder="2px outset #CBCBAE";
oncolor="#ffffff";
onsubimage="{$aurl}white_arrow.gif";
padding=4;
separatorcolor="#336600";
separatorsize=1;
subimage="{$aurl}arrow.gif";
subimagepadding=4;
itemwidth=100;
image='{$aurl}grey-bar.gif';
pageimage="{$aurl}grey-bar-select.gif";
pagematch="";
}

with(milonic=new menuname("Main Menu")){
alwaysvisible=1;
orientation="Vertical";
position="relative";
style=menuStyle;
aI("status=Back To Home Page;text=HOME;url=http://www.milonic.com/;");
aI("showmenu=Categores;text=Categores;pagematch=catid=;");
aI("showmenu=Archives;text=Archives;pagematch=archive;");
aI("showmenu=Remarks;text=Remarks;");
aI("showmenu=Links;text=LINKS;");
aI("showmenu=Search;text=¸¡º÷;");
aI("showmenu=Login;text=Login;");
aI("showmenu=Otherblog;fontsize=xx-small;fontfamily=Tahoma;text=`<small>Other Blog</small>`;pageimage=;");
}


with(milonic=new menuname("Links")){
style=submenuStyle;
aI("status=Apache Web Server, the basis of Milonic's Web Site;text=Apache Server;url=http://www.apache.org/;");
aI("status=MySQL, Milonic's Prefered Choice of Database Server;text=MySQL Database Server;url=http://ww.mysql.com/;");
aI("status=PHP - Web Server Scripting as used by Milonic;text=PHP - Development;url=http://www.php.net/;");
aI("status=PHP Based Web Forum, Milonic's Recommended Forum Software;text=phpBB Web Forum System;url=http://www.phpbb.net/;");
aI("showmenu=Anti Spam;status=Anti Spam Solutions, as used by Milonic;text=Anti Spam;");
}

with(milonic=new menuname("Anti Spam")){
style=submenuStyle;
aI("text=Spam Cop;url=http://www.spamcop.net/;");
aI("text=Mime Defang;url=http://www.mimedefang.org/;");
aI("text=Spam Assassin;url=http://www.spamassassin.org/;");
}


EOD;
//++++++++++++++++++++++++++++++++++++++
$menuVars=array();
$menuData="";
//++(SEARCH)++++++++++++++++++++++++++++++++++++
		$mmMenu=new mMenu();
		$mmMenu->style="submenuStyle";
		$searchForm = $this->doParse('<%searchform%>',$skinType);
		$mmMenu->addItemFromText('text=`' . $searchForm . '`;type=form;align=center;onbgcolor=;onborder=;image=;');
		$mmMenu->createMenu("Search");

//++(Login)++++++++++++++++++++++++++++++++++++
		$mmMenu=new mMenu();
		$mmMenu->style="submenuStyle";
		$searchForm = $this->doParse('<%loginform%>',$skinType);
		$mmMenu->addItemFromText('text=`' . $searchForm . '`;type=form;align=center;onbgcolor=;onborder=;image=;');
		
		$mmMenu->addItemFromText('text=Jump to Admin Page;url='.$CONF['AdminURL'].';align=center;onbgcolor=;onborder=;image=;');
		$mmMenu->createMenu("Login");

//++(other blog)++++++++++++++++++++++++++++++++++++

		$query = 'SELECT bnumber as blogid, bname as blogname, burl as blogurl, bshortname, bdesc as blogdesc';
		$query .= ' FROM '.sql_table('blog');
		$query .= ' WHERE bnumber <>'.$blogid;
		$query .= ' ORDER BY bnumber';

		$res = sql_query($query);
		$bn=1;
		$mmMenu=new mMenu();
		$mmMenu->style="submenuStyle";
		while ($o = mysql_fetch_object($res)) {
			$this->bid = $o->blogid;
			$burl = createBlogidLink($o->blogid);
			$mmMenu->addItemFromText("text=".$o->blogname.";url=".$burl.";pageimage=;");
		}
		$mmMenu->createMenu("Otherblog");


//++(archives)++++++++++++++++++++++++++++++++++++
		if($amTemp = $this->_getArchiveList('month', 0, $blogid, $catid, $subcatid)){
//			print_r($amTemp);
			$archiveYears = array_keys($amTemp);
			$mmMenu=new mMenu();
			$mmMenu->style="submenuStyle";
			$mmMenu->overflow="scroll";
			for($i=0;$i<count($archiveYears);$i++){
				$mmMenu->addItemFromText("showmenu=ay" . $archiveYears[$i] . ";text=" . $archiveYears[$i] . ";pagematch=" . $archiveYears[$i] . ";");
			}
			$mmMenu->createMenu("Archives");
				
				
			foreach($amTemp as $ay => $values){
				$mmMenu=new mMenu();
				$mmMenu->style="submenuStyle";
				$mmMenu->overflow="scroll";
				for($i=0;$i<count($values);$i++){
					$mmMenu->addItemFromText("showmenu=am" . $values[$i]['archivedate'] . ";text=" . $values[$i]['archivedate'] . ";url=" . $values[$i]['archivelink'] . ";pagematch=" . $values[$i]['archivedate'] . ";");
				}
				$mmMenu->createMenu("ay".$ay);
			}

			$adTemp = $this->_getArchiveList('day', 0, $blogid, $catid, $subcatid);
			foreach($adTemp as $archiveMonth => $Values){
				$mmMenu=new mMenu();
				$mmMenu->style="submenuStyle";
				$mmMenu->overflow="scroll";
				for($i=0;$i<count($Values);$i++){
					$mmMenu->addItemFromText("text=" . $Values[$i]['archivedate'] . ";url=" . $Values[$i]['archivelink'] . ";pagematch=" . $Values[$i]['archivedate'] . ";");
				}
				$mmMenu->createMenu($archiveMonth);
			}
			
		}else{
			$mmMenu=new mMenu();
			$mmMenu->style="submenuStyle";
			$mmMenu->addItemFromText("text=(No archives);image=;");
			$mmMenu->createMenu("Archives");
		}
//++(category)++++++++++++++++++++++++++++++++++++
		$cquery = 'SELECT catid as catid, cname as catname, cdesc as catdesc FROM '.sql_table('category').' WHERE cblog='.$blogid.' ORDER BY catid';
		$cres = sql_query($cquery);
		$mmMenu=new mMenu();
		$mmMenu->style="submenuStyle";
		while ($co = mysql_fetch_object($cres)) {
			$curl = $this->_de(createBlogidLink($blogid, array('catid'=>$co->catid)));
			$chkFlg = $this->checkMSCVersion();
			$menuExtra = "";
			if($chkFlg > 1){
				$keyName = 'catid'.$co->catid;
				if($subcatArray[$keyName] = $this->_getScatsFromCatid($co->catid, $chkFlg)){
					$menuExtra = "showmenu=catid" . $co->catid.";";
				}
			}
			$mmMenu->addItemFromText($menuExtra . "text=" . $co->catname . ";url=" . $curl . ";pagematch=catid=" . $co->catid . ";");
		} //end of storing catid item
		$mmMenu->createMenu("Categores");

//		print_r($subcatArray);
		if($subcatArray){
			foreach($subcatArray as $keyName=>$valueArray){
				if($valueArray){
				$cid = str_replace('catid','',$keyName);
				$mmMenu=new mMenu();
				$mmMenu->style="submenuStyle";
				for($i=0;$i<count($valueArray);$i++){
					$sid = $valueArray[$i]['scatid'];
					$surl = $this->_de(createBlogidLink($blogid, array('catid'=>$cid, 'subcatid'=>$sid)));
					if($this->checkMSCVersion() > 2){
						$this->getDescendantFromScatid($sid, 0);
						$extra = ($this->r[$sid])? 'showmenu=sid'.$sid.';' : '';
						$mmMenu->addItemFromText($extra."text=" . $valueArray[$i]['sname'] . ";url=" . $surl . ";pagematch=catid=" . $cid ."&subcatid=".$sid.";");
					}else{
						$mmMenu->addItemFromText("text=" . $valueArray[$i]['sname'] . ";url=" . $surl . ";pagematch=catid=" . $cid ."&subcatid=".$sid.";");
					}
				}
				$mmMenu->createMenu("$keyName");
				}
			}
		}

		if($this->r){
			foreach($this->r as $sid=>$valueArray){
				$mmMenu=new mMenu();
				$mmMenu->style="submenuStyle";
				for($i=0;$i<count($valueArray);$i++){
					$ssid = $valueArray[$i];
					$sname = $this->mplugin->_getScatNameFromID($ssid);
					$surl = createBlogidLink($blogid, array('catid'=>$cid, 'subcatid'=>$ssid));
					$extra = ($this->r[$ssid])? 'showmenu=sid'.$ssid.';' : '';
						$mmMenu->addItemFromText($extra."text=" . $sname . ";url=" . $this->_de($surl) . ";pagematch=catid=" . $cid ."&subcatid=".$ssid.";");
				}
				
				$mmMenu->createMenu("sid".$sid);
			}
		}
//++(Remarks)++++++++++++++++++++++++++++++++++++
		$mmMenu=new mMenu();
		$mmMenu->style="submenuStyle";

		if($remarks = $this->_getRemarks($blogid, $catid, $subcatid, $mode='both')){
			$mmMenu->divides=3;
			$mmMenu->overflow="scroll";
			for($i=0;$i<count($remarks);$i++){
				$text =  '<b>'.$remarks[$i][name] . "</b>(" . $remarks[$i][date] . ")<br />" . $remarks[$i][shortentext];
				$mmMenu->addItemFromText("text=" . $this->removeN($text) . ";url=" . $remarks[$i][linkurl] . "#c" . $remarks[$i][commentid] .";pagematch=itemid=".$remarks[$i][itemid].";image=;");
			}
		}else{
			$mmMenu->addItemFromText("text=(No remarks);image=;");
		}
		$mmMenu->createMenu("Remarks");






		commitMenus();
//++++++++++++++++++++++++++++++++++++++
		}else{
			return;
		}
	}	//end of function doAction

	
	function removeN($var) {
		return preg_replace("/[\r\n]/","",$var); 
	}
	
	function doParse($content,$type='') {
		global $CONF;
		$sType = $type;
		if ($type == 'pageparser') {
			$type = 'index';
		}
		$handler = new ACTIONS($sType);
		$parser = new PARSER(SKIN::getAllowedActionsForType($type), $handler);
		$handler->parser =& $parser;
		ob_start();
			$parser->parse($content);
			$res = ob_get_contents();
		ob_end_clean();
		$res = str_replace('"',"'",$res);
		$res = str_replace("\r\n","",$res);

		return $res;
	}

	function _getRemarks($blogid, $catid, $subcatid, $mode='both'){
		global $manager; 
		$numberOfCharacters = 60;
		$numberOfTitleCharacters = 40;
		$toadd = "...";

		if(!is_numeric($numberOfWritebacks)){
			$filter = $numberOfWritebacks;
			$numberOfWritebacks   = 5; // defaults to 5
		}

		$mtable = '';
		if($catid){
			$linkparams[catid] = $catid;
			if ($manager->pluginInstalled('NP_MultipleCategories')) {
				$where .= ' and ((i.inumber=p.item_id and (p.categories REGEXP "(^|,)'.intval($catid).'(,|$)" or i.icat='.intval($catid).')) or (i.icat='.intval($catid).' and p.item_id IS NULL))';
				$mtable = ' LEFT JOIN '.sql_table('plug_multiple_categories').' as p ON  i.inumber=p.item_id';
				$mplugin =& $manager->getPlugin('NP_MultipleCategories');
				if ($subcatid && method_exists($mplugin,"getRequestName")) {
					$linkparams[subcatid] = $subcatid;
//family
					if($this->checkMSCVersion() >2){
						$Children = array();
						$Children = explode('/',intval($subcatid).$this->getDescendantFromScatid(intval($subcatid), 1));
					}
					if($Children[1]){
						for($i=0;$i<count($Children);$i++){
							$temp_whr[] = ' p.subcategories REGEXP "(^|,)'.intval($Children[$i]).'(,|$)" ';
						}
						$where .= ' and ';
						$where .= ' ( ';
						$where .= join(' or ', $temp_whr);
						$where .= ' )';
					}else{
						$where .= ' and p.subcategories REGEXP "(^|,)'.intval($subcatid).'(,|$)"';
					}
//family end
				}
			} else {
				$where .= ' and i.icat='.intval($catid);
			}
		}

		// select
		$query = "SELECT c.cnumber, c.cuser, c.cbody, c.citem, c.cmember, c.ctime ,UNIX_TIMESTAMP(c.ctime) as ctimest";
		$query .= " FROM ".sql_table('comment').' as c, ' . sql_table('item').' as i'.$mtable;
		$query .= " WHERE c.citem=i.inumber and i.iblog=".$blogid.$where;
		if($filter){
			$query .= $filter;
		}
		$query .= " ORDER by c.ctime DESC LIMIT 0,".$numberOfWritebacks;

		$comments = mysql_query($query);
		
		if(mysql_num_rows($comments)){
			while($row = mysql_fetch_object($comments)) {
				$temp = array();
				$temp[itemid] = $cid  = $row->citem;
				$temp[commentid] = $cid  = $row->cnumber;
				$temp[timestamp] = $ct  = $row->ctimest;
				$temp[date] = $ctst  = date("y-m-d H:i",strtotime($row->ctime));
				$temp[fulltext] = $text  = strip_tags($row->cbody);
				$temp[shortentext] = $ctext = shorten($text,$numberOfCharacters,$toadd);

				if (!$row->cmember) $temp[name] = $myname = $row->cuser;
				else {
					$mem = new MEMBER;
					$mem->readFromID(intval($row->cmember));
					$temp[name] = $myname = $mem->getDisplayName();
				}

				$temp[linkurl] = $itemlink = $this->_de(createItemLink($row->citem, $linkparams));
				$resultArray[] = $temp;
			}
		}
		return $resultArray;
	}

	function _de($url){
		return str_replace("&amp;",'&',$url);
	}

	function getDescendantFromScatid($subcat_id, $mode=0){
		$res = sql_query("select scatid, parentid, sname from ".sql_table('plug_multiple_categories_sub')." where parentid = '$subcat_id'");
		if($mode >0){
			while ($so =  mysql_fetch_object($res)) {
				$r .= $this->getDescendantFromScatid($so->scatid, 1) . '/' . $so->scatid;
			}
			return $r;
		}else{
			while ($so =  mysql_fetch_object($res)) {
				$this->r[$subcat_id][] = $this->getDescendantFromScatid($so->scatid, 0);
			}
			return $subcat_id;
		}
	}

	function checkMSCVersion(){
	global $manager; 
		if ($manager->pluginInstalled('NP_MultipleCategories')) {
			$this->mplugin =& $manager->getPlugin('NP_MultipleCategories');
			if (method_exists($this->mplugin,"getRequestName")) {
				$res = sql_query("SHOW FIELDS from ".sql_table('plug_multiple_categories_sub') );
				while ($co = mysql_fetch_assoc($res)) {
					if($co['Field'] == 'parentid') return 3;
				}
				return 2;
			}else{
				return 1;
			}
		}else{
			return 0;
		}
	}


	function _getScatsFromCatid($catid, $version=0){
		if($version==0)return;
		$aResult = array();	
		$query = 'SELECT * FROM '.sql_table('plug_multiple_categories_sub').' WHERE catid=' . intval($catid);
		if($version>2) $query .= ' AND parentid=0';
		$res = sql_query($query);
		while ($a = mysql_fetch_assoc($res)){
			array_push($aResult,$a);
		} 
		return $aResult;
	}

	function _getArchiveList($mode = 'month', $limit = 0, $blogid, $catid=0, $subcatid=0) {
		global $CONF, $manager;
		
		if(!$blogid) $blogid = $CONF['DefaultBlog'];

		$b =& $manager->getBlog($blogid);
		if ($catid) 
			$linkparams = array('catid' => $catid);

		$query = 'SELECT i.itime, SUBSTRING(i.itime,1,4) AS Year, SUBSTRING(i.itime,6,2) AS Month, SUBSTRING(i.itime,9,2) as Day FROM '.sql_table('item').' as i';
		if ($catid) {
			$query .= ' LEFT JOIN '.sql_table('plug_multiple_categories').' as p ON i.inumber=p.item_id';
		}
		$query .= ' WHERE i.iblog=' . $blogid
		. ' and i.itime <=' . mysqldate($b->getCorrectTime())	// don't show future items!
		. ' and i.idraft=0'; // don't show draft items
		
		if ($catid) {
			$query .= ' and ((i.inumber=p.item_id and (p.categories REGEXP "(^|,)'.intval($catid).'(,|$)" or i.icat='.intval($catid).')) or (i.icat='.intval($catid).' and p.item_id IS NULL))';
			$linkparams = array('catid' => $catid);
		}
		if ($subcatid) {
			if($this->checkMSCVersion() >2){
					$Children = array();
					$Children = explode('/',intval($subcatid).$this->getDescendantFromScatid(intval($subcatid), 1) );
			}
			if($Children[1]){
					for($i=0;$i<count($Children);$i++){
						$temp_whr[] = ' p.subcategories REGEXP "(^|,)'.intval($Children[$i]).'(,|$)" ';
					}
					$where .= ' and ';
					$where .= ' ( ';
					$where .= join(' or ', $temp_whr);
					$where .= ' )';
			}else{
				$query .= ' and p.subcategories REGEXP "(^|,)'.intval($subcatid).'(,|$)"';
			}
			$linkparams['subcatid'] = $subcatid;
		}
		
		$query .= ' GROUP BY Year, Month';
		if ($mode == 'day')
			$query .= ', Day';
		
		$query .= ' ORDER BY i.itime ASC';
		
		if ($limit > 0) 
			$query .= ' LIMIT ' . intval($limit);
		
		$res = sql_query($query);

		while ($current = mysql_fetch_object($res)) {
			$current->itime = strtotime($current->itime);	// string time -> unix timestamp
			$archivedate = ($mode == 'day')? date('Y-m-d',$current->itime): date('Y-m',$current->itime);

			$data['year'] = $y = date('Y',$current->itime);
			$data['month'] = date('m',$current->itime);
			if ($mode == 'day') $data['day'] = date('d',$current->itime);
			$data['archivedate'] = $archivedate;
			$data['archivelink'] = $this->_de(createArchiveLink($blogid,$archivedate,$linkparams));
			if($mode == 'day'){
				$menuname = 'am'.date('Y-m',$current->itime);
				$temp[$menuname][] = $data;
			}else{
				$temp[$y][] = $data;
			}
		}
		mysql_free_result($res);
		return $temp;
	}


}	//end of CLASS

class mmenuStyle
{
	
	function createMenuStyle($styleName)
	{
		global $menuData;
		$styleArray=get_object_vars($this);
		$menuData.="with($styleName=new mm_style()){\n";
		
		foreach ($styleArray as $fieldName => $fieldValue) 
		{
			if(ereg("color",$fieldName))
			{
				if(substr($fieldValue,0,1)!="#" && is_numeric($fieldValue))$fieldValue="#".$fieldValue;
			}
			
			$menuData.= "$fieldName=\"$fieldValue\";\n";
		}
   
		$menuData.= "}\n\n";
	}
}


class mMenu{
	var $menuItems;
	function createMenu($menuName){
		global $menuData;
		$menuArray=get_object_vars($this);

		$menuData.= "with(milonic=new menuname(\"$menuName\")){\n";
		$tempMenuItems="";
		foreach ($menuArray as $fieldName => $fieldValue) {
			global $menuData;
			if($fieldName!="menuItems")
			{
				if($fieldName=="style")
				{
					$menuData.= "$fieldName=$fieldValue;\n";
				}
				else
				{
					$menuData.= "$fieldName=\"$fieldValue\";\n";
				}
				
			}
			else
			{
				if($fieldName=="menuItems")$tempMenuItems=$fieldValue;
			}
		}
   
   		$menuData.= $tempMenuItems."\n";
		$menuData.= "}\n\n";
	}
	
	
	function addItemFromText($itemText){
		global $menuData;
		$this->menuItems.="aI(\"".$itemText . "\");\n";	
	}

	function addItemFromItem($menuItem){
		global $menuData;
		$tempVar="";
		foreach ($menuItem as $fieldName => $fieldValue) {
			if(ereg("color",$fieldName)){
				if(substr($fieldValue,0,1)!="#")$fieldValue="#".$fieldValue;
			}
			
			$tempVar.="$fieldName=$fieldValue;";
		}
		$this->menuItems.="aI(\"".$tempVar . "\");\n";	
	}	
	
}


class mItem{
	function addItemElement($mtype,$mval){
		$this->$mtype=$mval;
	}
}


function commitMenus(){
	global $menuData,$menuVars;

echo $menuData;

}




?>