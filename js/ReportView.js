// On pageload
$(function(){
	// If viewing a report, then fetch the report
	if ($('#report_parent_div').length) {
		var pagenum = getParameterByName('pagenum') == '' ? '1' : getParameterByName('pagenum');
		if (page != 'surveys/index.php') {
			fetchReportAjax(getParameterByName('report_id'), pagenum, getLiveFilterUrlFromParams());
		} else {
			fetchReportAjax(report_id, pagenum, "&__report="+getParameterByName('__report')+getLiveFilterUrlFromParams());
		}
	}
});

function loadReportNewPage(pagenum, preventLoadAll) {
	if (typeof preventLoadAll == 'undefined') preventLoadAll = false;
	// Get report_id
	if (typeof report_id == 'undefined') report_id = getParameterByName('report_id');
	// Get live filter URL
	var dynamicFiltersUrl = getLiveFilterUrl();
	// Stats&Charts page or table report page?
	if (getParameterByName('stats_charts') == '1') {
		// STATS (load new page)
		window.location.href = app_path_webroot+'DataExport/index.php?pid='+pid+'&stats_charts=1&report_id='+report_id+(report_id=='SELECTED' ? '&instruments='+getParameterByName('instruments')+'&events='+getParameterByName('events') : '')+dynamicFiltersUrl;
	} else {
		// TABLE REPORT	(reload via AJAX)
		if (preventLoadAll && pagenum == 'ALL') {
			$('.report_page_select').val(1);
			simpleDialog("We're sorry, but it appears that you will not be able to view ALL pages of this report at the same time. The report is simply too large to view all at once. You may only view each page individually. Our apologies for this inconvenience.");
			return;
		}
		// Show page number in progress text
		if (pagenum == '0') {
			// Maintain the ALL pages option if currently showing all pages, else revert to page 1
			pagenum = (getParameterByName('pagenum') == 'ALL') ? 'ALL' : 1;
		} else if (isNumeric(pagenum)) {
			$('#report_load_progress_pagenum_text').show();
			$('#report_load_progress_pagenum').html(pagenum);
		}
		$('#report_parent_div').html('');
		// Change URL for last tab and for browser address bar
		if (page != 'surveys/index.php') {
			var baseUrl = app_path_webroot+'DataExport/index.php?pid='+pid+'&report_id='+report_id+(report_id=='SELECTED' ? '&instruments='+getParameterByName('instruments')+'&events='+getParameterByName('events') : '');
			var newUrl = baseUrl+'&pagenum='+pagenum+dynamicFiltersUrl;
			$('#sub-nav li:last a').attr('href', newUrl);
		} else {
			var baseUrl = dirname(dirname(app_path_webroot))+'/surveys/index.php?__report='+getParameterByName('__report');
			var newUrl = baseUrl+'&pagenum='+pagenum+dynamicFiltersUrl;
			dynamicFiltersUrl += "&__report="+getParameterByName('__report');
		}
		modifyURL(newUrl);
		// Run report
		setTimeout(function(){
			fetchReportAjax(report_id, pagenum, dynamicFiltersUrl);
		}, 50);
	}
}

// Get URL for appending live filters to report AJAX URL (obtain from main page URL params)
function getLiveFilterUrlFromParams() {
	var dynamicFiltersUrl = '';
	var this_dyn_filter;
	if (max_live_filters == null) max_live_filters = 3;
	for (var i=1; i<=max_live_filters; i++) {
		if (getParameterByName('lf'+i) != '') {
			dynamicFiltersUrl += '&lf'+i+'='+getParameterByName('lf'+i);
		}
	}
	return dynamicFiltersUrl;
}

// Reset the live filters on a report and reload the report
function resetLiveFilters() {
	$('select[id^="lf"]').val('');
	loadReportNewPage(0);
}

