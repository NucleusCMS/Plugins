<?php 
// plugin description
define('_TB_DESCRIPTION',               'トラックバックの受送信を行います');

// BLOG settings messages
define('_TB_isAcceptWOLinkDef',         '言及リンクがなくてもTBを受付するか? (blogデフォルト)');
define('_TB_isAcceptWOLinkDef_VAL',     'はい|yes|いいえ(保留)|block|いいえ(無視)|ignore');
define('_TB_AllowTrackBack',            'このブログでTBを受付するか?');
define('_TB_NotifyEmailBlog',           'ping受付時のメール送信先(;で区切って複数入力可能)');

// ITEM add/eit form messages
define('_TB_LIST_IT',                   '送信リストに追加');
define('_TB_ItemAcceptPing',            'TBを受付するか?');
define('_TB_isAcceptWOLink',            '言及リンクがなくてもTBを受付するか?');
define('_TB_isAcceptWOLink_VAL',        'ブログデフォルトに従う|default|はい|yes|いいえ|no');

// Global plugin options messages
define('_TB_AcceptPing',                'トラックバックの受付をするか?');
define('_TB_SendPings',                 'トラックバックの送信を可能にするか?');
define('_TB_AutoXMLHttp',               'autodiscovery機能(記事内のリンク先のTrackbackURLの自動検知)を使うか?');
define('_TB_CheckIDs',                  'ping受付時に有効なitemidかどうかをチェックするか?');
define('_TB_NotifyEmail',               'ping受付時のメール送信先(;で区切って複数入力可能)');
define('_TB_DropTable',                 'プラグインの削除時にデータを削除するか?');
define('_TB_HideUrl',                   '一覧表示の際に外部のURLをリダイレクトに変換するか?');
define('_TB_ajaxEnabled',               '管理画面でAjaxを有効にするか');

// notify e-mail template
define('_TB_NORTIFICATION_MAIL_BODY',   "<%blogname%> から ID:<%tb_id%> の記事に対してトラックバックを受信しました。 "
									  . "詳細は下記のとおりです:\n\n"
									  . "URL:\t<%url%>\nタイトル:\t<%title%>\n概要:\t<%excerpt%>\nブログ名:\t<%blogname%>");
define('_TB_NORTIFICATION_MAIL_TITLE',  "トラックバックを受信しました ID:<%tb_id%>");

// template title
define('_TB_dateFormat',                '日付の形式');
define('_TB_tplHeader',                 'TB一覧テンプレート(ヘッダ部)');
define('_TB_tplEmpty',                  'TB一覧テンプレート(0件のとき)');
define('_TB_tplItem',                   'TB一覧テンプレート(アイテム部)');
define('_TB_tplFooter',                 'TB一覧テンプレート(フッタ部)');
define('_TB_tplLocalHeader',            'ローカルTB一覧テンプレート(ヘッダ部)');
define('_TB_tplLocalEmpty',             'ローカルTB一覧テンプレート(0件のとき)');
define('_TB_tplLocalItem',              'ローカルTB一覧テンプレート(アイテム部)');
define('_TB_tplLocalFooter',            'ローカルTB一覧テンプレート(フッタ部)');
define('_TB_tplNO_ACCEPT',              'トラックバック拒否の時のメッセージ');
define('_TB_tplTbNone',                 'TB数表示形式(0件)');
define('_TB_tplTbOne',                  'TB数表示形式(1件)');
define('_TB_tplTbMore',                 'TB数表示形式(2件以上)');

// template values
define('_TB_dateFormat_VAL',            "%Y/%m/%d %H:%I");
define('_TB_tplHeader_VAL',             "<div class=\"tb\">\n\t<div class=\"head\">トラックバック</div><%admin%>\n\n");
define('_TB_tplEmpty_VAL',              "\t<div class=\"empty\">\n\t\tこのエントリにトラックバックはありません\n\t</div>\n\n");

define('_TB_tplItem_VAL',               "\t<div class=\"item\">\n\t\t<div class=\"name\"><%name%></div>\n"
									  . "\t\t<div class=\"body\">\n\t\t\t<a href=\"<%url%>\"><%title%>:</a> <%excerpt%>\n"
									  . "\t\t</div>\n\t\t<div class=\"date\">\n\t\t\t<%date%>\n\t\t</div>\n\t</div>\n\n");
define('_TB_tplFooter_VAL',             "\t<div class=\"info\">\n\t\tこの<a href=\"<%action%>\">トラックバックURL</a>を使ってこの記事にトラックバックを送ることができます。\n"
									  . "\t\tもしあなたのブログがトラックバック送信に対応していない場合には"
									  . "<a href=\"<%form%>\" onclick=\"window.open(this.href, 'trackback', 'scrollbars=yes,width=600,height=340,left=10,top=10,status=yes,resizable=yes'); return false;\">こちらのフォーム</a>"
									  . "からトラックバックを送信することができます。.\n\t</div>\n</div>");
define('_TB_tplLocalHeader_VAL',        "<div class=\"tblocal\">\n\t<div class=\"head\">ローカルトラックバック</div>\n\n");
define('_TB_tplLocalEmpty_VAL',         "");
define('_TB_tplLocalItem_VAL',          "\t<div class=\"item\">\n\t\t<div class=\"body\">\n\t\t\t<%delete%> <a href=\"<%url%>\"><%title%></a>: <%excerpt%>\n"
									  . "\t\t</div>\n\t\t<div class=\"date\">\n\t\t\t<%timestamp%>\n\t\t</div>\n\t</div>\n\n");
define('_TB_tplLocalFooter_VAL',        "\t</div>");

// error messages
define('_TB_msgNOTALLOWED_SEND',        "トラックバックの送信が許可されていません");
define('_TB_msgDISABLED_SEND',          "トラックバックの送信が無効に設定されています");
define('_TB_msgNO_SENDER_URL',          "トラックバック送信先のURLが空です");
define('_TB_msgBAD_SENDER_URL',         "トラックバック送信先のURLが正しくありません");
define('_TB_msgCOULDNOT_SEND_PING',     "エラーのためにトラックバックが送信出来ませんでした(エラー：%s)");
define('_TB_msgRESP_HTTP_ERROR',        "エラーが発生しました：HTTPエラー ： [%s] %s");
define('_TB_msgAN_ERROR_OCCURRED',      "エラーが発生しました：%s");
define('_TB_msgTBID_IS_MISSING',        "トラックバックIDが指定されていません");
define('_TB_msgTB_COULDNOT_TB_UPDATE',  "トラックバックデータを更新できませんでした: %s");
define('_TB_msgDUPLICATED_TB_BLOCKED',  "Trackback: itemid:%dへの%sからのトラックバックをブロックしました[拒否]");
define('_TB_msgLINK_CHECK_OK',          "Trackback: リンクチェック OK. (link: %s pat: %s )");
define('_TB_msgLINK_CHECK_IGNORE',      "Trackback: リンクチェック NG. [拒否] (itemid:%d from: %s cnt: %s pat: %s)");
define('_TB_msgLINK_CHECK_BLOCK',       "Trackback: リンクチェック NG. [ブロック] (itemid:%d from: %s cnt: %s pat: %s);
define('_TB_msgCOULDNOT_SAVE_DOUBLE',   'データ重複のためトラックバックを保存できませんでした: ');
define('_TB_msgTRACKBACK_ERROR',        'トラックバックエラー: %s (%s)');









