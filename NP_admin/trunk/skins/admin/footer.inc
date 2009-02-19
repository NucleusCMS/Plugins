<%if(loggedin)%>
<h2><%text(_LOGOUT)%></h2>
<ul>
<li><a href="?action=overview"><%text(_BACKHOME)%></a></li>
<li><a href='?action=logout'><%text(_LOGOUT)%></a></li>
</ul>
<%endif%>

<div class="foot">
<a href="http://nucleuscms.org/">Nucleus CMS</a> &copy; 2002-<%admin(date,Y)%>
The Nucleus Group - <a href="http://nucleuscms.org/donate.php">Donate!</a>
</div>
