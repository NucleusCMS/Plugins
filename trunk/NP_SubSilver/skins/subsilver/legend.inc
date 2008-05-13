<br />
<%if(category)%>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody><tr>
    <td align="left" valign="top"><table border="0" cellpadding="0" cellspacing="3">
      <tbody><tr>
        <td align="left" width="20"><img src="<%skinfile(images/folder_new.gif)%>" alt="新しい記事有り" height="18" width="19" /></td>
        <td class="gensmall">新しい記事有り</td>
        <td>&nbsp;&nbsp;</td>
        <td align="center" width="20"><img src="<%skinfile(images/folder.gif)%>" alt="新しい記事無し" height="18" width="19" /></td>
        <td class="gensmall">新しい記事無し</td>
        <td>&nbsp;&nbsp;</td>
        <td align="center" width="20"><img src="<%skinfile(images/folder_announce.gif)%>" alt="重要トピック" height="18" width="19" /></td>
        <td class="gensmall">重要トピック</td>
      </tr>
      <tr>
        <td align="center" width="20"><img src="<%skinfile(images/folder_new_hot.gif)%>" alt="新しい記事有り（人気）" height="18" width="19" /></td>
        <td class="gensmall">新しい記事有り（人気）</td>
        <td>&nbsp;&nbsp;</td>
        <td align="center" width="20"><img src="<%skinfile(images/folder_hot.gif)%>" alt="新しい記事無し（人気）" height="18" width="19" /></td>
        <td class="gensmall">新しい記事無し（人気）</td>
        <td>&nbsp;&nbsp;</td>
        <td align="center" width="20"><img src="<%skinfile(images/folder_sticky.gif)%>" alt="告知トピック" height="18" width="19" /></td>
        <td class="gensmall">告知トピック</td>
      </tr>
      <tr>
        <td class="gensmall"><img src="<%skinfile(images/folder_lock_new.gif)%>" alt="新しい記事有り（ロック）" height="18" width="19" /></td>
        <td class="gensmall">新しい記事有り（ロック）</td>
        <td>&nbsp;&nbsp;</td>
        <td class="gensmall"><img src="<%skinfile(images/folder_lock.gif)%>" alt="新しい記事無し（ロック）" height="18" width="19" /></td>
        <td class="gensmall">新しい記事無し（ロック）</td>
      </tr>
    </tbody></table></td>
    <td align="right"><span class="gensmall">
      新規投稿: <b><%if(subSilver,member,can=postnew)%>可<%else%>不可<%endif%></b><br />
      返信: <b><%if(subSilver,member,can=reply)%>可<%else%>不可<%endif%></b><br />
      自分の記事の編集: <b><%if(subSilver,member,can=editcomment)%>可<%else%>不可<%endif%></b><br />
      自分の記事の削除: <b><%if(subSilver,member,can=editcomment)%>可<%else%>不可<%endif%></b><br />
    </span></td>
  </tr>
</tbody></table>

<%else%>

<table cellspacing="3" border="0" align="center" cellpadding="0">
  <tr>
  <td width="20" align="center"><img src="<%skinfile(images/folder_new_big.gif)%>" alt="新しい記事有り" /></td>
  <td><span class="gensmall">新しい記事有り</span></td>
  <td>&nbsp;&nbsp;</td>
  <td width="20" align="center"><img src="<%skinfile(images/folder_big.gif)%>" alt="新しい記事無し" /></td>
  <td><span class="gensmall">新しい記事無し</span></td>
  <td>&nbsp;&nbsp;</td>
  <td width="20" align="center"><img src="<%skinfile(images/folder_locked_big.gif)%>" alt="ロック状態" /></td>
  <td><span class="gensmall">ロック状態</span></td>
  </tr>
</table>

<%endif%>
