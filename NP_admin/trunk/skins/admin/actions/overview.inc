<!--overview-->

<%admin(message)%>
<h2><%text(_OVERVIEW_YRBLOGS)%></h2>
<%admin(bloglist)%>

<h2><%text(_OVERVIEW_YRDRAFTS)%></h2>
<%admin(draftlist)%>

<h2><%text(_OVERVIEW_YRSETTINGS)%></h2>
<ul>
<li><a href="?action=editmembersettings"><%text(_OVERVIEW_EDITSETTINGS)%></a></li>
<li><a href="?action=browseownitems"><%text(_OVERVIEW_BROWSEITEMS)%></a></li>
<li><a href="?action=browseowncomments"><%text(_OVERVIEW_BROWSECOMM)%></a></li>
</ul>

<%if(admin)%>
<h2><%text(_OVERVIEW_MANAGEMENT)%></h2>
<ul>
<li><a href="?action=manage"><%text(_OVERVIEW_MANAGE)%></a></li>
</ul>
<%endif%>

<!--/overview-->