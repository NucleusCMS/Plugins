<table align="center" border="0" cellpadding="2" cellspacing="2" width="100%">
  <tbody><tr>
	<td align="left"><span class="nav"><a href="<%sitevar(url)%>" class="nav"><%sitevar(name)%> フォーラム一覧</a></span></td>

  </tr>
</tbody></table>

<table class="forumline" align="center" border="0" cellpadding="3" cellspacing="1" width="100%">
  <tbody><tr>
	<th class="thHead" colspan="2" height="25" nowrap="nowrap">ユーザー情報 :: <%member(realname)%></th>
  </tr>
  <tr>
	<td class="catLeft" align="center" height="28" width="40%"><b><span class="gen">ユーザー画像</span></b></td>

	<td class="catRight" width="60%"><b><span class="gen"><a href="<%self%>?memberid=<%member(id)%>"><%member(realname)%></a>の詳細</span></b>
          <%if(subSilver,member,position=ownerofpage)%>
            <a href="<%adminurl%>?action=memberedit&amp;memberid=<%member(id)%>">
            <img src="<%skinfile(images/lang_english/icon_edit.gif)%>" /></a>
          <%endif%>
        </td>
  </tr>
  <tr>
	<td class="row1" align="center" height="6" valign="top"><br /><span class="postdetails">
      <%if(subSilver,member,position=superadmin)%>Administrator
      <%elseif(subSilver,member,position=blogadmin)%>Moderator
      <%endif%>
    </span></td>
	<td class="row1" rowspan="3" valign="top"><table border="0" cellpadding="3" cellspacing="1" width="100%">
		<tbody><tr>
		  <td align="right" nowrap="nowrap" valign="middle"><span class="gen">登録日:&nbsp;</span></td>

		  <td width="100%"><b><span class="gen"><%subSilver(member,regdate,subSilver/index)%> 頃</span></b></td>
		</tr>
		<tr>
		  <td align="right" nowrap="nowrap" valign="top"><span class="gen">投稿数:&nbsp;</span></td>
		  <td valign="top"><b><span class="gen"><%subSilver(member,replies)%></span></b><br /><span class="genmed">[全体の割合: <%subSilver(member,percent)%>% / 1日の投稿数: <%subSilver(member,repliesperday)%>]</span> <br /><span class="genmed"><a href="<%self%>?special=search&amp;search_author=<%member(realname)%>" class="genmed"><%member(realname)%>の投稿記事を全て検索</a></span></td>
		</tr>

		<tr>
		  <td align="right" nowrap="nowrap" valign="middle"><%if(subSilver,member,has=url)%><span class="gen"><span style="white-space: nowrap;">ホームページ:&nbsp;</span></span><%endif%></td>
		  <td><span class="gen"><b><%subSilver(member,url)%></b></span></td>

		</tr>

	  </tbody></table>
	</td>
  </tr>
  <tr>
	<td class="catLeft" align="center" height="28"><b><span class="gen">連絡先: <%member(realname)%> </span></b></td>
  </tr>
  <tr>

	<td class="row1" valign="top"><table border="0" cellpadding="3" cellspacing="1" width="100%">
		<tbody><tr>
		  <td align="right" nowrap="nowrap" valign="middle"><span class="gen">プライベートメッセージ:</span></td>
		  <td class="row1" valign="middle">
        <form method="post" action="">
            <input type="image" name="subSilver_action" value="sendmessage" src="<%skinfile(images/lang_english/icon_pm.gif)%>" alt="メッセージを送信" title="メッセージを送信" />
        </form></td>
		</tr>
	  </tbody></table>
	</td>
  </tr>
</tbody></table>

<%if(subSilver,postVar,subSilver_action=sendmessage)%>
<table width="100%" cellspacing="0" border="0" align="center" cellpadding="2">
  <tr><td><span class="gensmall">&nbsp;</span></td></tr>
</table>
<table class="forumline" border="0" cellpadding="3" cellspacing="1" width="100%">
	<tbody><tr>
		<th class="thHead" colspan="2" height="25"><b>プライベートメッセージ</b></th>
	</tr>
	<tr>
      <td  class="commentform">
        <table align="center" border="0"><tr><td>
          <%membermailform%>
        </td></tr></table>
      </td>
	</tr>
  </tbody>
</table>
<%endif%>
