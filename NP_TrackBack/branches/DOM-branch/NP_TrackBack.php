<?php

// vim: tabstop=4:shiftwidth=4

/* ==========================================================================================
 * Trackback 2.0 for Nucleus CMS 
 * ==========================================================================================
 * This program is free software and open source software; you can redistribute
 * it and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA  or visit
 * http://www.gnu.org/licenses/gpl.html
 * ==========================================================================================
 * NP_Trackback.php
 *
 * @author    rakaz
 * @author    nakahara21
 * @author    hsur
 * @author    shizuki
 * @copyright 2002-2009 rakaz
 * @copyright 2002-2009 rakaznakahara21
 * @copyright 2002-2009 hsur
 * @copyright 2002-2009 shizuki
 * @license http://nucleuscms.org/license.txt GNU General Public License
 * @link http://japan.nucleuscms.org/wiki/plugins:trackback
 * @version 2.0.3 jp13 DOMDocument-branche $Id$
 */

/**
 * class NP_TrackBack
 *
 * @since first version
 */

class NP_TrackBack extends NucleusPlugin
{

/**
 * Plugin API calls, for installation, configuration and setup
 */

// {{{ function getName()

    /**
     * return PLUGIN's name
     *
     * @retrun string
     */
    function getName()
    {
        return 'Nucleus CMS TrackBack plugin DOM-branche';
    }

// }}}
// {{{ function getAuthor()

    /**
     * return PLUGIN's author(s)
     *
     * @retrun string
     */
    function getAuthor()
    {
        return 'rakaz + nakahara21 + hsur + shizuki';
    }

// }}}
// {{{ function getURL()

    /**
     * return URL of distribution site or author's e-mail address
     *
     * @retrun string
     */
    function getURL()
    {
        return 'http://japan.nucleuscms.org/wiki/plugins:trackback';
    }

// }}}
// {{{ function getVersion()

    /**
     * return PLUGIN's version
     *
     * @retrun string
     */
    function getVersion()
    {
        return '2.0.3 jp13 DOM-branche $Revision$';
    }

// }}}
// {{{ function getDescription()

    /**
     * return PLUGIN's description
     *
     * @retrun string
     */
    function getDescription()
    {
        return '[2.0.3 jp13 DOM-branche $Revision$]<br />' . _TB_DESCRIPTION;
    }

// }}}
// {{{ function getTableList()

    /**
     * return data base tables this plugin uses
     *
     * @retrun array
     */
    function getTableList()
    {
        $retArr = array(
            sql_table("plugin_tb"),
            sql_table("plugin_tb_lookup"),
            sql_table('plugin_tb_lc')
        );
        return $retArr;
     }

// }}}
// {{{ function getEventList()

    /**
     * return Nucleus CMS APIs this plugin uses
     *
     * @retrun array
     */
    function getEventList()
    {
        $retArr = array(
            'QuickMenu',
            'PostAddItem',
            'AddItemFormExtras',
            'EditItemFormExtras',
            'PreUpdateItem',
            'PrepareItemForEdit',
//          'BookmarkletExtraHead',
            'RetrieveTrackback',
            'SendTrackback',
            'InitSkinParse',
            'TemplateExtraFields'
        );
        return $retArr;
    }

// }}}
// {{{ function getMinNucleusVersion()

    /**
     * return Lowest Nucleus CMS version by which this plugin operates
     *
     * @retrun array
     */
    function getMinNucleusVersion()
    {
        return 341;
    }

// }}}
// {{{ function supportsFeature($feature)

    /**
     * return "true" if feature support
     *
     * @param str feature name
     * @retrun int
     */
    function supportsFeature($feature)
    {
        switch($feature) {
            case 'SqlTablePrefix':
                return 1;
            default:
                return 0;
        }
    }

// }}}
// {{{ function install()

