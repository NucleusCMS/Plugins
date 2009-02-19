<!--_shownavigation-->

<table class="navigation">
<tr><td>
  <form method="post" action=""><div>
  <input type="submit" value="&lt;&lt; <%text(_LISTS_PREV)%>" />
  <%admin(inputhiddenfrompost,amount)%>
  <%admin(inputhiddenfrompost,search)%>
  <%admin(inputhiddenfrompost,start,prev)%>
  </div></form>
</td><td>
  <form method="post" action=""><div>
  <input type="text" name="amount" size="3" value="<%admin(postvar,amount,10)%>" /> <%text(_LISTS_PERPAGE)%>
  <%admin(inputhiddenfrompost,search)%>
  <%admin(inputhiddenfrompost,start)%>
  <input type="submit" value="&gt; <%text(_LISTS_CHANGE)%>" />
  </div></form>
</td><td>
  <form method="post" action=""><div>
  <%admin(inputhiddenfrompost,amount)%>
  <input type="hidden" name="start" value="0" />
  <input type="text" name="search" size="7" value="<%admin(postvar,search)%>" />
  <input type="submit" value="&gt; <%text(_LISTS_SEARCH)%>" />
  </div></form>
</td><td>
  <form method="post" action=""><div>
  <input type="submit" value="<%text(_LISTS_NEXT)%> &gt; &gt;" />
  <%admin(inputhiddenfrompost,amount)%>
  <%admin(inputhiddenfrompost,search)%>
  <%admin(inputhiddenfrompost,start,next)%>
  </div></form>
</td><td>
  <form method="post" action=""><div>
  <input type="submit" value="<%text(_SKIN_RESET_BTN)%>" />
  </div></form>
</td></tr>
</table>

<!--/_shownavigation-->
