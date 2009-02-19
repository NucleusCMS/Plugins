<!--[head]-->

<table><thead>
<tr><th><%text(_NAME)%></th><th colspan='7'><%text(_LISTS_ACTIONS)%></th></tr>
</thead>
<tbody>

<!--[body]-->

<tr>
<td title='blogid:<%contents(bnumber)%> shortname:<%contents(bshortname)%>'><a href='<%contents(burl)%>'><img src='<%skinfile(images/globe.gif)%>' width='13' height='13' alt='<%text( _BLOGLIST_TT_VISIT)%>' /></a> <%contents(bname)%></td>
<td><a href='?action=createitem&amp;blogid=<%contents(bnumber)%>' title='<%text(_BLOGLIST_TT_ADD)%>'><%text(_BLOGLIST_ADD)%></a></td>
<td><a href='?action=itemlist&amp;blogid=<%contents(bnumber)%>' title='<%text(_BLOGLIST_TT_EDIT)%>'><%text(_BLOGLIST_EDIT)%></a></td>
<td><a href='?action=blogcommentlist&amp;blogid=<%contents(bnumber)%>' title='<%text(_BLOGLIST_TT_COMMENTS)%>'><%text(_BLOGLIST_COMMENTS)%></a></td>
<td><a href='?action=bookmarklet&amp;blogid=<%contents(bnumber)%>' title='<%text(_BLOGLIST_TT_BMLET)%>'><%text(_BLOGLIST_BMLET)%></a></td>

<%if(contents,tadmin,1)%>
  <td><a href='?action=blogsettings&amp;blogid=<%contents(bnumber)%>' title='<%text(_BLOGLIST_TT_SETTINGS)%>'><%text(_BLOGLIST_SETTINGS)%></a></td>
  <td><a href='?action=banlist&amp;blogid=<%contents(bnumber)%>' title='<%text(_BLOGLIST_TT_BANS)%>'><%text(_BLOGLIST_BANS)%></a></td>
<%endif%>

<%if(admin)%>
  <td><a href='?action=deleteblog&amp;blogid=<%contents(bnumber)%>' title='<%text(_BLOGLIST_TT_DELETE)%>'><%text(_BLOGLIST_DELETE)%></a></td>
<%endif%>

</tr>

<!--[foot]-->

</tbody></table>