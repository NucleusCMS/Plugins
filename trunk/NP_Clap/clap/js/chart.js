function drawUsingKeyAndParam(key, param) {
	var c = new Chart(document.getElementById(key));
	c.setDefaultType(CHART_LINE);
	c.setShowLegend(true);
	
	m = param['max'];
	if( m > 30 ) m = 30;

	c.setGridDensity(param['length']-0, m);
	c.setVerticalRange(0, param['max']-0);

	c.setHorizontalLabels( param['label']);
	c.add('Total Count','#40ff40', param['count']);
	c.add('IP Count','#4040FF', param['ipcount']);
	c.add('Msg Count','#FF4040', param['msgcount']);
	c.draw();
}

function updateMyChart(){
	var yearSelect = $('year');
	var monthSelect = $('month');

	var pars = '&ajax=1&action=plugin&name=Clap&type=chart';
	pars = pars + '&year=' + yearSelect.options[yearSelect.selectedIndex].value;
	pars = pars + '&month=' + monthSelect.options[monthSelect.selectedIndex].value;
	pars = pars + '&ticket=' + ticket;
	var myAjax = new Ajax.Request(
		actionurl, 
		{ method: 'get', parameters: pars, onSuccess: drawMyChart, onFailure: updateMyChartFailed }
	);
}

function drawMyChart(originalRequest){
	var param = eval( "(" + originalRequest.responseText + ")" );
	
	drawUsingKeyAndParam('key', param['key']);
	drawUsingKeyAndParam('daysOfMonth', param['dayOfMonth']);
	drawUsingKeyAndParam('daysOfWeek', param['dayOfWeek']);
	drawUsingKeyAndParam('hours', param['hours']);

	var yearSelect = $('year');
	var monthSelect = $('month');	
	var date = $('date');
	date.innerHTML =  yearSelect.options[yearSelect.selectedIndex].value + '/' + monthSelect.options[monthSelect.selectedIndex].value
}

function updateMyChartFailed(originalRequest){
	var d = $('message');
	d.innerHTML = 'Update Failed';
}

function setList(select, list, selected){
	for(i=0;i<list.length;i++){
		select.options[i] = new Option(list[i],list[i]);
	}
	select.selectedIndex = selected-0;
}

window.onload = function() {
	ieCanvasInit('chart/iecanvas.htc');
	var yearSelect = $('year');
	var monthSelect = $('month');

	var now = new Date();
	var yearArr = new Array();
	for(i=0;i<5;i++){
		yearArr.push( 1900 + now.getYear() - i + "" );
	}	

	setList(yearSelect, yearArr, 0);
	setList(monthSelect, ["1","2","3","4","5","6","7","8","9","10","11","12"], now.getMonth() );
	yearSelect.onchange = updateMyChart;
	monthSelect.onchange = updateMyChart;

	updateMyChart();
};
