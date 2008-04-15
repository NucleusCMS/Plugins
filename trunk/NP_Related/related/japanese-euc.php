<?php

define(_RELATED_MESSAGE_DESC,		'記事に対する関連情報をリスト化して表示します。
	例：<%Related(local,5)%> <%Related(google,5)%> 
	Googleのウェブサービスを利用する場合、AJAX Search APIキーを取得する必要があります。
');

define(_RELATED_OPTION_GOOGLEKEY,		'Google AJAX Search APIキー');
define(_RELATED_OPTION_AMAZONTOKEN,		'Amazon APIキー');
define(_RELATED_OPTION_ASO_ID,			'AmazonアソシエイトID');
define(_RELATED_OPTION_HEADER_LC,		'見出しの開始（ローカル検索）');
define(_RELATED_OPTION_HEADER_GO,		'見出しの開始（Google検索）');
define(_RELATED_OPTION_HEADER_AM,		'見出しの開始（Amazon検索）');
define(_RELATED_OPTION_HEADER_END,		'見出しの終了');
define(_RELATED_OPTION_LISTHEADING,		'リストの開始');
define(_RELATED_OPTION_LISTFOOTER,		'リストの終了');
define(_RELATED_OPTION_ITEMHEADING,		'リストアイテムの開始');
define(_RELATED_OPTION_ITEMFOOTER,		'リストアイテムの終了');
define(_RELATED_OPTION_NOTITLE,			'題名がないとき');
define(_RELATED_OPTION_NORESULTS,		'検索結果がないとき');
define(_RELATED_OPTION_FLG_NOHEADER,	'検索結果がないときは見出しを表示しない');
define(_RELATED_OPTION_MORELINK,		'MOREリンク');
define(_RELATED_OPTION_MAXLENGTH,		'題名の長さ上限');
define(_RELATED_OPTION_MAXLENGTH2,		'スニペットの長さ上限');
define(_RELATED_OPTION_FLG_SNIPPET,		'スニペットを表示');
define(_RELATED_OPTION_FLG_TIMELOCAL,	'タイムスタンプ表示（ローカル検索）');
define(_RELATED_OPTION_FLG_SRCHCOND_AND,	'AND検索（ローカル検索）');
define(_RELATED_OPTION_CURRENTBLOG,		'同一ブログ内のみ検索（ローカル検索）');
define(_RELATED_OPTION_SEARCHRANGE,		'検索対象（ローカル検索）');
define(_RELATED_OPTION_INTERVAL,		'外部APIへの問合せ間隔（時間）');
define(_RELATED_OPTION_LANGUAGE,		'GoogleAPIへの言語指定');
define(_RELATED_OPTION_TOEXCLUDE,		'除外ドメイン指定');
define(_RELATED_OPTION_FLG_CACHE_ERASE,	'今すぐキャッシュデータを削除');
define(_RELATED_OPTION_FLG_ERASE,		'アンインストール時に全てのデータを削除');

define(_RELATED_REGEXP_QUOTESTYLE,
	"/『(.+)』|「(.+)」|\"(.+)\"|”(.+)”|\'(.+)\'|’(.+)’|\((.+)\)|（(.+)）|【(.+)】|\[(.+)\]/");
define(_RELATED_REGEXP_DELIMITER,		"/\s+|,|、|。|:|：/");

define(_RELATED_MSG_JUMP_LC,		"サイト内検索へジャンプします");
define(_RELATED_MSG_JUMP_GO,		"Googleへジャンプします");

?>