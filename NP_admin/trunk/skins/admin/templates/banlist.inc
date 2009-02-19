<!--[head]-->

<table><thead>
<tr><th><%text(_LIST_BAN_IPRANGE)%></th><th><%text(_LIST_BAN_REASON)%></th><th><%text(_LISTS_ACTIONS)%></th></tr>
</thead>
<tbody>

<!--[body]-->

<tr>
<td><%contents(iprange)%></td>
<td><%contents(reason)%></td>
<td><a href='index.php?action=banlistdelete&amp;blogid=<%contents(blogid)%>&amp;iprange=<%contents(iprange)%>'><%text(_LISTS_DELETE)%></a></td>
</tr>

<!--[foot]-->

</tbody></table>