<?php
/**
  * Modified by hsur ( http://blog.cles.jp/np_cles )
  * $Id: index.php,v 1.6 2008-05-03 22:38:17 hsur Exp $
*/
	// vim: tabstop=2:shiftwidth=2
	//
	// Nucleus Admin section;
	// Created by Xiffy
	//
	// Modified by hsur ($Id: index.php,v 1.6 2008-05-03 22:38:17 hsur Exp $)

	$strRel = '../../../';
	include($strRel . 'config.php');
	
	include($DIR_LIBS . 'PLUGINADMIN.php');
	require_once($DIR_PLUGINS . 'sharedlibs/sharedlibs.php');
	require_once('cles/Feedback.php');
	
	if ($blogid) {$isblogadmin = $member->isBlogAdmin($blogid);}
	else $isblogadmin = 0;
	
	if (!($member->isAdmin() || $isblogadmin)) {
		$oPluginAdmin = new PluginAdmin('SpamBayes');
		$pbl_config = array();
		$oPluginAdmin->start();
		echo "<p>"._ERROR_DISALLOWED."</p>";
		$oPluginAdmin->end();
		exit;
	}
	
	// Actions
	$action = requestVar('page');
	$aActionsNotToCheck = array(
		'',
	);
	if (!in_array($action, $aActionsNotToCheck)) {
		if (!$manager->checkTicket()) doError(_ERROR_BADTICKET);
	}
	
	if (isset($_GET['page'])) {$action = $_GET['page'];}
	if (isset($_POST['page'])) {$action = $_POST['page'];}
	
	// Okay; we are allowed. let's go
	// create the admin area page
	$oPluginAdmin = new PluginAdmin('SpamBayes');
	$oPluginAdmin->start();
	$fb =& new cles_Feedback($oPluginAdmin);
	
	if( defined('NP_SPAMBAYES_APIURL') && (! $oPluginAdmin->plugin->getOption('appid'))){
		echo '<h2>Plugin Error!</h2>';
		echo '<h3>Yahoo! Japan Application ID が設定されていません</h3>';
		$oPluginAdmin->end();
		exit;
	}
	
	$action = requestVar('page');
	if ($action == 'clearlog') {
		$filter     = requestVar('filter')     ? requestVar('filter')     : 'all';
		$filtertype = requestVar('filtertype') ? requestVar('filtertype') : 'all';
		$ipp        = requestVar('ipp')		   ? requestVar('ipp')        : 10;
		$keyword	= requestVar('keyword');
		$oPluginAdmin->plugin->spambayes->nbs->clearlog($filter, $filtertype, $keyword, $ipp);
		$action = 'log';
		// reset values to no filter; otherwise the view will be empty
		if ($_REQUEST['amount'] <> 'cp' ) {
			$_REQUEST['filter']     = 'all';
			$_REQUEST['filtertype'] = 'all';
			$_REQUEST['keyword']    = '';
		}
	}
	
	sb_nucmenu($oPluginAdmin->plugin->getOption('enableTrainall'),$oPluginAdmin->plugin->getOption('enableLogging'));
	
	switch ($action) {
		case 'update':
			$oPluginAdmin->plugin->spambayes->updateProbabilities();
			break;
		case 'trainall':
			sb_trainall();
			break;
		case 'trainnew':
			sb_trainnew();
			break;
		case 'train':
			sb_train();
			sb_trainform();
			break;
		case 'untrain':
			sb_untrain();
			break;
		case 'trainlog':
			sb_trainlog();
			sb_log();
			break;
		case 'untrainall':
			sb_untrainall();
			break;
		case 'test':
			sb_test();
			sb_testform();
			break;
		case 'log':
			sb_log();
			break;
		case 'explain':
			sb_explain();
			sb_log();
			break;
		case 'batch':
			sb_batch();
			sb_log();
			break;
		case 'promote':
			sb_promote();
			sb_log();
			break;
			
		case 'trainspamtb':
			sb_trainspamtb();
			break;
		case 'trainspamtbnew':
			sb_trainspamtbnew();
			break;
		case 'traintb':
			sb_traintb();
			break;
		case 'traintbnew':
			sb_traintbnew();
			break;
		case 'trainblocked':
			sb_trainblocked();
			break;
		case 'trainblockednew':
			sb_trainblockednew();
			break;
		
		case 'report' :
			$extradata = '';
			if( defined('NP_SPAMBAYES_TOKENIZER') )
				$extradata .= 'Mecab';
			if( defined('NP_SPAMBAYES_APIURL') )
				$extradata .= 'Yahoo!';
				
			$fb->printForm($extradata);
			break;
	}
	
	$cats = $oPluginAdmin->plugin->spambayes->nbs->getCategories();
	$i = 0;
	$keys = array_keys($cats);
	echo '<fieldset><legend>Bayesianフィルタ 統計情報</legend><table>';
	echo '<tr><th>カテゴリ</th><th>確率</th><th>単語数</th></tr>';
	foreach($cats as $category) {
		echo "<tr><td><b>$keys[$i]</b></td>";
		foreach($category as $key => $value) {
			echo '<td>'.$value.'</td>';
		}
		echo '</tr>';
		$i++;
	}
	echo '</table></fieldset>';
	
	function getPluginOption($name) {
		global $pbl_config;
		return $pbl_config[$name];
	}
	function getPlugid() {
		global $oPluginAdmin;
		return $oPluginAdmin->plugin->plugid;
	}
	
	function sb_train() {
		global $oPluginAdmin;
		if (requestVar('catcode') > '' && requestVar('expression') > '') {
			$docid = $oPluginAdmin->plugin->spambayes->nbs->nextdocid();
			$oPluginAdmin->plugin->spambayes->train($docid,requestVar('catcode'),requestVar('expression'));
			$oPluginAdmin->plugin->spambayes->updateProbabilities();
		}
	}
	
	function sb_trainlog() {
		global $oPluginAdmin;
		if (requestVar('catcode') > '' && requestVar('id') > 0) {
			$query = 'select content from '.$oPluginAdmin->plugin->table_log.' where id = '.intval(requestVar('id'));
			$res = sql_query($query);
			$arr = mysql_fetch_array($res);
			if ($arr['content']) {
				$docid = $oPluginAdmin->plugin->spambayes->nbs->nextdocid();
				$oPluginAdmin->plugin->spambayes->train($docid,requestVar('catcode'),$arr['content']);
				$oPluginAdmin->plugin->spambayes->updateProbabilities();
				echo '<h3>文例を'.requestVar('catcode').'として学習しました</h3>';
			} else {
				echo 'エラーが発生しました';
			}
		}
	}
	
	function sb_test() {
		global $oPluginAdmin;
		$expression = requestVar('expression');
		if ($expression > '') {
			$score = $oPluginAdmin->plugin->spambayes->categorize($expression);
			if ((float)$score['spam'] > (float)$oPluginAdmin->plugin->getOption('probability')) {
				echo '<h2>テスト結果: spamです! [score:'.$score['spam'].']</h2>';
			} else {
				echo '<h2>テスト結果: hamです! [score:'.$score['ham'].']</h2>';
			}
			echo '<fieldset style="width:90%;"><legend>入力した文例:</legend>';
			echo htmlspecialchars($expression,ENT_QUOTES);
			echo '</fieldset>';
		}
	}
	
	function sb_trainall() {
		global $oPluginAdmin;
		// now train spam bayes with all current comments as ham!!
		$res = sql_query("select * from ".sql_table('comment'));
		while ($arr = mysql_fetch_array($res)) {
			$oPluginAdmin->plugin->spambayes->train($arr['cnumber'], 'ham', $arr['cbody'].' '.$arr['chost'].' '.$arr['cip']);
		}
		$oPluginAdmin->plugin->spambayes->updateProbabilities();
	}
	
	function sb_traintb() {
		global $oPluginAdmin;
		// now train spam bayes with all current trackbacks as ham!!
		$res = sql_query("select * from ".sql_table('plugin_tb').' where block = 0');
		while ($arr = mysql_fetch_array($res)) {
			$oPluginAdmin->plugin->spambayes->train($arr['id']+100000000, 'ham', $arr['title'].' '.$arr['excerpt'].' '.$arr['blog_name'].' '.$arr['url']);
		}
		$oPluginAdmin->plugin->spambayes->updateProbabilities();
	}
	
	function sb_traintbnew() {
		global $oPluginAdmin;
		// now train spam bayes with all current trackbacks as ham!!
		$res = sql_query("select * from ".sql_table('plugin_tb').' where block = 0');
		while ($arr = mysql_fetch_array($res)) {
			$oPluginAdmin->plugin->spambayes->trainnew($arr['id']+100000000, 'ham', $arr['title'].' '.$arr['excerpt'].' '.$arr['blog_name'].' '.$arr['url']);
		}
		$oPluginAdmin->plugin->spambayes->updateProbabilities();
	}

	function sb_trainspamtb() {
		global $oPluginAdmin;
		// now train spam bayes with all blocked trackbacks as spam!!
		$res = sql_query("select * from ".sql_table('plugin_tb').' where block = 1');
		while ($arr = mysql_fetch_array($res)) {
			$oPluginAdmin->plugin->spambayes->train($arr['id']+100000000, 'spam', $arr['title'].' '.$arr['excerpt'].' '.$arr['blog_name'].' '.$arr['url']);
		}
		$oPluginAdmin->plugin->spambayes->updateProbabilities();
	}
	
	function sb_trainspamtbnew() {
		global $oPluginAdmin;
		// now train spam bayes with all blocked trackbacks as spam!!
		$res = sql_query("select * from ".sql_table('plugin_tb').' where block = 1');
		while ($arr = mysql_fetch_array($res)) {
			$oPluginAdmin->plugin->spambayes->trainnew($arr['id']+100000000, 'spam', $arr['title'].' '.$arr['excerpt'].' '.$arr['blog_name'].' '.$arr['url']);
		}
		$oPluginAdmin->plugin->spambayes->updateProbabilities();
	}
	
	function sb_trainnew() {
		global $oPluginAdmin;
		// now train spam bayes with all current comments as ham!!
		$res = sql_query("select * from ".sql_table('comment'));
		while ($arr = mysql_fetch_array($res)) {
			$oPluginAdmin->plugin->spambayes->trainnew($arr['cnumber'], 'ham', $arr['cbody'].' '.$arr['chost'].' '.$arr['cip']);
		}
		$oPluginAdmin->plugin->spambayes->updateProbabilities();
	}
	
	function sb_trainblocked() {
		global $oPluginAdmin;
		// now train spam bayes with all current comments as ham!!
		$res = sql_query("select * from ".sql_table('plug_cc_pending')." where processed = 1");
		while ($arr = mysql_fetch_array($res)) {
			$oPluginAdmin->plugin->spambayes->train($arr['id']+200000000, 'spam', $arr['cbody'].' '.$arr['chost'].' '.$arr['cip']);
		}
		$oPluginAdmin->plugin->spambayes->updateProbabilities();
	}
	
	function sb_trainblockednew() {
		global $oPluginAdmin;
		// now train spam bayes with all current comments as ham!!
		$res = sql_query("select * from ".sql_table('plug_cc_pending')." where processed = 1");
		while ($arr = mysql_fetch_array($res)) {
			$oPluginAdmin->plugin->spambayes->trainnew($arr['id']+200000000, 'spam', $arr['cbody'].' '.$arr['chost'].' '.$arr['cip']);
		}
		$oPluginAdmin->plugin->spambayes->updateProbabilities();
	}
	
	function sb_untrainall() {
		global $oPluginAdmin;
		// now untrain spam bayes with all current comments as ham!!
		$res = sql_query("select * from ".sql_table('comment'));
		while ($arr = mysql_fetch_array($res)) {
			$oPluginAdmin->plugin->spambayes->untrain($arr['cnumber']);
		}
		$oPluginAdmin->plugin->spambayes->updateProbabilities();
	}
	
	function sb_untrain() {
		global $oPluginAdmin, $manager;
		if (requestVar('ref') > 0) {
			$oPluginAdmin->plugin->spambayes->untrain(requestVar('ref'));
			$oPluginAdmin->plugin->spambayes->updateProbabilities();
			echo '<h3>文例を削除しました</h3>';
		}
		// build document table ...
		$startpos = requestVar('startpos') ? requestVar('startpos') : 0;
		$filterform = '<td></td>';
		$total = $oPluginAdmin->plugin->spambayes->nbs->countreftable();
	
		$pager = buildpager($startpos, $total, $filter, $filtertype, $filterform,'untrain', $keyword, 10);
		$res = $oPluginAdmin->plugin->spambayes->nbs->getreftable($startpos);
	
		echo '<h2>Bayesianフィルタ: 学習済み文例 ['.$total.'] </h2>';
		echo '<table>';
		echo $pager;
		echo '<tr><th>種別</th><th>文例</th><th>&nbsp;</th></tr>';
	
		while ($arr = mysql_fetch_array($res)) {
			echo '<tr><td>'.$arr['catcode'].'</td><td>'.htmlspecialchars($arr['content'],ENT_QUOTES).'</td><td><a href="'.htmlspecialchars($manager->addTicketToUrl(serverVar('PHP_SELF').'?page=untrain&ref='.$arr['ref']),ENT_QUOTES).'">文例を削除</a></td></tr>';
		}
		echo $pager;
		echo '</table>';
	}
	
	function sb_explain(){
		global $oPluginAdmin;
		$id = requestVar('id');
		echo '<h2>評価の詳細: 調整前のスコアデータ (hamのスコアの昇順)</h2>';
		$arr = $oPluginAdmin->plugin->spambayes->nbs->getLogevent($id);
	
		$oPluginAdmin->plugin->spambayes->explain($arr['content']);
	}

	function sb_promote(){
		global $oPluginAdmin;
		$id = requestVar('id');
		echo '<h2>コメントの復活: '.$id.'</h2>';
		$arr = $oPluginAdmin->plugin->spambayes->nbs->getLogevent($id);
		$itemid = explode('itemid:', $arr['log']);
		$itemid = $itemid[1];
		echo 'itemid: '.$itemid.'<br />';
		$blogid = getBlogIDFromItemID($itemid);
		$comment = explode('^^',$arr['content']);
	
		$body		= addslashes($comment[0]);
		$host		= addslashes($comment[1]);
		$name		= addslashes($comment[2]);
		$url		= addslashes($comment[3]);
		$ip			= addslashes($comment[4]);
		$memberid	= 0;
		$timestamp	= $arr['logtime'];
	
	
		$query = 'INSERT INTO '.sql_table('comment').' (CUSER, CMAIL, CMEMBER, CBODY, CITEM, CTIME, CHOST, CIP, CBLOG) '
				   . "VALUES ('$name', '$url', $memberid, '$body', $itemid, '$timestamp', '$host', '$ip', '$blogid')";
		sql_query($query);
		echo '<b>コメントを復活させました</b><br />';
		//echo '-- end promote --';
	}
	
	function sb_batch() {
		global $oPluginAdmin;
		$logids = requestIntArray('batch');
		$action = requestVar('batchaction');
		//debug: var_dump($logids);
		if ($logids){
			foreach ($logids as $id) {
				switch ($action) {
					case 'tspam':
					case 'tham':
						$ar = $oPluginAdmin->plugin->spambayes->nbs->getLogevent($id);
						$docid = $oPluginAdmin->plugin->spambayes->nbs->nextdocid();
						$cat = substr($action,1);
						$oPluginAdmin->plugin->spambayes->train($docid,$cat,$ar['content']);
						echo '学習しました('.$cat.'): '.$id.'<br />';
						break;
					case 'delete':
						echo '削除しました: '.$id.'<br />';
						$oPluginAdmin->plugin->spambayes->nbs->removeLogevent($id);
				}
			}
			$oPluginAdmin->plugin->spambayes->updateProbabilities();
		}
		//echo '--end of batch--';
	}
		
	function sb_nucmenu($trainall, $logging) {
		global $oPluginAdmin, $manager, $CONF;
?>
	<!-- sorry, it's stronger then me :-) this javascript less popup's are styled using: http://meyerweb.com/eric/css/edge/popups/demo.html -->
	<style type="text/css">
		.adminmenu span {
			display:none;
		}
		.adminmenu a:hover span {
			display:block;
			position: absolute;
			text-decoration: none;
			top: 100px;
			left: 350px;
			width: 225px;
			background-color:#ffff7d;
			padding: 10px;
			font-weight: normal;
			font-size: 14px;
			border: 1px solid black;
			z-index: 100;
		}
		.adminmenu a:hover {
			background-color: #ffff7d;
		}
	</style>
<?php
		$total = $oPluginAdmin->plugin->spambayes->nbs->countlogtable('all');
	   	echo "<h2>SpamBayes 管理</h2>\n";
		echo "<ul class=\"adminmenu\">\n";
		echo sb_menu(
			$CONF['PluginURL'].'spambayes/index.php?page=train',
			'学習データを入力',
			"Bayesianフィルタのための学習データを手動で入力します。学習データはham(spamでないもの)とspamの２種類があります。プラグインの動作精度を向上させるためには両方のデータが満遍なく必要です。"
		);
		echo sb_menu(
			$CONF['PluginURL'].'spambayes/index.php?page=untrain',
			'学習データの削除',
			"これまでに学習したデータをBayesianフィルタから削除します"
		);

		if ($logging == 'yes') {
			echo sb_menu(
				$CONF['PluginURL'].'spambayes/index.php?page=log',
				"Bayesianフィルタの動作履歴 ($total)",
				"フィルタの動作履歴を表示します。Bayesianフィルタがコメントやトラックバックをどのように判定したのかが判ります。判定に誤りがある場合にはBayesianフィルタにそれを教えることができます。(運用初期における動作の微調整に有効です)"
			);
		}
		
		if ($trainall == 'yes') {
			echo sb_menu(
				$CONF['PluginURL'].'spambayes/index.php?page=trainall',
				'全てのコメントをhamとして学習',
				"このコマンドを実行すると、これまでに寄せられている全てのコメントをham(spamでないもの)として学習します。あらかじめspamコメントは削除しておきましょう。コメントの量によっては時間がかかる可能性があります。（このメニューはオプションによって非表示にすることができます。）"
			);
			echo sb_menu(
				$CONF['PluginURL'].'spambayes/index.php?page=untrainall',
				"hamとして学習したデータを全て削除",
				"上記の「全てのコメントをhamとして学習する」で学習したデータを全て削除します。上記による学習の効果が芳しくなかった場合にのみ、実行してください。コメントの量によっては時間がかかる可能性があります。（このメニューはオプションによって非表示にすることができます。）"
			);
			echo sb_menu(
				$CONF['PluginURL'].'spambayes/index.php?page=traintb',
				"全ての公開済みトラックバックをhamとして学習",
				'このコマンドを実行すると、全ての公開済みトラックバックをhamとして学習します。（このメニューはオプションによって非表示にすることができます。）'
				);
			echo sb_menu(
				$CONF['PluginURL'].'spambayes/index.php?page=trainspamtb',
				"全ての保留トラックバックをspamとして学習",
				'このコマンドを実行すると、全ての保留済みトラックバックをspamとして学習します。（このメニューはオプションによって非表示にすることができます。）'
			);
		}
		echo sb_menu(
			$CONF['PluginURL'].'spambayes/index.php?page=trainnew',
			"コメントをhamとして学習",
			"前回の実行後に新たに登録されたコメントをhamとして学習します。該当するコメントがない場合は何もしません。"
		);
		echo sb_menu(
			$CONF['PluginURL'].'spambayes/index.php?page=traintbnew',
			"公開済みトラックバックをhamとして学習",
			"前回の実行後に新たに公開されたトラックバックをhamとして学習します。該当するトラックバックがない場合何もしません。"
		);
		echo sb_menu(
			$CONF['PluginURL'].'spambayes/index.php?page=trainspamtbnew',
			"保留トラックバックをspamとして学習",
			"前回の実行後に新たに保留されたトラックバックをspamとして学習します。該当するトラックバックがない場合何もしません。"
		);
		echo sb_menu(
			$CONF['PluginURL'].'spambayes/index.php?page=update',
			"統計情報のアップデート",
			"Bayesianフィルタの統計情報をアップデートします。統計情報は自動的にアップデートされるため、通常は実行する必要はありません。"
		);
		echo sb_menu(
			$CONF['PluginURL'].'spambayes/index.php?page=test',
			"動作テスト",
			"実際に文例を入力して、Bayesianフィルタの動作をテストすることができます。"
		);
		echo sb_menu(
			$CONF['AdminURL']."index.php?action=pluginoptions&plugid=".getPlugid(),
			"SpamBayes プラグインオプション",
			"SpamBayes プラグインオプションを変更することができます。"
		);
		echo sb_menu(
			$CONF['PluginURL'].'spambayes/index.php?page=report',
			"動作確認報告",
			"作者に動作確認レポートを送信します。"
		);
			echo "</ul>\n";
	}
	
	function sb_menu($path, $menu, $popup=''){
		global $manager;
		return '<li><a href="'.htmlspecialchars($manager->addTicketToUrl($path),ENT_QUOTES).'">'.$menu.'<span>'.$popup.'</span></a></li>'."\n";
	}
	
	function sb_log() {
		global $oPluginAdmin, $manager;
		$ticket = $manager->_generateTicket();
		
		$startpos   = requestVar('startpos')   ? requestVar('startpos')   : 0;
		$filter     = requestVar('filter')     ? requestVar('filter')     : 'all';
		$filtertype = requestVar('filtertype') ? requestVar('filtertype') : 'all';
		$ipp        = requestVar('ipp')        ? requestVar('ipp')        : 10;
		$keyword    = requestVar('keyword');
		$filterform = buildfilterform($filter,$filtertype,$keyword,$ipp);
	
		$total      = $oPluginAdmin->plugin->spambayes->nbs->countlogtable($filter, $filtertype, $keyword);
		if ($filter == 'all') {
			$htotal = $oPluginAdmin->plugin->spambayes->nbs->countlogtable('ham',$filtertype, $keyword);
			$stotal = $oPluginAdmin->plugin->spambayes->nbs->countlogtable('spam',$filtertype, $keyword);
			echo '<h2>Bayesianフィルタ: 動作履歴 [計:'.$total.'件 (ham: '.$htotal.'件, spam: '.$stotal.'件) ]</h2>';
		} else {
			echo '<h2>Bayesianフィルタ: 動作履歴 [計:'.$total.'件]</h2>';
		}
	
		$res = $oPluginAdmin->plugin->spambayes->nbs->getlogtable($startpos,$filter, $filtertype, $keyword, $ipp);
		$pager = buildpager($startpos, $total, $filter, $filtertype, $filterform,'log', $keyword, $ipp);
		if ($total % $ipp == 0) {
			$ap = intval(floor($total / $ipp));
		} else {
			$ap = intval(floor($total / $ipp)) + 1;
		}
		$cp = intval($startpos + $ipp) / $ipp;
		echo '<table>';
		echo '<tr><th colspan="2">'.$cp.'/'.$ap.'ページ</th><td colspan="2">表示: <form style="display:inline"><input type="hidden" name="ticket" value="'.$ticket.'" /><input type="text" size="3" name="ipp" value="'.$ipp.'" /> 行を１ページに表示する <input type="submit" value="変更" /><input type="hidden" name="amount" value="cp" /><input type="hidden" name="filter" value="'.$filter.'" /><input type="hidden" name="filtertype" value="'.$filtertype.'" /><input type="hidden" name="keyword" value="'.$keyword.'" /><input type="hidden" name="page" value="log" /></form>';
		echo '<span style="text-align:right" class="batchoperations">';
		if ($filter <> 'all') {
			$filter_text .= ' 種別: <b>'.$filter.'</b>';
		}
		if ($filtertype <> 'all') {
			$filter_text .= ' イベント: <b>'.$filtertype.'</b>';
		}
		if ($keyword > '') {
			$filter_text .= ' キーワード: <b>'.$keyword.'</b>';
		}
		
		if( $filter_text ){
			echo "絞込み条件: ".$filter_text;
		}
		
		echo '</span></td></tr>';
		echo $pager;
		$extraaction = '&filter='.$filter.'&filtertype='.urlencode($filtertype).'&startpos='.$startpos.'&keyword='.$keyword.'&ipp='.$ipp.'&ticket='.$ticket;
		echo '<tr><th>日付</th><th>イベント</th><th>文例</th><th>&nbsp;</th></tr><form method="post"><input type="hidden" name="ticket" value="'.$ticket.'" />';
		$i = 0;
		while ($arr = mysql_fetch_array($res)) {
			echo '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);"><td>'.$arr['logtime'].'<br /><b>'.$arr['catcode'].'</b></td><td>'.$arr['log'].'</td><td><input id="batch'.$i.'" name="batch['.$i.']" value="'.$arr['id'].'" type="checkbox"><label for="batch'.$i.'">'.htmlspecialchars(str_replace('^^', ' ',$arr['content']),ENT_QUOTES).'</label></td>';
			echo '<td><a href="'.htmlspecialchars(serverVar('PHP_SELF').'?page=trainlog&catcode=ham&id='.$arr['id'].$extraaction,ENT_QUOTES).'"><nobr>hamとして学習</nobr></a>';
			echo ' <a href="'.htmlspecialchars(serverVar('PHP_SELF').'?page=trainlog&catcode=spam&id='.$arr['id'].$extraaction,ENT_QUOTES).'"><nobr>spamとして学習</nobr></a>';
			echo '<br /><a href="'.htmlspecialchars(serverVar('PHP_SELF').'?page=explain&id='.$arr['id'].$extraaction,ENT_QUOTES).'"><nobr>評価の詳細</nobr></a>';
			if (strstr($arr['log'], 'itemid:')) {
				echo '<br /><br /><a style="color:red" href="'.htmlspecialchars(serverVar('PHP_SELF').'?page=promote&id='.$arr['id'].$extraaction,ENT_QUOTES).'"><nobr>復活</nobr></a>';
			}
			echo '</td>';
			echo '</tr>';
			$i++;
		}
		if (mysql_num_rows($res) == 0) {
			echo '<tr><td colspan="4"><b>ログは空です。</b></td></tr>';
		}
		echo '<tr><td colspan="4"><div class="batchoperations">選択したものを次の通り処理する:<select name="batchaction">';
		echo '<option value="tspam">spamとして学習</option>';
		echo '<option value="tham">hamとして学習</option>';
		echo '<option value="delete">削除</option></select><input name="page" value="batch" type="hidden">';
		echo '<input type="hidden" name="ipp" value="'.$ipp.'"/><input type="hidden" name="filter" value="'.$filter.'" /><input type="hidden" name="filtertype" value="'.$filtertype.'" /><input type="hidden" name="keyword" value="'.$keyword.'" />';
		echo '<input value="実行" type="submit">(
				 <a href="" onclick="if (event && event.preventDefault) event.preventDefault(); return batchSelectAll(1); ">全て選択</a> -
				 <a href="" onclick="if (event && event.preventDefault) event.preventDefault(); return batchSelectAll(0); ">選択解除</a>
				)
			</div></td></tr></form>';
		echo '<tr><td colspan="4"><div class="batchoperations"><form action="" method="get" style="display:inline"><input type="hidden" name="ticket" value="'.$ticket.'" /><input type="hidden" name="ipp" value="'.$ipp.'"/><input type="hidden" name="page" value="clearlog" /><input type="hidden" name="amount" value="cp" /><input type="hidden" name="filter" value="'.$filter.'" /><input type="hidden" name="filtertype" value="'.$filtertype.'" /><input type="hidden" name="keyword" value="'.$keyword.'" /><input type="submit" value="最初の'.$ipp.'件を削除" /></form> <form action="" method="get" style="display:inline"><input type="hidden" name="ticket" value="'.$ticket.'" /><input type="hidden" name="ipp" value="'.$ipp.'"/><input type="hidden" name="page" value="clearlog" /><input type="hidden" name="amount" value="cf" /><input type="hidden" name="filter" value="'.$filter.'" /><input type="hidden" name="filtertype" value="'.$filtertype.'" /><input type="hidden" name="keyword" value="'.$keyword.'" /><input type="submit" value="現在の絞込み条件に該当するログを削除" /></form> <form action="" method="get" style="display:inline"><input type="hidden" name="ticket" value="'.$ticket.'" /><input type="hidden" name="page" value="clearlog" /><input type="submit" value="全てのログを削除" /></form></div></td></tr>';
		echo '<tr><th colspan="2">'.$cp.'/'.$ap.'ページ</th><td colspan="2">表示: <form style="display:inline"><input type="hidden" name="ticket" value="'.$ticket.'" /><input type="text" size="3" name="ipp" value="'.$ipp.'" />行を１ページに表示する <input type="submit" value="変更" /><input type="hidden" name="amount" value="cp" /><input type="hidden" name="filter" value="'.$filter.'" /><input type="hidden" name="filtertype" value="'.$filtertype.'" /><input type="hidden" name="keyword" value="'.$keyword.'" /><input type="hidden" name="page" value="log" /></form></td></tr>';
		echo $pager;
		echo '</table>';
	}
	
	function sb_trainform() {
		global $manager;
		echo "<form action=\"".serverVar('PHP_SELF')."\" method=\"get\">\n";
		echo $manager->addTicketHidden();
		echo "<input type=\"hidden\" name=\"page\" value=\"train\" />\n";
		echo "<select name=\"catcode\"><option value=\"ham\">ham(spamでないもの)として学習</option><option value=\"spam\" selected=\"1\">spamとして学習</option></select><br />";
		echo "<textarea class=\"sb_textinput\" cols=\"60\" rows=\"6\" name=\"expression\" ></textarea><br />";
		echo "<input type=\"submit\" value=\"学習する\" />\n";
		echo "</form>\n";
	}
	
	function sb_testform() {
		global $manager;
		echo "<h2>文例を入力</h2>";
		echo "<form action=\"".serverVar('PHP_SELF')."\" method=\"get\">\n";
		echo $manager->addTicketHidden();
		echo "<input type=\"hidden\" name=\"page\" value=\"test\" />\n";
		echo "<textarea class=\"sb_textinput\" cols=\"60\" rows=\"6\" name=\"expression\" ></textarea><br />";
		echo "<input type=\"submit\" value=\"この文例をテストする\" />\n";
		echo "</form>\n";
	}
	
	function buildpager($startpos, $total, $filter, $filtertype, $filterform, $action, $keyword, $ipp) {
		global $manager;
		$ticket = $manager->_generateTicket();
		
		$pager = '<tr>';
		if ($startpos >= $ipp) {
			$pager .= '<td><form action="" method="get" style="display:inline"><input type="hidden" name="page" value="'.$action.'" />';
			$pager .= '<input type="hidden" value="'.($startpos - $ipp).'" name="startpos" /><input type="hidden" name="ticket" value="'.$ticket.'" /><input type="submit" value="前ページ" /><input type="hidden" name="filter" value="'.$filter.'" /><input type="hidden" name="filtertype" value="'.$filtertype.'" /><input type="hidden" name="keyword" value="'.$keyword.'" /><input type="hidden" name="ipp" value="'.$ipp.'"/></form></td>'.$filterform;
		} else {
			$pager .= '<td></td>'.$filterform;
		}
		if (($total - $ipp) > $startpos) {
			$pager .= '<td><form action="" method="get" style="display:inline"><input type="hidden" name="page" value="'.$action.'" />';
			$pager .= '<input type="hidden" value="'.($startpos + $ipp).'" name="startpos" /><input type="hidden" name="ticket" value="'.$ticket.'" /><input type="submit" value="次ページ" /><input type="hidden" name="filter" value="'.$filter.'" /><input type="hidden" name="filtertype" value="'.$filtertype.'" /><input type="hidden" name="keyword" value="'.$keyword.'" /><input type="hidden" name="ipp" value="'.$ipp.'"/></form></td>';
		} else {
			$pager .= '<td></td>';
		}
		$pager .= '</tr>';
		return $pager;
	}
	
	function buildfilterform($filter,$filtertype, $keyword, $ipp) {
		global $oPluginAdmin, $manager;
		$ticket = $manager->_generateTicket();

		$selected   = $filter == 'all' ? 'selected':'';
		$filterform = '<td colspan="2"><form style="display:inline">絞込み: <select name="filter"><option value="all" '.$selected.'>全て</option>';
		$selected   = $filter == 'ham' ? 'selected':'';
		$filterform .= '<option value="ham" '.$selected.'>hamのみ</option>';
		$selected   = $filter == 'spam' ? 'selected':'';
		$filterform .= '<option value="spam" '.$selected.'>spamのみ</option></select> <input type="hidden" name="page" value="log"/><input type="hidden" name="ipp" value="'.$ipp.'"/>';
	
		$logtypes   = $oPluginAdmin->plugin->spambayes->nbs->getlogtypes();
		$selected   = $filtertype == 'all' ? 'selected':'';
		$filterform .= '<select name="filtertype"><option value="all" '.$selected.'>全てのイベント</option>';
		foreach($logtypes as $logtype) {
			$selected = $filtertype == $logtype ? 'selected' : '';
			$show = explode(' ',$logtype);
			$show = $show[0] == 'event' ? $show[1] : $show[0];
			$filterform .= '<option value="'.$logtype.'" '.$selected.'>'.$show.'</option>';
		}
		$filterform .= '</select><input type="hidden" name="ticket" value="'.$ticket.'" /><input type="submit" value="適用" /></form>';
		$filterform .= '&nbsp;|&nbsp;<form style="display:inline"><input type="hidden" name="ticket" value="'.$ticket.'" /><input type="hidden" name="page" value="log"/><input type="hidden" name="filter" value="'.$filter.'"/><input type="hidden" name="filtertype" value="'.$filtertype.'"/><input type="hidden" name="ipp" value="'.$ipp.'"/><input type="text" name="keyword" value="'.$keyword.'" /><input type="submit" value="検索" /></form>';
		$filterform .= '</td>';
		return $filterform;
	}
	$oPluginAdmin->end();