// Determine if at least one live filter in a report is selected (return boolean)
function liveFiltersSelected() {
	var this_dyn_filter;
	if (max_live_filters == null) max_live_filters = 3;
	for (var i=1; i<=max_live_filters; i++) {
		this_dyn_filter = $('#lf'+i);
		if (this_dyn_filter.length && this_dyn_filter.val() != '') {
			return true;
		}
	}
	return false;
}

// Get URL for appending live filters to report AJAX URL (obtain from drop-downs)
function getLiveFilterUrl() {
	var dynamicFiltersUrl = '';
	var this_dyn_filter;
	if (max_live_filters == null) max_live_filters = 3;
	for (var i=1; i<=max_live_filters; i++) {
		this_dyn_filter = $('#lf'+i);
		if (this_dyn_filter.length && this_dyn_filter.val() != '') {
			dynamicFiltersUrl += '&lf'+i+'='+this_dyn_filter.val();
		}
	}
	return dynamicFiltersUrl;
}

// Show spinner icon as plot spaceholder (using Google Chart Tools)
function showSpinner(field) {
	var currentDivHeight = $('#plot-'+field).height();
	$('#plot-'+field).html('<div style="text-align:center;width:500px;height:'+currentDivHeight+'px;"><img title="Loading..." alt="Loading..." src="'+app_path_images+'progress.gif"></div>');
}


// Retrieve variable's value from URL
function getParameterByName(name,use_parent_window) {
	if (use_parent_window == null) use_parent_window = false;
	var loc = (use_parent_window ? window.opener.location.href : window.location.href);
	name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	var regexS = "[\\?&]"+name+"=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec( loc );
	if( results == null )
		return "";
	else
		return strip_tags(results[1]);
}

