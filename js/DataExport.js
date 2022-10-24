// Set ajax variable
var exportajax;
// Variable to track last report field type (textbox or drop-down)
var rprtft = 'text';

// On pageload
$(function(){
	// Add id to all auto suggest text boxes and enable auto suggest for them
	enableAutoSuggestFields();
	// Trigger to select export format when you click its row
	resetExportOptionRows();
	$('table#export_choices_table tr td').click(function(){
		$(this).find('input[name="export_format"]').prop('checked', true);
		resetExportOptionRows();
	});
	$('table#export_choices_table tr td').mouseenter(function(){
		$(this).css({'background':'#d9ebf5', 'border':'1px solid #ccc'});
	}).mouseleave(function(){
		if (!$(this).find('input[name="export_format"]').prop('checked')) {
			$(this).css({'background':'#eee', 'border':'1px solid #eee', 'border-bottom':'1px solid #ddd'});
			$('table#export_choices_table tr td:last').css({'border':'1px solid #eee'});
		}
	});
	// Add d-print-none class to page elements so that they don't display when printing
	$('div#center h3, div#sub-nav, #showPlotsStatsOptions').addClass('d-print-none');
	// Add/Edit Report: Enable drag-n-drop of fields on table
	if ($('table#create_report_table').length) {
		// If report title is blank, then set cursor in title field
		var report_title_field = $('table#create_report_table input[name="__TITLE__"]');
		if (report_title_field.val() == '') report_title_field.focus();
		// Add "nodrop" class on all rows except report fields rows
		$('table#create_report_table tr:not(.field_row)').addClass('nodrop');
		$('table#create_report_table tr.field_row:last').addClass('nodrop');
		// Enable drag n drop
		$('table#create_report_table').tableDnD({
			onDrop: function(table, row) {
				// Reset the "Field X" text for report field rows
				resetFieldNumLabels();
				// Highlight row
				$(row).find('td:eq(0) div, td:eq(1), select, input').effect('highlight',{},2000);
			},
			dragHandle: "dragHandle"
		});
		// Set hover action for table rows to display dragHandle icon
		setTableRowHover($('table#create_report_table tr.field_row'));
		// Set up drag-n-drop pop-up tooltip
		$('#dragndrop_tooltip_trigger').tooltip2({ tipClass: 'tooltip4sm', position: 'top center', offset: [30,0], predelay: 100, delay: 0, effect: 'fade' });
		setDragHandleHover($('.dragHandle'));
		// Set triggers for onchange of field drop-down to display form name
		showFormLabel($('#create_report_table .field-dropdown option[value=""]:not(:selected)').parent());
	}
	// Report List: Enable drag n drop on report list table
	if ($('table#table-report_list').length) {
		enableReportListTable();
	}
	// Change default behavior of the multi-select boxes so that they are more intuitive to users when selecting/de-selecting options
	$("select[multiple]").each(function(){
		modifyMultiSelect($(this), 'ms-selection');
	});
	// Deal with multi-selects that have a blank "All" option that is the default when nothing else is selected
	$('#export_selected_instruments, #export_selected_events').change(function(event){
		var obparent = $(this);
		var valueSelected = event.target.value;
		var blankOption = obparent.find('option[value=""]');
		setTimeout(function(){
			if (valueSelected != '' && blankOption.hasClass('ms-selection')) {
				// Deselect the blank option
				blankOption.removeClass('ms-selection').prop('selected', false);
			} else if (valueSelected == '') {
				// Deselect all options but the blank option
				obparent.find('option').removeClass('ms-selection').prop('selected', false);
				blankOption.addClass('ms-selection').prop('selected', true);
			}
		},100);
	});

	// When adding/editing report, show/hide the public link div
	if ($('#is_public').length)
	{
		$('#is_public').change(function(){
			if ($(this).prop('checked')) {
				// Was checked
				// If report not created yet, then tell user to create it first
				if (getParameterByName('report_id') == '') {
					$('#public_link_div').removeClass('hide');
					$('#public_link_div_note_save').addClass('text-danger').effect('highlight',{},3000);
					setTimeout(function(){
						$('#public_link_div').addClass('hide');
					},5000);
					$('#public-report-enable-warning').addClass('hide');
					$(this).prop('checked', false);
				} else if ($('#is_public_saved').val() == '1') {
					// If the report is public but someone merely unchecked it on this page without saving, then allow them to recheck it with no process
					$('#public_link_div').removeClass('hide');
					$('#public-report-enable-warning').addClass('hide');
					// Set report as not modifiable
					$('#create_report_form :input, #create_report_form button, #save-report-btn').prop('disabled',true);
					tinymce.activeEditor.getBody().setAttribute('contenteditable', false);
					$('#is_public').parentsUntil('tr').find(':input').prop('disabled',false);
					$('.public-report-no-modify-notice').fadeIn(1000);
					$('#request-pending-text').hide();
				} else {
					// Display dialog to enable
					simpleDialog(null, lang.report_builder_190,'dialog-make-report-public',750, null, lang.global_53, function(){
						// AJAX request to make the report public
						userEnablePublicReport(getParameterByName('report_id'));
					}, ((reports_allow_public == '2' && !super_user_not_impersonator) ? lang.report_builder_192 : lang.report_builder_191));
					$(this).prop('checked', false);
					$('.ui-dialog-buttonpane:visible :button:eq(1)').button('disable');
					$('.ui-dialog-buttonpane:visible :button:eq(1)').addClass('font-weight-bold');
					$('#dialog-make-report-public input.make-report-public-checkbox-agreement').prop('checked',false);
				}
			} else {
				// Was unchecked
				$('#public_link_div').addClass('hide');
				$('#public-report-enable-warning').removeClass('hide');
				$(this).prop('checked', false);
				// Set report as modifiable
				$('#create_report_form :input, #create_report_form button, #save-report-btn').prop('disabled',false);
				tinymce.activeEditor.getBody().setAttribute('contenteditable', true);
				$('.public-report-no-modify-notice').fadeOut(1000);
			}
		});
		// Disable all inputs if the report is currently public
		if ($('#is_public').prop('checked')) {
			$('#create_report_form :input, #create_report_form button, #save-report-btn').prop('disabled',true);
			setTimeout(function(){ tinymce.activeEditor.getBody().setAttribute('contenteditable', false); },500);
			$('#is_public').parentsUntil('tr').find(':input').prop('disabled',false);
		}
		// If admin is approving request to make report public, auto-open the prompt
		if (super_user_not_impersonator && getParameterByName('openPromptPublicReport') == '1' && !$('#is_public').prop('checked')) {
			$('#is_public').trigger('click');
			// Show the notice to the admin
			$('#admin-approval-notice-make-report-public').show();
		}
		$('input.make-report-public-checkbox-agreement').click(function(){
			if ($('#dialog-make-report-public input.make-report-public-checkbox-agreement:checked').length == $('#dialog-make-report-public input.make-report-public-checkbox-agreement').length) {
				// All are checked, so enable button
				$('.ui-dialog-buttonpane:visible :button:eq(1)').button('enable');
				$('.ui-dialog-buttonpane:visible :button:eq(1)').effect('highlight', {}, 3000);
			} else {
				// Not met requirements, so disable button
				$('.ui-dialog-buttonpane:visible :button:eq(1)').button('disable');
			}
		});
	}

	// Copy-to-clipboard action
	$('.btn-clipboard').click(function(){
		copyUrlToClipboard(this);
	});
});

// Copy-to-clipboard action
try {
	var clipboard = new Clipboard('.btn-clipboard');
} catch (e) {}

// Copy the public report URL to the user's clipboard
function copyUrlToClipboard(ob) {
	// Create progress element that says "Copied!" when clicked
	var rndm = Math.random()+"";
	var copyid = 'clip'+rndm.replace('.','');
	$('.clipboardSaveProgress').remove();
	var clipSaveHtml = '<span class="clipboardSaveProgress" id="'+copyid+'">Copied!</span>';
	$(ob).after(clipSaveHtml);
	$('#'+copyid).toggle('fade','fast');
	setTimeout(function(){
		$('#'+copyid).toggle('fade','fast',function(){
			$('#'+copyid).remove();
		});
	},2000);
}


