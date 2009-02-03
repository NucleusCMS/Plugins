<?php

class NP_CommentEdit extends NucleusPlugin
{

	function getName()
	{	// name of plugin
		return 'Comment Editable';
	}

	function getAuthor()
	{	// author of plugin
		return 'nakahara21';
	}

	function getURL()
	{	// an URL to the plugin website
		return 'http://japan.nucleuscms.org/wiki/plugins:commentedit';
	}

	function getVersion()
	{	// version of the plugin
		return '0.3';
	}

	// a description to be shown on the installed plugins listing
	function getDescription()
	{ 
		return 'Comment Editable';
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

	function doTemplateCommentsVar(&$item, &$comment, $type, $param1 = 'QQQQQ') { 
		global $CONF, $member;

		if ($member->isLoggedIn()) {
			$commentid = intval($comment['commentid']);
			if ($member->canAlterComment($commentid)) {
				$editLink  = $CONF['AdminURL']
						   . 'index.php?action=commentedit&amp;commentid='
						   . $commentid;
				$delLink   = $CONF['AdminURL']
						   . 'index.php?action=commentdelete&amp;commentid='
						   . $commentid;
				$printData = "<small>\n"
						   . '[ <a href="' . $editLink . '" target="_blank"> '
						   . _LISTS_EDIT . "</a> ]\n"
						   . '[ <a href="' . $delLink . '" target="_blank"> '
						   . _LISTS_DELETE . "</a> ]\n"
						   . "</small>\n";
				echo $printData;
			}
		}
	}

}
