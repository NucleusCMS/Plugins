<!--[head]-->

<form method="post" action="<%self%>?action=batchcomment">

<table><thead>
<tr><th><%text(_LISTS_INFO)%></th><th><%text(_LIST_COMMENT)%></th><th colspan='3'><%text(_LISTS_ACTIONS)%></th>
</thead>
<tbody>

<!--[body]-->

<tr>
<td>
  <%admin(date,Y-m-d@H:i,ctime)%><br />
  <%ifnot(contents,mname,)%>
    <%contents(mname)%> <%text(_LIST_COMMENTS_MEMBER)%>
  <%else%>
    <%contents(cuser)%>
  <%endif%>
  <%ifnot(contents,cmail,)%>
    <br /><%contents(cmail)%>
  <%endif%>
  <%ifnot(contents,cemail,)%>
    <br /><%contents(cemail)%>
  <%endif%>
</td>
<td>
  <input type="checkbox" id="batch<%contents(i)%>" name="batch[<%contents(i)%>]" value="<%contents(cnumber)%>" />
  <label for="batch<%contents(i)%>">
    <%contents(cbody,shorten,300,...)%>
  </label>
</td>

<td style="white-space:nowrap"><a href='?action=commentedit&amp;commentid=<%contents(cnumber)%>'><%text(_LISTS_EDIT)%></a></td>
<td style="white-space:nowrap"><a href='?action=commentdelete&amp;commentid=<%contents(cnumber)%>'><%text(_LISTS_DELETE)%></a></td>
<%if(blogadmin)%>
  <td style="white-space:nowrap"><a href='?action=banlistnewfromitem&amp;itemid=<%contents(citem)%>&amp;ip=<%contents(cip)%>' title='<%contents(chost)%>'><%text(_LIST_COMMENT_BANIP)%></a></td>
<%endif%>

</tr>

<!--[foot]-->

</tbody></table>
<%admin(set,batchtype,comment)%>
<%parsedinclude(templates/_batchoperations.inc)%>
</form>