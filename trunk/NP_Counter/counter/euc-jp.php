<?php

// plugin description
if ($GLOBALS['HACK']['show_plugin_desc'])
	$this->description = 
		'多機能版カウンターです。'.
		'プラグインオプションでカウント方式の変更などができます。'.
		'スキンの記述例: &lt;%Counter%&gt; or &lt;%Counter(image,another)%&gt;. '.
		'第二パラメータは追加の画像パスとして認識されます（スキン別にカウンター画像を切り替えられます）。';

// words in plugin option
$this->opt['graphical_counter'] = '画像カウンター';
$this->opt['graphics_path'] = '画像パス。デフォルトは nucleus/plugins/counter/。 "/"で終わる必要があります。';
$this->opt['ext'] = '画像ファイルの拡張子（デフォルトはgif）';
$this->opt['init_val'] = 'カウンター初期値。0以外で、現在より大きい値が有効です。';
$this->opt['figure'] = '桁指定。指定する必要がないときは0を代入。"6/3" は合計表示6桁、詳細表示が各3桁です。';
$this->opt['flg_detail'] = '詳細表示(7日間or1週間、昨日、今日)';
$this->opt['flg_week'] = '1週間（日曜から土曜まで）を詳細表示に使う';
$this->opt['flg_bdate'] = 'カウント開始日を表示する';
$this->opt['begin_date'] = 'カウント開始日';
$this->opt['count_mode'] = 'カウント方式。 [normal] 全アクセスを記録 [ip1] 時間期限付きIPチェック [ip2] IP別一日1カウント';
$this->opt['time_limit'] = 'ip1の制限時間';
$this->opt['flg_showmode'] = 'カウント方式を表示する';
$this->opt['flg_pluglink'] = 'プラグインリンクを表示する。管理者権限でログインしたときはプラグインのバージョンチェッカーとしても機能します。';
$this->opt['flg_erase'] = 'アンインストール時にデータを消去する';

?>
