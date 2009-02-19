<%if(contents,yesno,1)%>
  <input name="<%contents(name)%>" value="1" tabindex="<%contents(tabindex)%>" checked="checked" id="<%contents(name)%>1" type="radio" />
  <label for="<%contents(name)%>1"><%text(_YES)%></label>
  <input disabled="disabled" name="<%contents(name)%>" value="0" id="<%contents(name)%>0" type="radio" />
  <label for="<%contents(name)%>0"><%text(_NO)%></label>
<%else%>
  <input disabled="disabled" name="<%contents(name)%>" value="1" tabindex="<%contents(tabindex)%>" id="<%contents(name)%>1" type="radio" />
  <label for="<%contents(name)%>1"><%text(_YES)%></label>
  <input name="<%contents(name)%>" value="0" id="<%contents(name)%>0" checked="checked" type="radio" />
  <label for="<%contents(name)%>0"><%text(_NO)%></label>
<%endif%>