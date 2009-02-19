<!--[head]-->
<select name="deflang" tabindex="85">
<option value=""><%text(_MEMBERS_USESITELANG)%></option>

<!--[body]-->
<%if(contents,selected,1)%>
  <option value='<%contents(name)%>' selected='selected'><%contents(name)%></option>
<%else%>
  <option value='<%contents(name)%>'><%contents(name)%></option>
<%endif%>

<!--[foot]-->
</select>