// Display a prompt to an admin to enable a report as public
function userEnablePublicReport(report_id) {
	var request_approval_by_admin = (super_user_not_impersonator && getParameterByName('openPromptPublicReport') == '1') ? '1' : '0';
	$.post(app_path_webroot+'DataExport/report_public_enable.php?action=enable&pid='+pid, { report_id: report_id, request_approval_by_admin: request_approval_by_admin, user: getParameterByName('user') }, function(data) {
		if (data == '' || data == '0') {
			alert(woops);
			return;
		}
		simpleDialog(data);
		if (reports_allow_public == '1' || super_user_not_impersonator) {
			$('#is_public').prop('checked',true);
			$('#public_link_div').removeClass('hide');
			$('#public-report-enable-warning').addClass('hide');
			// Set report as not modifiable
			$('#create_report_form :input, #create_report_form button, #save-report-btn').prop('disabled',true);
			tinymce.activeEditor.getBody().setAttribute('contenteditable', false);
			$('#is_public').parentsUntil('tr').find(':input').prop('disabled',false);
			$('.public-report-no-modify-notice').fadeIn(1000);
			$('#request-pending-text').hide();
		} else if (reports_allow_public == '2' && !super_user_not_impersonator) {
			$('#request-pending-text').show();
		}
	});
}

// Confirm Custom URL
function confirmCustomUrl(hash,report_id,custom_url){
	custom_url = trim(custom_url);
	if(custom_url != ''){
		showProgress(1);
		$.post(app_path_webroot+'DataExport/report_public_enable.php?action=shorturl&pid='+pid, { hash: hash, report_id: report_id, custom_url: custom_url }, function(data) {
			showProgress(0,0);
			if (data == '0' || data == '') {
				simpleDialog(woops,null,null,350,"customizeShortUrl('"+hash+"','"+report_id+"')",'Close');
			} else if (data == '1') {
				simpleDialog('The text you entered does not make a valid URL. Please try again using only letters, numbers, and underscores.',null,null,350,"customizeShortUrl('"+hash+"','"+report_id+"')",'Close');
			} else if (data == '2') {
				simpleDialog('Unfortunately, the URL you entered has already been taken. Please try again.',null,null,350,"customizeShortUrl('"+hash+"','"+report_id+"')",'Close');
			} else {
				if (data.indexOf('ERROR:') > -1) {
					var title = "ERROR!";
					simpleDialog("<div class='fs14'></div>"+data+"</div>", title,null,600);
				} else {
					var title = "SUCCESS!";
					$('#create-custom-link-btn').hide();
					$('#short-link-display').show();
					$('#reporturl-custom').val(data);
					simpleDialog("<div class='fs14'></div>"+langCreateCustomLink+"</div><a href='"+data+"' class='d-block mt-3 fs15' target='_blank' style='text-decoration:underline;'>"+data+"</a>", title,null,600);
				}
			}
		});
	}else{
		simpleDialog('Please enter a valid url.',null,null,350,"customizeShortUrl('"+hash+"','"+dash_id+"')");
	}
}

// Delete Custom URL link
function removeCustomUrl(report_id){
	showProgress(1);
	$.post(app_path_webroot+'DataExport/report_public_enable.php?action=remove_shorturl&pid='+pid, { report_id: report_id }, function(data) {
		showProgress(0,0);
		if (data == '0' || data == '') {
			simpleDialog(woops);
		} else {
			$('#create-custom-link-btn').show();
			$('#short-link-display').hide();
			$('input.customurl-input').val('');
			simpleDialog(data);
		}
	});
}

// Customize short url
function customizeShortUrl(hash, report_id){
	simpleDialog(null,null,'custom_url_dialog',550,null,'Cancel',function(){
		confirmCustomUrl(hash,report_id,$('input.customurl-input').val())
	},'Submit');
}

// Check if a filter field's operator is CONTAINS, NOT_CONTAIN, STARTS_WITH, ENDS_WITH
function applyValdtn(ob) {
	var opts = new Array('CONTAINS', 'NOT_CONTAIN', 'STARTS_WITH', 'ENDS_WITH');
	return !in_array($(ob).parents('tr:first').find('.limiter-operator').val(), opts);
}

// Add form fields to report
function addFormFieldsToReport(form) {
	// Loop through all form fields
	var fields = formFields[form].split(',');
	var thisfield;
	// Move autocomplete dropdown to original position
	resetAcDdPosition();
	showProgress(1,0);
	var k = 0;
	for (var i=0; i<fields.length; i++) {
		thisfield = fields[i];
		// Make sure not already added to report
		if ($('.field-hidden[value="'+thisfield+'"]').length == 0) {
			// Special exception for first field since it already exists on page
			//if (k == 0) {
				//$('.field-dropdown-a:last').trigger('click').parents('tr:first').children().effect('highlight', { }, 2000);
			//}
			// Add field
			$('.field-hidden:last').val(thisfield);
			$('.field-dropdown:last').show().val(getFieldLabel(thisfield));
			$('.field-hidden:last').parents('tr:first').attr('hl','1');
			addNewReportRow($('.field-hidden:last'), true);
			k++;
		}
	}
	// Reset drop-down
	$('#add_form_field_dropdown').val('');
	showProgress(0,100);
	// Enable table drag n drop for new row
	$('table#create_report_table').tableDnDUpdate();
	// Highlight all new rows
	$('table#create_report_table tr.field_row[hl="1"]').each(function(){
		$(this).attr('hl','0');
		highlightTableRowOb($(this), 2000);
	});
}

// Show/hide the advanced logic row in Report Builder
function showAdvancedLogicRow(show_advanced, skip_confirm) {
	// If converting to advanced, get user to confirm its okay to abandon simple format
	if (show_advanced && !skip_confirm && $('.limiter-dropdown option[value=""]:not(:selected)').length > 0) {
		initDialog('convertAdvancedLogicConfirm');
		$('#convertAdvancedLogicConfirm')
			.html(langConvertToAdvLogic2+"<div style='color:#444;margin:20px 0 2px;font-weight:bold;'>"+langPreviewLogic+"</div><div style='font-family:verdana;color:#C00000;'>"+convertSimpleLogicToAdvanced()+"</div>")
			.dialog({ bgiframe: true, modal: true, width: 500, title: langConvertToAdvLogic,
			position: { my: "left bottom", at: "left top", of: $('tr#adv_logic_row_link') },
			buttons:
				[{ text: closeBtnTxt, click: function() {
					$(this).dialog('close');
				}},
				{ text: langConvert, click: function() {
					showAdvancedLogicRow(show_advanced, true);
					$(this).dialog('close');
				}}]
			});
		return;
	}
	// If converting to back to simple, get user to confirm its okay to abandon advanced logic
	$('tr#adv_logic_row textarea[name="advanced_logic"]').val( trim($('tr#adv_logic_row textarea[name="advanced_logic"]').val()) );
	if (!show_advanced && !skip_confirm && $('tr#adv_logic_row textarea[name="advanced_logic"]').val().length > 0) {
		initDialog('convertAdvancedLogicConfirm');
		$('#convertAdvancedLogicConfirm').html(langConvertToAdvLogic3).dialog({ bgiframe: true, modal: true, width: 500, title: langConvertToAdvLogic5,
			position: { my: "left bottom", at: "left top", of: $('tr#adv_logic_row_link2') },
			buttons:
				[{ text: closeBtnTxt, click: function() {
					$(this).dialog('close');
				}},
				{ text: langConvertToAdvLogic4, click: function() {
					showAdvancedLogicRow(show_advanced, true);
					$(this).dialog('close');
				}}]
			});
		return;
	}
	// Convert
	if (show_advanced) {
		$('.limiter_and_row').addClass('hidden');
		var logic = convertSimpleLogicToAdvanced();
		// Add logic to textarea
		$('tr#adv_logic_row textarea[name="advanced_logic"]').val(logic);
		// Remove all original filter rows
		var i = 0;
		$('tr.limiter_and_row, tr.limiter_row').each(function(){
			if (i++ > 1) $(this).remove();
		});
		$('.limiter-dropdown:first').val('').trigger('change');
	} else {
		$('.limiter_and_row, .limiter_row').removeClass('hidden').hide();
		$('tr#adv_logic_row textarea[name="advanced_logic"]').val('');
		$('.limiter_and_row').find('a').hide(); // Hide delete icon
		// Reset initial limiter field so that its onchange displays new limiter dropdown rows
		$('.limiter-dropdown:last').val('').trigger('change');
		var rowob = $('.limiter-dropdown:last').parents('tr:first');
		rowob.find('a').hide(); // Show delete icon
		rowob.find('.limiter-dropdown').attr('onchange',"rprtft='dropdown';addNewLimiterRow($(this));fetchLimiterOperVal($(this));");
	}
	$('.limiter_row, #adv_logic_row_link, #adv_logic_row_link2, #adv_logic_row, #oper_value_hdr, #how_to_filters_link').toggle();
	// Highlight row
	if (show_advanced) {
		highlightTableRowOb($('tr#adv_logic_row'), 2500);
		highlightTableRowOb($('tr#adv_logic_row_link2'), 2500);
	} else {
		highlightTableRowOb($('tr.limiter_row:first'), 2500);
		highlightTableRowOb($('tr#adv_logic_row_link'), 2500);
	}
}

