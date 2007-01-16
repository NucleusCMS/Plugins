<?php
/**
  *
  * 0.94 bug fix
  *       add language files
  *       configuration by option
  * 0.93 sec fix
  *       subcategory link bug fix
  *
  */

class NP_Dtree extends NucleusPlugin
{

    function getName()
    {
        return 'Navigation Tree'; 
    }

    function getAuthor()
    { 
        return 'nakahara21 + shizuki'; 
    }

    function getURL()
    {
        return 'http://nakahara21.com/'; 
    }

    function getVersion()
    {
        return '0.94'; 
    }

    function getDescription()
    { 
        return _DTREE_DESCRIPTION;  //'Show Navigation Tree. Usage: &lt;%Dtree()%&gt;';
    }

    function supportsFeature($what)
    {
        switch($what){
            case 'SqlTablePrefix':
                return 1;
            default:
                return 0;
        }
    }

    function install()
    {
        $this->createOption('folderLinks',      _DTREE_DIR_LINK,    'yesno',    'yes');
        $this->createOption('useSelection',     _DTREE_SELECTION,   'yesno',    'no');
        $this->createOption('useCookies',       _DTREE_COOKIE,      'yesno',    'no');
        $this->createOption('useLines',         _DTREE_LINE,        'yesno',    'yes');
        $this->createOption('useIcons',         _DTREE_ICON,        'yesno',    'yes');
        $this->createOption('useStatusText',    _DTREE_ST_TEXT,     'yesno',    'no');
        $this->createOption('closeSameLevel',   _DTREE_CL_SLEVEL,   'yesno',    'no');
        $this->createOption('inOrder',          _DTREE_IN_ORDER,    'yesno',    'no');
    }

    function init()
    {
        global $admin;
        $language = ereg_replace( '[\\|/]', '', getLanguageName());
        if (file_exists($this->getDirectory().'language/'.$language.'.php')) {
            include_once($this->getDirectory().'language/'.$language.'.php');
        }else {
            include_once($this->getDirectory().'language/english.php');
        }
    }

    function doSkinVar($skinType, $itemid = 0)
    { 
        global $blogid, $catid, $subcatid;
        $adminURL = htmlspecialchars($this->getAdminURL(), ENT_QUOTES, _CHARSET);
        if (is_numeric($blogid)) {
            $blogid = intval($blogid);
        } else {
            $id     = getBlogIDFromName($blogid);
            $blogid = intval($id);
        }
        $itemid   = intval($itemid);
        $catid    = intval($catid);
        $subcatid = intval($subcatid);
        
//      $randomID = 'tree' . uniqid(rand());
        $randomID = 'tree' . preg_replace('|[^0-9a-f]|i', '', uniqid(rand()));

        echo '<script type="text/javascript" src="' . $adminURL . 'dtree.php"></script>' . "\n";

        if ($skinType == 'template') {
            $data = '<script type="text/javascript"' . ' src="' . $adminURL
                    . 'dtreedata.php?'
                    . 'o=' . $randomID . 'a'
                    . '&amp;'
                    . 'bid=' . $blogid
                    . '&amp;'
                    . 'id=' . $itemid
                    . '"></script>' . "\n";
            echo $data;
            $data = '<a href="javascript: ' . $randomID . 'a.openAll();">' . _DTREE_OPENALL . '</a>' . "\n"
                  . ' | ' . "\n"
                  . '<a href="javascript: ' . $randomID . 'a.closeAll();">' . _DTREE_CLOSEALL . '</a>' . "\n";
            echo $data;
            return;
        }

        $eq = '';
        if (!empty($catid)) {
        }   $eq .= '&amp;cid=' . $catid;
        if (!empty($subcatid)) {
            $eq .= '&amp;sid=' . $subcatid;
        }

        $data = '<script type="text/javascript" src="' . $adminURL
                . 'dtreedata.php?'
                . 'o=' . $randomID . 'd'
                . '&amp;'
                . 'bid=' . $blogid . $eq . '">'
                . '</script>';
        echo $data;
        $data = '<a href="javascript: ' . $randomID . 'd.openAll();">' . _DTREE_OPENALL . '</a>' . "\n"
              . ' | ' . "\n"
              . '<a href="javascript: ' . $randomID . 'd.closeAll();">' . _DTREE_CLOSEALL . '</a>' . "\n";
        echo $data;
/*        if (!(intRequestVar('page') > 0) !$catid && !$subcatid) {
            echo '<script type="text/javascript">' . $randomID . 'd.openAll();</script>';
        }*/

    }

    function doTemplateVar(&$item)
    {
        $this->doSkinVar('template', $item->itemid);
    }

}
?>