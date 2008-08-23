<?php
//Copyright (C) 2008 菅礼紗(http://www.okanesuita.org/).

//妹★関数-----------------------------------------------------------

//セッション初期化

class AuthSister {
	var $ver = 'Ver.0.3.5.alpha';
	var	$load;
	var	$mes_a;
	var	$mes_b;
	var	$method;
	var	$len_min;
	var	$len_max;
	var	$outlen;
		
	var	$fpath;
		
	var	$basedir;
	var	$imageurl;
	
	function session_start(){
		session_start();
	}
	
	//妹ヘッダを挿入します
	function header(){
		require(dirname(__FILE__).'/'.$this->load.'/config.inc.php');
		$this->image = $auth_sister_image;
		$this->fsize = $auth_sister_fsize;
		$this->fx = $auth_sister_fx;
		$this->fy = $auth_sister_fy;
		$this->header = str_replace("[basedir]", $this->basedir, $auth_sister_header);
		$this->header = str_replace("[imageurl]", $this->imageurl, $this->header);
		$this->html = $auth_sister_html;
		
		echo $this->header;
	}
	
	//妹認証の初期化
	function load(){
	
		$loc = dirname(__FILE__).'/'.$this->load.'/words.txt';//辞書ファイル読み込み
		//ファイル存在する場合は続行
		if(file_exists($loc)){
			if( $_SESSION['auth_sister'] && $_SESSION['auth_sister']['ticket'] ) return;
			$lines = file($loc, FILE_IGNORE_NEW_LINES);
			$cnt = count($lines); //行数カウント
			$cnt--;
			$point = mt_rand(0, $cnt); //乱数発生
			$_SESSION['auth_sister']['authID'] = uniqid(mt_rand());

			//質問文	正解メッセージ	不正解メッセージ	正解文	正解文の処理モード	逆処理スイッチ
			//↓回答文の処理モード
			//未指定・0:入力されたすべての文字列を含む
			//1:正規表現による
			//2:完全一致			
			list(
			$_SESSION['auth_sister']['question'],//質問文
			$_SESSION['auth_sister']['res_true'],//正解メッセージ
			$_SESSION['auth_sister']['res_false'],//不正解メッセージ
			$_SESSION['auth_sister']['answer'],//正解文
			$_SESSION['auth_sister']['anmode'],//正解文の処理モード
			$_SESSION['auth_sister']['rebirth']//逆処理スイッチ(0=OFF/1=ON)
			)=explode("\t",$lines[$point]);//各変数に読み出し
			
			$_SESSION['auth_sister']['ticket'] = true;	//画像読込権
			
			//以下マクロ処理だよ！
			//乱数発生だよ！
			$ran = mt_rand(1,9999);
			//乱数を平仮名にしちゃうよ！
			$ranran = $ran;
			$ranran = str_replace("0","れい"	,$ranran);
			$ranran = str_replace("1","いち"	,$ranran);
			$ranran = str_replace("2","に"  	,$ranran);
			$ranran = str_replace("3","さん"	,$ranran);
			$ranran = str_replace("4","よん"  	,$ranran);
			$ranran = str_replace("5","ご"  	,$ranran);
			$ranran = str_replace("6","ろく"	,$ranran);
			$ranran = str_replace("7","なな"	,$ranran);
			$ranran = str_replace("8","はち"	,$ranran);
			$ranran = str_replace("9","きゅう"	,$ranran);
			//乱数を漢字にしちゃおうかな！
			$kanran = $ran;
			$kanran = str_replace("0","零"	,$kanran);
			$kanran = str_replace("1","一"	,$kanran);
			$kanran = str_replace("2","二" 	,$kanran);
			$kanran = str_replace("3","三"	,$kanran);
			$kanran = str_replace("4","四"	,$kanran);
			$kanran = str_replace("5","五" 	,$kanran);
			$kanran = str_replace("6","六"	,$kanran);
			$kanran = str_replace("7","七"	,$kanran);
			$kanran = str_replace("8","八"	,$kanran);
			$kanran = str_replace("9","九"	,$kanran);
			//こんどは画数だぞ！
			$kankaku = $ran;
			$kankaku = str_replace("0","0"	,$kankaku);
			$kankaku = str_replace("1","一"	,$kankaku);
			$kankaku = str_replace("2","七" ,$kankaku);
			$kankaku = str_replace("3","川"	,$kankaku);
			$kankaku = str_replace("4","六"	,$kankaku);
			$kankaku = str_replace("5","四" ,$kankaku);
			$kankaku = str_replace("6","竹"	,$kankaku);
			$kankaku = str_replace("7","初"	,$kankaku);
			$kankaku = str_replace("8","松"	,$kankaku);
			$kankaku = str_replace("9","音"	,$kankaku);
			//[rand]
			$_SESSION['auth_sister']['question'] = str_replace("[rand]", $ran, $_SESSION['auth_sister']['question']);
			$_SESSION['auth_sister']['answer'] = str_replace("[rand]", $ran, $_SESSION['auth_sister']['answer']);
			//[rand_kana]
			$_SESSION['auth_sister']['question'] = str_replace("[rand_kana]", $ranran, $_SESSION['auth_sister']['question']);
			$_SESSION['auth_sister']['answer'] = str_replace("[rand_kana]", $ranran, $_SESSION['auth_sister']['answer']);
			//[rand_kan]
			$_SESSION['auth_sister']['question'] = str_replace("[rand_kan]", $kanran, $_SESSION['auth_sister']['question']);
			$_SESSION['auth_sister']['answer'] = str_replace("[rand_kan]", $kanran, $_SESSION['auth_sister']['answer']);		
			//[rand_kankaku]
			$_SESSION['auth_sister']['question'] = str_replace("[rand_kankaku]", $kankaku, $_SESSION['auth_sister']['question']);
		} else {
			return 'File not found:'.$loc;
		}
	}
	
	
	//妹認証の表示セット 先に初期化しておくこと
	function insert(){
		if( !$_SESSION['auth_sister']['authID'] ) die('authID is not initialized.');
		
		$output = str_replace("[authID]", $_SESSION['auth_sister']['authID'], $this->html);
		echo $output;
	}
	
