<%ifnot(admin)%>
<a href="?action=overview"><%text(_BACKHOME)%></a>
<%elseif(admin,getVar,memberid=)%>
<a href="?action=overview"><%text(_BACKHOME)%></a>
<%else%>
<a href="?action=usermanagement"><%text(_MEMBERS_BACKTOOVERVIEW)%></a>
<%endif%>

<script type="text/javascript" src="javascript/numbercheck.js"></script>

<h2><%text(_MEMBERS_EDIT)%></h2>

<%admin(editmembersettings)%>