// Check advanced logic for syntax errors
function check_advanced_logic() {
	$('tr#adv_logic_row textarea[name="advanced_logic"]').val( trim($('tr#adv_logic_row textarea[name="advanced_logic"]').val()) );
	var logic = $('tr#adv_logic_row textarea[name="advanced_logic"]').val();
	// Return true if logic is blank
	if (logic == '') return true;
	// Make ajax request to check the logic via PHP (use async=false)
	var isSuccess = false;
	$.ajax({
        url: app_path_webroot+'Surveys/automated_invitations_check_logic.php?pid='+pid,
        type: 'POST',
		data: { logic: logic, redcap_csrf_token: redcap_csrf_token },
        async: false,
        success:
            function(data){
				if (data == '0') {
					alert(woops);
				} else if (data == '1') {
					// Success - so do nothing
					isSuccess = true;
				} else {
					// Error msg - problems in logic to fix
					simpleDialog(data);
				}
            }
    });
	// Return success value
	return isSuccess;
}

// Convert simple report filtering logic in Report Builder to advanced format
function convertSimpleLogicToAdvanced() {
	// Capture logic in string
	var logic = '';
	var ob, valdt;
	var all_oper = new Array('CONTAINS', 'NOT_CONTAIN', 'STARTS_WITH', 'ENDS_WITH');
	// Loop through all filter rows
	var i = 0;
	$('tr.limiter_row').each(function(){
		// object
		ob = $(this);
		// Get field, operator, and value
		var varname = ob.find('.limiter-dropdown').val();
		var oper = ob.find('.limiter-operator').val();
		var inputvalob = ob.find('input.limiter-value');
		if (inputvalob.length) {
			var val = inputvalob.val();
		} else {
			var val = ob.find('select.limiter-value').val();
		}
		// If the field or operator is blank then skip it
		if (varname == '' || oper == '') return;
		// If longitudinal, then get unique event name from event_id in drop-down
		var eventname = '';
		if (longitudinal) {
			var this_event_id = ob.find('.event-dropdown').val();
			if (this_event_id == '') {
				eventname = "[event-name]";
			} else {
				eventname = uniqueEvents[this_event_id];
				if (eventname != '') eventname = "[" + eventname + "]";
			}
		}
		// Check if field is a MDY or DMY date/datetime/datetime_seconds field
		if (inputvalob.length && val != '') {
			if (inputvalob.hasClass('date_mdy')) {
				val = date_mdy2ymd(val);
			} else if (inputvalob.hasClass('datetime_mdy') || inputvalob.hasClass('datetime_seconds_mdy')) {
				valdt = val.split(' ');
				val = date_mdy2ymd(valdt[0])+' '+valdt[1];
			} else if (inputvalob.hasClass('date_dmy')) {
				val = date_dmy2ymd(val);
			} else if (inputvalob.hasClass('datetime_dmy') || inputvalob.hasClass('datetime_seconds_dmy')) {
				valdt = val.split(' ');
				val = date_dmy2ymd(valdt[0])+' '+valdt[1];
			}
		}
		// Determine if this is an AND drop-down row
		logic += (ob.find('.lgoo').css('visibility') != 'hidden') ? (i == 0 ? "(" : " OR ") : (i == 0 ? "(" : ") AND (");
		// If is "contains", "not contain", "starts_with", or "ends_with"
		if (in_array(oper, all_oper)) {
			logic += oper.toLowerCase() + "(" + eventname + "[" + varname + "], \"" + val.replace(/"/g, "\\\"") + "\")";
		}
		// If is "checked" or "unchecked"
		else if (oper == 'CHECKED' || oper == 'UNCHECKED') {
			logic += eventname + "[" + varname + "(" + val + ")] = \"" + (oper == 'CHECKED' ? "1" : "0") + "\"";
		}
		// Normal
		else {
			var quotes = (isNumeric(val) && oper != 'E' && oper != 'NE') ? '' : '"';
			logic += eventname + "[" + varname + "] " + allLimiterOper[oper].replace(/ /g, "") + " " + quotes + val.replace(/"/g, "\\\"") + quotes;
		}
		// Increment counter
		i++;
	});
	// Add final parenthesis
	if (logic != '') logic += ")";
	// Return logic
	return logic;
}

// Enable report list table
function enableReportListTable() {
	// Add dragHandle to first cell in each row
	$("table#table-report_list tr").each(function() {
		var report_id = trim($(this.cells[0]).text());
		$(this).prop("id", "reprow_"+report_id).attr("reportid", report_id);
		if (isNumeric(report_id)) {
			// User-defined reports (draggable)
			$(this.cells[0]).addClass('dragHandle');
			// $(this.cells[3]).addClass('opacity50');
			// $(this.cells[4]).addClass('opacity50');
		} else {
			// Pre-defined reports
			$(this).addClass("nodrop").addClass("nodrag");
		}
	});
	// Restripe the report list rows
	restripeReportListRows();
	if (user_rights_reports) {
		// Enable drag n drop (but only if user has "reports" user rights)
		$('table#table-report_list').tableDnD({
			onDrop: function(table, row) {
				// Loop through table
				var ids = "";
				var this_id = $(row).prop('id');
				$("table#table-report_list tr").each(function() {
					// Gather form_names
					var row_id = $(this).attr("reportid");
					if (isNumeric(row_id)) {
						ids += row_id + ",";
					}
				});
				// Save new order via ajax
				$.post(app_path_webroot+'DataExport/report_order_ajax.php?pid='+pid, { report_ids: ids }, function(data) {
					if (data == '0') {
						alert(woops);
						window.location.reload();
					} else if (data == '2') {
						window.location.reload();
					}
					// Update left-hand menu panel of Reports
					updateReportPanel();
				});
				// Reset report order numbers in report list table
				resetReportOrderNumsInTable();
				// Restripe table rows
				restripeReportListRows();
				// Highlight row
				setTimeout(function(){
					var i = 1;
					$('tr#'+this_id+' td').each(function(){
						if (i++ != 1) $(this).effect('highlight',{},2000);
					});
				},100);
			},
			dragHandle: "dragHandle"
		});
		// Create mouseover image for drag-n-drop action and enable button fading on row hover
		$("table#table-report_list tr:not(.nodrag)").mouseenter(function() {
			$(this.cells[0]).css('background','#ffffff url("'+app_path_images+'updown.gif") no-repeat center');
			$(this.cells[0]).css('cursor','move');
			// $(this.cells[3]).removeClass('opacity50');
			// $(this.cells[4]).removeClass('opacity50');
		}).mouseleave(function() {
			$(this.cells[0]).css('background','');
			$(this.cells[0]).css('cursor','');
			// $(this.cells[3]).addClass('opacity50');
			// $(this.cells[4]).addClass('opacity50');
		});
		// Set up drag-n-drop pop-up tooltip
		var first_hdr = $('#report_list .hDiv .hDivBox th:first');
		first_hdr.prop('title',langDragReport);
		first_hdr.tooltip2({ tipClass: 'tooltip4sm', position: 'top center', offset: [25,0], predelay: 100, delay: 0, effect: 'fade' });
		$('.dragHandle').mouseenter(function() {
			first_hdr.trigger('mouseover');
		}).mouseleave(function() {
			first_hdr.trigger('mouseout');
		});
	}
}

// Restripe the rows of the report list table
function restripeReportListRows() {
	// Loop through the pre-defined ones fist
	var i = 1;
	$("table#table-report_list tr").each(function() {
		// Restripe table
		$(this).removeClass('erow');
		if (i > 2 &&  i % 2 == 1) $(this).addClass('erow');
		i++;
	});
}

