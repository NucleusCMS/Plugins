<?php

class NP_TreeMenu extends NucleusPlugin {

	function getName() 		{ return 'JavaScript Tree Menu'; }
	function getAuthor()  	{ return 'nakahara21'; }
	function getURL()  		{ return 'http://nakahara21.com/'; }
	function getVersion() 	{ return '0.5'; }
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
		global $CONF, $manager;
		$fileName = $CONF['ActionURL'].'?action=plugin&name=TreeMenu&type=f';
?>
<script language="JavaScript" src="<?php echo $this->getAdminURL(); ?>ua.js"></script>
<script language="JavaScript" src="<?php echo $this->getAdminURL(); ?>ftiens4.js"></script>
<script language="JavaScript" src="<?php echo $fileName; ?>"></script>

<a style="display:none;" href="http://www.treemenu.net/" target=_blank>Menu</a>

<span class=TreeviewSpanArea>
<script>initializeDocument()</script>
<a style="font-size:7pt;text-decoration:none;color:silver" href="http://www.treemenu.net/" target=_blank>* By treemenu.net</a>

<noscript>
A tree for site navigation will open here if you enable JavaScript in your browser.
</noscript>
</span>
<?php



	}

	function doAction($type) {
		if($type == 'f'){
		global $manager, $CONF;
		$aurl = $this->getAdminURL();

echo <<<EOD

// You can find instructions for this file at http://www.treeview.net

//Environment variables are usually set at the top of this file.
USETEXTLINKS = 1
STARTALLOPEN = 0
USEFRAMES = 0
USEICONS = 1
WRAPTEXT = 1
PRESERVESTATE = 1

ICONPATH = '{$aurl}icons/'
HIGHLIGHT = 1
HIGHLIGHT_COLOR = ''
HIGHLIGHT_BG    = 'silver'
BUILDALL = 0
GLOBALTARGET = "S" // variable only applicable for addChildren uses

foldersTree = gFld("Home", "{$CONF['IndexURL']}")

EOD;

echo <<<EOD

// You can find instructions for this file at http://www.treeview.net

//Environment variables are usually set at the top of this file.
USETEXTLINKS = 1
STARTALLOPEN = 0
USEFRAMES = 0
USEICONS = 1
WRAPTEXT = 1
PRESERVESTATE = 1
ICONPATH = '{$aurl}icons/'

foldersTree = gFld("Home", "{$CONF['IndexURL']}")

EOD;

		$query = 'SELECT bnumber as blogid, bname as blogname, burl as blogurl, bshortname, bdesc as blogdesc';
		$query .= ' FROM '.sql_table('blog').' b';
		$query .= ' ORDER BY bnumber';

		$res = sql_query($query);
		$bn=1;
		while ($o = mysql_fetch_object($res)) {
			$this->bid = $o->blogid;
			$burl = createBlogidLink($o->blogid);
			echo 'blog'.$bn.' = insFld(foldersTree, gFld("'.$o->blogname.'", "'.$burl.'"))'."\n";
			$cquery = 'SELECT c.catid as catid, c.cname as catname, c.cdesc as catdesc'
			        .' FROM '.sql_table('category').' as c'
			        .' WHERE c.cblog='.$o->blogid
			        .' ORDER BY c.catid';
			$cres = sql_query($cquery);
			$cn = 1;
			while ($co = mysql_fetch_object($cres)) {
				$curl = createBlogidLink($this->bid, array('catid'=>$co->catid));
				echo 'cat'.$cn.' = insFld(blog'.$bn.', gFld("'.$co->catname.'", "'.$curl.'"))'."\n";
				if ($manager->pluginInstalled('NP_MultipleCategories')) {
				$sres = sql_query("SELECT scatid as subcatid, sname as subname, sdesc as subdesc FROM ".sql_table('plug_multiple_categories_sub')." WHERE catid=".$co->catid." AND parentid=0");
				if (mysql_num_rows($sres) > 0) {
					$sn = 1;
					while ($so =  mysql_fetch_object($sres)) {
						$surl = createBlogidLink($this->bid, array('catid'=>$co->catid, 'subcatid'=>$so->subcatid));
						$nodeName = 'scat'.$sn;
						echo $nodeName.' = insFld(cat'.$cn.', gFld("'.$so->subname.'", "'.$surl.'"))'."\n";
						$this->did = 100;
						echo $this->scanChild($nodeName, $so->subcatid, $co->catid);
						$sn++;
					}
				}
				}
			$cn++;
			}
		$bn++;
		}



/*
echo <<<EOD

  aux1 = insFld(foldersTree, gFld("Expand for example with pics and flags", "javascript:undefined"))
    aux2 = insFld(aux1, gFld("United States", "demoFrameless.html?pic=%22beenthere_unitedstates%2Egif%22"))
 			insDoc(aux2, gLnk("S", "Boston", "demoFrameless.html?pic=%22beenthere_boston%2Ejpg%22"))
 			insDoc(aux2, gLnk("S", "Tiny pic of New York City", "demoFrameless.html?pic=%22beenthere_newyork%2Ejpg%22"))
 			insDoc(aux2, gLnk("S", "Washington", "demoFrameless.html?pic=%22beenthere_washington%2Ejpg%22"))
    aux2 = insFld(aux1, gFld("php", "http://nakahara21.com/000/index.php?catid=2"))
      insDoc(aux2, gLnk("S", "London", "demoFrameless.html?pic=%22beenthere_london%2Ejpg%22"))
      insDoc(aux2, gLnk("S", "Lisbon", "demoFrameless.html?pic=%22beenthere_lisbon%2Ejpg%22"))
  aux1 = insFld(foldersTree, gFld("Types of node", "javascript:undefined"))
    aux2 = insFld(aux1, gFld("Expandable with link", "demoFrameless.html?pic=%22beenthere_europe%2Egif%22"))
      insDoc(aux2, gLnk("S", "London", "demoFrameless.html?pic=%22beenthere_london%2Ejpg%22"))
    aux2 = insFld(aux1, gFld("Expandable without link", "javascript:undefined"))
 			insDoc(aux2, gLnk("S", "NYC", "demoFrameless.html?pic=%22beenthere_newyork%2Ejpg%22"))
    insDoc(aux1, gLnk("B", "Opens in new window", "http://www.treeview.net/treemenu/demopics/beenthere_pisa.jpg"))


EOD;
*/
		}else{
			return;
		}
	}

	function scanChild($nodeName, $sid=0, $catid){
				$sres = sql_query("SELECT scatid as subcatid, sname as subname, sdesc as subdesc FROM ".sql_table('plug_multiple_categories_sub')." WHERE parentid=".$sid);
				if (mysql_num_rows($sres) > 0) {
					$ssn = 0;
					while ($so =  mysql_fetch_object($sres)) {
//						$surl = createCategoryLink($catid, array('subcatid'=>$so->subcatid));
						$surl = createBlogidLink($this->bid, array('catid'=>$catid, 'subcatid'=>$so->subcatid));
						$this->did++;
						$snode = 'sscat'.$this->did;
						$out .= $snode.' = insFld('.$nodeName.', gFld("'.$so->subname.'", "'.$surl.'"))'."\n";
						$out .= $this->scanChild($snode, $so->subcatid, $catid);
					}
				}
		return $out;
	}

}
?>