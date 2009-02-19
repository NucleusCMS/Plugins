<%if(contents,yesno,1)%>
  <input name="<%contents(name)%>" value="<%contents(value)%>" tabindex="<%contents(tabindex)%>" id="<%contents(name)%>" type="checkbox" checked="checked" />
<%else%>
  <input name="<%contents(name)%>" value="<%contents(value)%>" tabindex="<%contents(tabindex)%>" id="<%contents(name)%>" type="checkbox" />
<%endif%>
				