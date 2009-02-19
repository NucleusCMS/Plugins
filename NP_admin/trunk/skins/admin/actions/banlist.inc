<!--banlist-->

<p><a href="?action=overview">(<%text(_BACKHOME)%>)</a></p>

<h2><%text(_BAN_TITLE)%>: '<a href="<%blogsetting(url)%>"><%blogsetting(name)%></a>'</h2>

<%ifnot(blogadmin)%>
  <%text(_ERROR_DISALLOWED)%>
<%else%>

<%admin(banlist)%>
<h2><%text(_BAN_NEW_TITLE)%></h2>
<p><a href='?action=banlistnew&amp;blogid=<%admin(blogsetting,ID)%>'><%text(_BAN_NEW_TEXT)%></a></p>

<%endif%>

<!--/banlist-->