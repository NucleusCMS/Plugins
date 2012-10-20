<?php
/**
 * ImprovedMedia plugin for Nucleus CMS
 * Version 3.0.1
 * Written By Mocchi, Feb.28, 2010
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 */

define('_IM_DESCRIPTION',	'ファイル削除と名前変更機能、サブ・ディレクトリ管理機能を追加した外部ファイル管理機能に差し替えます。また、設定を変更することにより、メディア・ディレクトリを個別のウェブログのディレクトリに配置した運用を可能とします。');
define('_IM_OPTION_PRIVATE',	'プライベート・コレクション・フォルダを使いますか？');
define('_IM_OPTION_ITEMDISPLAY',	'一つの画面にいくつのファイルを表示しますか？（5から50まで）');
define('_IM_OPTION_GREYBOX',	'Media ControlウィンドウにGreyBoxユーティリティを使いますか？');
define('_IM_OPTION_EACHBLOGDIR',	'それぞれのウェブログがある個別ディレクトリでのファイル管理を利用しますか？');

define('_IM_HEADER_TEXT',	' - ImprovedMedia plugin for Nucleus CMS');
define('_IM_HEADER_RENAME_CONFIRM',	'ファイル名の変更（ステップ 1の1）');
define('_IM_HEADER_ERASE_CONFIRM',	'ファイルの削除（ステップ 1の1）');
define('_IM_HEADER_UPLOAD_SELECT',	'ファイルのアップロード（ステップ 1の1）');
define('_IM_HEADER_EMBED_CONFIRM',	'ファイルの挿入（ステップ 2の1）');
define('_IM_HEADER_EMBED',	'ファイルの挿入（ステップ 2の2）');
define('_IM_HEADER_SUBDIR_CREATE_CONFIRM',	'サブ・ディレクトリの作成');
define('_IM_HEADER_SUBDIR_REMOVE_CONFIRM',	'サブ・ディレクトリの削除');
define('_IM_HEADER_SUBDIR_RENAME_CONFIRM',	'サブ・ディレクトリ名の変更');

define('_IM_ANCHOR_TEXT',	'ファイル管理');
define('_IM_VIEW_TT',	' :ファイル表示 (新しいウィンドウが開きます)');
define('_IM_FILTER',	'フィルター: ');
define('_IM_FILTER_APPLY',	'フィルター適応');
define('_IM_FILTER_LABEL',	'フィルター（大文字小文字無関係）: ');
define('_IM_UPLOAD_TO',	'アップロード先');
define('_IM_UPLOAD_NEW',	'新規アップロード');
define('_IM_UPLOADLINK',	'新しいファイルのアップロード');
define('_IM_COLLECTION_SELECT',	'選択');
define('_IM_COLLECTION_TT',	'このコレクション・ディレクトリに切り替え');
define('_IM_COLLECTION_LABEL',	'コレクション・ディレクトリの変更: ');
define('_IM_MODIFIED',	'更新日');
define('_IM_FILENAME',	'ファイル名');
define('_IM_DIMENSIONS',	'サイズ');
define('_IM_WEBLOG_LABEL',	'ウェブログ');

