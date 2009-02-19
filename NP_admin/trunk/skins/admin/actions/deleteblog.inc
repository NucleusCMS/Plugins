<!--deleteblog-->

<%admin(begin,defaultBlog)%><%conf(DefaultBlog)%><%admin(end)%>
<%admin(begin,blogid)%><%blogsetting(id)%><%admin(end)%>
<%if(admin,compare,defaultBlog=blogid)%>
  <%admin(showerror,_ERROR_DELDEFBLOG)%>
<%else%>

<h2><%text(_DELETE_CONFIRM)%></h2>
<p><%text(_WARNINGTXT_BLOGDEL)%></p>
<div>
<%text(_CONFIRMTXT_BLOG)%><b><%admin(blogsetting,Name)%></b>
</div>
<form method="post" action="index.php"><div>
<input type="hidden" name="action" value="deleteblogconfirm" />
<%admin(ticket,hidden)%>
<input type="hidden" name="blogid" value="<%admin(blogsetting,ID)%>" />
<input type="submit" tabindex="10" value="<%text(_DELETE_CONFIRM_BTN)%>" />
</div></form>

<%endif%>

<!--/deleteblog-->