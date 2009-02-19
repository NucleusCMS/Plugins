<!--[head]-->

<form method="post" action="<%self%>?action=batchcategory">

<table><thead>
<tr><th><%text(_LISTS_NAME)%></th><th><%text(_LISTS_DESC)%></th><th colspan='2'><%text(_LISTS_ACTIONS)%></th></tr>
</thead>
<tbody>

<!--[body]-->

<tr>
  <td><input type="checkbox" id="batch<%contents(i)%>" name="batch[<%contents(i)%>]" value="<%contents(catid)%>" />
  <label for="batch<%contents(i)%>"><%contents(cname)%></label></td>
  <td><%contents(cdesc)%></td>
  <td><a href='?action=categorydelete&amp;blogid=<%contents(cblog)%>&amp;catid=<%contents(catid)%>' tabindex='<%contents(tabindex)%>'><%text(_LISTS_DELETE)%></a></td>
  <td><a href='index.php?action=categoryedit&amp;blogid=<%contents(cblog)%>&amp;catid=<%contents(catid)%>' tabindex='<%contents(tabindex)%>'><%text(_LISTS_EDIT)%></a></td>
</tr>

<!--[foot]-->

</tbody></table>
<%admin(set,batchtype,category)%>
<%parsedinclude(templates/_batchoperations.inc)%>
</form>