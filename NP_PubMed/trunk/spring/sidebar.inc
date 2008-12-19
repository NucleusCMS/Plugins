<!-- search form -->
<div class="sidebar">
<dl class="sidebardl">
<dt>Search</dt>
<dd><%searchform%></dd>
</dl>
</div>

<!-- page menu -->
<div class="sidebar">
<dl class="sidebardl">
<dt>Navigation</dt>
<dd><a href="http://www.ncbi.nlm.nih.gov/sites/entrez?db=PubMed" onclick="window.open(this.href);return false;">NIH PubMed site</a></dd>
<!-- admin link, only visible if logged in -->
<%if(loggedin)%>
<dd><a href="<%adminurl%>plugins/pubmed/?blogid=<%blogsetting(id)%>">PubMed search</a></dd>
<dd><a href="<%adminurl%>" title="Admin Area">Admin</a></dd>
<%endif%>
</dl>
</div>

<!-- category list, not on error or member page -->
<%if(skintype,error)%><%else%>
<%if(skintype,member)%><%else%>
<div class="sidebar">
<dl class="sidebardl">
<dt>Categories</dt>
<%categorylist(spring/index)%>
</dl>
</div>
<%endif%><%endif%>

<!-- bloglist-->
<!--
<div class="sidebar">
<dl class="sidebardl">
<dt>Blogs</dt>
<%bloglist(spring/index)%>
</dl>
</div>
-->

<!-- login form -->
<div class="sidebar">
<dl class="sidebardl">
<dt>Login</dt>
<dd><%loginform%></dd>
</dl>
</div>


<!-- links -->
<!--
<div class="sidebar">
<dl class="sidebardl">
<dt>Links</dt>
<dd><a href="http://nucleuscms.org" title="Nucleus CMS Home">nucleuscms.org</a></dd>
<dd><a href="http://docs.nucleuscms.org/" title="Nucleus CMS Documentation">docs.nucleuscms.org</a></dd>
<dd><a href="http://forum.nucleuscms.org" title="Nucleus CMS Support Forum">forum.nucleuscms.org</a></dd>
<dd><a href="http://wiki.nucleuscms.org/" title="Nucleus CMS Wiki">wiki.nucleuscms.org</a></dd>
<dd><a href="http://skins.nucleuscms.org/" title="Nucleus CMS Skins">skins.nucleuscms.org</a></dd>
<dd><a href="http://wiki.nucleuscms.org/plugin" title="Nucleus CMS Plugins">plugins.nucleuscms.org</a></dd>
<dd><a href="http://dev.nucleuscms.org/" title="Nucleus Developer Network">dev.nucleuscms.org</a></dd>
</dl>
</div>
-->

<!-- Powered by -->
<div class="sidebar">
<div class="sidebardl centerize">
<%nucleusbutton(images/nucleus.gif,85,31)%>
</div>
</div>