define('_IM_FORBIDDEN_ACCESS',	'禁止している操作によるアクセスです');
define('_IM_ALT_TOOLONG',	'説明文が40文字を超えています');
define('_IM_ERASE_FAILED',	'ファイルの削除に失敗しました');
define('_IM_MISSING_FILE',	'ファイルを見つけることができませんでした');
define('_IM_MISSING_DIRECTORY',	'コレクション・ディレクトリを見つけることができませんでした');
define('_IM_REMIND_DIRECTORY',	'アップロード用フォルダ内に任意のディレクトリを作成してください。');
define('_IM_REMIND_MEDIADIR',	'アップロード用フォルダが適切に設置されていません。アップロード用フォルダとそのアクセス権限を再設定して下さい。');
define('_IM_RENAME_FORBIDDEN',	'ファイルにアクセスできませんでした。あなたのアクセス権を確認してください');
define('_IM_RENAME_FILEEXIST',	'変更したい名前のファイルがすでに存在しています');
define('_IM_RENAME_TOOLONG',	'ファイル名が30文字を超えています');
define('_IM_RENAME_WRONG',	'ファイル名に指定文字以外が含まれています');
define('_IM_NOTICE',	'注意！');
define('_IM_FUNCTION',	'機能');
define('_IM_RENAME_FAILED',	'名前の変更に失敗しました');
define('_IM_RENAME_BLANK',	'ファイル名が空白です');
define('_IM_RENAME',	'名前変更');
define('_IM_RENAME_DESCRIPTION',	'変更後の名前を、拡張子ぬきで、30文字までで入力してください。使用できる文字は英数字と3種類の記号（アンダーバー、ハイフン、プラス）です。日本語は使用できません。');
define('_IM_RENAME_AFTER',	'変更後の名前');
define('_IM_RENAME_FILENAME',	'名前を変更するファイル');
define('_IM_FILENAME_CLICK',	'ファイル名');
define('_IM_FILTER_NONE',	'フィルタなし');
define('_IM_ACTION',	'動作');
define('_IM_RETURN',	'一覧へ');
define('_IM_COLLECTION',	'コレクション・ディレクトリ');
define('_IM_COLLECTION_DESC',	'ファイルをアップロードするディレクトリを選択してください。');
define('_IM_SHORTNAME',	'ウェブサイトの短縮名');
define('_IM_UPDATE',	'登録日');
define('_IM_TYPE',	'種類');
define('_IM_ERASE',	'削除');
define('_IM_ERASE_CONFIRM',	'以下のファイルを消去します');
define('_IM_ERASE_DONE',	'削除する');
define('_IM_INCLUDE',	'文章に挿入');
define('_IM_INCLUDE_DESC',	'以下のファイルを文章に挿入します。');
define('_IM_INCLUDE_ALT',	'ファイルの説明文');
define('_IM_INCLUDE_ALT_DESC',	'ファイルの簡単な説明文を、40文字までで入力してください。必須です。');
define('_IM_INCLUDE_MODIFIED',	'修正する');
define('_IM_INCLUDE_FILE_SELECTED',	'選択したファイル');
define('_IM_INCLUDE_WAY',	'ファイルの表示方法');
define('_IM_INCLUDE_WAY_POPUP',	'説明文を記事に表示し、クリックでポップアップ表示');
define('_IM_INCLUDE_WAY_INLINE',	'画像をそのままのサイズで記事に埋め込む');
define('_IM_INCLUDE_WAY_OTHER',	'このファイルは、上で入力した説明文がアンカーテキストとなります。文書中の説明文をクリックすると、ファイルがポップアップ表示されます。');
define('_IM_INCLUDE_CODE',	'入力画面に埋め込まれるコード');
define('_IM_INCLUDE_CODE_DESC',	'入力画面には、以下のコードが埋め込まれます。このコードは再編集しないでください。');
define('_IM_INCLUDE_WAY_DECIDE',	'決定');
define('_IM_UPLOAD_USED_ASCII',	'ファイル名は必ず英数字');
define('_IM_UPLOAD_USED_ASCII_DESC1',	'日本語を用いると、アドレスが文字化けしてブラウザで参照できなくなります可能性があります。');
define('_IM_UPLOAD_USED_ASCII_DESC2',	'その場合は、削除機能を用いて削除してください。そして、名前を変更してから、再度アップロードしてください。');
define('_IM_UPLOAD_USED_FILETYPE',	'現在利用できるファイルのタイプ');
define('_IM_UPLOAD_CONPLETE',	'ファイルのアップロードが成功しました');
define('_IM_COLLECTION_AMOUNT',	'ファイル数: ');
define('_IM_COLLECTION_BRANK',	'ファイルなし');
define('_IM_REQUIREMENT',	'説明文を入力してください');
define('_IM_ITEMDISPLAY_WRONG',	'1ページの表示ファイル数を、5件から50件の間で指定してください。');

