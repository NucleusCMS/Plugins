<%callback(PrepareItemForEdit)%>

<h2><%text(_EDIT_ITEM)%></h2>

<%admin(insertJavaScriptInfo)%>
<script type="text/javascript" src="<%skinfile(javascript/numbercheck.js)%>"></script>
<script type="text/javascript" src="<%skinfile(javascript/xmlhttprequest.js)%>"></script>

<form id="addform" method="post" action="<%self%>" >

<div>

<input type="hidden" name="action" value="itemupdate" />
<input name="itemid" value="<%contents(itemid)%>" type="hidden" />
<input type="hidden" name="draftid" value="0" />
<%admin(ticket,hidden)%>

<table>
  <tr>
    <th colspan="2"><%text(_EDIT_ITEM)%></th>
  </tr><tr>
    <td><%text(_AUTOSAVEDRAFT)%></td>
    <td><span id="info"><%text(_AUTOSAVEDRAFT_NOTYETSAVED)%></span></td>
  </tr><tr>
    <td><%text(_EDIT_ORIG_AUTHOR)%></td>
    <td><%contents(author)%></td>
  </tr><tr>
    <td><%text(_ADD_TITLE)%></td>
    <td><input 
      <%admin(set,which,title)%><%parsedinclude(actions/_jsinput.inc)%>
      tabindex="10"
      size="60" 
      maxlength="160" 
      value="<%contents(title)%>"
      /></td>
  </tr><tr>
    <td>
      <%text(_ADD_BODY)%> <%admin(help,additem)%>
    </td>
    <td>
      <%parsedinclude(actions/_jsbuttonbar.inc)%>
      <textarea
        <%admin(set,which,body)%><%parsedinclude(actions/_jsinput.inc)%>
        tabindex="20"
        cols="60"
        rows="20"><%contents(body)%></textarea>
    </td>
  </tr><tr id="edit" style="display:none;">
    <td><%text(_ADD_PREVIEW)%></td>
    <td><b><span id="prevtitle">&#160;</span></b><br />
      <span id="prevbody">&#160;</span><br />
      <span id="prevmore">&#160;</span><br />
    </td>
  </tr><tr>
  
