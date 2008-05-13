<form method="post" action="#nucleus_cf">
<input type="hidden" name="body" value="" id="subsilver_form_body" />
<input type="hidden" name="dummy" value="" id="subsilver_form_dummy" />

<table border="0" cellpadding="2" cellspacing="2" width="100%">
  <tbody><tr>
	<td colspan="2" align="left" valign="bottom"><a class="maintitle" href="?itemid=<%itemid%>"><%itemtitle%></a>
        <%if(admin)%><a href="<%adminurl%>?action=itemedit&itemid=<%itemid%>"><img src="<%skinfile(images/lang_english/icon_edit.gif)%>"/ alt="edit" title="edit" /></a><%endif%>
        <br /><span class="gensmall"><b></b><br />
	  &nbsp; </span></td>
  </tr>
</tbody></table>

<table border="0" cellpadding="2" cellspacing="2" width="100%">
  <tbody><tr>
    <td align="left" nowrap="nowrap" valign="bottom">
      <span class="nav">
        <input type="image" name="subSilver_action" value="posting" src="<%skinfile(images/lang_english/post.gif)%>" alt="トピックの新規投稿" title="トピックの新規投稿" onclick="
document.getElementById('subsilver_form_body').value='';
document.getElementById('subsilver_form_dummy').name=this.name;
document.getElementById('subsilver_form_dummy').value=this.value;
return true;
" />
        &nbsp;&nbsp;
        <input type="image" name="subSilver_action" value="commentform" src="<%skinfile(images/lang_english/reply.gif)%>" alt="返信" title="返信" onclick="
document.getElementById('subsilver_form_body').value='';
document.getElementById('subsilver_form_dummy').name=this.name;
document.getElementById('subsilver_form_dummy').value=this.value;
return true;
" />
      </span>
    </td>
	<td align="left" valign="middle"><span class="nav">&nbsp;&nbsp;&nbsp;<a href="<%sitevar(url)%>" class="nav"><%sitevar(name)%> フォーラム一覧</a> -&gt; <a class="nav" href="<%self%>?catid=<%category(id)%>&amp;blogid=<%blogsetting(id)%>"><%category(name)%></a></span></td>
        <td align="right">
          <%if(hasplugin,DisplayComments)%><span class="nav">
          <%DisplayComments(pageswitch,info,ページ移動)%>&nbsp;
          <%DisplayComments(pageswitch,prev,前へ)%>
          <%DisplayComments(pageswitch,index,10)%>
          <%DisplayComments(pageswitch,next,次へ)%>
          </span><%endif%>
        </td>
  </tr>
</tbody></table>

<table class="forumline" border="0" cellpadding="3" cellspacing="1" width="100%">
	<tbody><tr align="right">
		<td class="catHead" colspan="2" height="28"><span class="nav"><a href="<%prevlink%>" class="nav">前のトピックを表示</a> :: <a href="<%nextlink%>" class="nav">次のトピックを表示</a> &nbsp;</span></td>

	</tr>

<%if(hasplugin,DisplayComments)%>
<%DisplayComments(comments,subSilver/index,10)%>
<%else%>
<%comments(subSilver/index)%>
<%endif%>
	
</tbody></table>

<table align="center" border="0" cellpadding="2" cellspacing="2" width="100%">
  <tbody><tr>
    <td align="left" nowrap="nowrap" valign="bottom">
      <span class="nav">
        <input type="image" name="subSilver_action" value="posting" src="<%skinfile(images/lang_english/post.gif)%>" alt="トピックの新規投稿" title="トピックの新規投稿" onclick="
document.getElementById('subsilver_form_body').value='';
document.getElementById('subsilver_form_dummy').name=this.name;
document.getElementById('subsilver_form_dummy').value=this.value;
return true;
" />
        &nbsp;&nbsp;
        <input type="image" name="subSilver_action" value="commentform" src="<%skinfile(images/lang_english/reply.gif)%>" alt="返信" title="返信" onclick="
document.getElementById('subsilver_form_body').value='';
document.getElementById('subsilver_form_dummy').name=this.name;
document.getElementById('subsilver_form_dummy').value=this.value;
return true;
" />
      </span>
    </td>
	<td align="right" nowrap="nowrap" valign="top">
          <%if(hasplugin,DisplayComments)%><span class="nav">
          <%DisplayComments(pageswitch,info,ページ移動)%>&nbsp;
          <%DisplayComments(pageswitch,prev,前へ)%>
          <%DisplayComments(pageswitch,index,10)%>
          <%DisplayComments(pageswitch,next,次へ)%>
          </span><%endif%>
        </td>
  </tr>
</tbody></table>

</form>

<%if(subSilver,postVar,subSilver_action=commentform)%>
<a name="subSilver_cf" id="subSilver_cf"></a>
<table class="forumline" border="0" cellpadding="3" cellspacing="1" width="100%">
<tbody><tr>
  <th class="thHead" colspan="2" height="25"><b>返信の投稿</b></th>
</tr>
<tr>
  <td class="commentform">
    <table align="center" border="0"><tr><td>
      <%commentform%>
    </td></tr></table>
  </td>
</tr>
</tbody>
</table>
<%endif%>
