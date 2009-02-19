<%if(admin,conf,DisableJsTools=0)%>
  <div class="jsbuttonbar">
    <%admin(jsbutton,cut,cutThis(),_ADD_CUT_TT,Ctrl + X)%>
    <%admin(jsbutton,copy,copyThis(),_ADD_COPY_TT,Ctrl + C)%>
    <%admin(jsbutton,paste,pasteThis(),_ADD_PASTE_TT,Ctrl + V)%>
    <%admin(jsbutton,spacer)%>
    <%admin(jsbutton,bold,boldThis(),_ADD_BOLD_TT,Ctrl + Shift + B)%>
    <%admin(jsbutton,italic,italicThis(),_ADD_ITALIC_TT,Ctrl + Shift + I)%>
    <%admin(jsbutton,link,ahrefThis(),_ADD_HREF_TT,Ctrl + Shift + A)%>
    <%admin(jsbutton,spacer)%>
    <%admin(jsbutton,alignleft,alignleftThis(),_ADD_ALIGNLEFT_TT)%>
    <%admin(jsbutton,alignright,alignrightThis(),_ADD_ALIGNRIGHT_TT)%>
    <%admin(jsbutton,aligncenter,aligncenterThis(),_ADD_ALIGNCENTER_TT)%>
    <%admin(jsbutton,spacer)%>
    <%admin(jsbutton,left,leftThis(),_ADD_LEFT_TT)%>
    <%admin(jsbutton,right,rightThis(),_ADD_RIGHT_TT)%>
    <%admin(jsbutton,media,addMedia(),_ADD_MEDIA_TT,Ctrl + Shift + M)%>
    <%admin(jsbutton,preview,showedit(),_ADD_PREVIEW_TT)%>
  </div>
<%elseif(admin,conf,DisableJsTools=2)%>
  <div class="jsbuttonbar">
    <%admin(jsbutton,bold,boldThis(),_ADD_BOLD_TT)%>
    <%admin(jsbutton,italic,italicThis(),_ADD_ITALIC_TT)%>
    <%admin(jsbutton,link,ahrefThis(),_ADD_HREF_TT)%>
    <%admin(jsbutton,spacer)%>
    <%admin(jsbutton,alignleft,alignleftThis(),_ADD_ALIGNLEFT_TT)%>
    <%admin(jsbutton,alignright,alignrightThis(),_ADD_ALIGNRIGHT_TT)%>
    <%admin(jsbutton,aligncenter,aligncenterThis(),_ADD_ALIGNCENTER_TT)%>
    <%admin(jsbutton,spacer)%>
    <%admin(jsbutton,left,leftThis(),_ADD_LEFT_TT)%>
    <%admin(jsbutton,right,rightThis(),_ADD_RIGHT_TT)%>
    <%admin(jsbutton,media,addMedia(),_ADD_MEDIA_TT)%>
  </div>
<%endif%>
