<!--blogsettings-->

<p><a href="?action=overview">(<%text(_BACKHOME)%>)</a></p>

<%ifnot(blogadmin)%>
  <%text(_ERROR_DISALLOWED)%>
<%else%>

<script type="text/javascript" src="javascript/numbercheck.js"></script>

<h2><%text(_EBLOG_TITLE)%>: '<a href="<%blogsetting(url)%>"><%blogsetting(name)%></a>'</h2>

<h3><%text(_EBLOG_TEAM_TITLE)%></h3>

<p>Members currently on your team: <%admin(blogsettingsteam)%></p>
<p><form method="get" action="">
  <input type="hidden" name="action" value="manageteam" />
  <input type="hidden" name="blogid" value="<%blogsetting(id)%>" />
  <input type="submit" value="<%text(_EBLOG_TEAM_TEXT)%>" />
</form></p>

<h3><%text(_EBLOG_SETTINGS_TITLE)%></h3>
<form method="post" action="<%self%>"><div>
<input type="hidden" name="action" value="blogsettingsupdate" />
<%admin(ticket,hidden)%>
<input type="hidden" name="blogid" value="<%blogsetting(id)%>" />

<table><tr>
  <td><%text(_EBLOG_NAME)%></td>
  <td><input name="name" tabindex="10" size="40" maxlength="60" value="<%admin(blogsetting,Name)%>" /></td>
</tr><tr>
  <td><%text(_EBLOG_SHORTNAME)%> <%admin(help,shortblogname)%>
		<%text(_EBLOG_SHORTNAME_EXTRA)%>
  </td>
  <td><input name="shortname" tabindex="20" maxlength="15" size="15" value="<%admin(blogsetting,ShortName)%>" /></td>
</tr><tr>
  <td><%text(_EBLOG_DESC)%></td>
  <td><input name="desc" tabindex="30" maxlength="200" size="40" value="<%admin(blogsetting,Description)%>" /></td>
</tr><tr>
  <td><%text(_EBLOG_URL)%></td>
  <td><input name="url" tabindex="40" size="40" maxlength="100" value="<%admin(blogsetting,URL)%>" /></td>
</tr><tr>
  <td><%text(_EBLOG_DEFSKIN)%> <%admin(help,blogdefaultskin)%></td>
  <td><%admin(blogsettingsskin)%></td>
</tr><tr>
  <td><%text(_EBLOG_LINEBREAKS)%> <%admin(help,convertbreaks)%></td>
  <td><%admin(inputyesno,convertbreaks,blogsetting/bconvertbreaks,tabindex=55)%></td>
</tr><tr>
  <td><%text(_EBLOG_ALLOWPASTPOSTING)%> <%admin(help,allowpastposting)%></td>
  <td><%admin(inputyesno,allowpastposting,blogsetting/ballowpast,tabindex=57)%></td>
</tr><tr>
  <td><%text(_EBLOG_DISABLECOMMENTS)%></td>
  <td><%admin(inputyesno,comments,blogsetting/bcomments,tabindex=60)%></td>
</tr><tr>
  <td><%text(_EBLOG_ANONYMOUS)%></td>
  <td><%admin(inputyesno,public,blogsetting/bpublic,tabindex=70)%></td>
</tr><tr>
  <td><%text(_EBLOG_REQUIREDEMAIL)%></td>
  <td><%admin(inputyesno,reqemail,blogsetting/breqemail,tabindex=72)%></td>
</tr><tr>
  <td><%text(_EBLOG_NOTIFY)%> <%admin(help,blognotify)%></td>
  <td><input name="notify" tabindex="80" maxlength="60" size="40" value="<%admin(blogsetting,NotifyAddress)%>" /></td>
