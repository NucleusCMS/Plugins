<!--[head]-->

<form method="post" action="<%self%>?action=batchitem">

<table><thead>
<tr><th><%text(_LIST_ITEM_INFO)%></th><th><%text(_LIST_ITEM_CONTENT)%></th><th style="white-space:nowrap" colspan='1'><%text(_LISTS_ACTIONS)%></th></tr>
</thead>
<tbody>

<!--[body]-->

<%if(admin,future,itime)%><%admin(set,class,future)%>
<%elseif(contents,idraft,1)%><%admin(set,class,draft)%>
<%else%><%admin(set,class,)%>
<%endif%>

<tr>

<td<%ifnot(admin,set,class=)%> class="<%admin(get,class)%>"<%endif%>>
  <%text(_LIST_ITEM_BLOG)%><%contents(bshortname)%><br />
  <%text(_LIST_ITEM_CAT)%><%contents(cname)%><br />
  <%text(_LIST_ITEM_AUTHOR)%><%contents(mname)%><br />
  <%text(_LIST_ITEM_DATE)%><%admin(date,Y-m-d,itime)%><br />
  <%text(_LIST_ITEM_TIME)%><%admin(date,H:i,itime)%>
</td>

<td<%ifnot(admin,set,class=)%> class="<%admin(get,class)%>"<%endif%>>
  <input type="checkbox" id="batch<%contents(i)%>" name="batch[<%contents(i)%>]" value="<%contents(inumber)%>" />
  <label for="batch<%text(filled,i)%>"><b><%contents(ititle,strip_tags)%></b></label><br />
  <%contents(ibody,shorten,300,...)%>

</td>

<td style="white-space:nowrap"<%ifnot(admin,set,class=)%> class="<%admin(get,class)%>"<%endif%>>
  <a href='?action=itemedit&amp;itemid=<%contents(inumber)%>'><%text(_LISTS_EDIT)%></a><br />
  <a href='?action=itemcommentlist&amp;itemid=<%contents(inumber)%>'><%text(_LISTS_COMMENTS)%></a><br />
  <a href='?action=itemmove&amp;itemid=<%contents(inumber)%>'><%text(_LISTS_MOVE)%></a><br />
  <a href='?action=itemdelete&amp;itemid=<%contents(inumber)%>'><%text(_LISTS_DELETE)%></a>
</td>

</tr>

<!--[foot]-->

</tbody></table>
<%admin(set,batchtype,item)%>
<%parsedinclude(templates/_batchoperations.inc)%>
</form>