// Copy a report
function copyReport(report_id, confirmCopy) {
	if (confirmCopy == null) confirmCopy = true;
	// Get report title from table
	var row_id = $('#repcopyid_'+report_id).parents('tr:first').attr('id');
	var report_title = trim($('#repcopyid_'+report_id).parents('tr:first').find('td:eq(2)').text());
	if (confirmCopy) {
		// Prompt user to confirm copy
		simpleDialog(langCopyReportConfirm
			+ '<br>"<span style="color:#C00000;font-size:14px;">'+report_title+'</span>"'+langQuestionMark,
			langCopyReport,null,350,null,closeBtnTxt,"copyReport("+report_id+",false);",langCopy);
	} else {
		// Copy via ajax
		$.post(app_path_webroot+'DataExport/report_copy_ajax.php?pid='+pid, { report_id: report_id }, function(data) {
			if (data == '0') {
				alert(woops);
				return;
			}
			// Parse JSON
			var json_data = jQuery.parseJSON(data);
			// Replace current report list on page
			$('#report_list_parent_div').html(json_data.html);
			// Re-enable table
			enableReportListTable();
			initWidgets();
			// Highlight new row then remove row from table
			var i = 1;
			$('tr#reprow_'+json_data.new_report_id+' td').each(function(){
				if (i++ != 1) $(this).effect('highlight',{},2000);
			});
			// Update left-hand menu panel of Reports
			updateReportPanel();
		});
	}
}

// Delete a report
function deleteReport(report_id, confirmDelete) {
	if (confirmDelete == null) confirmDelete = true;
	// Get report title from table
	var row_id = $('#repdelid_'+report_id).parents('tr:first').attr('id');
	var report_title = trim($('#repdelid_'+report_id).parents('tr:first').find('td:eq(2)').text());
	if (confirmDelete) {
		// Prompt user to confirm deletion
		simpleDialog(langDeleteReportConfirm
			+ '<br>"<span style="color:#C00000;font-size:14px;">'+report_title+'</span>"'+langQuestionMark,
			langDeleteReport,null,350,null,closeBtnTxt,"deleteReport("+report_id+",false);",langDelete);
	} else {
		// Delete via ajax
		$.post(app_path_webroot+'DataExport/report_delete_ajax.php?pid='+pid, { report_id: report_id }, function(data) {
			if (data == '0') {
				alert(woops);
				return;
			}
			// Highlight deleted row then remove row from table
			var i = 1;
			$('tr#'+row_id+' td').each(function(){
				if (i++ != 1) $(this).effect('highlight',{},700);
			});
			setTimeout(function(){
				$('tr#'+row_id).hide('fade',function(){
					$('tr#'+row_id).remove();
					resetReportOrderNumsInTable();
					restripeReportListRows();
				});
			},300);
			// Update left-hand menu panel of Reports
			updateReportPanel();
		});
	}
}


// Reset report order numbers in report list table
function resetReportOrderNumsInTable() {
	var i = 1;
	$("table#table-report_list tr:not(.nodrag)").each(function(){
		$(this).find('td:eq(1) div').html(i++);
	});
}

// Set hover action for table rows to display dragHandle icon
function setTableRowHover(rowob) {
	rowob.mouseenter(function() {
		$(this).find('td.dragHandle').css({'cursor':'move', 'background':'#F0F0F0 url("'+app_path_images+'updown.gif") no-repeat 5px center'});
	}).mouseleave(function() {
		$(this).find('td.dragHandle').css({'cursor':'', 'background':'#F0F0F0 url("'+app_path_images+'label-bg.gif") repeat-x scroll 0 0'});
	});
}

// Set hover action for table drag n drop dragHandle
function setDragHandleHover(ob) {
	ob.mouseenter(function() {
		$('#dragndrop_tooltip_trigger').trigger('mouseover');
	}).mouseleave(function() {
		$('#dragndrop_tooltip_trigger').trigger('mouseout');
	});
}

// Display or hide the limiter group operator row (AND/OR dropdown)
function displaylimiterGroupOperRow(ob) {
	// Determine if this is the clone drop-down in the preceding row
	var isClone = ob.hasClass('lgoc');
	var thisrow = ob.parents('tr:first');
	var thisval = ob.val();
	// If change AND to OR
	if (isClone) {
		// Hide the current row
		thisrow.hide();
		// Make visible the original in-row drop-down and set its value as same as this one
		thisrow.next().find('.lgoo').css('visibility','visible').val(thisval);
	}
	// If change OR to AND
	else {
		// Show the prev row
		thisrow.prev().show();
		// Hide the original in-row drop-down and set the clone's value as same as this one
		thisrow.find('.lgoo').css('visibility','hidden');
		thisrow.prev().find('.lgoc').val(thisval);
	}
}

// Display or hide user access custom view options
function displayUserAccessOptions() {
	if ($('#create_report_table input[name="user_access_radio"]:checked').val() == 'SELECTED') {
		// Open custom user access options
		$('#selected_users_div').show('blind','fast');
	} else {
		// Hide options
		$('#selected_users_div').hide('blind','fast');
	}
	$('#selected_users_note1, #selected_users_note2').toggle();
}

// Display or hide user access custom edit options
function displayUserAccessEditOptions() {
	if ($('#create_report_table input[name="user_edit_access_radio"]:checked').val() == 'SELECTED') {
		// Open custom user access options
		$('#selected_users_edit_div').show('blind','fast');
	} else {
		// Hide options
		$('#selected_users_edit_div').hide('blind','fast');
	}
	$('#selected_users_edit_note1, #selected_users_edit_note2').toggle();
}

// Make sure user has chosen export format and whether to archive files in File Repository
function exportFormatDialogSaveValidate() {
	return ($('#exportFormatDialog input[name="export_format"]:checked').length && $('#exportFormatDialog input[name="export_options_archive"]:checked').length);
}

// Reset style of data export option table rows
function resetExportOptionRows() {
	// Set bg color and border for all rows first
	$('table#export_choices_table tr td').css({'background':'#eee', 'border':'1px solid #eee', 'border-bottom':'1px solid #ddd'});
	$('table#export_choices_table tr td:last').css({'border':'1px solid #eee'});
	// Set for selected row
	$('table#export_choices_table tr td input[name="export_format"]:checked').parents('td:first').css({'background':'#d9ebf5', 'border':'1px solid #ccc'});
}

// Display "Working" export div as progress indicator
function showProgressExport(show,ms) {
	// Set default time for fade-in/fade-out
	if (ms == null) ms = 500;
	if (!$("#working_export").length) {
		$('body').append('<div id="working_export"><div style="margin:10px 20px 10px 10px;"><img src="'+app_path_images+'progress_circle.gif">&nbsp; '+langIconSaveProgress+'</div>'
			+ '<div style="margin:15px 10px 5px 0;font-weight:normal;font-size:12px;">'+langIconSaveProgress2+'</div>'
			+ '<div id="working_export_long" class="hidden yellow">'+langIconSaveProgress3+'</div>'
			+ '<div style="margin:10px 5px 2px;text-align:right;"><button id="export_cancel_btn" class="jqbuttonmed" style="font-size:11px;" onclick="cancelExportAjax()">'+window.lang.global_53+'</button></div></div>');
		$('#export_cancel_btn').button();
	}
	$('#working_export_long').addClass('hidden');
	if (!$("#fade").length) $('body').append('<div id="fade"></div>');
	if (show) {
		$('#fade').addClass('black_overlay').show();
		$('#working_export').center().fadeIn(ms);
	} else {
		setTimeout(function(){
			$("#fade").removeClass('black_overlay').hide();
			$("#working_export").fadeOut(ms);
		},ms);
	}
}

// Cancel the export data ajax request
function cancelExportAjax() {
	if (exportajax.readyState == 1) {
		exportajax.abort();		
		$('#working_export_long').addClass('hidden');
	}
	showProgressExport(0,0);
}

