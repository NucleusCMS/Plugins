<?php 
/* 
NP_FootNote 
はてなダイアリーなどで使用される脚注を作成するプラグイン。 
本文中に(())で囲まれたフレーズがあると、脚注として表示します。 
もとのデータ自体は変更せず、パースする際に変換しています。 

変更履歴 
0.3:注釈がない記事にも無駄なコードを追加していたバグ修正。
0.2:拡張領域に入力がない場合無駄なコードを追加していたバグ修正。
0.1+:本文と拡張文とで注解を分ける指定をオプションに追加。
0.1：本文注の部分に入ったAタグのtitle属性から不要な文字を削除するようにした。またこの部分の表示/非表示を切り替えるオプションを追加。 
0.06：同じ行に(())があると一つの注としてまとめられるバグを修正。注内部での改行をサポート。 
0.05：拡張領域のみに注がある場合に注が表示されないバグを修正。 
0.04：拡張領域への注に対応。注がある場合には拡張領域に注を表示するようにした。 
0.03：とりあえず版リリース。 

*/ 
class NP_FootNote extends NucleusPlugin { 

    function getName() { 
        return 'Foot Note Plugin.';  
    } 
    function getAuthor()  {  
        return 'charlie + nakahara21';  
    } 
    function getURL()  
    { 
        return 'http://xx.nakahara21.net/';  
    } 
    function getVersion() { 
        return '0.3';  
    } 
    function getDescription() {  
        return 'はてな、Wikiで使用される脚注を生成するプラグインです。本文中に((と))で囲まれたフレーズがあると、脚注として表示します。'; 
    } 
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}


	function install() { 
		$this->createOption('CreateTitle','本文注のリンクにTitle属性を付加しますか？','yesno','yes'); 
		$this->createOption('Split','本文と拡張文で注解を分けますか？(アイテムページは常にまとめて最下部になります)','yesno','no'); 
	} 

	function getEventList() { 
		return array('PreItem','PreSkinParse'); 
	} 

	function event_PreSkinParse($data) { 
		$this->type = $data['type'];
	}

	function event_PreItem($data) { 
		global $i, $id, $notelist;
		$this->currentItem = &$data["item"]; 
		$i =0;
		$notelist = array();
		$id = $this->currentItem->itemid;
		$this->currentItem->body = preg_replace_callback("/\(\((.*)\)\)/Us", array(&$this, 'footnote'), $this->currentItem->body); 
		if($this->getOption('Split') == 'yes' && $this->type != 'item'){
			if($footnote = @join('',$notelist))
				$this->currentItem->body .= '<ul class="footnote">' . $footnote . '</ul>';
			$notelist = array();
		}
		if($this->currentItem->more){
			$this->currentItem->more = preg_replace_callback("/\(\((.*)\)\)/Us", array(&$this, 'footnote'), $this->currentItem->more); 
			if($footnote = @join('',$notelist))
				$this->currentItem->more .= '<ul class="footnote">' . $footnote . '</ul>';
		}elseif($footnote = @join('',$notelist)){
			$this->currentItem->body .= '<ul class="footnote">' . $footnote . '</ul>';
		}
	} 

	function footnote($matches){
		global $i, $id, $notelist;
		$i++;
		if($this->getOption('CreateTitle') == 'yes'){
			$fnote2 = htmlspecialchars(strip_tags($matches[1]));
			$fnote2 = preg_replace('/\r\n/s','',$fnote2);
			$fnote2 = ' title="'.$fnote2.'"';
		}else{
			$fnote2 = '';
		}
		$note = '<span class="footnote"><a href="#'.$id.'-'.$i.'"'.$fnote2.'>*'.$i.'</a><a name="'.$id.'-'.$i.'f"></a></span>';
		$notelist[] = '<a name="'.$id.'-'.$i.'"></a>'.'<li><a href="#'.$id.'-'.$i.'f">注'.$i.'</a>'.$matches[1].'</li>';
		return $note;
	
	}
} 
?>