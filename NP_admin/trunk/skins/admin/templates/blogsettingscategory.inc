<!--[head]-->
<select name="defcat" tabindex="110">
<!--[body]-->
<%if(contents,selected,1)%>
  <option value="<%contents(value)%>" selected="selected"><%contents(text)%></option>
<%else%>
  <option value="<%contents(value)%>"><%contents(text)%></option>
<%endif%>
<!--[foot]-->
</select>