<%if(blogsetting,bcomments,1)%>
    <td><%text(_ADD_DISABLE_COMMENTS)%></td>
    <td>
      <input name="closed" type="radio" tabindex="30" value="1" <%if(admin,set,closed=1%>checked="checked"<%endif%> id="closed_yes" /><label for="closed_yes"><%text(_YES)%></label>
      <input name="closed" type="radio" tabindex="30" value="0" <%if(admin,set,closed=0%>checked="checked"<%endif%> id="closed_no" /><label for="closed_no"><%text(_NO)%></label>
      <br />
      <small><%text(_EDIT_COMMENTSNOTE)%></small>	
    </td>
  </tr><tr>
<%endif%>


    <td><%text(_ADD_CATEGORY)%></td>
    <td><%admin(additemcategory)%></td>
  </tr><tr>
    <td><%text(_EDIT_SUBMIT)%></td>
    <td>
      <input tabindex="60" type="submit" value="<%text(_EDIT_SUBMIT)%>" 
        onclick="return checkSubmit();" />
<%if(set,draft=1)%>
      <br /><br />
      <input name="actiontype" value="addnow" type="radio" id="act_now" /><label for="act_now" tabindex="61"><%text(_ADD_ADDNOW)%></label>
      <br />
      <input name="actiontype" value="addfuture" type="radio" id="act_future" tabindex="61" /><label for="act_future"><%text(_ADD_ADDLATER)%></label> <%helplink(future)%>
      <div class="indent">
        <%text(_ADD_PLACE_ON)%>
        <input id="inputyear" name="year" tabindex="71" size="4" value="<%currenttime(year)%>" />/
        <input id="inputmonth" name="month" tabindex="72" size="2" value="<%currenttime(mon)%>" />/
        <input id="inputday" name="day" tabindex="73" size="2" value="<%currenttime(mday)%>" />&nbsp;
        
        <input id="inputhour" name="hour" tabindex="74" size="2" value="<%currenttime(hours)%>" />:
        <input id="inputminutes" name="minutes" tabindex="75" size="2" value="<%currenttime(minutes)%>" />
        (yyyy/mm/dd hh:mm)
        <%if(admin,blogsetting,ballowpast=0)%>
          <br />
          <%text(_ADD_NOPASTDATES)%>
        <%endif%>
      </div>
      <input name="actiontype" value="adddraft" type="radio" checked='checked' id="act_draft" tabindex="61" /><label for="act_draft"><%text(_EDIT_BACKTODRAFTS)%></label> <%helplink(draft)%>
      <br />
      <input name="actiontype" value="delete" type="radio" id="act_delete" tabindex="61" /><label for="act_delete"><%text(_BMLET_DELETE)%></label>
<%else%>

      <br /><br />

      <input name="actiontype" value="addnow" type="radio" checked='checked' id="act_now" tabindex="61" /><label for="act_now"><%text(_ADD_ADDNOW)%></label>
      <br />
    <%if(blogsetting,ballowpast,1)%>
      <input name="actiontype" value="addfuture" type="radio" id="act_future" tabindex="61" /><label for="act_future" ><%text(_ADD_ADDLATER)%></label> <%admin(help,future)%>
      <div class="indent" onkeydown="document.getElementById('act_future').checked='checked'">
        <%text(_ADD_PLACE_ON)%>
        <input id="inputyear" name="year" tabindex="63" size="4" value="<%admin(date,Y)%>" /> -
        <input id="inputmonth" name="month" tabindex="64" size="2" value="<%admin(date,n)%>" /> -
        <input id="inputday" name="day" tabindex="65" size="2" value="<%admin(date,j)%>" />
        &nbsp;&nbsp;
        <input id="inputhour" name="hour" tabindex="66" size="2" value="<%admin(date,H)%>" /> :
        <input id="inputminutes" name="minutes" tabindex="67" size="2" value="<%admin(date,i)%>" />
        <br />
        (yyyy-mm-dd hh:mm)
      </div>
    <%endif%>
      <input name="actiontype" tabindex="68" value="adddraft" type="radio" id="act_draft" /><label for="act_draft"><%text(_ADD_ADDDRAFT)%></label> <%admin(help,draft)%>
<%endif%>
    </td>
  </tr><tr>
    <td><%text(_ADD_PLUGIN_EXTRAS)%></td>
    <td>
      <%callback(AddItemFormExtras)%>
    </td>
  </tr>
      <%admin(insertPluginOptions,item)%>  
    <tr>
      <th colspan="2"><%text(_ADD_MORE)%></th>
  </tr><tr>
    <td><%text(_ADD_MORE)%>
      <br /><%admin(help,extended)%>
    </td>
    <td>
      <%parsedinclude(actions/_jsbuttonbar.inc)%>
      <textarea 
        <%admin(set,which,more)%><%parsedinclude(actions/_jsinput.inc)%>      
        tabindex="70"
        cols="60"
        rows="20"
        ><%contents(more)%></textarea>
    </td>
  </tr><tr>
    <td><%text(_SUBMIT)%></td>
    <td>
      <input tabindex="75" type="submit" value="<%text(_EDIT_SUBMIT)%>" 
        onclick="return checkSubmit();" />
      <input tabindex="80" type="button" name="autosavenow" value="<%text(_AUTOSAVEDRAFT_NOW)%>"
        onclick="autoSaveDraft();" />
    </td>
  </tr>
</table>

</div>
</form>

<script type="text/javascript">
  var xmlhttprequest = new Array();
  xmlhttprequest[0] = createHTTPHandler(); // AutoDraft handler
  xmlhttprequest[1] = createHTTPHandler(); // UpdateTicket handler
  var seconds = now(); // Last AutoDraft time
  var checks = 0; // Number of checks since last AutoDraft
  var addform = document.getElementById('editform'); // The form id
  var goal = document.getElementById('info'); // The html div id where 'Last saved: date time' must come
  var goalurl = '../action.php'; // The PHP file where the content must be posted to
  var lastsavedtext = '<%text(_AUTOSAVEDRAFT_LASTSAVED)%>'; // The language variable for 'Last saved'
  var formtype = 'edit'; // Add or edit form
</script>