// Open data export dialog (to choose export format)
function showExportFormatDialog(report_id, odm_export_metadata) {
	if (odm_export_metadata == null) odm_export_metadata = false;
	// Hide the "export DAGs and survey fields" box unless a pre-defined report
	if (isNumeric(report_id)) {
		$('#exportFormatForm #export_dialog_dags_survey_fields_options').hide();
	} else {
		$('#exportFormatForm #export_dialog_dags_survey_fields_options').show();
	}
	// Get report name
	var report_name = 'report';
	if ($('table#table-report_list').length) {
		report_name = trim($('table#table-report_list tr#reprow_'+report_id+' td:eq(2)').text());
	} else if ($('#this_report_title').length) {
		report_name = trim($('#this_report_title').text());
	} else if ((page == 'ProjectSetup/other_functionality.php' || getParameterByName('other_export_options') == '1') && langExportWholeProject != null) {
		report_name = langExportWholeProject;
	}

	// Show only the REDCap whole project export option, if clicked the button to do so
	if (odm_export_metadata) {
		$('#export_format_fieldset').hide();
		$('#export_whole_project_fieldset').show();
		$('#exportFormatForm input[name="export_format"][value="odm_project"]').prop('checked', true);
		$('table#export_choices_table tr td').css({'background':'#eee', 'border':'1px solid #eee', 'border-bottom':'1px solid #ddd'});
		$('table#export_choices_table tr td:last').css({'border':'1px solid #eee'});
	} else {
		$('#export_format_fieldset').show();
		$('#export_whole_project_fieldset').hide();
	}
	// If live filters are selected, then show note in popup to filter export data
	if (typeof liveFiltersSelected !== 'undefined' && typeof liveFiltersSelected === 'function' && liveFiltersSelected()) {
		$('#export_dialog_live_filter_option').show();
	} else {
		$('#export_dialog_live_filter_option').hide();
	}
	// If we're exporting ODM metadata+data, then default the "Export blank values for gray instrument status?" to BLANK values
	if (report_id == 'ALL' && odm_export_metadata) {
		$('select[name=returnBlankForGrayFormStatus]').val('1');
	}
	// Set title text
	var title = "<i class='fas fa-file-download' style='vertical-align:middle;'></i> "
			  + "<span style='vertical-align:middle;font-size:15px;'>"+langExporting+" \""+report_name+"\"</span>";
	// Show dialog
	$('#exportFormatDialog').dialog({ title: title, bgiframe: true, modal: true, width: 1100, open: function(){ fitDialog(this) }, buttons:
		[{ text: closeBtnTxt, click: function() {
			$(this).dialog('close');
		}},
		{text: (odm_export_metadata ? exportBtnTxt2 : exportBtnTxt), click: function() {
			// Make sure necessary options are selected
			if (!exportFormatDialogSaveValidate()) {
				simpleDialog(langSaveValidate,langError);
				return;
			}
			// Set params
			var params = $('form#exportFormatForm').serializeObject();
			params.report_id = report_id;
			// Start clock so we can display progress for set amount of time
			var start_time = new Date().getTime();
			var min_wait_time = 1000;
			// Close dialog
			$('#exportFormatDialog').dialog('close');
			// Set notice to display "export may take a long time" after waiting 3 minutes
			setTimeout(function(){
				$('#working_export_long').removeClass('hidden');
			}, 3*60000);
			// Get all the form values and submit via ajax
			exportajax = $.post(app_path_webroot+'DataExport/data_export_ajax.php?pid='+pid+getSelectedInstrumentList()+getInstrumentsListFromURL()+((typeof getLiveFilterUrl !== 'undefined' && typeof getLiveFilterUrl === 'function') ? getLiveFilterUrl() : "")+getOdmMetadataOptions(), params, function(data) {
				//simpleDialog(data,null,null,1500);showProgressExport(0,0);return;
				if (data == '0' || data == '') {
					showProgressExport(0,0);
					simpleDialog(langExportFailed,langError);
					return;
				}
				// End clock
				var total_time = new Date().getTime() - start_time;
				// If total_time is less than min_wait_time, then wait till it gets to min_wait_time
				var wait_time = (total_time < min_wait_time) ? (min_wait_time-total_time) : 0;
				// Set wait time, if any
				setTimeout(function(){
					// Close other dialogs
					showProgressExport(0,0);
					try {
						// Parse JSON
						var json_data = jQuery.parseJSON(data);
						// Display success dialog
						simpleDialog(json_data.content, json_data.title, null, 750);
					} catch (e) {
						simpleDialog(langExportFailed,langError);
					}
				}, wait_time);
			})
			.fail(function(xhr, textStatus, errorThrown) {
				showProgressExport(0,0);
				if (xhr.statusText == 'Internal Server Error') simpleDialog(langExportFailed,langError);
			});
			// Set progress bar if still running after a moment
			setTimeout(function(){
				showProgressExport(1,300);
			},100);
		}}]
	});
	$('#exportFormatDialog').dialog("widget").find(".ui-dialog-buttonpane button").eq(1).css({'font-weight':'bold', 'color':'#333'});
}

// Show form name for report field: Add onchange trigger to display the form name next to the field drop-down
function showFormLabel(ob) {
	ob.each(function(){
		$(this).change(function(){
			var this_field = $(this).val();
			var this_row = $(this).parents('tr:first');
			var this_span = this_row.find('.fnb');
			if (this_field == '') {
				this_span.html('');
				this_row.find('.fna').css('visibility','hidden');
			} else {
				this_span.html( formLabels[fieldForms[$(this).val()]] );
				this_row.find('.fna').css('visibility','visible');
			}
		});
	});
}

// Get field label for a field
function getFieldLabel(field) {
	return $('#field-dropdown option[value="'+field+'"]').text();
}

// When user clicks field on Create Report step 2 to begin editing
function editReportField(ob, hideAutoSuggest) {	
	if (hideAutoSuggest == null) hideAutoSuggest = false;
	var row = ob.parents('tr:first');
	// Hide the disabled field and move the dropdown/autocomplete fields into this row
	row.find('input.field-dropdown').hide().parent().after( $('#field-dropdown-container').detach() );
	// Hide the dropdown and show the autocomplete
	$('#field-dropdown-container').show();
	if (hideAutoSuggest) {
		$('#field-dropdown-container select').show().val( row.find('.field-hidden').val() ).effect('highlight',{},1000).focus();
		$('#field-dropdown-container input').hide().val( row.find('.field-hidden').val() );
		row.find('.field-auto-suggest-a').show();
		row.find('.field-dropdown-a').hide();
	} else {
		$('#field-dropdown-container select').hide();
		$('#field-dropdown-container input').show().val( row.find('.field-hidden').val() ).effect('highlight',{},1000).focus();
		row.find('.field-auto-suggest-a').hide();
		row.find('.field-dropdown-a').show();
	}
}

// Reset current row
function resetRow(ob) {
	var rowob = ob.parents('tr:first');
	// Reset inputs
	rowob.find('.field-dropdown').show();
	// Reset buttons
	rowob.find('.field-auto-suggest-a').hide();
	rowob.find('.field-dropdown-a').show();
	// Move autocomplete dropdown to original position
	resetAcDdPosition();
}

// Move autocomplete dropdown to original position (out of the current row)
function resetAcDdPosition(){
	try {
		if ($('#field-dropdown-row #field-dropdown-container').length == 0) {
			$('#field-dropdown-row').append( $('#field-dropdown-container').detach() );
		}
	} catch(e) { }
}

// Reset current row
function resetRow1(ob) {
	var rowob = ob.parents('tr:first');
	var field = $('#field-dropdown').val();
	var fieldLabel = getFieldLabel(field);
	resetRow(ob);
	// Set value
	rowob.find('.field-hidden').val(field);
	rowob.find('.field-dropdown').show().val(fieldLabel);
}

// Add new report field row when creating/modifying report
function addNewReportRow(ob, skipValCheck) {
	if (typeof skipValCheck == 'undefined') skipValCheck = false;
	// Get row object and reset some CSS (in case highlight effect is still going on)
	var rowob = ob.parents('tr:first');
	var val = ob.val();
	
	if (!skipValCheck) {
		// Reset autocomplete dropdown values
		$('#field-dropdown-container select').hide().val('');
		$('#field-dropdown-container input').hide().val('');
		// Check value
		if (val == '') return false;
		// Make sure the field hasn't already been added to the report. If so, then return false;
		if ($('.field-hidden[value="'+val+'"]').length > (rprtft == 'dropdown' ? 0 : 1)) {
			// Give it an id number temporarily so we can reference it
			obId = "flddd-"+Math.floor(Math.random()*10000000000000000);
			rowob.find('.field-dropdown').attr('id', obId).val('');
			rowob.find('.field-hidden').val('');
			simpleDialog(langChooseOtherfield,null,null,null,"$('#"+obId+"').parent().trigger('click');");
			return false;
		}
		// Reset the row and move autocomplete to original position.
		resetRow(ob);
		rowob.find('.field-dropdown').val(getFieldLabel(val));
		rowob.find('.field-hidden').val(val);
	}
	
	//rowob.find('.field-dropdown, .field-auto-suggest').css('background','#fff');
	rowob.find('td').css('background','#F0F0F0 url("'+app_path_images+'label-bg.gif") repeat-x scroll 0 0');
	rowob.removeClass('nodrop');
	// Set trigger for onchange of field drop-down to display form name
	rowob.find('.fnb').html( formLabels[fieldForms[val]] );
	rowob.find('.fna').css('visibility','visible');
	// Get row
	var row = rowob.clone();
	$('#create_report_table tr.field_row:last').after(row);
	
	// In new row, make sure the drop-down value gets reset
	var newrow = $('#create_report_table tr.field_row:last');
	newrow.find('.field-dropdown, .field-hidden').val('');
	newrow.find('.field-auto-suggest').trigger('blur').removeAttr('id');
	newrow.find('.fnb').html('');
	newrow.find('.fna').css('visibility','hidden');
	// In new row, increment the row/field number
	var fieldnum_span = newrow.find('.field_num');
	var fieldnum = (fieldnum_span.text()*1)+1;
	fieldnum_span.html(fieldnum);
	// For IE8-9, it will sometimes append one extra row to the bottom of the table when adding a new row.
	// Not sure why this happens, but if it does, then remove the extra row.
	if (IEv <= 9 && fieldnum < $('#create_report_table tr.field_row').length) {
		$('#create_report_table tr.field_row:last').remove();
		return false;
	}
	// In new row, make sure auto-suggest field shows with drop-down hidden
	newrow.find('.field-dropdown-div').show();
	if (rprtft == 'dropdown') {
		newrow.find('.field-dropdown-a').hide();
		newrow.find('.field-auto-suggest-a').show();
	} else {
		newrow.find('.field-dropdown-a').show();
		newrow.find('.field-auto-suggest-a').hide();
	}
	rowob.find('a').show(); // Show delete icon
	// Remove the onchange event from the original row so that changing it doesn't trigger new rows to appear
	rowob.find('.field-dropdown').removeAttr('onchange');
	// Trigger the form label display
	rowob.find('.field-dropdown').trigger('change');
	// Set hover action for table row to display dragHandle icon
	setTableRowHover(rowob);
	// Enable dragHandle for row
	rowob.find('td:first').addClass('dragHandle');
	setDragHandleHover(rowob.find('td.dragHandle'));
	// Enable table drag n drop for new row
	if (!skipValCheck) $('table#create_report_table').tableDnDUpdate();
	// Highlight new row
	if (!skipValCheck) highlightTableRowOb(newrow, 2000);
	// Add auto suggest trigger to new row
	//enableAutoSuggestFields();
	// Put cursor in the new row's auto suggest text box
	if (!skipValCheck) editReportField(newrow.find('.field-dropdown'), (rprtft == 'dropdown'));
}

