<!--_batchoperations-->

<div class="batchoperations">
<%text(_BATCH_WITH_SEL)%>

<select name="batchaction">
  <%if(contents,batchtype,item)%>
    <option value="delete"><%text(_BATCH_ITEM_DELETE)%></option>
    <option value="move"><%text(_BATCH_ITEM_MOVE)%></option>
  <%elseif(contents,batchtype,member)%>
    <option value="delete"><%text(_BATCH_MEMBER_DELETE)%></option>
    <option value="setadmin"><%text(_BATCH_MEMBER_SET_ADM)%></option>
    <option value="unsetadmin"><%text(_BATCH_MEMBER_UNSET_ADM)%></option>
  <%elseif(contents,batchtype,team)%>
    <option value="delete"><%text(_BATCH_TEAM_DELETE)%></option>
    <option value="setadmin"><%text(_BATCH_TEAM_SET_ADM)%></option>
    <option value="unsetadmin"><%text(_BATCH_TEAM_UNSET_ADM)%></option>
  <%elseif(contents,batchtype,category)%>
    <option value="delete"><%text(_BATCH_CAT_DELETE)%></option>
    <option value="move"><%text(_BATCH_CAT_MOVE)%></option>
  <%elseif(contents,batchtype,comment)%>
    <option value="delete"><%text(_BATCH_COMMENT_DELETE)%></option>
  <%endif%>
</select>

<%note(
  The hidden action was removed.  Set the action in form tab.
  For the cases of team and comment, blogid and itemid must be set in form tab as well.
)%>

<%admin(ticket,hidden)%>

<input type="submit" value="<%text(_BATCH_EXEC)%>" />

<a href="" onclick="if (event &amp;&amp; event.preventDefault) event.preventDefault(); return batchSelectAll(1); "><%text(_BATCH_SELECTALL)%>></a> -
<a href="" onclick="if (event &amp;&amp; event.preventDefault) event.preventDefault(); return batchSelectAll(0); "><%text(_BATCH_DESELECTALL)%></a>

</div>

<!--/_batchoperations-->