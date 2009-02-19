<!--[head]-->
<table>
<thead>
<tr><th><%text(_LISTS_BLOG)%></th><th><%text(_LISTS_TITLE)%></th><th colspan='2'><%text(_LISTS_ACTIONS)%></th></tr>
</thead>
<tbody>

<!--[body]-->
<tr>
<td><%contents(bshortname)%></td>
<td><%contents(ititle,strip_tags)%></td>
<td><a href='?action=itemedit&amp;itemid=<%contents(inumber)%>'><%text(_LISTS_EDIT)%></a></td>
<td><a href='?action=itemdelete&amp;itemid=<%contents(inumber)%>'><%text(_LISTS_DELETE)%></a></td>
</tr>

<!--[foot]-->
</tbody>
</table>