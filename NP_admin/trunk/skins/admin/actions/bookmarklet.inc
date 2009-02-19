<!--bookmarklet-->

<p><a href="?action=overview">(<%text(_BACKHOME)%>)</a></p>

<%ifnot(onteam)%>
  <%text(_ERROR_DISALLOWED)%>
<%else%>

<h2><%text(_BOOKMARKLET_TITLE)%></h2>

<p>
<%text(_BOOKMARKLET_DESC1)%>
<%text(_BOOKMARKLET_DESC2)%>
<%text(_BOOKMARKLET_DESC3)%>
<%text(_BOOKMARKLET_DESC4)%>
<%text(_BOOKMARKLET_DESC5)%>
</p>

<h3><%text(_BOOKMARKLET_BOOKARKLET)%></h3>
<p>
<%text(_BOOKMARKLET_BMARKTEXT)%><small><%text(_BOOKMARKLET_BMARKTEST)%></small>
<br /><br />
<%admin(begin,blogname)%><%blogsetting(name)%><%admin(end)%>
<a href="<%admin(bookmarklet)%>"><%admin(sprintf,_BOOKMARKLET_ANCHOR,blogname)%></a>
<%text(_BOOKMARKLET_BMARKFOLLOW)%>
</p>

<h3><%text(_BOOKMARKLET_RIGHTCLICK)%></h3>
<p>
<%text(_BOOKMARKLET_RIGHTTEXT1)%>
<a href="?action=regfile&amp;blogid=<%blogsetting(id)%>&amp;ticket=<%admin(ticket)%>"><%text(_BOOKMARKLET_RIGHTLABEL)%></a>
<%text(_BOOKMARKLET_RIGHTTEXT2)%>
</p>

<p>
<%text(_BOOKMARKLET_RIGHTTEXT3)%>
</p>

<h3><%text(_BOOKMARKLET_UNINSTALLTT)%></h3>
<p>
<%text(_BOOKMARKLET_DELETEBAR)%>
</p>

<p>
<%text(_BOOKMARKLET_DELETERIGHTT)%>
</p>

<ol>
	<li><%text(_BOOKMARKLET_DELETERIGHT1)%></li>
	<li><%text(_BOOKMARKLET_DELETERIGHT2)%></li>
	<li><%text(_BOOKMARKLET_DELETERIGHT3)%></li>
	<li><%text(_BOOKMARKLET_DELETERIGHT4)%></li>
	<li><%text(_BOOKMARKLET_DELETERIGHT5)%></li>
</ol>

<%endif%>

<!--/bookmarklet-->