<?php
	$strRel = '../../../'; 
	include($strRel . 'config.php');

?>
var plug_itemf_cid  = -1;
var plug_itemf_url  = "<?php echo $CONF['ActionURL'];?>";
var plug_itemf_xobj = false;

window.onload = function() { 
	var o = document.getElementsByName('catid');
	plug_itemf_init(o);
	//o.addEventListener('change', plug_itemf_change, true);
};


function plug_itemf_init(o) {
	plug_itemf_cid = o[0].value;
	plug_itemf_xmlhttp();
}

function plug_itemf_change(o) {
	plug_itemf_cid = o[o.selectedIndex].value;
	plug_itemf_xmlhttp();
}

/*function plug_itemf_change(e) {
	alert('event:'+ e.target.selectedIndex);
	var plug_itemf_cid = e.target[e.target.selectedIndex].value;
	plug_itemf_set();
}*/

function plug_itemf_set(data){
	var v = data.split("[[[ itemformat_splitter ]]]");
	document.getElementById('inputtitle').value = v[0];
	document.getElementById('inputbody').value  = v[1];
	document.getElementById('inputmore').value  = v[2];
}

function plug_itemf_receive()
{
	if (plug_itemf_xobj.readyState == 4 && plug_itemf_xobj.status == 200) 
	{
		plug_itemf_set(plug_itemf_xobj.responseText);
	}
}

function plug_itemf_xmlhttp_connect()
{
	var pname = 'ItemFormat';
	var url = plug_itemf_url + '?action=plugin&name=' + pname + '&type=get&cid=' + plug_itemf_cid;
	
	plug_itemf_xobj.onreadystatechange=plug_itemf_receive
	plug_itemf_xobj.open("GET",url,true)
	plug_itemf_xobj.send('')
}

function plug_itemf_xmlhttp() {
	try 
	{
		plug_itemf_xobj = new ActiveXObject("Msxml2.XMLHTTP");
	} 
	catch (e) 
	{
		try 
		{
			plug_itemf_xobj = new ActiveXObject("Microsoft.XMLHTTP");
		} 
		catch (e) 
		{
			plug_itemf_xobj = false;
		}
	}

	if (! plug_itemf_xobj && typeof XMLHttpRequest!='undefined') 
	{
		plug_itemf_xobj = new XMLHttpRequest();
	}
	
	if (plug_itemf_xobj)
	{
		plug_itemf_xmlhttp_connect();
	}
}

