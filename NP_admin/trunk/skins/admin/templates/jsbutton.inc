<%if(contents,type,spacer)%>
  <span class="jsbuttonspacer"></span>
<%else%>
  <span class="jsbutton"
    onmouseover="BtnHighlight(this);"
    onmouseout="BtnNormal(this);"
    onclick="<%contents(code)%>" >
  <img src="<%skinfile(images/)%>button-<%contents(type)%>.gif" alt="<%contents(tooltip)%>" width="16" height="16"/>
  </span>
<%endif%>