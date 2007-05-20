<?php 

define('_NPMC_DESCRIPTION',              'マルチ/サブカテゴリーを提供します。サブカテゴリーの階層は、無限に重ねることが出来ます'); 

define('_MC_EDIT_PLUGIN_OPTIONS',        'このプラグインのオプション編集画面へ'); 
define('_MC_SUB_CATEGORIES',             'サブカテゴリ'); 
define('_MC_EDIT_SUB_CATEGORIES',        'サブカテゴリの編集画面へ'); 
define('_MC_EDIT_SUB_CATEGORIES_OF',     'サブカテゴリの編集 - カテゴリ名'); 
define('_MC_CREATE_NEW_SUB_CATEGORY',    'サブカテゴリの新規作成'); 
define('_MC_SCAT_NAME',                  'サブカテゴリ名');
define('_MC_SCAT_DESC',                  'サブカテゴリの説明');
define('_MC_SCAT_CREATE',                '新しいサブカテゴリを作成');
define('_MC_SCAT_UPDATE',                'サブカテゴリの更新');
define('_MC_SCAT_EDIT',                  'サブカテゴリの編集 - サブカテゴリ名 ');
define('_MC_SCAT_DATA_UPDATE',           'サブカテゴリのデータを更新しました。');
define('_MC_SCAT_MISSING',               'サブカテゴリが見つかりません。');
define('_MC_SCAT_ERROR_NAME',            'エラーです。サブカテゴリ名を入力してください。');
define('_MC_CONFIRMTXT_SCAT',            '以下のサブカテゴリを削除しようとしています: ');

define('_MC_SCAT_PARENT_NAME',           '親となる(サブ)カテゴリ');
define('_MC_SCAT_ORDER_UPDATE',          'サブカテゴリの並び順を更新しました。');
define('_MC_MODIFY_CHILDREN_ORDER',      '子サブカテゴリの並び順の変更');
define('_MC_SUBMIT_CHILDREN_ORDER',      '並び順を反映');
define('_MC_NO_CHILDREN_ORDER',          '( 子となるサブカテゴリは複数ありません )');
define('_MC_SCAT_DELETE_NOTE_LIST',      '削除しようとするサブカテゴリには、直下に以下の子サブカテゴリが存在します');
define('_MC_SCAT_DELETE_NOTE_PARENT',    'もし削除を実行した場合、子カテゴリは階層が繰り上がり次の(サブ)カテゴリに自動的に属します');
define('_MC_SCAT_TABLE_UPDATE_INFO',     'サブカテゴリを無限階層管理したい場合は、テーブルのアップグレードが必要です');
define('_MC_SCAT_TABLE_UPDATE',          'サブカテゴリ管理用テーブルのアップグレードは完了しました');

define('_MC_SCAT_PARENT_NAME_DESC',      '（自分自身を指定した場合、変更しません。）');
define('_MC_SHOW_ORDER_MENU_KEY',        '並び替え基準');
define('_MC_SHOW_ORDER_MENU_SNAME',      '名前');
define('_MC_SHOW_ORDER_MENU_SDESC',      '説明');
define('_MC_SHOW_ORDER_MENU_INDIVIDUAL', '個別指定');

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