// Render Multiple Box Plots/Bar Charts (using Google Chart Tools)
function renderCharts(nextfields,charttype,results_code_hash) {
	// Do initial checking/setting of parameters
	if (nextfields.length < 1) return;
	if (isSurveyPage == null) isSurveyPage = false;
	if (charttype == null) charttype = '';
	if (results_code_hash == null || !isSurveyPage) results_code_hash = '';
	var hash = getParameterByName('s');
	var record = getParameterByName('record');
	// Do ajax request
	var url = app_path_webroot+'DataExport/plot_chart.php?pid='+getParameterByName('pid');
	var url = plotchart;
	if (hash != '') {
		// Show results to survey participant (use passthru mechanism to avoid special authentication issues)
		url = dirname(dirname(app_path_webroot))+'/surveys/index.php?pid='+pid+'&s='+hash+'&__results='+getParameterByName('__results')+'&__passthru='+escape('DataExport/plot_chart.php');
	} else if (record != '') {
		// Overlay results from one record
		var event_id = getParameterByName('event_id');
		url += '&record='+record+'&event_id='+event_id;
	}
	$.post(url, { fields: nextfields, charttype: charttype, isSurveyPage: (isSurveyPage ? '1' : '0'), results_code_hash: results_code_hash, includeRecordsEvents: includeRecordsEvents, hasFilterWithNoRecords: hasFilterWithNoRecords }, function(resp_data){
		var json_data = jQuery.parseJSON(resp_data);
		// Set variables
		var field = json_data.field;
		var form = json_data.form;
		var nextfields = json_data.nextfields;
		var raw_data = json_data.data;
		var minValue = json_data.min;
		var maxValue = json_data.max;
		var medianValue = json_data.median;
		var respondentData = json_data.respondentData;
		var showChart = json_data.showChart; // Used to hide Bar Charts if lacking diversity
		if (charttype != '') {
			var plottype = charttype;
		} else {
			var plottype = json_data.plottype;
		}
		// If no data was sent OR plot should be hidden due to lack of diversity, then do not display field (would cause error)
		if (!showChart || raw_data.length == 0) {
			// Hide the field div
			if (showChart && raw_data.length == 0) {
				$('#plot-'+field).html( $('#no_show_plot_div').html() );
			} else {
				$('#plot-'+field).hide();
				$('#plot-download-btn-'+field).addClass('hideforever');
			}
			if (isSurveyPage) $('#stats-'+field).remove(); // Only hide the stats table for survey results
			$('#chart-select-'+field).hide();
			$('#refresh-link-'+field).hide();
			// Perform the next ajax request if more fields still need to be processed
			if (nextfields.length > 0) {
				renderCharts(nextfields,charttype,results_code_hash);
			}
			return;
		}
		// Show download button
		$('#plot-download-btn-'+field).show();
		// Instantiate data object
		var data = new google.visualization.DataTable();
		// Box Plot
		if (plottype == 'BoxPlot')
		{
			// Store record names and event_id's into array to allow navigation to page
			var recordEvent = new Array();
			// Set text for the pop-up tooltip
			var tooltipText = (isSurveyPage ? 'Value entered by survey participant /' : 'Click plot point to go to this record /');
			// Add data columns
			data.addColumn('number', '');
			data.addColumn('number', 'Value');
			// Add data rows
			for (var i = 0; i < raw_data.length; i++) {
				// Add to chart data
				data.addRow([{v: raw_data[i][0], f: raw_data[i][0]+'\n\n'}, {v: raw_data[i][1], f: tooltipText}]);
				// Add to recordEvent array
				if (!isSurveyPage) {
					recordEvent[i] = '&id='+raw_data[i][2]+'&event_id='+raw_data[i][3]+'&instance='+raw_data[i][4];
				}
			}
			// Add median dot
			data.addColumn('number', 'Median');
			data.addRow([{v: medianValue, f: medianValue+'\n\n'}, null, {v: 0.5, f: 'Median value /'}]);
			// Add single respondent/record data point
			if (respondentData != '') {
				var tooltipTextSingleResp1, tooltipTextSingleResp2;
				if (isSurveyPage) {
					tooltipTextSingleResp1 = tooltipTextSingleResp2 = 'YOUR value';
				} else {
					tooltipTextSingleResp1 = 'Value for selected record ('+record+')';
					tooltipTextSingleResp2 = 'Click plot point to go to this record';
				}
				data.addColumn('number', tooltipTextSingleResp1);
				data.addRow([{v: respondentData*1, f: respondentData+'\n\n'}, null, null, {v: 0.5, f: tooltipTextSingleResp2+' /'}]);
				// Add to recordEvent array
				if (!isSurveyPage) {
					recordEvent[i+1] = '&id='+record+'&event_id='+event_id;
				}
			}
			// Display box plot
			var chart = new google.visualization.ScatterChart(document.getElementById('plot-'+field));
			var chartHeight = 250;
			chart.draw(data, {chartArea: {top: 10, left: 30, height: (chartHeight-50)}, width: 650, height: chartHeight, legend: 'none', vAxis: {minValue: 0, maxValue: 1, textStyle: {fontSize: 1} }, hAxis: {minValue: minValue, maxValue: maxValue} });
			// Set action to open form in new tab when select a plot point
			if (!isSurveyPage) {
				google.visualization.events.addListener(chart, 'select', function selectPlotPoint(){
					var selection = chart.getSelection();
					if (selection.length < 1) return;
					var message = '';
					for (var i = 0; i < selection.length; i++) {
						var itemrow = selection[i].row;
						if (itemrow != null && recordEvent[itemrow] != null) {
							window.open(app_path_webroot+'DataEntry/index.php?pid='+pid+'&page='+form+recordEvent[itemrow]+'&fldfocus='+field+'#'+field+'-tr','_blank');
							return;
						}
					}
				});
			}
		}
		// Bar/Pie Chart
		else
		{
			// Add data columns
			data.addColumn('string', '');
			if (isSurveyPage) {
				data.addColumn('number', 'Count from other respondents');
				data.addColumn('number', 'Count from YOU');
			} else {
				data.addColumn('number', 'Count');
				data.addColumn('number', 'Count from the selected record');
			}
			// Add data rows
			data.addRows(raw_data);
			// Display bar chart or pie chart
			if (plottype == 'PieChart') {
				var chart = new google.visualization.PieChart(document.getElementById('plot-'+field));
				var chartHeight = 300;
				chart.draw(data, {chartArea: {top: 10, height: (chartHeight-50)}, width: 600, height: chartHeight, legend: 'none', hAxis: {minValue: minValue, maxValue: maxValue} });
			} else if (plottype == 'BarChart') {
				var chart = new google.visualization.BarChart(document.getElementById('plot-'+field));
				var chartHeight = 80+(raw_data.length*60);
				chart.draw(data, {colors:['#3366CC','#FF9900'], isStacked: true, chartArea: {top: 10, height: (chartHeight-50)}, width: 600, height: chartHeight, legend: 'none', hAxis: {minValue: minValue, maxValue: maxValue} });
			}
		}
		// Perform the next ajax request if more fields still need to be processed
		if (nextfields.length > 0) {
			renderCharts(nextfields,charttype,results_code_hash);
		}
	});
}

