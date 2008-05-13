<%subSilver(form,javascript)%>

<form method="get" action="">
<input type="hidden" name="special" value="search" />

<table class="forumline" border="0" cellpadding="4" cellspacing="1" width="100%">
  <tbody><tr>
    <th class="thHead" colspan="4" height="25">検索クエリ</th>

  </tr>
  <tr>
    <td class="row1" colspan="2" width="50%"><span class="gen">キーワードを検索:</span><br /><span class="gensmall">検索では <u>AND</u>, <u>OR</u>, <u>NOT</u> を使用することができます。</span></td>
    <td class="row2" colspan="2" valign="top"><span class="genmed"><input style="width: 300px;" class="post" name="query" size="30" type="text" /></span></td>

  </tr>
  <tr>
    <td class="row1" colspan="2"><span class="gen">投稿者を検索:</span></td>
    <td class="row2" colspan="2" valign="middle"><span class="genmed">
      <input style="width: 300px;" class="post" name="search_author" size="30" type="text" /><br />
      <input name="search_author_method" value="exactly" type="radio" /> 完全一致
      <input name="search_author_method" value="match" type="radio" checked="checked" /> 部分一致
    </span></td>
  </tr>
  <tr>
    <th class="thHead" colspan="4" height="25">検索オプション</th>

  </tr>
  <tr>
    <td class="row1" align="right"><span class="gen">フォーラム:&nbsp;</span></td>
    <td class="row2"><span class="genmed"><select class="post" name="catid" id="subsilver_form_catid" onchange="
if (this.value>0) document.getElementById('subsilver_form_blogid').value = subSilver_blogidFromCatid(this.value);
"><option value="0">全て</option><%subSilver(form,categorylist)%></select></span></td>
    <td class="row1" align="right" nowrap="nowrap"><span class="gen">特定期間内を検索:&nbsp;</span></td>
    <td class="row2" valign="middle"><span class="genmed">
      <select class="post" name="search_time">
      <option value="0" selected="selected">全ての記事</option>
      <option value="1">1日以内</option>
      <option value="7">1週間以内</option>
      <option value="14">2週間以内</option>
      <option value="30">1ヶ月以内</option>
      <option value="90">3ヶ月以内</option>
      <option value="180">6ヶ月以内</option>
      <option value="364">1年以内</option>
      </select><br />
      <input name="search_fields" value="all" checked="checked" type="radio" /> トピックの題名と本文を検索<br />
      <input name="search_fields" value="msgonly" type="radio" /> 本文のみを検索<br />
      <input name="search_fields" value="titleonly" type="radio" onclick="
if (document.getElementById('subsilver_form_sort').value=='replies') document.getElementById('subsilver_form_sort').value='time';
" /> トピックの題名のみを検索
    </span></td>
  </tr>
  <tr>
    <td class="row1" align="right"><span class="gen">カテゴリ:&nbsp;</span></td>
    <td class="row2"><span class="genmed"><select class="post" name="blogid" id="subsilver_form_blogid" onchange="
if (this.value!=subSilver_blogidFromCatid( document.getElementById('subsilver_form_catid').value )) 
document.getElementById('subsilver_form_catid').value = 0;
"><option value="0">全て</option><%subSilver(form,bloglist)%>
    </select></span></td>
    <td class="row1" align="right"><span class="gen">ソート方法:&nbsp;</span></td>
    <td class="row2" nowrap="nowrap" valign="middle"><span class="genmed">
      <select class="post" name="sort_by" id="subsilver_form_sort">
      <option value="replies">検索語を含む記事の数</option>
      <option value="time">投稿時間</option>
      <option value="title">トピックの題名</option>
      <option value="forum">フォーラム</option></select><br />
      <input name="sort_dir" value="ASC" type="radio" /> 昇順<br />
      <input name="sort_dir" value="DESC" checked="checked" type="radio" /> 降順</span>&nbsp;
    </td>
  </tr>
  <tr>
    <td class="catBottom" colspan="4" align="center" height="28"><input class="liteoption" value="検索" type="submit" /></td>
  </tr>
</tbody></table>

</form>