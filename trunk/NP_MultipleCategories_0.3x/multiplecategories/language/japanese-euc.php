<?php 

define('_NPMC_DESCRIPTION',              'マルチ/サブカテゴリーを提供します。'); 

define('_NP_MCOP_ADDINDEX',              '[ノーマルURLの時の設定]設定されたブログのURLが「/」で終わっていたら、パラメーター文字列の前に「index.php」を追加しますか？');
define('_NP_MCOP_ADBIDDEF',              'デフォルトブログのカテゴリーのURLにブログIDを付加しますか？');
define('_NP_MCOP_ADBLOGID',              'カテゴリーの属するブログのURLがデフォルトブログのものと違う場合に、URLにブログIDを付加しますか？');
define('_NP_MCOP_MAINSEP',               'アイテムが属する本来のカテゴリーと追加カテゴリーとの区切り文字');
define('_NP_MCOP_ADDSEP',                'アイテムが複数の追加カテゴリーに所属する場合の追加カテゴリーの区切り文字');
define('_NP_MCOP_SUBFOMT',               'テンプレート変数として使用した時の、カテゴリーとサブカテゴリーの表示方法のテンプレート');
define('_NP_MCOP_CATHEADR',              'カテゴリーリストのヘッダ。テンプレート変数は<%blogid%>, <%blogurl%>, <%self%>が使用できます');
define('_NP_MCOP_CATLIST',               'カテゴリーリスト本体。テンプレート変数は<%catname%>,<%catdesc%>,<%catid%>,<%catlink%>,<%catflag%>,<%catamount%>,<%subcategorylist%>,<%amount%>が使用できます');
define('_NP_MCOP_CATFOOTR',              'カテゴリーリストフッター。テンプレート変数は<%blogid%>, <%blogurl%>, <%self%>が使用できます');
define('_NP_MCOP_CATFLAG',               'カテゴリーリスト中の表示中のカテゴリーのHTMLに付加するCSS用のクラス(ハイライト用)');
define('_NP_MCOP_SUBHEADR',              'サブカテゴリーリストのヘッダ');
define('_NP_MCOP_SUBLIST',               'サブカテゴリーリスト本体。テンプレート変数は<%subname%>, <%subdesc%>, <%subcatid%>, <%sublink%>, <%subflag%>, <%subamount%>が使用できます');
define('_NP_MCOP_SUBFOOTR',              'サブカテゴリーリストのフッター');
define('_NP_MCOP_SUBFLAG',               'サブカテゴリーリスト中の表示中のサブカテゴリーのHTMLに付加するCSS用のクラス(ハイライト用)');
define('_NP_MCOP_REPLACE',               'カテゴリーがサブカテゴリーを持っている時、テンプレート変数<%amount%>を任意の文字に置き換えますか？（REPLACEオプション）');
define('_NP_MCOP_REPRCHAR',              'テンプレート変数<%amount%>と置き換える文字。（REPLACEオプションを「はい」にした場合のみ有効）');
define('_NP_MCOP_ARCHEADR',              'アーカイブリストのヘッダー。テンプレート変数は<%blogid%>が使用できます');
define('_NP_MCOP_ARCLIST',               'アーカイブリストの本体。テンプレート変数は<%archivelink%>,<%blogid%>が使用できます。日付のフォーマットは標準のテンプレートの指定方法に従って指定する事が出来ます');
define('_NP_MCOP_ARCFOOTR',              'アーカイブリストのフッター。テンプレート変数は<%blogid%>が使用できます');
define('_NP_MCOP_LOCALE',                '日付のロケール');
define('_NP_MCOP_QICKMENU',              'クイックメニューに表示しますか？');
define('_NP_MCOP_DELTABLE',              'アンインストール時にデータを全て破棄しますか？');

?>