    /**
     * setup NP_TrackBack
     *
     * @retrun void
     */
    function install()
    {
        switch (strtoupper(_CHARSET) == 'UTF-8') {
            case 'UTF-8':
                $collate = 'utf8_unicode_ci';
                $charset = 'utf8';
                break;
            case 'EUC-JP':
                $collate = 'ujis_japanese_ci';
                $charset = 'ujis';
                break;
            default:
                $collate = 'latin1_swedish_ci';
                $charset = 'latin1';
                break;
        }
        // Create tables
        sql_query("
            CREATE TABLE IF NOT EXISTS `" . sql_table('plugin_tb') . "` (
                `id`        int(11) NOT NULL AUTO_INCREMENT,
                `tb_id`     int(11) NOT NULL,
                `url`       text COLLATE utf8_unicode_ci NOT NULL,
                `block`     tinyint(4) NOT NULL,
                `spam`      tinyint(4) NOT NULL,
                `link`      tinyint(4) NOT NULL,
                `title`     text COLLATE " . $collate . ",
                `excerpt`   text COLLATE " . $collate . ",
                `blog_name` text COLLATE " . $collate . ",
                `timestamp` datetime DEFAULT NULL,
                PRIMARY     KEY (`id`),
                            KEY `tb_id_block_timestamp_idx` (`tb_id`,`block`,`timestamp`)
            ) ENGINE=MyISAM DEFAULT CHARSET=" . $charset . " COLLATE=" . $collate
        );

        sql_query("
            CREATE TABLE IF NOT EXISTS `" . sql_table('plugin_tb_lc') . "` (
                `tb_id`   int(11) NOT NULL,
                `from_id` int(11) NOT NULL,
                PRIMARY   KEY (`tb_id`,`from_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=" . $charset . " COLLATE=" . $collate
        );

        sql_query("
            CREATE TABLE IF NOT EXISTS `" . sql_table('plugin_tb_lookup') . "` (
                `link`  text COLLATE " . $collate . " NOT NULL,
                `url`   text COLLATE " . $collate . " NOT NULL,
                `title` text COLLATE " . $collate . ",
                PRIMARY KEY (`link`(100))
            ) ENGINE=MyISAM DEFAULT CHARSET=" . $charset . " COLLATE=" . $collate
        );

        // plugin options

        // global options
        $this->createOption('AcceptPing',  _TB_AcceptPing,  'yesno', 'yes');
        $this->createOption('SendPings',   _TB_SendPings,   'yesno', 'yes');
        $this->createOption('AutoXMLHttp', _TB_AutoXMLHttp, 'yesno', 'yes');
        $this->createOption('CheckIDs',    _TB_CheckIDs,    'yesno', 'yes');
        $this->createOption('dateFormat',  _TB_dateFormat,  'text',  _TB_dateFormat_VAL);
        $this->createOption('NotifyEmail', _TB_NotifyEmail, 'text',  '');
        $this->createOption('DropTable',   _TB_DropTable,   'yesno', 'no');
        $this->createOption('HideUrl',     _TB_HideUrl,     'yesno', 'yes');
        $this->createOption('ajaxEnabled', _TB_ajaxEnabled, 'yesno', 'no');

        // default templates
        $this->createOption('tplHeader',      _TB_tplHeader,       'textarea', _TB_tplHeader_VAL);
        $this->createOption('tplEmpty',       _TB_tplEmpty,        'textarea', _TB_tplEmpty_VAL);
        $this->createOption('tplItem',        _TB_tplItem,         'textarea', _TB_tplItem_VAL);
        $this->createOption('tplFooter',      _TB_tplFooter,       'textarea', _TB_tplFooter_VAL);
        $this->createOption('tplLocalHeader', _TB_tplLocalHeader,  'textarea', _TB_tplLocalHeader_VAL);
        $this->createOption('tplLocalEmpty',  _TB_tplLocalEmpty,   'textarea', _TB_tplLocalEmpty_VAL);
        $this->createOption('tplLocalItem',   _TB_tplLocalItem,    'textarea', _TB_tplLocalItem_VAL);
        $this->createOption('tplLocalFooter', _TB_tplLocalFooter,  'textarea', _TB_tplLocalFooter_VAL);
        $this->createOption('tplTbNone',      _TB_tplTbNone,       'text',     "No Trackbacks");
        $this->createOption('tplTbOne',       _TB_tplTbOne,        'text',     "1 Trackback");
        $this->createOption('tplTbMore',      _TB_tplTbMore,       'text',     "<%number%> Trackbacks");
        $this->createOption('tplTbNoAccept',  _TB_tplNO_ACCEPT,    'text',     "Sorry, no trackback pings are accepted.");

        // blog options
        $this->createBlogOption('NotifyEmailBlog',    _TB_NotifyEmailBlog,   'text',   ''); 
        $this->createBlogOption('isAcceptW/OLinkDef', _TB_isAcceptWOLinkDef, 'select', 'block', _TB_isAcceptWOLinkDef_VAL);
        $this->createBlogOption('AllowTrackBack',     _TB_AllowTrackBack,    'yesno',  'yes');

        // item options
        $this->createItemOption('ItemAcceptPing',  _TB_ItemAcceptPing, 'yesno',  'yes');
        $this->createItemOption('isAcceptW/OLink', _TB_isAcceptWOLink, 'select', 'default', _TB_isAcceptWOLink_VAL);
    }

// }}}
// {{{ function uninstall()

    /**
     * delete TrackBack table if uninstall
     *
     * @retrun void
     */
    function uninstall()
    {
        if ($this->getOption('DropTable') == 'yes') {
            sql_query ('DROP TABLE ' . sql_table('plugin_tb'));
            sql_query ('DROP TABLE ' . sql_table('plugin_tb_lookup'));
            sql_query ('DROP TABLE ' . sql_table('plugin_tb_lc'));
        }
    }

// }}}
// {{{ function init()

    /**
     * initialize
     *
     * @retrun void
     */
    function init()
    {
        // include language file for this plugin 
        $language = ereg_replace( '[\\|/]', '', getLanguageName()); 
        if (file_exists($this->getDirectory() . 'language/' . $language . '.php')) {
            include_once($this->getDirectory() . 'language/' . $language . '.php'); 
        } else {
            include_once($this->getDirectory() . 'language/english.php'); 
        }
        $this->notificationMail      = _TB_NORTIFICATION_MAIL_BODY;
        $this->notificationMailTitle = _TB_NORTIFICATION_MAIL_TITLE;
        $this->userAgent             = $this->getName() . ' ( ' . $this->getVersion() . ' )';
    }

// }}}
// {{{ function doSkinVar($skinType, $what = '', $tb_id = '', $amount = 'limit-1', $template = '')

    /**
     * skin vars process
     *
     * @param str
     * @param str
     * @param int
     * @param int/str
     * @param str
     */
    function doSkinVar($skinType, $what = '', $tb_id = '', $amount = 'limit-1', $template = '')
    {
        global $itemid, $manager, $CONF;
        if(preg_match('/limit/i', $tb_id)){
            $amount = $tb_id;
            $tb_id  = '';
        }
        $amount = intval(str_replace('limit', '', $amount));
        if ($tb_id == '') {
            $tb_id = intval($itemid);
        }
        $isAcceptPing = $this->isAcceptTrackBack($tb_id);
        switch ($what) {
            case 'tbcode':
            case 'code':
                // Insert Auto-discovery RDF code
                $spamcheck = array (
                    'type'     => 'tbcode',
                    'id'       => -1,
                    'title'    => '',
                    'excerpt'  => '',
                    'blogname' => '',
                    'url'      => '',
                    'return'   => true,
                    'live'     => true,
                    'data'     => '', //Backwards compatibility with SpamCheck API 1
                    'ipblock'  => true,
                );
//              $manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck));
                $spam = false;
                if (isset($spamcheck['result']) && $spamcheck['result'] == true){
                    $spam = true;
                }
                if($skinType == 'item' && !$spam && $isAcceptPing) {
                    $this->insertCode($tb_id);
                }
                break;
            case 'tburl':
            case 'url':
                // Insert TrackBack URL
                if($isAcceptPing) {
                    echo $this->getTrackBackUrl($tb_id);
                } else {
                    if (!empty($template)) {
                        $template =& $manager->getTemplate($template); 
                        $template =  $template['NP_TrackBack_tplTbNoAccept'];
                    } else {
                        $template =  $this->getOption('tplTbNoAccept');
                    }
                    echo TEMPLATE::fill($template, array());
                }
                break;
            case 'form':
            case 'manualpingformlink':
                // Insert manual ping URL
                echo $this->getManualPingUrl($tb_id);
                break;
            case 'sendpinglink':
                echo $manager->addTicketToUrl($this->getAdminURL() . 'index.php?action=ping&amp;id=' . intval($tb_id));
                break;
            case 'count':
                // Insert TrackBack count
                $count = $this->getTrackBackCount($tb_id);
                if (!empty($template)) {
                    $template =& $manager->getTemplate($template);
                }
                switch ($count) {
                    case 0:
                        if (is_array($template)) {
                            $template =  $template['NP_TrackBack_tplTbNone'];
                        } else {
                            $template =  $this->getOption('tplTbNone');
                        }
                        break;
                    case 1:
                        if (is_array($template)) {
                            $template =  $template['NP_TrackBack_tplTbOne'];
                        } else {
                            $template =  $this->getOption('tplTbOne');
                        }
                        break;
                    default:
                        if (is_array($template)) {
                            $template =  $template['NP_TrackBack_tplTbMore'];
                        } else {
                            $template =  $this->getOption('tplTbMore');
                        }
                        break;
                }
                echo TEMPLATE::fill($template, array('number' => $count));
                break;
            case 'list':
            case '':
                // Shows the TrackBack list
                $this->showList($tb_id, $amount);
                break;
            case 'required':
                // show requred URL
                echo  $this->getRequiredURL($tb_id);
                break;
            case 'locallist':
                // shows the Local list
                $this->showLocalList($tb_id);
                break;
            default:
                return;
        }
    }

// }}}
// {{{ function doTemplateVar(&$item, $what = '', $template = '')

    /**
     * template vars process
     *
     * @param obj
     * @param str
     * @param str
     */
    function doTemplateVar(&$item, $what = '', $template = '')
    {
        $this->doSkinVar('template', $what, $item->itemid, $template);
    }

// }}}
// {{{ function doTemplateCommentsVar(&$item, &$comment, $what = '', $template = '')

    /**
     * comment template vars process
     *
     * @param obj
     * @param obj
     * @param str
     * @param str
     */
    function doTemplateCommentsVar(&$item, &$comment, $what = '', $template = '')
    {
        $this->doSkinVar('templatecomments', $what, $item->itemid, $template);
    }

// }}}
// {{{ function doAction($type)

    /**
     * A trackback ping is to be received on the URL
     * http://yourdomain.com/action.php?action=plugin&name=TrackBack&tb_id=1234
     * Extra variables to be passed along are url, title, excerpt, blog_name
     *
     * @param str
     */
    function doAction($type)
    {
        global $CONF,$manager;
        $aActionsNotToCheck = array(
            '',
            'ping',
            'form',
            'redirect',
            'left',
        );
        if (!in_array($type, $aActionsNotToCheck)) {
            if (!$manager->checkTicket()) return _ERROR_BADTICKET;
        }
        switch ($type) {
            case '':
            // When no action type is given, assume it's a ping
                $errorMsg = $this->handlePing();
                $this->xmlResponse($errorMsg);
                break; 
            case 'ping':
            // Manual ping
                $errorMsg = $this->handlePing();
                if ($errorMsg != '') {
                    $this->showManualPingError(intRequestVar('tb_id'), $errorMsg);
                } else {
                    $this->showManualPingSuccess(intRequestVar('tb_id'));
                }
                break; 
            case 'form':
            // Show manual ping form
                $tb_id        = intRequestVar('tb_id');
                $isAcceptPing = $this->isAcceptTrackBack($tb_id);
                if ($isAcceptPing) {
                    $this->showManualPingForm($tb_id);
                } else {
                    if (!empty(requestVar['template'])) {
                        $template =& $manager->getTemplate(requestVar['template']); 
                        $template =  $template['NP_TrackBack_tplTbNoAccept'];
                    } else {
                        $template =  $this->getOption('tplTbNoAccept');
                    }
                    echo TEMPLATE::fill($template, array());
                }
                break;
            case 'detect':
            // Detect trackback
                list($url, $title) = $this->getURIfromLink(html_entity_decode(requestVar('tb_link')));
                $url   = addslashes($url);
                $url   = $this->_utf8_to_javascript($url);
                $title = addslashes($title);
                $title = $this->_utf8_to_javascript($title);
                echo "tbDone('" . requestVar('tb_link') . "', '" . $url . "', '" . $title . "');";
                break;
            case 'redirect':
            // redirect 
                return $this->redirect(intRequestVar('tb_id'), requestVar('urlHash'));
                break;
            case 'left':
                echo $this->showLeftList(intRequestVar('tb_id'), intRequestVar('amount'));
                break;
            case 'deletelc':
            // delete a trackback(local)
                $err = $this->deleteLocal(intRequestVar('tb_id'), intRequestVar('from_id'));
                if ($err) {
                    return $err;
                }
                header('Location: ' . serverVar('HTTP_REFERER'));
                break;
        }
        exit;
    }

// }}}
// {{{ function doIf($key = '', $value = '')

    /**
     * COMPARE key and value
     *
     * @param str
     * @param str
     */
    function doIf($key = '', $value = '')
    {
        global $itemid;
        //echo "key: $key, value: $value";
        switch (strtolower($key)) {
            case '':
            case 'accept':
                if ($value == '') {
                    $value = 'yes';
                }
                $value = ($value == 'no' || (!$value)) ? false : true;
                $ret   = false;
                if ($itemid) {
                    $ret = $this->isAcceptTrackBack($itemid);
                } else {
                    $ret = $this->isAcceptTrackBack();
                }
                return ($value == false) ? (!$ret) : $ret;
            case 'required':
                if ($value == '') {
                    $value = 'yes';
                }
                $value = ($value == 'no' || (!$value)) ? false : true;
                $ret = false;
                if( $itemid ) {
                    $ret = $this->isEnableLinkCheck($itemid);
                }
                return ($value == false) ? (!$ret) : $ret;
            default:
                return false;
        }
    }

// }}}
// {{{ function event_InitSkinParse(&$data)

    /**
     * A trackback ping is to be received on the URL
     * http://yourdomain.com/item/1234.trackback
     * Extra variables to be passed along are url, title, excerpt, blog_name
     *
     * @param arr
     */
    function event_InitSkinParse(&$data)
    {
        global $CONF, $itemid;
        $format = requestVar('format');
        if ($CONF['URLMode'] == 'pathinfo') {
            if (preg_match('/(\/|\.)(trackback)(\/|$)/', serverVar('PATH_INFO'), $matches)) {
                $format = $matches[2];
            }
        }
        
        if ($format == 'trackback' && $data['type'] == 'item') {
            $errorMsg = $this->handlePing(intval($itemid));
            if ($errorMsg != '') {
                $this->xmlResponse($errorMsg);
            } else {
                $this->xmlResponse();
            }
            exit;
        }
    }

// }}}
// {{{ function event_TemplateExtraFields(&$data)

    /**
     * extra template field
     *
     * @param arr
     */
    function event_TemplateExtraFields(&$data)
    {
        $data['fields']['NP_TrackBack'] = array(
            'NP_TrackBack_tplTbNoAccept' => _TB_tplNO_ACCEPT,
            'NP_TrackBack_tplTbNone'     => _TB_tplTbNone,
            'NP_TrackBack_tplTbOne'      => _TB_tplTbOne,
            'NP_TrackBack_tplTbMore'     => _TB_tplTbMore,
            'NP_TrackBack_tplItem'       => _TB_tplItem,
        );
    }

// }}}
// {{{ function event_SendTrackback($data)

    /**
     * trackbackping send via xmlrpc
     *
     * @param arr
     */
    function event_SendTrackback($data)
    {
        global $manager;
        // Enable sending trackbacks for the XML-RPC API, otherwise we would 
        // get an error because the current user is not exactly logged in.
        $this->xmlrpc =  true;
        $itemid       =  intval($data['tb_id']);
        $item         =& $manager->getItem($itemid, 0, 0);
        if (!$item) {
            return; // don't ping for draft & future
        }
        if ($item['draft']) {
            return;   // don't ping on draft items
        }
        // gather some more information, needed to send the ping (blog name, etc)
        $blog      =& $manager->getBlog(getBlogIDFromItemID($itemid));
        $blog_name =  $blog->getName();
        $title     =  $data['title'] != '' ? $data['title'] : $item['title'];
        $title     =  strip_tags($title);
        $excerpt   =  $data['body']  != '' ? $data['body']  : $item['body'];
        $excerpt   =  strip_tags($excerpt);
        $excerpt   =  $this->_cut_string($excerpt, 200);
        $url       =  $this->_createItemLink($itemid, $blog);
        
        while (list(,$url) = each($data['urls'])) {
            $res = $this->sendPing($itemid, $title, $url, $excerpt, $blog_name, $url);
            if ($res) {
                ACTIONLOG::add(WARNING, 'TrackBack Error:' . $res . ' (' . $url . ')');
            }
        }
    }

// }}}
// {{{ function event_RetrieveTrackback($data)

    /**
     * trackbackping receive via xmlrpc
     *
     * @param arr
     */
    function event_RetrieveTrackback($data)
    {
        
        $res = sql_query('
            SELECT 
                `url`,
                `title`,
                UNIX_TIMESTAMP(`timestamp`) AS timestamp
            FROM
                `' . sql_table('plugin_tb') . '`
            WHERE
                `tb_id` = ' . intval($data['tb_id']) . ' AND
                `block` = 0
            ORDER BY
                `timestamp` ASC
        ');
        
        while ($row = sql_fetch_assoc($res)) {
            $trackback = array(
                'title' => $row['title'],
                'url'   => $row['url'],
                'ip'    => ''
            );
            $data['trackbacks'][] = $trackback;
        }
    }

// }}}
// {{{ function event_BookmarkletExtraHead($data)

    /**
     * insert extra code to <head /> tags on bookmarklet
     *
     * @param arr
     *
    function event_BookmarkletExtraHead($data)
    {
        global $NP_TB_URL;
        list ($NP_TB_URL,) = $this->getURIfromLink(requestVar('loglink'));
    } 

// }}}
// {{{ function event_PrepareItemForEdit($data)

    /**
     * auto discover from item body
     *
     * @param arr
     */
    function event_PrepareItemForEdit($data)
    {
        if ($this->getOption('AutoXMLHttp') == 'no') {
            // The space between body and more is to make sure we didn't join 2 words accidently....
            $this->larray = $this->autoDiscovery($data['item']['body'] . ' ' . $data['item']['more']);
        }
    } 

// }}}
// {{{ function event_PostAddItem($data)

    /**
     * After an item has been added to the database, send out a ping if requested
     * (trackback_ping_url variable in request)
     *
     * @param arr
     */
    function event_PostAddItem($data)
    {
        $this->pingTrackback($data);
    }

// }}}
// {{{ function event_PreUpdateItem($data)

    /**
     * After an item has been updated on the database, send out a ping if requested
     * (trackback_ping_url variable in request)
     *
     * @param arr
     */
    function event_PreUpdateItem($data)
    {
        $this->pingTrackback($data);
    }

// }}}
// {{{ function event_AddItemFormExtras($data)

    /**
     * Add trackback options to add item form/bookmarklet
     *
     * @param arr
     */
    function event_AddItemFormExtras($data)
    {
        $this->itemFormExtra($data, 'add');
    }

// }}}
// {{{ function event_PreUpdateItem($data)

    /**
     * Add trackback options to edit item form/bookmarklet
     *
     * @param arr
     */
    function event_EditItemFormExtras($data)
    {
        $this->itemFormExtra($data, 'edit');
    }

// }}}
// {{{ function showLeftList($tb_id, $offset = 0, $amount = 99999999, $templateName = '')

    /**
     * Show a list of left trackbacks for this ID
     *
     * @param int
     * @param int
     * @param int
     * @param str
     */
    function showLeftList($tb_id, $offset = 0, $amount = 99999999, $templateName = '')
    {
        global $manager, $blog, $CONF;
        $tb_id = intval($tb_id);
        $out   = array();
        $query = '
            SELECT 
                `url`, 
                md5(`url`) as urlHash,
                `blog_name`,
                `excerpt`,
                `title`,
                UNIX_TIMESTAMP(`timestamp`) AS timestamp
            FROM
                `' . sql_table('plugin_tb') . '`
            WHERE
                `tb_id` = ' . $tb_id . ' AND
                `block` = 0
            ORDER BY 
                `timestamp` DESC
        ';
        if ($offset) {
            $query .= ' LIMIT ' . intval($offset) . ', ' . intval($amount);
        }
        $res       = sql_query($query);
        $templates = '';
        if (!empty($templateName)) {
            $templates =& $manager->getTemplate($templateName);
        }
        while($row = sql_fetch_array($res)) {
            $row['blog_name'] = htmlspecialchars($row['blog_name'], ENT_QUOTES);
            $row['title']     = htmlspecialchars($row['title'], ENT_QUOTES);
            $row['excerpt']   = htmlspecialchars($row['excerpt'], ENT_QUOTES);
            if (strtoupper(_CHARSET) != 'UTF-8') {
                $row['blog_name'] = $this->_restore_to_utf8($row['blog_name']);
                $row['title']     = $this->_restore_to_utf8($row['title']);
                $row['excerpt']   = $this->_restore_to_utf8($row['excerpt']);
                $row['blog_name'] = $this->_utf8_to_entities($row['blog_name']);
                $row['title']     = $this->_utf8_to_entities($row['title']);
                $row['excerpt']   = $this->_utf8_to_entities($row['excerpt']);
            }
            $iVars = array(
                'action'  => $this->getTrackBackUrl($tb_id),
                'form'    => $this->getManualPingUrl($tb_id),
                'name'    => $row['blog_name'], ENT_QUOTES),
                'title'   => $row['title'],
                'excerpt' => $this->_cut_string($row['excerpt'], 400),
                'url'     => htmlspecialchars($row['url'], ENT_QUOTES),
                'date'    => htmlspecialchars(strftime($this->getOption('dateFormat'), $row['timestamp']), ENT_QUOTES)
            );
            if ($this->getOption('HideUrl') == 'yes') {
                $iVars['url'] = $CONF['ActionURL'] . '?action=plugin&amp;name=TrackBack'
                              . '&amp;type=redirect&amp;tb_id=' . $tb_id
                              . '&amp;urlHash=' . $row['urlHash']
                              . '&amp;template=' . $templateName;
            } else {
                $iVars['url'] = $row['url'];
            }
            if (is_array($templates)) {
                $template = $templates['NP_TrackBack_tplItem'];
            } else {
                $template = $this->getOption('tplItem');
            }
            $out[] = TEMPLATE::fill($template, $iVars);
        }
        sql_free_result($res);
        return implode("\n", $out);
    }

// }}}
// {{{ function showList($tb_id, $amount = 0, $templateName = '')

    /**
     * Show a list of trackbacks for this ID
     *
     * @param int
     * @param int
     * @param str
     */
    function showList($tb_id, $amount = 0, $templateName = '')
    {
        $tb_id = intval($tb_id);
        global $manager, $blog, $CONF, $member;
        $enableHideurl = true;
        // for TB LinkLookup
        if( 
           strpos(serverVar('HTTP_USER_AGENT'), 'Hatena Diary Track') === false
        || strpos(serverVar('HTTP_USER_AGENT'), 'NP_TrackBack') === false
        || strpos(serverVar('HTTP_USER_AGENT'), 'TBPingLinkLookup') === false
        || strpos(serverVar('HTTP_USER_AGENT'), 'MT::Plugin::BanNoReferTb') === false
        || strpos(serverVar('HTTP_USER_AGENT'), 'livedoorBlog') === false
        ) {
            $enableHideurl = false;
            $amount        = '-1';
        }
        $query = '
            SELECT 
                `url`, 
                md5(`url`) as urlHash,
                `blog_name`,
                `excerpt`,
                `title`,
                UNIX_TIMESTAMP(`timestamp`) AS timestamp
            FROM
                `' . sql_table('plugin_tb') . '`
            WHERE
                `tb_id` = ' . $tb_id . ' AND
                `block` = 0
            ORDER BY 
                `timestamp` DESC
        ';
        if ($amount == '-1') {
            $query .= ' LIMIT 9999999';
        } elseif($amount) {
            $query .= ' LIMIT ' . intval($amount);
        }
        if ($amount != 0) {
            $res = sql_query($query);
        }
        $gVars = array(
            'action'   => $this->getTrackBackUrl($tb_id),
            'form'     => $this->getManualPingUrl($tb_id),
            'required' => $this->getRequiredURL($tb_id),
        );
        $templates = '';
        if (!empty($templateName)) {
            $templates =& $manager->getTemplate($templateName);
        }
        if ($member->isLoggedIn()) {
            $adminurl          = $manager->addTicketToUrl($this->getAdminURL() . 'index.php?action=list&id=' . $tb_id);
            $pingformurl       = $manager->addTicketToUrl($this->getAdminURL() . 'index.php?action=ping&id=' . $tb_id);
            $gVars['admin']    = '<a href="' . htmlspecialchars($adminurl, ENT_QUOTES) . '" target="_blank">[admin]</a>';
            $gVars['pingform'] = '<a href="' . htmlspecialchars($pingformurl, ENT_QUOTES) . '" target="_blank">[pingform]</a>';
        }
        if (is_array($templates)) {
            $tpl_Head = $templates['NP_TrackBack_tplHeader'];
            $tpl_Item = $templates['NP_TrackBack_tplItem'];
            $tpl_Empt = $templates['NP_TrackBack_tplEmpty'];
            $tpl_Foot = $templates['NP_TrackBack_tplFooter'];
        } else {
            $tpl_Head = $this->getOption('tplHeader');
            $tpl_Item = $this->getOption('tplItem');
            $tpl_Empt = $this->getOption('tplEmpty');
            $tpl_Foot = $this->getOption('tplFooter');
        }
        echo TEMPLATE::fill($tpl_Head, $gVars);
        while ($amount != 0 && $row = sql_fetch_array($res)) {
            $row['blog_name'] = htmlspecialchars($row['blog_name'], ENT_QUOTES);
            $row['title']     = htmlspecialchars($row['title'], ENT_QUOTES);
            $row['excerpt']   = htmlspecialchars($row['excerpt'], ENT_QUOTES);
            if (strtoupper(_CHARSET) != 'UTF-8') {
                $row['blog_name'] = $this->_restore_to_utf8($row['blog_name']);
                $row['title']     = $this->_restore_to_utf8($row['title']);
                $row['excerpt']   = $this->_restore_to_utf8($row['excerpt']);
                $row['blog_name'] = mb_convert_encoding($row['blog_name'], _CHARSET, 'UTF-8');
                $row['title']     = mb_convert_encoding($row['title'], _CHARSET, 'UTF-8');
                $row['excerpt']   = mb_convert_encoding($row['excerpt'], _CHARSET, 'UTF-8');
            }
            $iVars = array(
                'action'    => $this->getTrackBackUrl($tb_id),
                'form'      => $this->getManualPingUrl($tb_id),
                'name'      => htmlspecialchars($row['blog_name'], ENT_QUOTES),
                'title'     => htmlspecialchars($row['title'], ENT_QUOTES),
                'excerpt'   => htmlspecialchars($this->_cut_string($row['excerpt'], 400), ENT_QUOTES),
                'url'       => htmlspecialchars($row['url'], ENT_QUOTES),
                'date'      => htmlspecialchars(strftime($this->getOption('dateFormat'), $row['timestamp']), ENT_QUOTES)
            );
            if ($enableHideurl && $this->getOption('HideUrl') == 'yes') {
                $iVars['url'] = $CONF['ActionURL'] . '?action=plugin&amp;name=TrackBack'
                              . '&amp;type=redirect&amp;tb_id=' . $tb_id
                              . '&amp;urlHash=' . $row['urlHash'];
            } else {
                $iVars['url'] = $row['url'];
            }
            echo TEMPLATE::fill($tpl_Item, $iVars);
        }
        $q = '
            SELECT 
                count(*) 
            FROM 
                `' . sql_table('plugin_tb') . '`
            WHERE 
                `tb_id` = ' . $tb_id . ' AND
                `block` = 0
            ORDER BY
                `timestamp` DESC
        ';
        $result = sql_query($q);
        $total  = sql_result($result, 0, 0);
        if ($amount != -1 && $total > $amount) {
            $leftcount = $total - $amount;
            $adminURL  = $this->getAdminURL();
            $tb_id     = intval($tb_id);
            $amount    = intval($amount);
            echo <<<___SCRIPTCODE___
    <script type="text/javascript" src="{$adminURL}detectlist.php?tb_id={$tb_id}&amp;amount={$amount}"></script>';
    <a name="restoftrackback" id="restoftrackback"></a>
    <div id="tbshownavi"><a href="#restoftrackback" onclick="resttbStart(); return false;" id="tbshow">Show left {$leftcount} Trackbacks</a></div>
    <div id="tbhidenavi" style="display: none;"><a href="#restoftrackback" onclick="hideresttb(); return false;">Hide {$leftcount} Trackbacks</a></div>
    <div id="resttb"></div>

___SCRIPTCODE___;
        }
        if (sql_num_rows($res) == 0) {
                echo TEMPLATE::fill($tpl_Empt, $gVars);
        }
        sql_free_result($res);
        echo TEMPLATE::fill($tpl_Foot, $gVars);
    }

// }}}
// {{{ function getTrackBackCount($tb_id)

    /**
     * Returns the TrackBack count for a TrackBack item
     *
     * @param int
     * @return str
     */
    function getTrackBackCount($tb_id)
    {
        $query = 'SELECT COUNT(*) as result FROM %s WHERE tb_id=%d AND block = 0'
        return quickQuery(sprintf($query, sql_table('plugin_tb'), $tb_id));
    }

// }}}
// {{{ function getManualPingUrl($itemid)

    /**
     * Returns the manual ping URL
     *
     * @param int
     * @return str
     */
    function getManualPingUrl($itemid)
    {
        global $CONF;
        return $CONF['ActionURL'] . '?action=plugin&amp;name=TrackBack&amp;type=form&amp;tb_id=' . intval($itemid);
    }

// }}}
// {{{ function showManualPingError($itemid, $status = '')

    /**
     * Show the manual ping form
     *
     * @param int
     * @param str
     */
    function showManualPingError($itemid, $status = '')
    {
        global $CONF;
        $form    = true;
        $error   = true;
        $success = false;
        sendContentType('text/html', 'admin-trackback', _CHARSET);  
        require_once($this->getDirectory() . '/template.php');
        $mTemplate = new Trackback_Template(null, $this->getDirectory());
        $mTemplate->set ('CONF', $CONF);
        $mTemplate->set ('itemid', $itemid);
        $mTemplate->set ('form', $form);
        $mTemplate->set ('error', $error);
        $mTemplate->set ('success', $success);
        $mTemplate->set ('status', $status);
        $mTemplate->template('templates/form.html');
        echo $mTemplate->fetch();
    }

// }}}
// {{{ function showManualPingSuccess($itemid, $status = '')

    /**
     * Show the manual ping form
     *
     * @param int
     * @param str
     */
    function showManualPingSuccess($itemid, $status = '')
    {
        global $CONF;
        $form    = false;
        $error   = false;
        $success = true;
        sendContentType('text/html', 'admin-trackback', _CHARSET);  
        require_once($this->getDirectory() . '/template.php');
        $mTemplate = new Trackback_Template(null, $this->getDirectory());
        $mTemplate->set ('CONF', $CONF);
        $mTemplate->set ('itemid', $itemid);
        $mTemplate->set ('form', $form);
        $mTemplate->set ('error', $error);
        $mTemplate->set ('success', $success);
        $mTemplate->set ('status', $status);
        $mTemplate->template('templates/form.html');
        echo $mTemplate->fetch();
    }

// }}}
// {{{ function showManualPingForm($itemid, $text = '', $templateName = '')

    /**
     * Show the manual ping form
     *
     * @param int
     * @param str
     * @param str
     */
    function showManualPingForm($itemid, $text = '', $templateName = '')
    {
        global $CONF;
        $form    = true;
        $error   = false; 
        $success = false;
        // Check if we are allowed to accept pings
        if ( !$this->isAcceptTrackBack($itemid) ) {
            if (!empty($templateName)) {
                $templates =& $manager->getTemplate($templateName); 
                $template  =  $templates['NP_TrackBack_tplTbNoAccept'];
            } else {
                $template =  $this->getOption('tplTbNoAccept');
            }
            $text  =  TEMPLATE::fill($template, array());
            $form  = false;
            $error = true;
        }
        sendContentType('text/html', 'admin-trackback', _CHARSET);  
        require_once($this->getDirectory() . '/template.php');
        $mTemplate = new Trackback_Template(null, $this->getDirectory());
        $mTemplate->set ('CONF', $CONF);
        $mTemplate->set ('itemid', $itemid);
        $mTemplate->set ('form', $form);
        $mTemplate->set ('error', $error);
        $mTemplate->set ('success', $success);
        $mTemplate->set ('status', $status);
        $mTemplate->template('templates/form.html');
        echo $mTemplate->fetch();
    }

// }}}
// {{{ function getTrackBackUrl($itemid)

    /**
     * Returns the trackback URL
     *
     * @param int
     */
    function getTrackBackUrl($itemid)
    {
        global $CONF;
        return $CONF['ActionURL'] . '?action=plugin&amp;name=TrackBack&amp;tb_id='.$itemid;
    }

// }}}
// {{{ function itemFormExtra($data, $type = 'add')

    /**
     * Add trackback options to add/edit item form/bookmarklet
     *
     * @param int
     * @param str
     */
    function itemFormExtra($data, $type = 'add')
    {
        $listIt = _TB_LIST_IT;
        $admURL = $this->getAdminURL();
        echo <<<___FORMEXTRA___
    <h3>TrackBack</h3>
    <p>
        <label for="plug_tb_url">TrackBack URL:</label><br />
        <textarea id="plug_tb_url" name="trackback_ping_url" cols="60" rows="5" style="font:normal xx-small Tahoma, Arial, verdana ;"></textarea>
        <input type="button" name="btnAdd" value="{$listIt}" onClick="AddStart()" /><br />

___FORMEXTRA___;
        $XMLHttp = $this->getOption('AutoXMLHttp');
        if ($XMLHttp == 'yes') {
            echo <<<___FORMEXTRA___
        <div id="tb_auto">
            <input type="button" name="discoverit" value="Auto Discover" onclick="tbSetup();" />
            <img id='tb_busy' src='{$admURL}busy.gif' style="display:none;" /><br />
            <div id="tb_auto_title"></div>
            <table border="1">
                <tbody id="tb_ping_list"></tbody>
            </table>
            <input type="hidden" id="tb_url_amount" name="tb_url_amount" value="0" /> 
        </div>

___FORMEXTRA___;
            $this->jsautodiscovery();
    } elseif ($type == 'edit' && $XMLHttp != 'yes') {
            if (count($this->larray) > 0) {
                echo "\nAuto Discovered Ping URL's:<br />\n";
                echo '<input type="hidden" name="tb_url_amount" value="'.count($this->larray).'" />';
                $i = 0;
                while (list($url, $title) = each($this->larray)) {
                    if (_CHARSET != 'UTF-8') {
                        $title = $this->_utf8_to_entities($title);
                        $title = mb_convert_encoding($title, _CHARSET, 'UTF-8');
                    }
                    echo '<input type="checkbox" name="tb_url_' . $i . '" value="' . $url . '" id="tb_url_' . $i . '" />';
                    echo '<label for="tb_url_' . $i . '" title="' . $url . '">' . $title . '</label><br />';
                    $i++;
                }
            }
        }
        echo "</p>\n";
    }

// }}}
// {{{ function jsautodiscovery()

    /**
     * Insert Javascript AutoDiscovery routines
     */
    function jsautodiscovery()
    {
        echo '<script type="text/javascript" src="' . $this->getAdminURL() . 'autodetect.php"></script>';
    }

// }}}
// {{{ function insertCode($itemid)

    /**
     * Insert RDF code for item
     *
     * @param int
     */
    function insertCode($itemid)
    {
        $itemid = intval($itemid);
        global $manager, $CONF;
        $item  =& $manager->getItem($itemid, 0, 0);
        $blog  =& $manager->getBlog(getBlogIDFromItemID($item['itemid']));
        $uri   =  $this->_createItemLink($item['itemid'], $blog);
        $title =  strip_tags($item['title']);
        $desc  =  strip_tags($item['body']);
        $desc  =  $this->_cut_string($desc, 200);
        $desc  =  htmlspecialchars($desc, ENT_QUOTES);
        $tburl =  $this->getTrackBackUrl($itemid);
        $time  =  strftime('%Y-%m-%dT%H:%M:%S');
        echo <<<___RDFCODE___
<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
<rdf:Description
    rdf:about="{$uri}"
    dc:identifier="{$uri}"
    dc:title="{$title}"
    dc:description="{$desc}"
    trackback:ping="{$tburi}"
    dc:date="{$time}" />
</rdf:RDF>
-->

___RDFCODE___;
    }

// }}}
// {{{ function rssResponse($tb_id)

    /**
     * Retrieving TrackBack Pings (when __mode=rss)
     *
     * @param int
     */
    function rssResponse($tb_id)
    {
        $tb_id = intval($tb_id);
        global $manager, $CONF;
        $item =& $manager->getItem($tb_id, 0, 0);
        if ($item) {
            $blog      =& $manager->getBlog(getBlogIDFromItemID($item['itemid']));
            $blog_name =  $this->_restore_to_utf8($blog->getName());
            $title     =  $this->_restore_to_utf8($item['title']);
            $excerpt   =  $this->_restore_to_utf8($item['body']);
            $excerpt   =  $this->_cut_string($excerpt, 200);
            $url       =  $this->_createItemLink($item['itemid'], $blog);

            // Create response XML
            $dom      =  new DOMDocument('1.0', 'UTF-8');
            $response =  $dom->appendChild($dom->createElement('response'));
            $response->appendChild($dom->createElement('error', '0'));
            $rss      =  $response->appendChild($dom->createElement('rss'));
            $rss->setAttribute("version", "0.91");
            $channel  =  $rss->appendChild($dom->createElement('channel'));
            $channel->appendChild($dom->createElement('title', htmlspecialchars($title, ENT_QUOTES)));
            $channel->appendChild($dom->createElement('link', htmlspecialchars($url, ENT_QUOTES)));
            $channel->appendChild($dom->createElement('description', htmlspecialchars($excerpt, ENT_QUOTES)));

            $query = 'SELECT '
                   .    '`url`, '
                   .    '`blog_name`, '
                   .    '`excerpt`, '
                   .    '`title`, '
                   .    'UNIX_TIMESTAMP(`timestamp`) as timestamp '
                   . 'FROM '
                   .    sql_table('plugin_tb') . ' '
                   . 'WHERE '
                   .    '`tb_id` = ' . $tb_id . ' AND '
                   .    '`block` = 0 '
                   . 'ORDER BY '
                   .    '`timestamp` DESC';
            $res   = sql_query($query);
            while($data = sql_fetch_assoc($res)) {
                $data['title']   = htmlspecialchars($this->_restore_to_utf8($data['title']), ENT_QUOTES);
                $data['excerpt'] = htmlspecialchars($this->_restore_to_utf8($data['excerpt']), ENT_QUOTES);
                $data['url']     = htmlspecialchars($data['url'], ENT_QUOTES);
                $item            = $channel->appendChild($dom->createElement('item'));
                $item->appendChild($dom->createElement('title', $data['title']);
                $item->appendChild($dom->createElement('link', $data['url']);
                $item->appendChild($dom->createElement('description', $data['excerpt']);
            }
            header('Content-Type: text/xml');
            echo $dom->saveXML();
        } else {
            $this->xmlResponse(_ERROR_NOSUCHITEM);
        }
    }

// }}}
// {{{ function sendPing($itemid, $title, $url, $excerpt, $blog_name, $ping_url)

    /**
     * Send a Trackback ping to another website
     *
     * @param int
     * @param str
     * @param str
     * @param str
     * @param str
     * @param str
     */
    function sendPing($itemid, $title, $url, $excerpt, $blog_name, $ping_url)
    {
//        $sendEncoding = 'UTF-8';
        // 1. Check some basic things
        if (!$this->canSendPing()) {
            return _TB_msgNOTALLOWED_SEND;
        }
        if ($this->getOption('SendPings') == 'no') {
            return _TB_msgDISABLED_SEND;
        }
        if ($ping_url == '') {
            return _TB_msgNO_SENDER_URL;
        }
        // 2. Check if protocol is correct http URL
        $parsed_url = parse_url($ping_url);
        if (strpos($parsed_url['scheme'], 'http') !== 0 || !$parsed_url['host']) {
                return _TB_msgBAD_SENDER_URL;
        }
        // 3. Create contents
//        if ($sendEncoding != _CHARSET) {
        if (strtoupper(_CHARSET) != 'UTF-8') {
            $title     = mb_convert_encoding($title, 'UTF-8', _CHARSET);
            $excerpt   = mb_convert_encoding($excerpt, 'UTF-8', _CHARSET);
            $blog_name = mb_convert_encoding($blog_name, 'UTF-8', _CHARSET);
        }
        $ch      = curl_init();
        $data    = array(
            'title'     => $title,
            'url'       => $url,
            'excerpt'   => $excerpt,
            'blog_name' => $blog_name
        );
        $options = array(
            CURLOPT_URL            => $ping_url,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_RETURNTRANSFER => 1,
        );
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        if ($response === false) {
            return sprintf(_TB_msgCOULDNOT_SEND_PING, curl_error($ch), curl_errno($ch));
        }
        $respCd = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($respCd != 200) {
            return sprintf(_TB_msgRESP_HTTP_ERROR, $respCd, curl_error($ch));
        }
        $domDoc   = new DOMDocument;
        $domDoc->preserveWhiteSpace = false;
        $domDoc->loadXML($response);
        $encoding = $dom->encoding;
        if (empty($encoding)) {
            $encoding = $this->_detect_encoding($response);  //mb_detect_encoding($response, 'ASCII,ISO-2022-JP,UTF-8,EUC-JP,SJIS')
        }
        if (strtoupper($encoding) != "UTF-8" && strtoupper($encoding) != "ISO-8859-1") {
            $response = @mb_convert_encoding($response, "UTF-8", $encoding);
            $domDoc   = new DOMDocument;
            $domDoc->preserveWhiteSpace = false;
            $domDoc->loadXML($response);
        }
        $errors  = $domDoc->getElementsByTagName('error');
        $error   = $errors->item(0)->nodeValue;
        if (intval($error)) {
            $mesages = $domDoc->getElementsByTagName('message');
            $mesage  = $mesages->item(0)->nodeValue;
            if (strtoupper(_CHARSET) != 'UTF-8') {
                $mesage = @mb_convert_encoding($mesage, _CHARSET, "UTF-8");
            }
            return sprintf(_TB_msgAN_ERROR_OCCURRED, htmlspecialchars($mesage, ENT_QUOTES));
        }
        return '';
    }

// }}}
// {{{ function handlePing($tb_id = 0)

    /**
     * Send a Trackback ping to another website
     *
     * @param int
     * @return str
     */
    function handlePing($tb_id = 0)
    {
        global $manager;
        // Defaults
        $span  = false;
        $link  = false;
        $block = false;
        $rss   = false;
        if ($tb_id == 0) {
            $tb_id = intRequestVar('tb_id');
        }
        if (requestVar('__mode') == 'rss') {
            $rss = true;
        }
        if ($this->isEnableLinkCheck($tb_id)) {
            $block = true;
        }
        if (!$tb_id) {
            return _TB_msgTBID_IS_MISSING;
        }
        if ((!$manager->existsItem($tb_id,0,0)) && ($this->getOption('CheckIDs') == 'yes')) {
            return _ERROR_NOSUCHITEM;
        }
        // 0. Check if we need to output the list as rss
        if ($rss) {
            $this->rssResponse($tb_id);
            return;
        }
        // check: accept pings.
        $blogId       = getBlogIDFromItemID($tb_id);
        $isAcceptPing = $this->isAcceptTrackBack($tb_id);
        if (!$isAcceptPing) {
            return _TB_tplNO_ACCEPT;
        }
        // 1. Get attributes
        $b         =& $manager->getBlog(intval($blogId));
        $url       =  requestVar('url');
        $title     =  requestVar('title');
        $excerpt   =  requestVar('excerpt');
        $blog_name =  requestVar('blog_name');
        if ($url && preg_match('/https?:\/\/([^\/]+)/', $url, $matches) ){
            if( gethostbynamel($matches[1]) === FALSE ) {
                return _TB_msgBAD_SENDER_URL;
            }
        } else {
            return _TB_msgNO_SENDER_URL;
        }
        // 2. Conversion of encoding...
        $encoding = $this->_detect_encoding($excerpt);
        if (strtoupper(_CHARSET) != 'UTF-8') {
            $title     = $this->_strip_controlchar(strip_tags(mb_convert_encoding($title, _CHARSET, $encoding)));
            $excerpt   = $this->_strip_controlchar(strip_tags(mb_convert_encoding($excerpt, _CHARSET, $encoding)));
            $blog_name = $this->_strip_controlchar(strip_tags(mb_convert_encoding($blog_name, _CHARSET, $encoding)));
        } else {
            $title     = $this->_strip_controlchar($this->_convert_to_utf8($title, $encoding));
            $title     = $this->_decode_entities(strip_tags($title));
            $excerpt   = $this->_strip_controlchar($this->_convert_to_utf8($excerpt, $encoding));
            $excerpt   = $this->_decode_entities(strip_tags($excerpt));
            $blog_name = $this->_strip_controlchar($this->_convert_to_utf8($blog_name, $encoding));
            $blog_name = $this->_decode_entities(strip_tags($blog_name));
        }
        // 3. Save data in the DB
        $res = sql_query("
            SELECT 
                `tb_id`,
                `block`,
                `spam`
            FROM 
                `' . sql_table('plugin_tb') . '`
            WHERE 
                `url`   = '" . sql_real_escape_string($url) . "' AND 
                `tb_id` = '" . intval($tb_id) . "'
        ");
        if (sql_num_rows($res) != 0) {
            $rows = sql_fetch_assoc($res);
            $spam = ($rows['block'] || $rows['spam'] ) ? true : false;
            $res  = sql_query("
                UPDATE
                    `" . sql_table('plugin_tb') . "`
                SET 
                    `title`     = '" . sql_real_escape_string($title) . "', 
                    `excerpt`   = '" . sql_real_escape_string($excerpt) . "', 
                    `blog_name` = '" . sql_real_escape_string($blog_name) . "', 
                    `timestamp` = '  . mysqldate($b->getCorrectTime()) . '
                WHERE 
                    `url`       = '" . sql_real_escape_string($url) . "' AND 
                    `tb_id`     = '" . sql_real_escape_string(intval($tb_id)) . "'
            ');
            if (!$res) {
                return sprintf(_TB_msgTB_COULDNOT_TB_UPDATE, sql_error());
            }
        } else {
            // spam block
            $res = sql_query('
                SELECT 
                    `id` 
                FROM 
                    `' . sql_table('plugin_tb') . '` 
                WHERE 
                    `block` = 1 and 
                    `url`   = "' . sql_real_escape_string($url) . '"
            ');
            if (mysql_num_rows($res) != 0) {
                // NP_Trackback has blocked tb !
                ACTIONLOG :: add(INFO, sprintf(_TB_msgDUPLICATED_TB_BLOCKED, $tb_id, $url));
                return _TB_tplNO_ACCEPT;
            }
            // 4. SPAM check (for SpamCheck API 2 /w compat. API 1)
            $spamcheck = array (
                'type'     => 'trackback',
                'id'       => $tb_id,
                'title'    => $title,
                'excerpt'  => $excerpt,
                'blogname' => $blog_name,
                'url'      => $url,
                'return'   => true,
                'live'     => true,
                /* Backwards compatibility with SpamCheck API 1*/
                'data'     => $url . "\n" . $title . "\n" . $excerpt . "\n" . $blog_name . "\n" . serverVar('HTTP_USER_AGENT'),
                'ipblock'  => true,
            );
            $manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck));
            if (isset($spamcheck['result']) && $spamcheck['result'] == true) {
                $spam = true;
            }
            // 5. Content check (TO DO)
            $enableLinkCheck = $this->isEnableLinkCheck($tb_id);
            if ($spam == false || $enableLinkCheck == 'ignore') {
                if ($enableLinkCheck) {
                    $contents  = $this->retrieveUrl($url);
                    $linkArray = $this->getPermaLinksFromText($contents);
                    if (defined('NP_TRACKBACK_LINKCHECK_STRICT')) {
                        $itemLink = $this->_createItemLink($tb_id, $b);
                    } else {
                        $itemLink = $b->getURL();
                    }
                    $itemLinkPat = '{^' . preg_quote($itemLink) .'}i';
                    $itemLinkPat = str_replace('&','&(amp;)?', $itemLinkPat);
                    foreach ($linkArray as $l) {
                        if(preg_match($itemLinkPat, $l)) {
                            ACTIONLOG :: add(INFO, sprintf(_TB_msgLINK_CHECK_OK, $l, $itemLinkPat));
                            $link = true;
                            break;
                        }
                    }
                    if (!$link) {
                        $cnt = @count($linkArray);
                        if ($enableLinkCheck == 'ignore') {
                            ACTIONLOG :: add(INFO, sprintf(_TB_msgLINK_CHECK_IGNORE, $tb_id, $url, $cnt, $itemLinkPat));
                            return _TB_tplNO_ACCEPT;
                        } else {
                            ACTIONLOG :: add(INFO, sprintf(_TB_msgLINK_CHECK_BLOCK, $tb_id, $url, $cnt, $itemLinkPat));
                        }
                    }
                }
            }
            // 6. Determine if Trackback is safe...
            if ($enableLinkCheck) {
                $block = ($spam == true || $link == false);
            } else {
                $block = $spam == true;
            }
            $query = '
                INSERT INTO 
                    `' . sql_table('plugin_tb') . '` 
                SET
                    `tb_id`     = \'' . sql_real_escape_string(intval($tb_id)) . '\',
                    `block`     = \'' . ($block ? '1' : '0') . '\',
                    `spam`      = \'' . ($spam ? '1'  : '0') . '\',
                    `link`      = \'' . ($link ? '1'  : '0') . '\',
                    `url`       = \'' . sql_real_escape_string($url) . '\',
                    `title`     = \'' . sql_real_escape_string($title) . '\',
                    `excerpt`   = \'' . sql_real_escape_string($excerpt) . '\',
                    `blog_name` = \'' . sql_real_escape_string($blog_name) . '\',
                    `timestamp` = ' . mysqldate($b->getCorrectTime()) . '
            ';
            $res = sql_query($query);
            if (!$res) {
                return _TB_msgCOULDNOT_SAVE_DOUBLE . mysql_error() . $query;
            }
        }
        // 7. Send notification e-mail if needed
        $notifyAddrs = $this->getOption('NotifyEmail');
        $notifyAddrs = ($notifyAddrs ? $notifyAddrs . ';' : '') 
                     . $this->getBlogOption($blogId, 'NotifyEmailBlog');
        if ($notifyAddrs && $spam == false) {
            $vars = array (
                'tb_id'    => $tb_id,
                'url'      => $url,
                'title'    => $title,
                'excerpt'  => $excerpt,
                'blogname' => $blog_name
            );
            $mailto_title = TEMPLATE::fill($this->notificationMailTitle, $vars);
            $mailto_msg   = TEMPLATE::fill($this->notificationMail, $vars);
            global $CONF, $DIR_LIBS;
            // make sure notification class is loaded
            if (!class_exists('notification')) {
                include($DIR_LIBS . 'NOTIFICATION.php');
            }
            $notify = new NOTIFICATION($notifyAddrs);
            $notify->notify($mailto_title, $mailto_msg , $CONF['AdminEmail']);
            if ($manager->pluginInstalled('NP_Cache')) {
                $p =& $manager->getPlugin('NP_Cache');
                $p->setCurrentBlog($tb_id);
                $p->cleanItem($tb_id);
                $p->cleanArray(array('index'));
            }
        }
        if( $block ) {
            return _TB_tplNO_ACCEPT;
        }
            return '';
    }

// }}}
// {{{ function xmlResponse($errorMessage = '')

    /**
     * Send a Trackback ping to another website
     *
     * @param str
     */
    function xmlResponse($errorMessage = '')
    {
        $dom      =  new DOMDocument('1.0', 'UTF-8');
        $response =  $dom->appendChild($dom->createElement('response'));
        if ($errorMessage) {
            if (strtoupper(_CHARSET) != 'UTF-8') {
                $errorMessage = mb_convert_encoding($errorMessage, 'UTF-8');
                $response->appendChild($dom->createElement('error', '1'));
                $response->appendChild($dom->createElement('message', htmlspecialchars($errorMessage, ENT_QUOTES)));
            } elase {
                $response->appendChild($dom->createElement('error', '0'));
            }
        }
        exit;
    }

// }}}
// {{{ function canSendPing()

    /**
     * Check if member may send ping (check if logged in)
     *
     * @return bool
     */
    function canSendPing()
    {
        global $member;
        return $member->isLoggedIn() || $this->xmlrpc;
    }

// }}}
// {{{ function redirect($tb_id, $urlHash)

    /**
     * Redirect to trackbacked
     *
     * @param int
     * @param str
     */
    function redirect($tb_id, $urlHash)
    {
        $que = '
            SELECT
                `url` as result
            FROM
                `%s`
            WHERE
                `tb_id`    = %d AND
                md5(`url`) = "%s"
        ';
        $url = htmlspecialchars(quickQuery(sprintf($que, $tb_id, $url_Hash)), ENT_QUOTES);
        if (empty($url)) {
            global $CONF;
            $url = $CONF['SiteURL'];
        }
        $url = htmlspecialchars_decode(stripslashes($url), ENT_QUOTES);
        header('Location: ' . $url);
    }

// }}}
// {{{ function getRequiredURL($itemid)

    /**
     * Get required URL for link check
     *
     * @param int
     * @return str
     */
    function getRequiredURL($itemid)
    {
        global $manager;
        $blog =& $manager->getBlog(getBlogIDFromItemID(intval($itemid)));
        if ($this->isEnableLinkCheck(intval($itemid))) {
            return $this->_createItemLink(intval($itemid), $blog);
        }
        return '';
    }

// }}}
// {{{ function isEnableLinkCheck($itemid)

    /**
     * Is link check Enable ?
     *
     * @param int
     * @return bool
     */
    function isEnableLinkCheck($itemid)
    {
        switch($this->getItemOption($itemid, 'isAcceptW/OLink')) {
            case 'yes':
                return false;
                break;
            case 'no':
                return true;
                break;
            case 'default'
            default:
                $blogid = getBlogIDFromItemID(intval($itemid));
                $def    = $this->getBlogOption(intval($blogid), 'isAcceptW/OLinkDef');
                return $def != 'yes';
                break;
        }
    }

// }}}
// {{{ function isAcceptTrackBack($itemid = null)

    /**
     * Is TrackBack Accept ?
     *
     * @param int
     * @return bool
     */
    function isAcceptTrackBack($itemid = null)
    {
        $ret = false;
        if ($this->getOption('AcceptPing') == 'yes') {
            if ($itemid) {
                $bid = getBlogIDFromItemID(intval($itemid));
            } else {
                global $blog;
                if ($blog) {
                    $bid = $blog->getID();
                } else {
                    global $CONF;
                    $bid = $CONF['DefaultBlog'];
                }
            }
            if ($this->getBlogOption($bid, 'AllowTrackBack') == 'yes') {
                if ($itemid) {
                    $ret = $this->$this->getItemOption(intval($itemid), 'ItemAcceptPing') == 'yes' ? true : false;
                } else {
                    $ret = true;
                }
            } else {
                $ret = false;
            }
        }
        return $ret;
    }

// }}}
// {{{ function pingTrackback($data)

    /**
     * Ping all URLs
     *
     * @param array
     */
    function pingTrackback($data)
    {
        global $manager, $CONF;
        $ping_urls_count = 0;
        $ping_urls       = array();
        $ping_url        = requestVar('trackback_ping_url');
        $localflag       = array();
        if (trim($ping_url)) {
            $ping_urlsTemp = array();
            $ping_urlsTemp = preg_split("/[\s,]+/", trim($ping_url));
            for ($i=0; $i<count($ping_urlsTemp); $i++) {
                $ping_urls[] = trim($ping_urlsTemp[$i]);
                $ping_urls_count++;
            }
        }
        $tb_url_amount   = requestVar('tb_url_amount');
        for ($i=0; $i<$tb_url_amount; $i++) {
            $tb_temp_url = requestVar('tb_url_' . $i);
            if ($tb_temp_url) {
                $ping_urls[$ping_urls_count] = $tb_temp_url;
                $localflag[$ping_urls_count] = (requestVar('tb_url_' . $i . '_local') == 'on') ? 1 : 0;
                $ping_urls_count++;
            }
        }
        if ($ping_urls_count <= 0) {
            return;
        }
        $itemid =  $data['itemid'];
        $item   =& $manager->getItem($itemid, 0, 0);
        if (!$item) {
            return; // don't ping for draft & future
        }
        if ($item['draft']) {
            return;   // don't ping on draft items
        }
        // gather some more information, needed to send the ping (blog name, etc)      
        $blog      =& $manager->getBlog(getBlogIDFromItemID($itemid));
        $blog_name =  $blog->getName();
        $title     =  $data['title'] != '' ? $data['title'] : $item['title'];
        $title     =  strip_tags($title);
        $excerpt   =  $data['body'] != '' ? $data['body'] : $item['body'];
        $excerpt   =  strip_tags($excerpt);
        $excerpt   =  $this->_cut_string($excerpt, 200);
        $url       =  $this->_createItemLink($item['itemid'], $blog);    
        for ($i=0; $i<count($ping_urls); $i++) {
            if (!$localflag[$i]) {
                $res = $this->sendPing($itemid, $title, $url, $excerpt, $blog_name, $ping_urls[$i]);
            } else {
                $res = $this->handleLocalPing($itemid, $title, $excerpt, $blog_name, $ping_urls[$i]);
            }
            if ($res) {
                ACTIONLOG::add(WARNING, 'TrackBack Error:' . $res . ' (' . $ping_urls[$i] . ')');
            
        }
    }






    
    
    

    
/*
            $CONF['ItemURL'] = preg_replace('/\/$/', '', $blog->getURL());   
            $url = createItemLink($itemid);
*/
    
            // send the ping(s) (add errors to actionlog)











            $dom      =  new DOMDocument('1.0', 'UTF-8');
            $response =  $dom->appendChild($dom->createElement('response'));
            $response->appendChild($dom->createElement('error', '0'));
            $rss      =  $response->appendChild($dom->createElement('rss'));
            $rss->setAttribute("version", "0.91");
            $channel  =  $rss->appendChild($dom->createElement('channel'));
            $channel->appendChild($dom->createElement('title', htmlspecialchars($title, ENT_QUOTES)));
            $channel->appendChild($dom->createElement('link', htmlspecialchars($url, ENT_QUOTES)));
            $channel->appendChild($dom->createElement('description', htmlspecialchars($excerpt, ENT_QUOTES)));

            $query = 'SELECT '
                   .    '`url`, '
                   .    '`blog_name`, '
                   .    '`excerpt`, '
                   .    '`title`, '
                   .    'UNIX_TIMESTAMP(`timestamp`) as timestamp '
                   . 'FROM '
                   .    sql_table('plugin_tb') . ' '
                   . 'WHERE '
                   .    '`tb_id` = ' . $tb_id . ' AND '
                   .    '`block` = 0 '
                   . 'ORDER BY '
                   .    '`timestamp` DESC';
            $res   = sql_query($query);
            while($data = sql_fetch_assoc($res)) {
                $data['title']   = htmlspecialchars($this->_restore_to_utf8($data['title']), ENT_QUOTES);
                $data['excerpt'] = htmlspecialchars($this->_restore_to_utf8($data['excerpt']), ENT_QUOTES);
                $data['url']     = htmlspecialchars($data['url'], ENT_QUOTES);
                $item            = $channel->appendChild($dom->createElement('item'));
                $item->appendChild($dom->createElement('title', $data['title']);
                $item->appendChild($dom->createElement('link', $data['url']);
                $item->appendChild($dom->createElement('description', $data['excerpt']);
            }
            header('Content-Type: text/xml');
            echo $dom->saveXML();






































