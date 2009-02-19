<!--showlogin-->

<h2><%text(_LOGIN)%></h2>
<%admin(message)%>

<form action="" method="post"><p>
<%text(_LOGIN_NAME)%>: <br />
<input name="login"  tabindex="10" /><br />
<%text(_LOGIN_PASSWORD)%>: <br />
<input name="password"  tabindex="20" type="password" /><br />
<input name="action" value="login" type="hidden" /><br />
<input type="submit" value="<%text(_LOGIN)%>" tabindex="30" /><br />
<small>
  <input type="checkbox" value="1" name="shared" tabindex="40" id="shared" />
  <label for="shared"><%text(_LOGIN_SHARED)%></label><br />
  <a href="?action=forgotpassword"><%text(_LOGIN_FORGOT)%></a>
</small>
</p></form>

<!--/showlogin-->