// Reset the "Field X" text for report field rows
function resetFieldNumLabels() {
	var k = 1;
	$('.field_num').each(function(){
		$(this).html(k++);
	});
}

// Delete report field row
function deleteReportField(ob) {
	var row = ob.parents('tr:first');
	// Remove it
	highlightTableRowOb(row, 700);
	setTimeout(function(){
		row.hide('fade',function(){
			// Remove the rows and run other things
			row.remove();
			// Reset the "Field X" text for report field rows
			resetFieldNumLabels()
		});
	},200);
}

// Delete filter field row
function deleteLimiterField(ob) {
	var row = ob.parents('tr:first');
	var prevrow = row.prev();
	// Remove them
	highlightTableRowOb(prevrow, 700);
	highlightTableRowOb(row, 700);
	setTimeout(function(){
		prevrow.hide('fade');
		row.hide('fade',function(){
			// Remove the rows and run other things
			prevrow.remove();
			row.remove();
			// Reset the "Filter X" text
			var k = 1;
			$('.limiter_num').each(function(){
				$(this).html(k++);
			});
			// Make sure the limiter group row is not displayed for the first limiter field
			if ($('.lgoc:first').val() == 'OR') {
				// Change to AND
				$('.lgoc:first').val('AND');
				$('.lgoo:first').val('AND').css('visibility','hidden');
			}
			$('.lgoc:first').parents('tr:first').hide();
		});
	},200);
}

// Add new report limiter row when creating/modifying report
function addNewLimiterRow(ob) {
	if (ob.val() == '') return false;
	// Get row and preceding limiter group row
	var rowob = ob.parents('tr:first');
	// Get row object and reset some CSS (in case highlight effect is still going on)
	rowob.find('.limiter-dropdown, .field-auto-suggest').css('background','#fff');
	rowob.find('td').css('background','#F0F0F0 url("'+app_path_images+'label-bg.gif") repeat-x scroll 0 0');
	var limit_group = rowob.find('.lgoo').val();
	var row = rowob.clone();
	var limiter_group_row = rowob.prev().clone();
	$('#create_report_table tr.limiter_row:last').after(row).after(limiter_group_row);
	// In new row, make sure the drop-down value gets reset
	var newrow = $('#create_report_table tr.limiter_row:last');
	newrow.find('.limiter-dropdown, .field-auto-suggest, .limiter-operator, .limiter-value').val('');
	newrow.find('.field-auto-suggest').trigger('blur').removeAttr('id');
	// Set AND/OR grouping options correctly
	var new_limit_group_row = newrow.prev();
	new_limit_group_row.find('.lgoc').val(limit_group);
	newrow.find('.lgoo').val(limit_group).css('visibility',(limit_group == 'AND' ? 'hidden' : 'visible'));
	if (limit_group == 'AND') {
		new_limit_group_row.show();
	} else {
		new_limit_group_row.hide();
	}
	// In new row, increment the row/field number
	var fieldnum_span = newrow.find('.limiter_num');
	var fieldnum = $('.limiter_num').length;
	fieldnum_span.html(fieldnum);
	// In new row, make sure auto-suggest field shows with drop-down hidden
	if (rprtft == 'text') {
		newrow.find('.limiter-dropdown-div').hide();
		newrow.find('.field-auto-suggest-div').show();
	} else {
		newrow.find('.limiter-dropdown-div').show();
		newrow.find('.field-auto-suggest-div').hide();
	}
	rowob.find('a').show(); // Show delete icon
	// Remove the onchange event from the original row so that changing it doesn't trigger new rows to appear
	rowob.find('.limiter-dropdown').attr('onchange','fetchLimiterOperVal($(this));');
	// Highlight new row
	highlightTableRowOb(newrow, 2000);
	// Add auto suggest trigger to new row
	enableAutoSuggestFields();
}

// Show or hide auto suggest limiter field text box
function showLimiterFieldAutoSuggest(ob, hideAutoSuggest) {
	if (hideAutoSuggest == null) hideAutoSuggest = true;
	var row = ob.parents('tr:first');
	if (hideAutoSuggest) {
		row.find('.limiter-dropdown-div').show();
		row.find('.field-auto-suggest-div').hide();
		// If auto-suggest value matches drop-down value, then copy it
		var auto_suggest_val = row.find('.field-auto-suggest').val();
		if (row.find('.limiter-dropdown option[value="'+auto_suggest_val+'"]')) {
			row.find('.limiter-dropdown').val(auto_suggest_val).effect('highlight',{},1000);
		}
	} else {
		row.find('.limiter-dropdown-div').hide();
		row.find('.field-auto-suggest-div').show();
		// Copy drop-down value into auto suggest text box
		var dropdown_val = row.find('.limiter-dropdown').val();
		if (dropdown_val != '') {
			row.find('.field-auto-suggest').val(dropdown_val).css('color','#000');
		} else {
			row.find('.field-auto-suggest').val('').css('color','#bbb').trigger('blur');
		}
		row.find('.field-auto-suggest').effect('highlight',{},1000);
	}
}

// Show or hide auto suggest sort field text box
function showSortFieldAutoSuggest(ob, hideAutoSuggest) {
	if (hideAutoSuggest == null) hideAutoSuggest = true;
	var row = ob.parents('tr:first');
	if (hideAutoSuggest) {
		row.find('.sort-dropdown-div').show();
		row.find('.field-auto-suggest-div').hide();
		// If auto-suggest value matches drop-down value, then copy it
		var auto_suggest_val = row.find('.field-auto-suggest').val();
		if (row.find('.sort-dropdown option[value="'+auto_suggest_val+'"]')) {
			row.find('.sort-dropdown').val(auto_suggest_val).effect('highlight',{},1000);
		}
	} else {
		row.find('.sort-dropdown-div').hide();
		row.find('.field-auto-suggest-div').show();
		// Copy drop-down value into auto suggest text box
		var dropdown_val = row.find('.sort-dropdown').val();
		if (dropdown_val != '') {
			row.find('.field-auto-suggest').val(dropdown_val).css('color','#000');
		} else {
			row.find('.field-auto-suggest').val('').css('color','#bbb').trigger('blur');
		}
		row.find('.field-auto-suggest').effect('highlight',{},1000);
	}
}

