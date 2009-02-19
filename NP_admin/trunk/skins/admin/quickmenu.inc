<div id="quickmenu">

<%if(loggedin)%>

  <ul>
  <li><a href="?action=overview"><%text(_QMENU_HOME)%></a></li>
  </ul>

  <h2><%text(_QMENU_ADD)%></h2>
  <%admin(qmenuadd)%>

  <h2><%member(yourname)%></h2>
  <ul>
  <li><a href="?action=editmembersettings"><%text(_QMENU_USER_SETTINGS)%></a></li>
  <li><a href="?action=browseownitems"><%text(_QMENU_USER_ITEMS)%></a></li>
  <li><a href="?action=browseowncomments"><%text(_QMENU_USER_COMMENTS)%></a></li>
  </ul>

  <%if(admin)%>
    <h2><%text(_QMENU_MANAGE)%></h2>
    <ul>
    <li><a href="?action=actionlog"><%text(_QMENU_MANAGE_LOG)%></a></li>
    <li><a href="?action=settingsedit"><%text(_QMENU_MANAGE_SETTINGS)%></a></li>
    <li><a href="?action=usermanagement"><%text(_QMENU_MANAGE_MEMBERS)%></a></li>
    <li><a href="?action=createnewlog"><%text(_QMENU_MANAGE_NEWBLOG)%></a></li>
    <li><a href="?action=backupoverview"><%text(_QMENU_MANAGE_BACKUPS)%></a></li>
    <li><a href="?action=pluginlist"><%text(_QMENU_MANAGE_PLUGINS)%></a></li>
    </ul>
    <h2><%text(_QMENU_LAYOUT)%></h2>
    <ul>
    <li><a href="?action=skinoverview"><%text(_QMENU_LAYOUT_SKINS)%></a></li>
    <li><a href="?action=templateoverview"><%text(_QMENU_LAYOUT_TEMPL)%></a></li>
    <li><a href="?action=skinieoverview"><%text(_QMENU_LAYOUT_IEXPORT)%></a></li>
    </ul>
  <%endif%>

  <%admin(qmenuplugin)%>

<%else%>
  <h2><%text(_QMENU_INTRO)%></h2>
  <%text(_QMENU_INTRO_TEXT)%>
<%endif%>

</div>
