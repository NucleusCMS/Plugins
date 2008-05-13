<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja-JP" lang="ja-JP">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<%text(_CHARSET)%>" />
<meta http-equiv="Content-Style-Type" content="text/css" />

<link rel="alternate" type="application/rss+xml" title="RSS" href="xml-rss2.php" />

<link rel="stylesheet" type="text/css" href="<%skinfile(subSilver.css)%>" />
<link rel="stylesheet" type="text/css" href="<%skinfile(nucleus.css)%>" />

<%if(skintype,index)%>
  <%if(subSilver,getVar,blogid=)%>
    <title><%subSilver(strip_tags,sitevar,name)%> :: 一覧</title>
  <%elseif(category)%>
    <title><%subSilver(strip_tags,begin)%><%blogsetting(name)%> :: <%category(name)%><%subSilver(strip_tags,end)%></title>
  <%else%>
    <title><%subSilver(strip_tags,begin)%><%sitevar(name)%> :: <%blogsetting(name)%><%subSilver(strip_tags,end)%></title>
  <%endif%>
<%elseif(skintype,item)%>
  <title><%subSilver(strip_tags,begin)%><%category(name)%> :: <%itemtitle(attribute)%><%subSilver(strip_tags,end)%></title>
<%elseif(skintype,member)%>
  <%if(subSilver,intGlobalVar,memberid=0)%>
    <title><%subSilver(strip_tags,begin)%><%sitevar(name)%> :: メンバーリスト<%subSilver(strip_tags,end)%></title>
  <%else%>
    <title><%subSilver(strip_tags,begin)%><%sitevar(name)%> :: <%member(realname)%><%subSilver(strip_tags,end)%></title>
  <%endif%>
<%else%>
    <title><%subSilver(strip_tags,sitevar,name)%></title>
<%endif%>

</head>
