<table align="center" border="0" cellpadding="2" cellspacing="2" width="100%">
  <tbody><tr>
    <td align="left" valign="bottom"><span class="maintitle">
      <%ifnot(subSilver,requestVar,query=)%>"<%query%>"での検索結果
      <%elseif(subSilver,requestVar,search_author_method=exactly)%><%subSilver(requestVar,search_author)%>の記事を含むトピック一覧
      <%else%>著者が"<%subSilver(requestVar,search_author)%>"の記事を含むトピック一覧
      <%endif%>
      <%if(hasplugin,PageSwitch)%>: <%PageSwitch(found)%>件<%endif%>
    </span><br /></td>
  </tr>
</tbody></table>

<table width="100%" cellspacing="0" cellpadding="2" border="0" align="center">
  <tr>
  <td align="left" valign="bottom"><span class="nav"><a href="<%sitevar(url)%>" class="nav"><%sitevar(name)%> フォーラム一覧</a></span>
  </td>
  <td class="nav" align="right" nowrap="nowrap" valign="bottom">
    <%ifnot(hasplugin,PageSwitch)%>
    <%elseif(PageSwitch,limit,10)%>ページ移動
    <%PageSwitch(prev,前へ)%>
    <%PageSwitch(index,2)%>
    <%PageSwitch(next,次へ)%>
    <%endif%>
  </td>
  </tr>
</table>

<table class="forumline" border="0" cellpadding="4" cellspacing="1" width="100%">
<tbody><tr>
  <th class="thCornerL" height="25" nowrap="nowrap" width="4%">&nbsp;</th>
  <th class="thTop" nowrap="nowrap">&nbsp;フォーラム&nbsp;</th>
  <th class="thTop" nowrap="nowrap">&nbsp;<span style="white-space: nowrap;">トピック&nbsp;</span></th>
  <th class="thTop" align="center" nowrap="nowrap" width="50">&nbsp;<span style="white-space: nowrap;">返信&nbsp;</span></th>
  <th class="thTop" align="center" nowrap="nowrap" width="100">&nbsp;<span style="white-space: nowrap;">投稿者&nbsp;</span></th>
  <th class="thTop" align="center" nowrap="nowrap" width="50">&nbsp;<span style="white-space: nowrap;">観閲&nbsp;</span></th>
  <th class="thCornerR" align="center" nowrap="nowrap">&nbsp;<span style="white-space: nowrap;">最新記事&nbsp;</span></th>
</tr>

<%searchresults(subsilver/index,10)%>

</tbody></table>