<table width="100%" cellspacing="0" cellpadding="2" border="0" align="center">
  <tr>
  <td align="left" valign="bottom"><span class="gensmall">
    現在の時刻 - <%subSilver(showClock,subSilver/index)%><br /></span><span class="nav"><a href="<%sitevar(url)%>" class="nav"><%sitevar(name)%> フォーラム一覧</a></span>
  </td>
  <td align="right" valign="bottom" class="gensmall">
    <a href="<%self%>?search_type=unanswered<%ifnot(subSilver,intGetVar,blogid=0)%>&amp;blogid=<%blogsetting(id)%><%endif%>&amp;query=未返信の記事" class="gensmall">未返信の記事を表示</a>
    <%ifnot(loggedin)%>
    <%elseif(subSilver,intGetVar,blogid=0)%>
      &nbsp;<span class="gensmall"><a href="<%self%>?subSilver_action=mark" class="gensmall">サイト全てのトピックをマーク</a></span>
    <%else%>
      &nbsp;<span class="gensmall"><a href="<%self%>?blogid=<%blogsetting(id)%>&amp;subSilver_action=mark" class="gensmall">カテゴリ全てのトピックをマーク</a></span>
    <%endif%>
  </td>
  </tr>
</table>

<table width="100%" cellpadding="2" cellspacing="1" border="0" class="forumline">
  <tr>
  <th colspan="2" class="thCornerL" height="25" nowrap="nowrap">&nbsp;フォーラム&nbsp;</th>
  <th width="60" class="thTop" nowrap="nowrap">&nbsp;<span style="white-space: nowrap;">トピック</span>&nbsp;</th>
  <th width="60" class="thTop" nowrap="nowrap">&nbsp;<span style="white-space: nowrap;">投稿記事</span>&nbsp;</th>
  <th class="thCornerR" nowrap="nowrap">&nbsp;<span style="white-space: nowrap;">最新記事</span>&nbsp;</th>
  </tr>
  <%subSilver(showBlogList,subSilver/index)%>
</table>