// Show or hide auto suggest live filter field text box
function showLiveFilterFieldAutoSuggest(ob, hideAutoSuggest) {
	if (hideAutoSuggest == null) hideAutoSuggest = true;
	var row = ob.parents('tr:first');
	if (hideAutoSuggest) {
		row.find('.livefilter-dropdown-div').show();
		row.find('.field-auto-suggest-div').hide();
		// If auto-suggest value matches drop-down value, then copy it
		var auto_suggest_val = row.find('.field-auto-suggest').val();
		if (row.find('.livefilter-dropdown option[value="'+auto_suggest_val+'"]')) {
			row.find('.livefilter-dropdown').val(auto_suggest_val).effect('highlight',{},1000);
		}
	} else {
		row.find('.livefilter-dropdown-div').hide();
		row.find('.field-auto-suggest-div').show();
		// Copy drop-down value into auto suggest text box
		var dropdown_val = row.find('.livefilter-dropdown').val();
		if (dropdown_val != '') {
			row.find('.field-auto-suggest').val(dropdown_val).css('color','#000');
		} else {
			row.find('.field-auto-suggest').val('').css('color','#bbb').trigger('blur');
		}
		row.find('.field-auto-suggest').effect('highlight',{},1000);
	}
}

// Add id to all auto suggest text boxes and enable auto suggest for them
function enableAutoSuggestFields() {
	$('table#create_report_table .field-auto-suggest').each(function(){
		var ob = $(this);
		var obId = ob.attr('id');
		if (obId == null) {
			obId = "autosug-"+Math.floor(Math.random()*10000000000000000);
			ob.attr('id', obId);
			var minLength = (autoSuggestFieldList.length > 300 ? (autoSuggestFieldList.length > 1000 ? 3 : 2) : 1);
			// Enable auto suggest
			$('#'+obId).autocomplete({ delay: 0, source: autoSuggestFieldList, minLength: minLength,
				select: function( event, ui ) {
					// Get just the variable name
					var thisvar = ui.item.value;
					thisvar = thisvar.substring(0, thisvar.indexOf(' '));
					$(this).val(thisvar);
					var thisrow = $('#'+obId).parents('tr:first');
					// Now display the drop-down in place of the auto suggest text box and trigger the change event for the drop-down
					if (thisrow.find('.field-dropdown').length) {
						rprtft = "text";				
						$('#field-dropdown-container').hide();
						thisrow.find('.field-hidden').val( $(this).val() );
						thisrow.find('.field-dropdown').show().val( getFieldLabel($(this).val()) );
						resetRow(ob);
						addNewReportRow(thisrow.find('.field-hidden'));		
					} else if (thisrow.find('.limiter-dropdown').length) {
						rprtft = "text";
						showLimiterFieldAutoSuggest(thisrow.find('.limiter-dropdown-a'),true);
						addNewLimiterRow(thisrow.find('.limiter-dropdown'));
						thisrow.find('.limiter-dropdown').trigger('change');
					} else {
						showSortFieldAutoSuggest(thisrow.find('.sort-dropdown-a'),true);
						thisrow.find('.sort-dropdown').trigger('change');
					}
					return false;
				}
			});
		}
	});
}

// Onkeydown action for Auto Suggest text box
function asdown(e) {
	if (e.keyCode == 13) {
		e.preventDefault();	
	}
}

// Onblur action for Auto Suggest text box
function asblur(ob) {
	ob = $(ob);
	ob.val( trim(ob.val()) );
	var val_entered = ob.val();
	var valueBlank = (val_entered == '');
	var id = ob.attr('id');
	var thisrow = ob.parents('tr:first');
	if (valueBlank) {
		//ob.val(langTypeVarName).css('color','#bbb');
		// Set corresponding drop-down with blank value (for consistency)
		if (thisrow.find('.field-hidden').length > 0) {
			thisrow.find('.field-hidden').val('');
			thisrow.find('.field-dropdown').val('');
			resetRow(ob);
		} else {
			ob.parents('td:first').find('select').val('');
		}
	} else if (!valueBlank) {
		//setTimeout(function(){
			// If entered value does not match anything in the drop-down, then give error msg
			var isAutoSuggestVisible = $('#'+id).is(":visible");
			if (isAutoSuggestVisible && !ob.parents('td:first').find('select option[value="'+val_entered+'"]').length) {
				setTimeout(function(){
					simpleDialog(null,null,'VarEnteredNoExist_dialog',null,"$('#"+id+"').focus();");
				},10);
			} else if (isAutoSuggestVisible) {
				if (thisrow.find('.field-dropdown').length) {
					rprtft = 'text';
					$('#field-dropdown-container').hide();
					thisrow.find('.field-hidden').val( val_entered );
					thisrow.find('.field-dropdown').show().val( getFieldLabel(val_entered) );
					resetRow(ob);
					addNewReportRow(thisrow.find('.field-hidden'));
				} else if (thisrow.find('.limiter-dropdown').length) {
					rprtft = 'text';
					showLimiterFieldAutoSuggest(thisrow.find('.limiter-dropdown-a'),true);
					addNewLimiterRow(thisrow.find('.limiter-dropdown'));
					thisrow.find('.limiter-dropdown').trigger('change');
				} else {
					showSortFieldAutoSuggest(thisrow.find('.sort-dropdown-a'),true);
					thisrow.find('.sort-dropdown').trigger('change');
				}
			}
		//},5);
	}
}

// Fetch limiter's operator/value pair via ajax
function fetchLimiterOperVal(ob) {
	$.post(app_path_webroot+'DataExport/report_filter_ajax.php?pid='+pid, { field_name: ob.val() }, function(data) {
		if (data == '0') {
			alert(woops);
			return;
		}
		// Find the table cell where limiter-operator is located and place return HTML there
		var td = ob.parents('tr:first').find('.limiter-operator').parents('td:first');
		td.html(data).effect('highlight',{},2000);
		td.find('.limiter-operator').focus();
		// Enable date/time picker in case the field just loaded is a date/time field
		initDatePickers();
	});
}

// Obtain list of usernames who would have access to a report based on the User Access selections on the page
function getUserAccessList(access_type) {
	access_type = (access_type != 'view') ? 'edit' : 'view'
	// Save the report via ajax
	$.post(app_path_webroot+'DataExport/report_user_access_list.php?pid='+pid+'&access_type='+access_type, $('form#create_report_form').serializeObject(), function(data) {
		if (data == '0') {
			alert(woops);
			return;
		}
		// Parse JSON
		var json_data = jQuery.parseJSON(data);
		simpleDialog(json_data.content, json_data.title, null, 600);
	});
}

// Save the new/existing report
function saveReport(report_id) {
	// Validate the report fields
	if (!validateCreateReport()) return false;
	// Validate the advanced filtering logic (if used)
	if (!check_advanced_logic()) return false;
	// Start clock so we can display progress for set amount of time
	var start_time = new Date().getTime();
	var min_wait_time = 500;
	// Save the report via ajax
	$.post(app_path_webroot+'DataExport/report_edit_ajax.php?pid='+pid+'&report_id='+report_id, $('form#create_report_form').serializeObject(), function(data) {
		if (data == '0') {
			showProgress(0,0);
			alert(woops);
			return;
		} else if (data == '2') {
			showProgress(0,0);
			simpleDialog(lang.report_builder_214);
			return;
		}
		// Update left-hand menu panel of Reports
		updateReportPanel();
		// Parse JSON
		var json_data = jQuery.parseJSON(data);
		// Build buttons for dialog
		var btns =	[{ text: 'Continue editing report', click: function() {
						if (json_data.newreport) {
							// Reload page with new report_id
							showProgress(1);
							window.location.href = app_path_webroot+'DataExport/index.php?pid='+pid+'&report_id='+json_data.report_id+'&addedit=1';
						} else {
							$(this).dialog('close').dialog('destroy');
						}
					}},
					{text: 'Return to My Reports & Exports', click: function() {
						window.location.href = app_path_webroot+'DataExport/index.php?pid='+pid;
					}},
					{text: 'View report', click: function() {
						window.location.href = app_path_webroot+'DataExport/index.php?pid='+pid+'&report_id='+json_data.report_id;
					}}];
		// End clock
		var total_time = new Date().getTime() - start_time;
		// If total_time is less than min_wait_time, then wait till it gets to min_wait_time
		var wait_time = (total_time < min_wait_time) ? (min_wait_time-total_time) : 0;
		// Set wait time, if any
		setTimeout(function(){
			showProgress(0,0);
			// Display success dialog
			initDialog('report_saved_success_dialog');
			$('#report_saved_success_dialog').html(json_data.content).dialog({ bgiframe: true, modal: true, width: 640,
				title: json_data.title, buttons: btns, close: function(){
					if (json_data.newreport) {
						// Reload page with new report_id
						showProgress(1);
						window.location.href = app_path_webroot+'DataExport/index.php?pid='+pid+'&report_id='+json_data.report_id+'&addedit=1';
					} else {
						$(this).dialog('destroy');
					}
				} });
			$('#report_saved_success_dialog').dialog("widget").find(".ui-dialog-buttonpane button").eq(2).css({'font-weight':'bold', 'color':'#333'});
		}, wait_time);
	});
	// Set progress bar if still running after a moment
	setTimeout(function(){
		showProgress(1,300);
	},100);
}

