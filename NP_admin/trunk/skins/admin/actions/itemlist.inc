<!--itemlist-->

<p><a href="?action=overview">(<%text(_BACKHOME)%>)</a></p>
<h2><%text(_ITEMLIST_BLOG)%> <a href="<%blogsetting(url)%>"><%blogsetting(name)%></a></h2>

<%if(onteam)%>
  <%ifnot(ifadmin,getvar,start)%>
    <p><a href="index.php?action=createitem&amp;blogid=<%blogsetting(id)%>"><%text(_ITEMLIST_ADDNEW)%></a></p>
<%endif%>

<%parsedinclude(actions/_shownavigation.inc)%>
<%admin(itemlist)%>
<%parsedinclude(actions/_shownavigation.inc)%>

<!--/itemlist-->