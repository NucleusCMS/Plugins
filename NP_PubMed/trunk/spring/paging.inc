<%if(hasplugin,PageSwitch)%>

<%if(PageSwitch,limit,10)%>
<div style="text-align:right;">Pages:
<%PageSwitch(prev,prev)%>
<%PageSwitch(index,2)%>
<%PageSwitch(next,next)%>
Page <%PageSwitch(num)%> of <%PageSwitch(total)%>
</div>
<%endif%>

<%else%>

<div style="text-align:right">
<%prevlink(<<< prev page,10)%>
&nbsp;&nbsp;&nbsp;
<%nextlink(next page >>>,10)%>
</div>

<%endif%>