	//auth_sister_auth()は認証成功の場合true、失敗の場合はfalseを返します。
	function auth(){
		$auth = false;
		$select = false;
		
		$authid= $_SESSION['auth_sister']['authID'];
		$select = $_POST[$authid];

		
		if($select){
			$select = mb_convert_encoding($select,"UTF-8","auto");//入力されたもの
			$answer	= mb_convert_encoding($_SESSION['auth_sister']['answer'],"UTF-8","auto");//正解文
			$mode	= $_SESSION['auth_sister']['anmode'];//処理モード
			
			$len = mb_strlen  ( $select  , "UTF-8");//入力文の文字数
			//文字数制限
			if(($len>=$this->len_min)&&($len<=$this->len_max)){
				//処理モード
				switch($mode):
					case 0://入力されたすべての文字列を含む
						if(mb_strstr($answer,$select,0,"UTF-8")) { $auth = true; }
						break;
					case 1://正規表現による
						if(mb_ereg($answer,$select))  { $auth = true; }
						break;
					case 2://完全一致
						if($answer==$select)  { $auth = true; }
						break;
					default://入力されたすべての文字列を含む
						if(mb_strstr($answer,$select,0,"UTF-8")) { $auth = true; }
				endswitch;
				//逆処理
				if($_SESSION['auth_sister']['rebirth']==1){
					if($auth) { $auth = false; }
					else { $auth = true; }
				}
				//認証結果文
				if($auth){
					$this->res = $_SESSION['auth_sister']['res_true'];
				} else {
					$this->res = $_SESSION['auth_sister']['res_false'];
				}
			//文字数エラー
			} else {
				$this->res = $outlen;
				$auth = false;
			}
		}
		return($auth);
	}
	
	//認証成功・失敗メッセージを返します
	function res(){
		if( $this->res )
			return $this->mes_a.$this->res.$this->mes_b;
		return '';
	}
	
	
	function show_image($mode, $type = "png"){
		switch($mode):
			case "img":
				session_start();
				if($_SESSION['auth_sister']['ticket']){
					require(dirname(__FILE__).'/'.$this->load.'/config.inc.php');
					$this->image = $auth_sister_image;
					$this->fsize = $auth_sister_fsize;
					$this->fx = $auth_sister_fx;
					$this->fy = $auth_sister_fy;
					$this->header = str_replace("[basedir]", $this->basedir, $auth_sister_header);
					$this->html = str_replace("[imageurl]", $this->imageurl, $auth_sister_html);
					
					header("Content-type: image/png");
					$text=$_SESSION['auth_sister']['question'];
					$text=mb_convert_encoding($text, "EUC-JP", "auto");
					$img = imagecreatefrompng(dirname(__FILE__).'/'.$this->load.'/'.$this->image);
					$color = imagecolorallocate($img,0x11,0x11,0x11);  //文字色
					
					imagettftext($img, $this->fsize, 0, $this->fx, $this->fy, $color, $this->font, $text );
							
					//画像形式
					if($type=="png"){
						imagepng($img);
					}
					
					imagedestroy($img);
					
					//$_SESSION['auth_sister']['ticket'] = false;
				}else{
					echo "Ticket expired.";
				}
			break;
		endswitch;
	}
}
