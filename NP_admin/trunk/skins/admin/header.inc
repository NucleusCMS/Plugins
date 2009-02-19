<div class="loginname">

<%if(loggedin)%>
  <%text(_LOGGEDINAS)%> <%member(yourname)%> - <a href="?action=logout"><%text(_LOGOUT)%></a><br />
  <a href='?action=overview'><%text(_ADMINHOME)%></a> - 
<%else%>
  <a href="?action=showlogin" title="Log in"><%text(_NOTLOGGEDIN)%></a>
<%endif%>

<a href="<%conf(IndexURL)%>"><%text(_YOURSITE)%></a><br />
(Nucleus CMS <%admin(global,nucleus,version)%>)

</div>
