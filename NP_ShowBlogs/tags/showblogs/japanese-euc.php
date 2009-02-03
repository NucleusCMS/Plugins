<?php
	define('_SHOWB_DESC',   '&lt;%blog%&gt;、&lt;%archive%&gt;を置き換えるプラグインです。<br />'
						  . '全ブログ、または任意のブログをページスイッチつきで表示します<br />'
						  . 'NP_MultipleCategories v0.15 以降のマルチカテゴリ、およびNP_TagEX に対応しています<br />'
						  . 'Usage: &lt;%ShowBlogs(default/index, 15, all, 2, DESC, 6/15/56/186, default/stick)%&gt;');
	define('_CAT_FORMAT',   'カテゴリー名の表示形式');
//	define('_CATNAME_SHOW', 'オールブログモードの時のカテゴリー名の表示形式'
//						  . '(0:｢カテゴリ名 on ブログ名｣, 1:カテゴリ名のみ, 2:ブログ名のみ)');
	define('_STICKMODE',    'カレントブログモードの時に表示する固定表示アイテム');
//	define('_STICKMODE',    'カレントブログモードの時に表示する固定表示アイテム'
//						  . '(0:全て表示する, 1:表示中のブログに所属するもののみ)');
	define('_ADCODE_1',     '1番目と2番目に表示されるアイテムの間に表示する広告のコード');
	define('_ADCODE_2',     '2番目と3番目に表示されるアイテムの間に表示する広告のコード');
	define('_TAG_MODE',     'NP_TagEX 使用時のページスイッチのモード');
	define('_SB_NEXTL',     '次のページへのリンクテキスト');
	define('_SB_PREVL',     '前のページへのリンクテキスト');
	define('_TAG_SELECT',   '全ブログの tag を表示|0|'
						  . '表示中のブログに属する tag のみ表示|1|'
						  . '表示中のカテゴリ・サブカテゴリに属する tag のみ表示|2');
	define('_STICKSELECT',  '表示中のブログにかかわらず全て表示|0|'
						  . '表示中のブログに属する固定アイテムのみ表示|1|');
?>