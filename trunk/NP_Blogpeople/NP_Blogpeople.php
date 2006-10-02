<?
// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_Blogpeople extends NucleusPlugin {
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function getName()	{return 'Blogpeople feed'; }
	function getAuthor()  {return 'nakahara21'; }
	function getURL()	 {return 'http://xx.nakahara21.net/'; }
	function getVersion() {return '0.3'; }
	function getDescription() {
		return 'Call this to import a Blogpeople feed. Currently all feeds work with the same defaults.';
	}

	function install() {
	}

	function doSkinVar($skintype, $feedURL = '') {
		global $manager, $blog, $CONF; 
	global $i, $tname, $bplink, $data;

		if ($blog) { 
			$b =& $blog; 
		} else { 
			$b =& $manager->getBlog($CONF['DefaultBlog']); 
		} 



	$result = @file($feedURL);
	
	if($result){
	$i = 0;
	foreach($result as $key => $value){
		$result[$key] = mb_convert_encoding($value, _CHARSET, "auto");
	}
	$data = join( "", $result );
//	echo $data;

    $parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
    xml_set_element_handler($parser, "startElement", "endElement");
    xml_set_character_data_handler($parser, "characterData");

    if(!xml_parse($parser, $data)){
        die(sprintf("XML error %d %d",
        xml_get_current_line_number($parser),
        xml_get_current_column_number($parser)));
    }
    }

	xml_parser_free($parser);


//	print_r($bplink);

//==(表示部分 サンプルA)=======================
/*
	echo '<ul class="nobullets">'."\n";
	foreach($bplink as $out){
			$update = '';
		if($out['description']){	//更新日時がある場合

//			↓「2003-11-18 15:40」のように単純表示
			$update = date ("Y-m-d H:i", $out['description']);

		}

		echo '<li><a href="'.$out['link'].'" target="_blank">'.$out['title'].'</a> '.$update;
		echo '</li>'."\n";
	}
	echo '<li><a href="http://www.blogpeople.net/" target="_blank"><img src="http://www.blogpeople.net/powered-by.gif" border="0" alt="Powered By BlogPeople"></a></li>';
	echo '</ul>'."\n";

*/
//==(表示部分 サンプルB)=======================
/*
	echo '<ul class="nobullets">'."\n";
	foreach($bplink as $out){
			$update = '';
		if($out['description']){	//更新日時がある場合
//			更新からの経過時間(単位は時間)
			$difhours = round(($b->getCorrectTime() - $out['description'])/60/60);
			if($difhours < 24){	//24時間以内の表示
				$update = 'Hot!';
			}elseif($difhours < 48){	//48時間以内の表示
				$update = $difhours . 'h';
			}else{				//48時間以上経過した場合は日数表示
				$update = round($difhours/24).'d';
			}
		}

		echo '<li><a href="'.$out['link'].'" target="_blank">'.$out['title'].'</a> '.$update;
		echo '</li>'."\n";
	}
	echo '<li><a href="http://www.blogpeople.net/" target="_blank"><img src="http://www.blogpeople.net/powered-by.gif" border="0" alt="Powered By BlogPeople"></a></li>';
	echo '</ul>'."\n";

*/
//==(表示部分 サンプルC)=======================
	echo '<ul class="nobullets">'."\n";
	foreach($bplink as $out){
			$update = '';
		if($out['description']){	//更新日時がある場合
//			更新からの経過時間(単位は時間)
			$difhours = round(($b->getCorrectTime() - $out['description'])/60/60);
			if($difhours < 24){	//24時間以内の表示
				$update = ' style="border-bottom:3px solid red"';
			}elseif($difhours < 48){	//48時間以内の表示
				$update = ' style="border-bottom:3px solid orange"';
			}else{				//48時間以上経過した場合は日数表示
				$update = ' style="border-bottom:3px solid silver"';
			}

		}

		echo '<li><a href="'.$out['link'].'" target="_blank"'.$update.'>'.$out['title'].'</a> ';
		echo '</li>'."\n";
	}
	echo '<li><a href="http://www.blogpeople.net/" target="_blank"><img src="http://www.blogpeople.net/powered-by.gif" border="0" alt="Powered By BlogPeople"></a></li>';
	echo '</ul>'."\n";
//===================================

	echo "<hr />";
	}
	}

function startElement($parser, $name, $attrs){
	global $i, $tname;
	if($name == 'item'){$i ++;}
	$tname = $name;
}

 function endElement($parser, $name){
}

function characterData($parser, $data){
	global $i, $tname, $bplink;
	$data = trim($data);
	if($data){
		switch($tname){
			case 'title': 
				$bplink[$i][title] = $data;
				break;
			case 'link': 
				$bplink[$i][link] = $data;
				break;
			case 'description': 
				$data = explode(" ",$data);
				sscanf($data[0],'更新日:%2c年%2c月%2c日',$py,$pm,$pd);
				sscanf($data[1],'%2c時%2c分',$ph,$pi);
				$bplink[$i][description] =  mktime ($ph,$pi,0,$pm,$pd,$py);
				break;
			default: 
				break;
		
		
		}


	}
}


}
?>
