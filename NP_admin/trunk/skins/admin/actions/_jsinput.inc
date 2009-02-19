  name="<%contents(which)%>"
  id="input<%contents(which)%>"
<%ifnot(admin,conf,DisableJsTools=1)%>
  onkeyup="storeCaret(this); updPreview('<%contents(which)%>'); doMonitor();"
  onclick="storeCaret(this);"
  onselect="storeCaret(this);"
<%elseif(admin,conf,DisableJsTools=0)%>
  onkeyup="doMonitor();"
  onkeypress="shortCuts();"
<%else%>
  onkeyup="doMonitor();"
<%endif%>