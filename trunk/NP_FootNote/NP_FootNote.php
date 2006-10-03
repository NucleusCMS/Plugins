<?php 
/* 
NP_FootNote 
はてなダイアリーなどで使用される脚注を作成するプラグイン。 
本文中に(())で囲まれたフレーズがあると、脚注として表示します。 
もとのデータ自体は変更せず、パースする際に変換しています。 

変更履歴 
0.3:SecurityFix and XHTML Valid.
0.3:注釈がない記事にも無駄なコードを追加していたバグ修正。
0.2:拡張領域に入力がない場合無駄なコードを追加していたバグ修正。
0.1+:本文と拡張文とで注解を分ける指定をオプションに追加。
0.1：本文注の部分に入ったAタグのtitle属性から不要な文字を削除するようにした。またこの部分の表示/非表示を切り替えるオプションを追加。 
0.06：同じ行に(())があると一つの注としてまとめられるバグを修正。注内部での改行をサポート。 
0.05：拡張領域のみに注がある場合に注が表示されないバグを修正。 
0.04：拡張領域への注に対応。注がある場合には拡張領域に注を表示するようにした。 
0.03：とりあえず版リリース。 

*/ 
class NP_FootNote extends NucleusPlugin
{

	var $bsname;
	var $item_id;
	var $note_id;
	var $notelist;

    function getName()
    {
        return 'Foot Note Plugin.';
    }

    function getAuthor()
    {
        return 'charlie + nakahara21 + shizuki';
    }

    function getURL()
    {
        return 'http://xx.nakahara21.net/';
    }

    function getVersion()
    {
        return '0.31';
    }

    function getDescription()
    {
        return 'はてな、Wikiで使用される脚注を生成するプラグインです。本文中に((と))で囲まれたフレーズがあると、脚注として表示します。';
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
		$this->createOption('CreateTitle', '本文注のリンクにTitle属性を付加しますか？', 'yesno', 'yes');
		$this->createOption('Split', '本文と拡張文で注解を分けますか？(アイテムページは常にまとめて最下部になります)', 'yesno', 'no');
	}

	function getEventList()
	{
		return array(
			'PreItem',
			'PreSkinParse'
		);
	}

	function event_PreSkinParse($data)
	{
		$this->skinType = $data['type'];
	}

	function event_PreItem($data)
	{
//		global $i, $id, $notelist;
		global $blogid, $manager;
//		$this->currentItem =& $data['item'];
		$this->node_id = 0;
		$b =& $manager->getBlog($blogid);
		$this->bsname = $b->getShortName();
		$this->notelist = array();
		$this->item_id = intval($data['item']->itemid);
		$data['item']->body = preg_replace_callback("/\(\((.*)\)\)/Us", array(&$this, 'footnote'), $data['item']->body);
		if ($this->getOption('Split') == 'yes' && $this->skinType != 'item') {
			if ($footnote = @join('', $this->notelist)) {
				$data['item']->body .= '<ul class="footnote">' . $footnote . '</ul>';
			}
			$this->notelist = array();
		}
		if ($data['item']->more) {
			$data['item']->more = preg_replace_callback("/\(\((.*)\)\)/Us", array(&$this, 'footnote'), $data['item']->more);
			if ($footnote = @join('', $this->notelist)) {
				$data['item']->more .= '<ul class="footnote">' . $footnote . '</ul>';
			}
		} elseif ($footnote = @join('', $this->notelist)) {
			$data['item']->body .= '<ul class="footnote">' . $footnote . '</ul>';
		}
	}

	function footnote($matches){
//		global $i, $id, $notelist;
		$this->node_id++;
		if ($this->getOption('CreateTitle') == 'yes') {
			$fnote2 = htmlspecialchars(strip_tags($matches[1]));
			$fnote2 = preg_replace('/\r\n/s', '', $fnote2);
			$fnote2 = ' title="' . $fnote2 . '"';
		}else{
			$fnote2 = '';
		}
		$note = '<span class="footnote"><a href="#' .
				$this->bsname . $this->item_id . '-' . $this->node_id . '"' . $fnote2 . '>*' . $this->node_id .
				'</a><a name="' . $this->bsname . $this->item_id . '-' . $this->node_id . 'f"></a></span>';
		$this->notelist[] = '<a name="' . $this->bsname . $this->item_id . '-' . $this->node_id . '"></a><li><a href="#' .
							$this->bsname . $this->item_id . '-' . $this->node_id . 'f">注' . $this->node_id . '</a>' . $matches[1] . '</li>';
		return $note;
	}
}

?>