</tr><tr>
  <td><%text(_EBLOG_NOTIFY_ON)%></td>
  <td>
    <%admin(inputcheckbox,notifyComment,blogsetting/notifyOnComment,value=3/tabindex=81)%><label for="notifyComment"><%text(_EBLOG_NOTIFY_COMMENT)%></label><br />
    <%admin(inputcheckbox,notifyVote,blogsetting/notifyOnVote,value=5/tabindex=82)%><label for="notifyVote"><%text(_EBLOG_NOTIFY_KARMA)%></label><br />
    <%admin(inputcheckbox,notifyNewItem,blogsetting/notifyOnNewItem,value=7/tabindex=83)%><label for="notifyNewItem"><%text(_EBLOG_NOTIFY_ITEM)%></label></td>
</tr><tr>

<%if(admin,eventSubscribed,SendPing)%>
    <td><%text(_EBLOG_PING)%> <%admin(help,sendping)%></td>
    <td><%admin(inputyesno,sendping,blogsetting/bsendping,tabindex=85)%></td>
  </tr><tr>
<%endif%>

  <td><%text(_EBLOG_MAXCOMMENTS)%> <%admin(help,blogmaxcomments)%></td>
  <td><input name="maxcomments" tabindex="90" size="3" value="<%admin(blogsetting,MaxComments)%>" /></td>
</tr><tr>
  <td><%text(_EBLOG_UPDATE)%> <%admin(help,blogupdatefile)%></td>
  <td><input name="update" tabindex="100" size="40" maxlength="60" value="<%admin(blogsetting,UpdateFile)%>" /></td>
</tr><tr>
  <td><%text(_EBLOG_DEFCAT)%></td>
  <td><%admin(blogsettingscategory)%></td>
</tr><tr>
  <td><%text(_EBLOG_OFFSET)%> <%admin(help,blogtimeoffset)%>
    <br /><%text(_EBLOG_STIME)%> <b><%admin(date,H:i,,servertime)%></b>
    <br /><%text(_EBLOG_BTIME)%> <b><%admin(date,H:i,,blogtime)%></b>
  </td>
  <td><input name="timeoffset" tabindex="120" size="3" value="<%admin(blogsetting,TimeOffset)%>" /></td>
</tr><tr>
  <td><%text(_EBLOG_SEARCH)%> <%admin(help,blogsearchable)%></td>
  <td><%admin(inputyesno,searchable,blogsetting/Searchable,tabindex=122)%></td>
</tr>
  <%admin(insertPluginOptions,blog)%>
<tr>
<tr>
  <th colspan="2"><%text(_EBLOG_CHANGE)%></th>
</tr><tr>
  <td><%text(_EBLOG_CHANGE)%></td>
  <td><input type="submit" tabindex="130" value="<%text(_EBLOG_CHANGE_BTN)%>" onclick="return checkSubmit();" /></td>
</tr></table>
</div></form>

<h3><%text(_EBLOG_CAT_TITLE)%></h3>

<%admin(set,tabindex,200)%>
<%admin(blogsettingscategorylist)%>

<form action="<%self%>" method="post"><div>
<input name="action" value="categorynew" type="hidden" />
<%admin(ticket,hidden)%>
<input name="blogid" value="<%admin(blogsetting,ID)%>" type="hidden" />

<table><tr>
  <th colspan="2"><%text(_EBLOG_CAT_CREATE)%></th>
</tr><tr>
  <td><%text(_EBLOG_CAT_NAME)%></td>
  <td><input name="cname" size="40" maxlength="40" tabindex="300" /></td>
</tr><tr>
  <td><%text(_EBLOG_CAT_DESC)%></td>
  <td><input name="cdesc" size="40" maxlength="200" tabindex="310" /></td>
</tr><tr>
  <td><%text(_EBLOG_CAT_CREATE)%></td>
  <td><input type="submit" value="<%text(_EBLOG_CAT_CREATE)%>" tabindex="320" /></td>
</tr></table>

</div></form>

<%callback(BlogSettingsFormExtras)%>

<%endif%>

<!--blogsettings-->