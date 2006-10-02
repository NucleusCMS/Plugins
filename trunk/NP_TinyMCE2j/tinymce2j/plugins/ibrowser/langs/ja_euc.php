<?php
	// ================================================
	// PHP image browser - iBrowser 
	// ================================================
	// iBrowser - language file: English
	// ================================================
	// Developed: net4visions.com
	// Copyright: net4visions.com
	// License: GPL - see license.txt
	// (c)2005 All rights reserved.
	// ================================================
	// Revision: 1.1                   Date: 07/07/2005
	// ================================================
	
	//-------------------------------------------------------------------------
	// charset to be used in dialogs
	$lang_charset = 'UTF-8';
	// text direction for the current language to be used in dialogs
	$lang_direction = 'ltr';
	//-------------------------------------------------------------------------
	
	// language text data array
	// first dimension - block, second - exact phrase
	//-------------------------------------------------------------------------
	// iBrowser
	$lang_data = array (  
		'ibrowser' => array (
		//-------------------------------------------------------------------------
		// common - im
		'im_001' => 'Image browser',
		'im_002' => 'iBrowser',
		'im_003' => 'メニュー',
		'im_004' => 'iBrowser 初期画面',
		'im_005' => '挿入',
		'im_006' => 'キャンセル',
		'im_007' => '挿入',		
		'im_008' => '画像のインライン挿入/変更',
		'im_009' => 'プロパティ',
		'im_010' => '画像のプロパティ',
		'im_013' => 'ポップアップ',
		'im_014' => '画像のポップアップ',
		'im_015' => 'About iBrowser',
		'im_016' => 'Section',
		'im_097' => '読込み中...',
		'im_098' =>	'Open section',
		'im_099' => 'Close section',
		//-------------------------------------------------------------------------
		// insert/change screen - in	
		'in_001' => '画像の挿入/変更',
		'in_002' => 'ライブラリ',
		'in_003' => 'ライブラリの選択',
		'in_004' => '画像',
		'in_005' => 'プレビュー',
		'in_006' => '画像の削除',
		'in_007' => 'クリックで拡大表示します',
		'in_008' => 'アップロード/ファイル名変更/ファイル削除の操作エリアを表示します',	
		'in_009' => 'Information',
		'in_010' => 'ポップアップ',		
		'in_013' => 'Create a link to an image to be opened in a new window.',
		'in_014' => 'ポップアップリンクの削除',	
		'in_015' => 'ファイル操作',	
		'in_016' => '名前の変更',
		'in_017' => '画像ファイル名の変更',
		'in_018' => 'アップロード',
		'in_019' => '画像のアップロード',	
		'in_020' => 'サイズ',
		'in_021' => 'Check the desired size(s) to be created while uploading image(s)',
		'in_022' => 'オリジナル',
		'in_023' => 'Image will be cropped',
		'in_024' => '削除',
		'in_025' => 'ディレクトリ',
		'in_026' => 'ディレクトリの作成',
		'in_027' => 'ディレクトリを作成',
		'in_028' => '幅',
		'in_029' => '高さ',
		'in_030' => 'Type',
		'in_031' => 'サイズ',
		'in_032' => '名前',
		'in_033' => '作成日時',
		'in_034' => '更新日時',
		'in_035' => 'Image info',
		'in_036' => 'Click on image to close window',
		'in_037' => '回転',
		'in_038' => 'Auto rotate: set to exif info, to use EXIF orientation stored by camera. Can also be set to +180&deg; or -180&deg; for landscape, or +90&deg; or -90&deg; for portrait. Positive values for clockwise and negative values for counterclockwise.',
		'in_041' => '',
		'in_042' => 'none',		
		'in_043' => 'portrait',
		'in_044' => '+ 90&deg;',	
		'in_045' => '- 90&deg;',
		'in_046' => 'landscape',	
		'in_047' => '+ 180&deg;',	
		'in_048' => '- 180&deg;',
		'in_049' => 'カメラ',	
		'in_050' => 'exif情報',
		'in_051' => 'WARNING: Current image is a dynamic thumbnail created by iManager - parameters will be lost on image change.',
		'in_052' => 'ファイル名一覧/サムネイル一覧の切替',
		'in_053' => 'ランダム',
		'in_054' => 'ランダム表示する場合にチェックを入れます',
		'in_055' => 'ランダムで画像を挿入する',
		'in_056' => 'パラメータ',
		'in_057' => 'パラメータをデフォルトにリセットする',
		'in_099' => 'デフォルト',	
		//-------------------------------------------------------------------------
		// properties, attributes - at
		'at_001' => 'Image attributes',
		'at_002' => 'Source',
		'at_003' => 'Title',
		'at_004' => 'TITLE値 - 画像にマウスをあわせたときにフロートするテキスト',
		'at_005' => 'Description',
		'at_006' => 'ALT値 - 画像の代替表示に使用するテキスト',
		'at_007' => 'Style',
		'at_008' => '選択したスタイルがcss定義済みであることを確認してください',
		'at_009' => 'CSSスタイル',	
		'at_010' => 'Attributes(属性)',
		'at_011' => '\'align\', \'border\', \'hspace\', \'vspace\' 属性は、XHTML 1.0 Strict DTDのサポート外です。代わりにcss定義を使用してください。',
		'at_012' => 'Align',	
		'at_013' => 'デフォルト',
		'at_014' => 'left',
		'at_015' => 'right',
		'at_016' => 'top',
		'at_017' => 'middle',
		'at_018' => 'bottom',
		'at_019' => 'absmiddle',
		'at_020' => 'texttop',
		'at_021' => 'baseline',		
		'at_022' => 'Size',
		'at_023' => 'Width',
		'at_024' => 'Height',
		'at_025' => 'Border',
		'at_026' => 'V-space',
		'at_027' => 'H-space',
		'at_028' => 'Preview',	
		'at_029' => '特殊文字の挿入',
		'at_030' => '特殊文字の挿入',
		'at_031' => 'Reset image dimensions to default values',
		'at_032' => 'Caption',
		'at_033' => 'checked: set image caption / unchecked: no image caption set or remove image caption',
		'at_034' => 'set image caption',
		'at_099' => 'デフォルト',	
		//-------------------------------------------------------------------------		
		// error messages - er
		'er_001' => 'エラー',
		'er_002' => '画像が選択されていません!',
		'er_003' => '幅の指定が数値ではありません',
		'er_004' => '高さの指定が数値ではありません',
		'er_005' => '囲み線の指定が数値ではありません',
		'er_006' => '左右余白の指定が数値ではありません',
		'er_007' => '上下余白の指定が数値ではありません',
		'er_008' => '画像を削除します ファイル名:',
		'er_009' => 'Renaming of thumbnails is not allowed! Please rename the main image if you like to rename the thumbnail image.',
		'er_010' => '画像名を変更します',
		'er_011' => '新しい名前が空であるか変更されていません!',
		'er_014' => '新規ファイル名を入力してください!',
		'er_015' => '有効なファイル名を入力してください!',
		'er_016' => 'Thumbnailing not available! Please set thumbnail size in config file in order to enable thumbnailing.',
		'er_021' => '画像をアップロードします',
		'er_022' => 'アップロード中 - 少々お待ち下さい...',
		'er_023' => '画像が選択されていないか、画像ファイルが存在しません',
		'er_024' => 'File',
		'er_025' => '既に存在します! 上書きの場合はOKを押してください...',
		'er_026' => '新しいファイル名を入力してください!',
		'er_027' => 'Directory doesn\'t physically exist',
		'er_028' => 'アップロード中にエラーが起こりました。 再試行してください',
		'er_029' => '画像のファイルタイプが不適切です',
		'er_030' => '削除は失敗しました! 再試行してください',
		'er_031' => '上書き',
		'er_032' => 'プレビューエリアからはみ出さない画像はズームしません',
		'er_033' => 'ファイル名変更に失敗しました。再試行してください',
		'er_034' => 'ディレクトリ作成に失敗しました! 再試行してください',
		'er_035' => 'Enlarging is not allowed!',
		'er_036' => '画像一覧が作成できません',
	  ),	  
	  //-------------------------------------------------------------------------
	  // symbols
		'symbols'		=> array (
		'title' 		=> 'Symbols',
		'ok' 			=> 'OK',
		'cancel' 		=> 'キャンセル',
	  ),	  
	)
?>