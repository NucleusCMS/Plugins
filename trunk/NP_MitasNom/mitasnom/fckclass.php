<?php 
/*
 * NP_MitasNom
 * Written by Katsumi
 * This library is GPL
 */

if (!class_exists('FCKeditor')) include (dirname(__FILE__).'/fckeditor.php');

class NucleusFCKeditor extends FCKeditor
{
	function NucleusFCKeditor($instanceName, &$plugin, $data='')
	{
		global $itemid,$blogid,$manager,$DIR_SKINS,$CONF;

		$this->__construct( $instanceName ) ;
		if ($plugin->getOption('usehttps')=='yes') $this->BasePath=preg_replace('/^http:/','https:',$plugin->getAdminURL());
		else $this->BasePath=$plugin->getAdminURL();
		$this->Value=$data;
		$this->Width=trim($plugin->this_getOption('width'));
		$this->Height=trim($plugin->this_getOption('height'));
		$this->ToolbarSet=$plugin->this_getOption('toolbar');
		
		// XML and CSS stuffs
		if (!$blogid && $itemid) $blogid=getBlogIDFromItemID($itemid);
		if (!$blogid) return;
		if (!($blog=&$manager->getBlog($blogid))) return;
		$skin=new SKIN($blog->getDefaultSkin());
		$styledir=$DIR_SKINS.$skin->getIncludePrefix().'mitasnom/';
		$styleURL=$CONF['SkinsURL'].$skin->getIncludePrefix().'mitasnom/';
		if (file_exists($styledir)) {
			if (file_exists($styledir.'fckstyles.xml'))
				$this->Config['StylesXmlPath']=$styleURL.'fckstyles.xml';
			if (file_exists($styledir.'fckstyles.css'))
				$this->Config['EditorAreaCSS']=$styleURL.'fckstyles.css';
		}
	}
}
?>