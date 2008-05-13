<%ifnot(category)%>

<table width="100%" cellpadding="3" cellspacing="1" border="0" class="forumline">
  <tr>
  <td class="catHead" colspan="2" height="28"><span class="cattitle"><a href="viewonline.php?sid=10078eb7dbbadbd85d2f386ceb800a0a" class="cattitle">オンライン管理</a></span></td>
  </tr>
  <tr>
  <td class="row1" align="center" valign="middle" rowspan="2"><img src="<%skinfile(images/whosonline.gif)%>" alt="オンライン管理" /></td>
  <td class="row1" align="left" width="100%"><span class="gensmall">ユーザーが投稿した記事の総数: <b><%subSilver(userdata,totalreplies)%></b><br />登録ユーザーの数: <b><%subSilver(userdata,number)%></b><br />一番新しい登録ユーザー: <b><a href="<%subSilver(userdata,newestlink)%>"><%subSilver(userdata,newest)%></a></b></span></td>
  </tr>
  <tr>
  <td class="row1" align="left"><%if(hasplugin,OnlineUsers)%>
    <span class="gensmall">オンライン状態のユーザーは<b><%OnlineUsers(online)%></b>人います :: 登録ユーザー（<%OnlineUsers(member)%>人）, ゲストユーザー（<%OnlineUsers(guest)%>人） &nbsp; [ <span style="color:#FFA34F">Administrator</span> ] &nbsp; [ <span style="color:#006600">Moderator</span> ]<br />ユーザー数の記録は <%OnlineUsers(maxonlinenum)%>人 <b>(<%OnlineUsers(recorddate,subSilver/index)%>&nbsp;<%OnlineUsers(recordtime,subSilver/index)%>)</b><br />登録ユーザー: <%OnlineUsers(onlinelist)%></span>
  <%endif%></td>
  </tr>
</table>
<table width="100%" cellpadding="1" cellspacing="1" border="0">
<tr>
	<td align="left" valign="top"><span class="gensmall">
      <%if(hasplugin,OnlineUsers)%>このデータは、過去<%OnlineUsers(howlong)%>分間の間にアクションを取ったユーザーに基づいたものです
      <%else%>&nbsp;
      <%endif%></span></td>
</tr>
</table>

<%endif%>