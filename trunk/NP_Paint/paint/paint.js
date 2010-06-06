// vim: tabstop=2:shiftwidth=2

/**
  * paint.js ($Revision: 1.15 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * 
  * $Id: paint.js,v 1.15 2010/06/06 11:44:19 hsur Exp $
*/

/*
  * Copyright (C) 2005-2010 CLES. All rights reserved.
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * 
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  * 
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
  * 
  * In addition, as a special exception, cles( http://blog.cles.jp/np_cles ) gives
  * permission to link the code of this program with those files in the PEAR
  * library that are licensed under the PHP License (or with modified versions
  * of those files that use the same license as those files), and distribute
  * linked combinations including the two. You must obey the GNU General Public
  * License in all respects for all of the code used other than those files in
  * the PEAR library that are licensed under the PHP License. If you modify
  * this file, you may extend this exception to your version of the file,
  * but you are not obligated to do so. If you do not wish to do so, delete
  * this exception statement from your version.
*/

function getLoginkey()
{
	var url = 'http://'+location.host+location.pathname;
	var pars = 'action=plugin&name=Paint&type=getLoginkey';
	
	//var d = $('message');
	//d.innerHTML = url + '?' + pars;

	var myAjax = new Ajax.Request(
		url, 
		{ method: 'get', parameters: pars, onSuccess: setLoginkey, onFailure: getLoginkeyFailed }
	);
}

function setLoginkey(originalRequest)
{
	var xmldoc = originalRequest.responseXML;
	var loginkey = xmldoc.getElementsByTagName('loginkey')[0].firstChild.nodeValue;

	var pat = new RegExp('loginkey=[^&=]+', 'i');
	header = header.replace(pat, 'loginkey=' + loginkey);
	
	var d = $('message');
	d.innerHTML = 'Ticket update OK -> ' + header;
	document.paintbbs.str_header = header;
}

function getLoginkeyFailed(originalRequest)
{
	var d = $('message');
	d.innerHTML = 'Ticket update NG';
}