// JavaScript equivalent of PHP's strip_tags() function
function strip_tags(input, allowed) {
	//  discuss at: http://phpjs.org/functions/strip_tags/
	// original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// improved by: Luke Godfrey
	// improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	//    input by: Pul
	//    input by: Alex
	//    input by: Marc Palau
	//    input by: Brett Zamir (http://brett-zamir.me)
	//    input by: Bobby Drake
	//    input by: Evertjan Garretsen
	// bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// bugfixed by: Onno Marsman
	// bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// bugfixed by: Eric Nagel
	// bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// bugfixed by: Tomasz Wesolowski
	//  revised by: Rafal Kukawski (http://blog.kukawski.pl/)
	//   example 1: strip_tags('<p>Kevin</p> <br /><b>van</b> <i>Zonneveld</i>', '<i><b>');
	//   returns 1: 'Kevin <b>van</b> <i>Zonneveld</i>'
	//   example 2: strip_tags('<p>Kevin <img src="someimage.png" onmouseover="someFunction()">van <i>Zonneveld</i></p>', '<p>');
	//   returns 2: '<p>Kevin van Zonneveld</p>'
	//   example 3: strip_tags("<a href='http://kevin.vanzonneveld.net'>Kevin van Zonneveld</a>", "<a>");
	//   returns 3: "<a href='http://kevin.vanzonneveld.net'>Kevin van Zonneveld</a>"
	//   example 4: strip_tags('1 < 5 5 > 1');
	//   returns 4: '1 < 5 5 > 1'
	//   example 5: strip_tags('1 <br/> 1');
	//   returns 5: '1  1'
	//   example 6: strip_tags('1 <br/> 1', '<br>');
	//   returns 6: '1 <br/> 1'
	//   example 7: strip_tags('1 <br/> 1', '<br><br/>');
	//   returns 7: '1 <br/> 1'

	allowed = (((allowed || '') + '')
		.toLowerCase()
		.match(/<[a-z][a-z0-9]*>/g) || [])
		.join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
	var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
		commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
	return input.replace(commentsAndPhpTags, '')
		.replace(tags, function ($0, $1) {
			return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
		});
}

//Display "Working" div as progress indicator
function showProgress(show,ms) {
	// Set default time for fade-in/fade-out
	if (ms == null) ms = 500;
	if (!$("#working").length) 	$('body').append('<div id="working"><img alt="Working..." src="'+app_path_images+'progress_circle.gif">&nbsp; Working...</div>');
	if (!$("#fade").length) 	$('body').append('<div id="fade"></div>');
	if (show) {
		$('#fade').addClass('black_overlay').show();
		$('#working').center().fadeIn(ms);
	} else {
		setTimeout(function(){
			$("#fade").removeClass('black_overlay').hide();
			$("#working").fadeOut(ms);
		},ms);
	}
}

// Center a jQuery object via .center()
jQuery.fn.center = function () {
	this.css("position","absolute");
	this.css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) +
		$(window).scrollTop()) + "px");
	this.css("left", Math.max(0, (($(window).width() - $(this).outerWidth()) / 2) +
		$(window).scrollLeft()) + "px");
	return this;
}
