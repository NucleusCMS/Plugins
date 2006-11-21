<?php 
/**
  *
  * FOOT NOTE PLUG-IN FOR NucleusCMS
  * PHP versions 4 and 5
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * (see nucleus/documentation/index.html#license for more info)
  *
  * @author	Original Author nakahara21
  * @copyright	2005-2006 nakahara21
  * @license	http://www.gnu.org/licenses/gpl.txt
  *             GNU GENERAL PUBLIC LICENSE Version 2, June 1991
  * @version	0.32
  * @link		http://nakahara21.com
  *
  **/
class NP_FootNote extends NucleusPlugin
{

    function getName()
    {
        return 'Foot Note Plugin.';
    }

    function getAuthor()
    {
        $author = 'charlie, '
        		. 'nakahara21, '
        		. 'shizuki';
        return $author;
    }

    function getURL()
    {
        $original = 'http://nakahara21.com/';
        $wikiPage = 'http://japan.nucleuscms.org/wiki/plugins:footnote';
        return $wikiPage;
    }

    function getVersion()
    {
        return '0.32';
    }

    function getDescription()
    {
        $description = _FNOTE_DESC;
        return $description;
    }

	function supportsFeature($what)
	{
		switch ($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}


	function install()
	{
		$this->createOption('CreateTitle', _CLT_TITLE, 'yesno', 'yes');
		$this->createOption('Split',       _NOTE_SPLT, 'yesno', 'no');
	}

	function getEventList()
	{
		$events = array (
						 'PreItem',
						 'PreSkinParse'
						);
		return $events;
	}

	function init()
	{
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory()  . $language . '.php')) {
			include_once($this->getDirectory() . $language . '.php');
		}else {
			include_once($this->getDirectory() . 'english.php');
		}
	}

	function event_PreSkinParse($data)
	{
		$this->skinType = $data['type'];
	}

	function event_PreItem($data)
	{
		$skinType       =  $this->skinType;
		$this->nodeId   =  0;
		$this->noteList =  array();
		$this->itemId   =  $data['item']->itemid;
		$cData          =  array(
							     &$this,
							     'footnote'
							    );
		$iBody          =& $data['item']->body;
		$iMore          =& $data['item']->more;
		$iBody          =  preg_replace_callback("/\(\((.*)\)\)/Us",
												 $cData,
												 $iBody);
		$nsplit         =  $this->getOption('Split');
		if ($nsplit == 'yes' && $skinType != 'item') {
			if ($footNote = implode('', $this->noteList)) {
				$iBody .= '<ul class="footnote">' . $footNote . '</ul>';
			}
			$this->noteList = array();
		}
		if ($iMore) {
			$iMore = preg_replace_callback("/\(\((.*)\)\)/Us",
										   $cData,
										   $iMore);
			if ($footNote = implode('', $this->noteList)) {
				$iMore .= '<ul class="footnote">' . $footNote . '</ul>';
			}
		} elseif ($footNote = implode('', $this->noteList)) {
			$iBody .= '<ul class="footnote">' . $footNote . '</ul>';
		}
	}

	function footnote($matches){
		global $manager;
		$this->nodeId++;
		$iid    =  intval($this->itemId);
		$bid    =  getBlogIDFromItemID($iid);
		$b      =& $manager->getBlog($bid);
		$bsname =  $b->getShortName();
		if ($this->getOption('CreateTitle') == 'yes') {
			$fNote = htmlspecialchars(strip_tags($matches[1]));
			$fNote = preg_replace('/\r\n/s', '', $fNote);
			$fNote = ' title="' . $fNote . '"';
		}else{
			$fNote = '';
		}
		$footNoteID       = $bsname . $iid . '-' . $this->nodeId;
		$note             = '<span class="footnote">'
					      . '<a'
					      . ' href="#' . $footNoteID . '"'
					      . $fNote
					      . ' name="' . $footNoteID . 'f"'
					      . ' id="'   . $footNoteID . 'f"'
					      . '>'
					      . '*' . $this->nodeid
					      . '</a>'
					      . '</span>';
		$this->noteList[] = '<li>'
					      . '<a '
					      . 'href="#' . $footNoteID . 'f" '
					      . 'name="' . $footNoteID . '"'
					      . 'id="'   . $footNoteID . '"'
					      . '>'
					      . _NOTE_WORD . $this->nodeId
					      . '</a>'
					      . $matches[1]
					      . '</li>';
		return $note;
	}
}
