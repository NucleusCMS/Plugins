<!--[head]-->
<form method="get" action=""><div>
<input type="hidden" name="action" value="createitem" />
<select name="blogid" tabindex="15000" onchange="return form.submit()">
<option value=""><%text(_QMENU_ADD_SELECT)%></option>

<!--[body]-->
<option value="<%contents(bnumber)%>" title="<%contents(bname)%>">
<%contents(bname,shorten,10)%>
</option>

<!--[foot]-->
</select>
</div></form>