<!--blogcommentlist-->

<p><a href="?action=overview">(<%text(_BACKHOME)%>)</a></p>
<h2><%text(_COMMENTS_BLOG)%> <a href="<%blogsetting(url)%>"><%blogsetting(name)%></a></h2>

<%parsedinclude(actions/_shownavigation.inc)%>
<%admin(blogcommentlist)%>
<%parsedinclude(actions/_shownavigation.inc)%>

<!--/blogcommentlist-->