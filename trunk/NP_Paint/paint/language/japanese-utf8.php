<?php 

// plugin description
define('_PAINT_defaultWidth', 'デフォルトのキャンバスサイズ(幅)');		
define('_PAINT_defaultHeight', 'デフォルトのキャンバスサイズ(高さ)');		
define('_PAINT_defaultAnimation', '動画ファイルを保存するか？');		
define('_PAINT_defaultApplet', 'デフォルトApplet');
define('_PAINT_defaultPalette', 'デフォルトPalette');
define('_PAINT_defaultImgType', 'デフォルトの画像形式');
define('_PAINT_defaultImgCompress', '画像形式がAUTOの場合の減色・圧縮率[0-100]');		
define('_PAINT_defaultImgDecrease', '画像形式がAUTOの場合に減色が有効になる閾値[KB](利用しない場合は0)');		
define('_PAINT_defaultImgQuality', '画像形式がJPGの場合の画像品質[0-100]');		
define('_PAINT_defaultAppletQuality', 'デフォルトのQuality(しぃペインターのみ)');
define('_PAINT_bodyTpl', '本文テンプレート');
define('_PAINT_tagTpl', 'Paintタグテンプレート');
define('_PAINT_imageTpl', '画像部テンプレート');
define('_PAINT_continueTpl', 'Continue部テンプレート');
define('_PAINT_debug', 'ログを出力を行うか？');

// log message
define('_PAINT_DESCRIPTION',			'お絵かきアプレットとの連携ができるようにします。詳しくはヘルプを参照してください。');
define('_PAINT_HeadersAlreadySent'.		'ヘッダはすでに送信されています');
define('_PAINT_NeedLogin',				'ログインが必要です');
define('_PAINT_UserNotFound',			'ユーザーが存在しません');
define('_PAINT_InvalidTicket',			'チケットが有効ではありません。投稿画面右下の「チケットを更新する」を実行してから再度投稿してください。');
define('_PAINT_phpVersion_before',		'NP_PaintにはPHP');
define('_PAINT_phpVersion_after',		'以降が必要です');
define('_PAINT_illegalCollection',		'不正なCollectionが指定されています');
define('_PAINT_canNotFindApplet',		'Appletが見つかりません');
define('_PAINT_fileIsNotSet',			'ファイルが指定されていません');
define('_PAINT_viewerIsNotSet',			'ビュアーが指定されていません');
define('_PAINT_canNotFindViewer',		'ビュアーが見つかりません');
define('_PAINT_canNotReadFile',			'ファイルを読み込めません');
define('_PAINT_canNotFindPrefix',		'Prefixが見つからないので生成します');
define('_PAINT_deleteFile',				'ファイルを削除します');
define('_PAINT_deleteFile_failure',		'ファイルの削除に失敗しました');
define('_PAINT_rename_failure',			'tmpfileのリネームに失敗しました');
define('_PAINT_rename_ok',				'tmpfileをリネームしました');
define('_PAINT_GDNotSupported',			'PHPでGDがサポートされていません。画像変換をスキップします');
define('_PAINT_convertToJpg',			'画像をJPGに変換します');
define('_PAINT_convertToJpg_succeeded',	'画像のJPG変換に成功しました');
define('_PAINT_convertToJpg_failure',	'画像のJPG変換に失敗しました');
define('_PAINT_pngRead_failure',		'PNG画像の読み込みに失敗しました');
define('_PAINT_canNotLoadClass',		'クラスのロードに失敗しました');

define('_PAINT_STAR',					'★');

// index.php
define('_Paint_directoryNotWriteable',	'ディレクトリが存在しないか、書き込み可能になっていません: ');
define('_PAINT_fileOpen_failure',		'ファイルのオープンに失敗しました');
define('_PAINT_canNotAutoInstall',		'この機能は自動インストールできません');
define('_PAINT_autoInstall',			'インストール可能なApplet/Palette/その他');
define('_PAINT_noSuchPlugin',			'そのようなプラグインはありません');
define('_PAINT_appletinstall',			'Applet/Paletteのインストール');
define('_PAINT_iniDownload',			'設定ファイルのダウンロード');
define('_PAINT_doDownload',				'自動インストールに必要な設定ファイルをダウンロード');
define('_PAINT_installSuffix',			'のインストール');
define('_PAINT_downloadSuffix',			'からファイルをダウンロードしてサーバに配置してください');

// Applet
define('_PAINT_Applet',	'(お絵かきApplet)');
define('_PAINT_Applet_PaintBBS_name',	'PaintBBS');
define('_PAINT_Applet_Shipainter_name',	'しぃペインター');
define('_PAINT_Applet_Shipainterpro_name',	'しぃペインターPro');

// Palette
define('_PAINT_Palette',	'(動的パレット)');

// Parser
define('_PAINT_Parser_useinput',				'php://inputを利用してデータを取得します');
define('_PAINT_Parser_contentLengthNotFound',	'CONTENT_LENGTHが取得できませんでしたが続行します');

// Viewer
define('_PAINT_Viewer_infoNotFond',	'情報はありません');
define('_PAINT_Viewer_spch_desc',	'(しぃペインターのアニメーション再生)');
define('_PAINT_Viewer_pch_desc',	'(PaintBBSのアニメーション再生)');