// Validate report attributes when adding/editing report
function validateCreateReport() {
	// Make sure there is a title
	var title_ob = $('#create_report_table input[name="__TITLE__"]');
	title_ob.val( trim(title_ob.val()) );
	if (title_ob.val() == '') {
		simpleDialog(langNoTitle,null,null,null,"$('#create_report_table input[name=__TITLE__]').focus();");
		return false;
	}
	// If doing custom user access, make sure something is selected
	if ($('#create_report_table input[name="user_access_radio"]:checked').val() != 'ALL'
		&& ($('#create_report_table select[name="user_access_users"] option:selected').length
			+ $('#create_report_table select[name="user_access_dags"] option:selected').length
			+ $('#create_report_table select[name="user_access_roles"] option:selected').length) == 0) {
		simpleDialog(langNoUserAccessSelected);
		return false;
	}
	// Make sure that at least one field is selected to view in report
	if ($('input.field-hidden[value!=""]').length == 0) {
		simpleDialog(langNoFieldsSelected);
		return false;
	}
	// Filters: Make sure that each has an operator selected (value is allowed to be blank for text fields)
	var limiter_error_count = 0;
	$('.limiter-dropdown option:selected[value!=""]').each(function(){
		if ($(this).parents('tr:first').find('.limiter-operator').val() == '') {
			limiter_error_count++;
		}
	});
	if (limiter_error_count > 0) {
		simpleDialog(limiter_error_count+" "+langLimitersIncomplete);
		return false;
	}
	// If we made it this far, then all is well
	return true;
}

// For multi-selects, if the last selected option is clicked, then de-select it
function clearMultiSelect(ob) {
	var selections = $(ob).find('option:selected').map(function(){ return this.value }).get().length;
}

// For the pre-defined report "Selected instruments/events", obtain the instruments/events selected
// in the multi-selects on the My Reports page, and make them comma-delimited to append to a URL.
function getSelectedInstrumentList(returnAllOnBlank) {
	returnAllOnBlank = !!returnAllOnBlank;
	// Get selected instruments
	var instruments = $('select#export_selected_instruments option:selected').map(function(){ return this.value }).get().join(",");
	if (instruments == '' && returnAllOnBlank) {
		instruments = $('select#export_selected_instruments option').map(function(){ return this.value }).get().join(",");
	}
	var instrumentsParam = (instruments == '') ? '' : '&instruments='+instruments;
	// Get selected events
	var events = '';
	if ($('select#export_selected_events').length) {
		var events = $('select#export_selected_events option:selected').map(function(){ return this.value }).get().join(",");
		if (events == '' && returnAllOnBlank) {
			events = $('select#export_selected_events option').map(function(){ return this.value }).get().join(",");
		}
	}
	var eventsParam = (events == '') ? '' : '&events='+events;
	// Return string
	return instrumentsParam+eventsParam;

}

// Get URL for appending ODM metadata options to report AJAX URL (obtain from checkboxes on Other Functionality page)
function getOdmMetadataOptions() {
	var url = new Array();
	var i=0;
	$('input.xml_options:checked').each(function(){
		url[i++] = $(this).val();
	})
	return '&xml_metadata_options='+url.join(',');
}

// Display "Quick Add" dialog
function openQuickAddDialog() {
	// Collect all fields already selected to include in the report
	var flds = new Array();
	var i = 0;
	var val;
	$('.field-hidden').each(function(){
		val = $(this).val();
		if (val != '') {
			flds[i++] = val;
		}
	});
	// Ajax call
	$.post(app_path_webroot+'DataExport/report_quick_add_field_ajax.php?pid='+pid, { checked_fields: flds.join(',') }, function(data){
		try {
			// Parse JSON
			var json_data = jQuery.parseJSON(data);
		} catch (e) {
			simpleDialog(woops,langError);
			return;
		}
		// Display success dialog
		simpleDialog(null, json_data.title, 'quickAddField_dialog', 600, function(){ 
			// Enable table drag n drop for new row
			$('table#create_report_table').tableDnDUpdate();
		}, 'Close');
		$('#quickAddField_dialog').html(json_data.content);
		fitDialog($('#quickAddField_dialog'));
		// Add text inside dialog's button pane
		var bptext = '<div style="float:right;margin:10px 100px 0 0;color:#444;font-weight:bold;font-size:14px;">'+langTotFldsSelected+' '
				   + '<span id="quickAddField_count" style="color:#800000;font-size:16px;">'+$('#quickAddField_dialog input[type="checkbox"]:checked').length+'</span></div>';
		$('#quickAddField_dialog').dialog("widget").find(".ui-dialog-buttonpane button").eq(0).after(bptext);
	});
}

// Add or delete field via "Quick Add" dialog
function qa(ob) {
	var fld = ob.attr('name');
	var isChecked = ob.prop('checked');
	if (isChecked) {
		// Make sure not already added to report
		if ($('.field-hidden[value="'+fld+'"]').length == 0) {
			// Add row
			$('.field-hidden:last').val(fld);
			$('.field-dropdown:last').show().val(getFieldLabel(fld));
			addNewReportRow($('.field-hidden:last'), true);
			highlightTableRowOb($('.field-hidden[value="'+fld+'"]').parents('tr:first'), 2000);
		}
	} else {
		// Remove row
		var dd = $('.field-hidden[value="'+fld+'"]');
		dd.parents('tr:first').find('td:last').find('a').trigger('click');
	}
	// Set total count
	$('#quickAddField_count').html( $('#quickAddField_dialog input[type="checkbox"]:checked').length );
}

// Select all fields for a form in "Quick Add" dialog
function reportQuickAddForm(form,select) {
	$('#quickAddField_dialog .frm-'+form).each(function(){
		var ob = $(this);
		var isChecked = ob.prop('checked');
		if ((!isChecked && select) || (isChecked && !select)) {
			ob.prop('checked', !isChecked);
			qa(ob);
		}
	});
}

//Data Cleaner functions
function ToggleDataCleanerDiv(fid,prefix,divId,spinId,field,svc,formVar,group_id,usingGCT) {
	if (usingGCT == null) usingGCT = false;
	var d = document.getElementById(divId);
	if (d.style.display != 'none' && d.style.display != '') {
		d.style.display = 'none';
		return;
	}
	/* Else we've got something to do */
	var s = document.getElementById(spinId);
	s.style.display = 'inline';
	//AJAX request to fetch values
	$.post(app_path_webroot+'DataExport/stats_highlowmiss.php?pid='+pid, { field: field, svc: svc, group_id: group_id, includeRecordsEvents: includeRecordsEvents }, function(data) {
		var val;
		var label;
		var id;
		var evtid;
		var instance;
		var html = prefix;
		s.style.display='none';
		/* element 0 is the count */
		var case_id = data.split('|');
		if (case_id[0] == 0) {
			//Zero records returned
			html += 'none';
		} else {
			//More than zero records returned. Parse them.
			for (var i = 1; i <= case_id[0]; i++) {
				var idv = case_id[i].split(':');
				id = idv[0];
				if (idv.length == 4){
					//High or Low values
					val = idv[1];
					evtid = idv[2];
					instance = idv[3];
				} else {
					//Missing values
					val = idv[0];
					evtid = idv[1];
					instance = idv[2];
				}
				if (instance > 1) val += " (#"+instance+")";
				html += '<a target="_blank" style="text-decoration:underline;" onclick="$(\'#'+field+'-mperc\').html(\'\'); return true;" href="'+app_path_webroot+'DataEntry/index.php?pid='+pid+'&page='+formVar+'&event_id='+evtid+'&id='+id+'&instance='+instance+'&fldfocus='+field+'#'+field+'-tr">'+val+'</a>, ';
			}
			html = html.substring(0,html.length-2);
		}
		d.innerHTML = html;
		d.style.display='block';
	});
}

// For the pre-defined report "Selected instruments/events", obtain the instruments/events in the query string of the URL
// and return them in order to append to another URL's query string.
function getInstrumentsListFromURL() {
	var instruments = getParameterByName('instruments');
	var instrumentsParam = (instruments == '') ? '' : '&instruments='+instruments;
	var events = getParameterByName('events');
	var eventsParam = (events == '') ? '' : '&events='+events;
	return instrumentsParam+eventsParam;
}