<table align="center" border="0" cellpadding="2" cellspacing="2" width="100%">
<tbody><tr>
  <td colspan="2" align="left" valign="bottom"><a class="maintitle" href="?catid=<%category(id)%>"><%category(name)%></a><br /><span class="gensmall"><b>モデレーター: 無し</b></span></td>
  <td align="right" nowrap="nowrap" valign="bottom"><span class="gensmall"><b></b></span></td>
</tr>
<tr>
  <td align="left" valign="middle" width="50">
    <form method="post" action="#nucleus_cf" style="position:absolute;">
    <input type="hidden" name="subSilver_action" value="posting" />
    <input type="image" src="<%skinfile(images/lang_english/post.gif)%>" alt="トピックの新規投稿" title="トピックの新規投稿" />
    </form><img src="<%skinfile(images/lang_english/post.gif)%>" alt="トピックの新規投稿" title="トピックの新規投稿" />
  </td>
  <td class="nav" align="left" valign="middle" width="100%"><span class="nav">&nbsp;&nbsp;&nbsp;<a href="<%sitevar(url)%>" class="nav"><%sitevar(name)%> フォーラム一覧</a> -&gt; <a class="nav" href="?catid=<%category(id)%>&amp;blogid=<%blogsetting(id)%>"><%category(name)%></a></span></td>
  <td class="nav" align="right" nowrap="nowrap" valign="bottom">
    <%ifnot(hasplugin,PageSwitch)%>
    <%elseif(PageSwitch,limit,10)%>ページ移動
    <%PageSwitch(prev,前へ)%>
    <%PageSwitch(index,2)%>
    <%PageSwitch(next,次へ)%>
    <%endif%>
    <%if(loggedin)%>&nbsp;<span class="gensmall"><a href="<%self%>?blogid=<%blogsetting(id)%>&amp;catid=<%category(id)%>&amp;subSilver_action=mark" class="nav">全てのトピックをマーク</a></span><%endif%>
  </td>
</tr>
</tbody></table>

<table class="forumline" border="0" cellpadding="4" cellspacing="1" width="100%">
<tbody><tr>
  <th colspan="2" class="thCornerL" align="center" height="25" nowrap="nowrap">&nbsp;<span style="white-space: nowrap;">トピック&nbsp;</span></th>
  <th class="thTop" align="center" nowrap="nowrap" width="50">&nbsp;<span style="white-space: nowrap;">返信&nbsp;</span></th>
  <th class="thTop" align="center" nowrap="nowrap" width="100">&nbsp;<span style="white-space: nowrap;">投稿者&nbsp;</span></th>
  <th class="thTop" align="center" nowrap="nowrap" width="50">&nbsp;<span style="white-space: nowrap;">観閲&nbsp;</span></th>
  <th class="thCornerR" align="center" nowrap="nowrap">&nbsp;<span style="white-space: nowrap;">最新記事&nbsp;</span></th>
</tr>

<%if(subSilver,getVar,startpos=)%>
<%subSilver(showStickies,subsilver/index)%>
<%elseif(subSilver,getVar,startpos=0)%>
<%subSilver(showStickies,subsilver/index)%>
<%endif%>

<%if(hasplugin,PageSwitch)%>
<%blog(subsilver/index,10)%>
<%else%>
<%blog(subsilver/index,10000)%>
<%endif%>

<tr>
  <td class="catBottom" colspan="6" align="center" height="28" valign="middle">
    <div class="genmed">
    <form method="get" action="<%self%>">
    <input type="hidden" name="special" value="search" />
    <input type="hidden" name="search_type" value="unanswered" />
    <input type="hidden" name="catid" value="<%category(id)%>" />
    <input type="hidden" name="blogid" value="<%blogsetting(id)%>" />
    特定期間内のトピックを表示:&nbsp;
    <select name="search_time">
    <option value="0" selected="selected">全てのトピック</option>
    <option value="1">1日以内</option>
    <option value="7">1週間以内</option>
    <option value="14">2週間以内</option>
    <option value="30">1ヶ月以内</option>
    <option value="90">3ヶ月以内</option>
    <option value="180">6ヶ月以内</option>
    <option value="364">1年以内</option>
    </select>&nbsp;
    <input type="hidden" name="query" value="特定期間内のトピックを表示" />
    <input type="submit" class="liteoption" value="移動" />
  </form></div></td>
</tr>
</tbody></table>

<table align="center" border="0" cellpadding="2" cellspacing="2" width="100%">
<tbody><tr>
  <td align="left" valign="middle" width="50">
    <form method="post" action="#nucleus_cf" style="position:absolute;">
    <input type="hidden" name="subSilver_action" value="posting" />
    <input type="image" src="<%skinfile(images/lang_english/post.gif)%>" alt="トピックの新規投稿" title="トピックの新規投稿" />
    </form><img src="<%skinfile(images/lang_english/post.gif)%>" alt="トピックの新規投稿" title="トピックの新規投稿" />
  </td>
  <td align="left" valign="middle" width="100%"><span class="nav">&nbsp;&nbsp;&nbsp;<a href="<%sitevar(url)%>" class="nav"><%sitevar(name)%> フォーラム一覧</a> -&gt; <a class="nav" href="?catid=<%category(id)%>&amp;blogid=<%blogsetting(id)%>"><%category(name)%></a></span></td>
  <td align="right" nowrap="nowrap" valign="middle"><br /><span class="nav"></span>
  </td>
</tr>
<%ifnot(hasplugin,PageSwitch)%>
<%elseif(PageSwitch,requied)%>
<tr>
  <td colspan="3" align="left"><span class="nav">Page <b><%PageSwitch(num)%></b> of <b><%PageSwitch(total)%></b></span></td>
</tr>
<%endif%>
</tbody></table>
