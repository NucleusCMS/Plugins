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

//==(ɽ����ʬ ����ץ�A)=======================
/*
	echo '<ul class="nobullets">'."\n";
	foreach($bplink as $out){
			$update = '';
		if($out['description']){	//����������������

//			����2003-11-18 15:40�פΤ褦��ñ��ɽ��
			$update = date ("Y-m-d H:i", $out['description']);

		}

		echo '<li><a href="'.$out['link'].'" target="_blank">'.$out['title'].'</a> '.$update;
		echo '</li>'."\n";
	}
	echo '<li><a href="http://www.blogpeople.net/" target="_blank"><img src="http://www.blogpeople.net/powered-by.gif" border="0" alt="Powered By BlogPeople"></a></li>';
	echo '</ul>'."\n";

*/
//==(ɽ����ʬ ����ץ�B)=======================
/*
	echo '<ul class="nobullets">'."\n";
	foreach($bplink as $out){
			$update = '';
		if($out['description']){	//����������������
//			��������ηв����(ñ�̤ϻ���)
			$difhours = round(($b->getCorrectTime() - $out['description'])/60/60);
			if($difhours < 24){	//24���ְ����ɽ��
				$update = 'Hot!';
			}elseif($difhours < 48){	//48���ְ����ɽ��
				$update = $difhours . 'h';
			}else{				//48���ְʾ�вᤷ����������ɽ��
				$update = round($difhours/24).'d';
			}
		}

		echo '<li><a href="'.$out['link'].'" target="_blank">'.$out['title'].'</a> '.$update;
		echo '</li>'."\n";
	}
	echo '<li><a href="http://www.blogpeople.net/" target="_blank"><img src="http://www.blogpeople.net/powered-by.gif" border="0" alt="Powered By BlogPeople"></a></li>';
	echo '</ul>'."\n";

*/
//==(ɽ����ʬ ����ץ�C)=======================
	echo '<ul class="nobullets">'."\n";
	foreach($bplink as $out){
			$update = '';
		if($out['description']){	//����������������
//			��������ηв����(ñ�̤ϻ���)
			$difhours = round(($b->getCorrectTime() - $out['description'])/60/60);
			if($difhours < 24){	//24���ְ����ɽ��
				$update = ' style="border-bottom:3px solid red"';
			}elseif($difhours < 48){	//48���ְ����ɽ��
				$update = ' style="border-bottom:3px solid orange"';
			}else{				//48���ְʾ�вᤷ����������ɽ��
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
				sscanf($data[0],'������:%2cǯ%2c��%2c��',$py,$pm,$pd);
				sscanf($data[1],'%2c��%2cʬ',$ph,$pi);
				$bplink[$i][description] =  mktime ($ph,$pi,0,$pm,$pd,$py);
				break;
			default: 
				break;
		
		
		}


	}
}


}
?>
