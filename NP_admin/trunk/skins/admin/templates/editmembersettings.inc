<%note( Set the AllowLoginEdit flag. )%>
<%admin(begin,ale)%><%conf(AllowLoginEdit)%><%admin(end)%>
<%if(admin)%><%admin(set,ale,1)%><%endif%>

<form method="post" action="<%self%>?action=changemembersettings" name="memberedit"><div>

<%admin(ticket,hidden)%>
<input type="hidden" name="memberid" value="<%contents(mnumber)%>" />

<table>
<tr><th colspan="2"><%text(_MEMBERS_EDIT)%></th></tr>
<tr>
  <td><%text(_MEMBERS_DISPLAY)%> <%admin(help,shortnames)%><br /><small<%text(_MEMBERS_DISPLAY_INFO)%></small></td>
  <td>
    <%if(admin,set,ale=1)%><input name="name" tabindex="10" maxlength="16" size="16" value="<%contents(mname)%>" />
    <%else%><%contents(mname)%><%endif%>
  </td>
</tr><tr>
  <td><%text(_MEMBERS_REALNAME)%></td>
  <td><input name="realname" tabindex="20" maxlength="60" size="40" value="<%contents(mrealname)%>" /></td>
</tr><tr>

<%if(admin,set,ale=1)%>
    <td><%text(_MEMBERS_PWD)%></td>
    <td><input type="password" tabindex="30" maxlength="40" size="16" name="password" /></td>
  </tr><tr>
    <td><%text(_MEMBERS_REPPWD)%></td>
    <td><input type="password" tabindex="35" maxlength="40" size="16" name="repeatpassword" /></td>
  </tr><tr>
<%endif%>

  <td><%text(_MEMBERS_EMAIL)%><br /><small><%text(_MEMBERS_EMAIL_EDIT)%></small></td>
  <td><input name="email" tabindex="40" size="40" maxlength="60" value="<%contents(memail)%>" /></td>
</tr><tr>
  <td><%text(_MEMBERS_URL)%></td>
  <td><input name="url" tabindex="50" size="40" maxlength="100" value="<%contents(murl)%>" /></td>
</tr><tr>

<%note( // only allow to change this by super-admins
        // we don't want normal users to 'upgrade' themselves to super-admins, do we? ;-) 
)%>
<%if(admin)%>
    <td><%text(_MEMBERS_SUPERADMIN)%> <%admin(help,superadmin)%></td>
    <td><%admin(inputyesno,admin,contents/madmin,tabindex=60)%></td>
  </tr><tr>
    <td><%text(_MEMBERS_CANLOGIN)%> <%admin(help,canlogin)%></td>
    <%if(contents,madmin,1)%><td><%admin(inputyesno,canlogin,1,tabindex=70,templates/inputyesnodisabled.inc)%></td>
    <%else%><td><%admin(inputyesno,canlogin,contents/mcanlogin,tabindex=70)%></td><%endif%>
  </tr><tr>
<%endif%>

  <td><%text(_MEMBERS_NOTES)%></td>
  <td><input name="notes" tabindex="80" size="40" maxlength="100" value="<%contents(mnotes)%>" /></td>
</tr><tr>
  <td><%text(_MEMBERS_DEFLANG)%> <%admin(help,language)%></td>
  <td><%admin(editmembersettingsdeflang)%></td>
</tr><tr>
  <%admin(insertPluginOptions,member)%>
</tr><tr>
  <th colspan="2"><%text(_MEMBERS_EDIT)%></th>
</tr><tr>
  <td><%text(_MEMBERS_EDIT)%></td>
  <td><input type="submit" tabindex="90" value="<%text(_MEMBERS_EDIT_BTN)%>" onclick="return checkSubmit();" /></td>
</tr>
</table>

</div></form>

<h3><%text(_PLUGINS_EXTRA)%></h3>
<%callback(MemberSettingsFormExtras)%>
