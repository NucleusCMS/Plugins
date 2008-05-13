<table align="center" border="0" cellpadding="2" cellspacing="2" width="100%">
<tbody><tr>
  <td class="nav" align="left" valign="middle" width="100%"><span class="nav">&nbsp;&nbsp;&nbsp;<a href="<%sitevar(url)%>" class="nav"><%sitevar(name)%> フォーラム一覧</a></span></td>
  <td class="nav" align="right" nowrap="nowrap" valign="bottom">
    <%ifnot(hasplugin,PageSwitch)%>
    <%elseif(PageSwitch,limit,10)%>ページ移動
    <%PageSwitch(prev,前へ)%>
    <%PageSwitch(index,2)%>
    <%PageSwitch(next,次へ)%>
    <%endif%>
  </td>
</tr></tbody></table>

<table class="forumline" border="0" cellpadding="3" cellspacing="1" width="100%">
<tbody><tr>
  <th class="thCornerL" height="25" nowrap="nowrap">
    #&nbsp;<%subSilver(sortbutton,ソート,images/s_asc.png,id_asc,images/s_desc.png,id_desc)%>
  </th>
  <th class="thTop" nowrap="nowrap">&nbsp;</th>
  <th class="thTop" nowrap="nowrap">
    名前&nbsp;<%subSilver(sortbutton,ソート,images/s_asc.png,name_asc,images/s_desc.png,name_desc)%>
  </th>
  <th class="thTop" nowrap="nowrap">
    登録日&nbsp;<%subSilver(sortbutton,ソート,images/s_asc.png,id_asc,images/s_desc.png,id_desc)%>
  </th>
  <th class="thTop" nowrap="nowrap"><span style="white-space: nowrap;">投稿記事</span></th>
  <th class="thCornerR" nowrap="nowrap"><span style="white-space: nowrap;">ホームページ</span></th>
</tr>

<%if(hasplugin,PageSwitch)%>
<%subSilver(memberlist,subSilver/index,10)%>
<%else%>
<%subSilver(memberlist,subSilver/index,10000)%>
<%endif%>

<tr>
  <td class="catBottom" colspan="6" align="center" height="28" valign="middle">
    <div class="genmed"><form method="get" action="<%self%>">
      <input type="hidden" name="special" value="member" />
      名前で検索: <input type="text" name="search_author" value="" />
      <input type="submit" value="検索" />
    </form></div>
  </td>
</tr>
</tbody></table>
