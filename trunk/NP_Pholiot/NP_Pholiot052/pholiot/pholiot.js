function showPholiot(p) {
	rgb = '0x' + p.bgcolor.substr(1);
	document.write('<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"');
	document.write(' codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"');
	document.write(' width="' + p.width + '"  height="' + p.height + '" id="pholiot">' + "\n");
	document.write('<param name=movie value="' + p.url + '" />');
	document.write(' <param name=quality value="high" />');
	document.write('<param name=menu value="' + p.menu + '">');
	document.write(' <param name=bgcolor value="' + p.bgcolor + '" />');
	document.write(' <param name="flashvars" value="xmluri=' + p.data_url + '&rgb=' + rgb + '&sw='+p.width+'&sh='+p.height+'" />' + "\n");
	document.write('<embed src="' + p.url + '"');
	document.write(' quality="high" menu="' + p.menu + '" bgcolor="' + p.bgcolor + '" width="' + p.width + '" height="' + p.height + '" name="pholiot"');
	document.write(' pluginspage="http://www.macromedia.com/go/getflashplayer"');
	document.write(' flashvars="xmluri=' + p.data_url + '&rgb=' + rgb + '&sw='+p.width+'&sh='+p.height+'"></embed>' + "\n");
	document.write('</object>');
}

function pholiotImageOpen(uri,width,height,close){
	newwin = window.open("", "pholiotimg", "toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=no,resizable=no,width="+width+",height="+height);
	newwin.window.focus();
	newwin.document.open();
	newwin.document.write('<html><head><title>Pholiot</title></head><body style="margin:0px;padding:0px">');
	if (close) {
		newwin.document.write('<a href="javascript:window.close()">');
	}
	newwin.document.write('<img src="'+uri+'" width="'+width+'" height="'+height+'" style="border:none" />');
	if (close) {
		newwin.document.write('</a>');
	}
	newwin.document.write('</body></html>');
	newwin.document.close();
}

