function getSelectedValues (select) {
  var r = new Array();
  for (var i = 0; i < select.options.length; i++)
    if (select.options[i].selected)
      r[r.length] = select.options[i].value;
  return r;
}


function submitCatOrder(){
    selectAllOptions(document.ordform.order);
    document.ordform.orderList.value = getSelectedValues(document.ordform.order);
    document.ordform.order.value = '';

}


function selectAllOptions(obj) {
	for (var i=0; i<obj.options.length; i++) {
		obj.options[i].selected = true;
		}
	}

function swapOptions(obj,i,j) {
	var o = obj.options;
	var i_selected = o[i].selected;
	var j_selected = o[j].selected;
	var temp = new Option(o[i].text, o[i].value, o[i].defaultSelected, o[i].selected);
	var temp2= new Option(o[j].text, o[j].value, o[j].defaultSelected, o[j].selected);
	o[i] = temp2;
	o[j] = temp;
	o[i].selected = j_selected;
	o[j].selected = i_selected;
	}

function moveOptionUp(obj) {
	// If > 1 option selected, do nothing
	var selectedCount=0;
	for (i=0; i<obj.options.length; i++) {
		if (obj.options[i].selected) {
			selectedCount++;
			}
		}
	if (selectedCount > 1) {
		return;
		}
	// If this is the first item in the list, do nothing
	var i = obj.selectedIndex;
	if (i == 0) {
		return;
		}
	swapOptions(obj,i,i-1);
	obj.options[i-1].selected = true;
	}

function moveOptionDown(obj) {
	// If > 1 option selected, do nothing
	var selectedCount=0;
	for (i=0; i<obj.options.length; i++) {
		if (obj.options[i].selected) {
			selectedCount++;
			}
		}
	if (selectedCount > 1) {
		return;
		}
	// If this is the last item in the list, do nothing
	var i = obj.selectedIndex;
	if (i == (obj.options.length-1)) {
		return;
		}
	swapOptions(obj,i,i+1);
	obj.options[i+1].selected = true;
	}

//<sato(na)0.402j>
function setScatDat(id, sname, sdesc) {
	this.id     = id;
	this.sname  = sname;
	this.sdesc  = sdesc;
}
function scatListRefresh(scatDat){
	//del
	optionsLength = document.ordform.order.options.length;
	for (i=0; i<=optionsLength; i++) document.ordform.order.options[0] = null;
	//add
	for(var i = 0; i < scatDat.length; i++) {
		label = scatDat[i].id + " [ " + scatDat[i].sname + " ] " + scatDat[i].sdesc;
		document.ordform.order.options[i] = new Option(label, scatDat[i].id);
	}
}
function sortidASC(a, b) {
	if (b.id == a.id) return 0;
	return a.id - b.id;
}
function sortidDESC(a, b) {
	if (b.id == a.id) return 0;
	return b.id - a.id;
}
function sortsnameASC(a, b) {
	if (b.sname == a.sname) return 0;
	return (a.sname > b.sname) ? 1 : -1;
}
function sortsnameDESC(a, b) {
	if (b.sname == a.sname) return 0;
	return (b.sname > a.sname) ? 1 : -1;
}
function sortsdescASC(a, b) {
	if (b.sdesc == a.sdesc) return 0;
	return (a.sdesc > b.sdesc) ? 1 : -1;
}
function sortsdescDESC(a, b) {
	if (b.sdesc == a.sdesc) return 0;
	return (b.sdesc > a.sdesc) ? 1 : -1;
}
//</sato(na)0.402j>