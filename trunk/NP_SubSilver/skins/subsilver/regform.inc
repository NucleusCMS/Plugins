<table align="center" border="0" cellpadding="2" cellspacing="2" width="100%">
<tbody><tr>
  <td align="left"><span class="nav"><a class="nav" href="<%sitevar(url)%>"><%sitevar(name)%> フォーラム一覧</a></span></td>
</tr>
</tbody></table>

<form method="post" action="action.php">
<input type="hidden" name="action" value="createaccount" />

<table class="forumline" border="0" cellpadding="3" cellspacing="1" width="100%">
<tbody><tr>
  <th class="thHead" colspan="2" height="25" valign="middle">登録情報</th>
</tr>
<tr>
  <td class="row2" colspan="2"><span class="gensmall">*がついている項目は必ず入力する必要があります</span></td>
</tr>
<tr>
  <td class="row1" width="38%"><span class="gen">ユーザー名: *</span>&nbsp;&nbsp; <small>(only a-z, 0-9)</small></td>
  <td class="row2"><input class="post" style="width: 400px;" name="name" size="20" maxlength="20" value="" type="text"></td>
</tr>
<tr>
  <td class="row1" width="38%"><span class="gen">本名: *</span></td>
  <td class="row2"><input class="post" style="width: 400px;" name="realname" size="40" maxlength="40" value="" type="text"></td>
</tr>
<tr>
  <td class="row1" width="38%"><span class="gen">E-mail: *</span>&nbsp;&nbsp; <small>(利用可能なものを。パスワードはここに送信されます。)</small></td>
  <td class="row2"><input class="post" style="width: 400px;" name="email" size="40" maxlength="40" value="" type="text"></td>
</tr>
<tr>
  <td class="row1" width="38%"><span class="gen">URL:</span></td>
  <td class="row2"><input class="post" style="width: 400px;" name="url" size="60" maxlength="60" value="" type="text"></td>
</tr>

<tr>
  <td class="catSides" colspan="2" height="28"><div style="text-align:center;"><input type="submit" value="アカウントの作成" /></div></td>
</tr>
</tbody></table>

</form>
