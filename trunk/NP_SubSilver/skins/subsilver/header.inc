<body bgcolor="#E5E5E5" text="#000000" link="#006699" vlink="#5493B4">

<noscript>このフォーラムを快適に使用するためには、Javascript をオンにしてください</noscript>

<a name="top"></a>

<table width="100%" cellspacing="0" cellpadding="10" border="0" align="center">
<tr>
<td class="bodyline">
  <table width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr>
  <td><a href="<%sitevar(url)%>"><img src="<%skinfile(images/logo_petitforum.png)%>" border="0" alt="<%sitevar(name)%> フォーラム一覧" vspace="1" /></a></td>
  <td align="center" width="100%" valign="middle"><span class="maintitle"><%sitevar(name)%></span><br /><span class="gen"><%subSilver(showConf,SiteDesc)%><br />&nbsp; </span>
    <table cellspacing="0" cellpadding="2" border="0">
      <tr>
      <td align="center" valign="top" nowrap="nowrap"><span class="mainmenu">
      &nbsp;<a href="<%self%>?special=faq" class="mainmenu"><img src="<%skinfile(images/icon_mini_faq.gif)%>" width="12" height="13" border="0" alt="よくある質問" hspace="3" />よくある質問</a>&nbsp;
      &nbsp;<a href="<%self%>?special=search" class="mainmenu"><img src="<%skinfile(images/icon_mini_search.gif)%>" width="12" height="13" border="0" alt="検索" hspace="3" />検索</a>&nbsp;
      &nbsp;<a href="<%self%>?special=member" class="mainmenu"><img src="<%skinfile(images/icon_mini_members.gif)%>" width="12" height="13" border="0" alt="メンバーリスト" hspace="3" />メンバーリスト</a>&nbsp;
      </span></td>
      </tr>
      <tr>
      <td height="25" align="center" valign="top" nowrap="nowrap"><span class="mainmenu">
      <%if(loggedin)%>&nbsp;<a href="<%adminurl%>?action=editmembersettings" class="mainmenu"><img src="<%skinfile(images/icon_mini_profile.gif)%>" width="12" height="13" border="0" alt="ユーザー設定" hspace="3" />ユーザー設定</a>&nbsp;
      <%else%>&nbsp;<a href="<%self%>?special=register" class="mainmenu"><img src="<%skinfile(images/icon_mini_register.gif)%>" width="12" height="13" border="0" alt="登録" hspace="3" />登録</a>&nbsp;<%endif%>
      &nbsp;<a href="<%adminurl%>" class="mainmenu"><img src="<%skinfile(images/icon_mini_login.gif)%>" width="12" height="13" border="0" alt="ログイン" hspace="3" /><%if(loggedin)%>管理メニュー<%else%>ログイン<%endif%></a>&nbsp;
      </span></td>
      </tr>
    </table>
  </td>
  </tr>
  </table>
  <br />