define('_IM_SUBDIR',	'サブ・ディレクトリ');
define('_IM_COLLECTION_FAILED_READ',	'コレクション・ディレクトリ情報の取得に失敗しました。アクセス権限を確認して下さい');
define('_IM_SUBDIR_LABEL',	'サブ・ディレクトリ: ');
define('_IM_SUBDIR_SELECT',	'選択');
define('_IM_SUBDIR_NONE',	'なし');
define('_IM_SUBDIR_TT',	'このサブ・ディレクトリに切り替え');
define('_IM_SUBDIR_DESC',	'ファイルをアップロードするサブ・ディレクトリを選択してください。なしの場合はコレクション・ディレクトリに保存されます。');
define('_IM_DISPLAY_FILES',	'ファイル表示');
define('_IM_SUBDIR_REMOVE',	'ディレクトリ削除');
define('_IM_DISPLAY_SUBDIR',	'サブ・ディレクトリ管理');
define('_IM_DISPLAY_SUBDIR_TT',	'サブ・ディレクトリの管理画面に移動');
define('_IM_DISPLAY_SUBDIR_SELECT',	'変 更');
define('_IM_CREATE_SUBDIR_CONFIRM',	'サブ・ディレクトリ作成');
define('_IM_CREATE_SUBDIR_COLLECTION_LABEL',	'コレクション・ディレクトリの指定');
define('_IM_CREATE_SUBDIR_COLLECTION',	'サブ・ディレクトリを作成するコレクション・ディレクトリを指定してください');
define('_IM_CREATE_SUBDIR_INPUT_NAME',	'サブ・ディレクトリのディレクトリ名');
define('_IM_CREATE_SUBDIR_CHARS',	'サブ・ディレクトリ名に指定された以外の文字種が使われています');
define('_IM_CREATE_SUBDIR_CHARS_DESC',	'サブ・ディレクトリ名に利用できる文字は、英数字と3種類の記号（アンダーバー、ハイフン、プラス）です。最長で20文字です。それ以外の文字種や日本語は使用できません。');
define('_IM_RENAME_SUBDIR_BLANK',	'サブ・ディレクトリ名が空白です');
define('_IM_RENAME_SUBDIR_TOOLONG',	'サブ・ディレクトリ名が20文字を超えています');
define('_IM_RENAME_SUBDIR_WRONG',	'サブ・ディレクトリ名に指定された以外の文字種が使われています');
define('_IM_RENAME_SUBDIR_DUPLICATE',	'同名のサブ・ディレクトリがすでに存在しています。');
define('_IM_CREATE_SUBDIR_WRONG',	'サブ・ディレクトリが作成できませんので、サーバ管理者に相談してください');
define('_IM_RENAME_SUBDIR_COLLECTION',	'名前を変更するサブ・ディレクトリ');
define('_IM_SUBDIR_NUM_FILES',	'ファイル数');
define('_IM_DISPLAY_SUBDIR_LABEL1',	'サブ・ディレクトリ数: ');
define('_IM_DISPLAY_SUBDIR_LABEL2',	', ファイル数: ');
define('_IM_DISPLAY_SUBDIR_RETURN',	'ファイル一覧');
define('_IM_REMOVE_SUBIDR',	'削除するサブ・ディレクトリ');
define('_IM_REMOVE_SUBIDR_CONFIRM',	'サブ・ディレクトリの削除の確認');
define('_IM_REMOVE_SUBIDR_REMIND', 	'サブ・ディレクトリを削除すると、中のファイルもすべて同時に削除されます。その際、すでにアイテム内に挿入されているコードは、この操作を行っても自動で書き換えられません。なお、コレクション・ディレクトリを削除することはできません。');
define('_IM_REMOVE_SUBDIR_FAILED', 	'サブ・ディレクトリの削除に失敗しています。サブ・ディレクトリもしくは含まれているファイルを確認してください。');
define('_IM_DISPLAY_SUBDIR_CAPTION',	'サブ・ディレクトリ一覧');
define('_IM_DISPLAY_SUBDIR_NOTHING',	'サブ・ディレクトリなし');
define('_IM_SUBDIR_REMOVE_FAILED',	'サブ・ディレクトリの削除に失敗しました。サブ・ディレクトリのアクセス権限もしくはサブ・ディレクトリ内のファイルのアクセス権限を確認して下さい。');
define('_IM_SUBDIR_FAILED_READ',	'サブ・ディレクトリ情報の取得に失敗しました。アクセス権限を確認して下さい');
?>
