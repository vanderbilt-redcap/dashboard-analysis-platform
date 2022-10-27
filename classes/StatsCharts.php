<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

class StatsCharts{
    // Display all charts and statistics on page and use AJAX to load the charts
    public static function outputStatsCharts($report_id=null,
        // The parameters below are ONLY used for $report_id == 'SELECTED'
                                             $selectedInstruments=array(), $selectedEvents=array(),$page)
    {
        global $lang, $Proj, $longitudinal, $user_rights, $enable_plotting;

        // Must have Graphical rights for this
        if (!$user_rights['graphical'] || !$enable_plotting) return $lang['global_01'];

        // Check if mycrypt is loaded because it is required
        if (!openssl_loaded()) {
            return \RCView::div(array('class'=>'red'),
                \RCView::tt("global_236"));
        }

        // Get report name
        $report_name = \DataExport::getReportNames($report_id, !$user_rights['reports']);

        // If report name is NULL, then user doesn't have Report Builder rights AND doesn't have access to this report
        if ($report_name === null) {
            return 	\RCView::div(array('class'=>'red'),
                $lang['global_01'] . $lang['colon'] . " " . $lang['data_export_tool_180']
            );
        }

        // Get report attributes
        $report = \DataExport::getReports($report_id, $selectedInstruments, $selectedEvents);
        // Obtain any dynamic filters selected from query string params
        list ($liveFilterLogic, $liveFilterGroupId, $liveFilterEventId) = \DataExport::buildReportDynamicFilterLogic($report_id);
        // Get num results returned for this report (using filtering mechanisms)
        list ($includeRecordsEvents, $num_results_returned) = \DataExport::doReport($report_id, 'report', 'html', false, false, false, false, false, false,
            false, false, false, false, false, array(), array(), true,
            false, false, true, true, $liveFilterLogic, $liveFilterGroupId, $liveFilterEventId);
        // If there are no filters, then set $includeRecordsEvents as empty array for faster processing
        if ($liveFilterLogic == '' && $liveFilterGroupId == '' && $liveFilterEventId == ''
            && $report['limiter_logic'] == '' && empty($report['limiter_dags']) && empty($report['limiter_events'])) {
            $includeRecordsEvents = array();
        }

        $report_description = $report['description'];

        // Set flag if there are no records returned for a filter (so we can distinguish this from a full data set with no filters)
        $hasFilterWithNoRecords = (empty($includeRecordsEvents)
            && ($report['limiter_logic'].$liveFilterLogic != '' || !empty($report['limiter_dags']) || !empty($report['limiter_events'])));
        // For ALL fields, give option to select specific form and yield only its fields
        if ($report_id == 'ALL') {
            // If there is only one form in this project, then set it automatically
            if (count($Proj->forms) == 1) $page = $Proj->firstForm;
            // Set fields
            if (isset($page) && isset($Proj->forms[$page])) {
                $report['fields'] = array_keys($Proj->forms[$page]['fields']);
            } else {
                $report['fields'] = array();
            }
        }

        // Obtain the fields to chart (they may be a subset of the fields in the report because not all fields
        // can be listed in the graphical view based on data type
        $fields = \DataExport::getFieldsToChart(PROJECT_ID, "", $report['fields']);

        // Get all HTML for charts and stats
        ob_start();
        \DataExport::renderCharts(PROJECT_ID, \DataExport::getRecordCountByForm(PROJECT_ID), $fields, "", $includeRecordsEvents, $hasFilterWithNoRecords);
        $charts_html = ob_get_clean();
        // Build dynamic filter options (if any)
        $dynamic_filters = \DataExport::displayReportDynamicFilterOptions($report_id);

        // Set html to return
        $html = "";
        // Action buttons
        $html .= \RCView::div(array('style'=>'margin: 10px 0px '.($dynamic_filters == '' ? '20' : '5').'px;'),
            \RCView::div(array('class'=>'', 'style'=>'float:left;width:350px;padding-bottom:5px;'),
                \RCView::div(array('style'=>'font-weight:bold;'),
                    $lang['custom_reports_02'] .
                    \RCView::span(array('style'=>'margin-left:5px;color:#800000;font-size:15px;'), $num_results_returned)
                ) .
                \RCView::div(array('style'=>''),
                    $lang['custom_reports_03'] .
                    \RCView::span(array('style'=>'margin-left:5px;'), \Records::getCountRecordEventPairs()) .
                    (!$longitudinal ? "" :
                        \RCView::div(array('style'=>'margin-top:3px;color:#888;font-size:11px;font-family:tahoma,arial;'),
                            $lang['custom_reports_09']
                        )
                    )
                )
            ) .
            \RCView::div(array('class'=>'d-print-none', 'style'=>'float:left;'),
                // Buttons: Stats, Export, Print, Edit
                \RCView::div(array(),
                    // Public link
                    (!($report['is_public'] && $user_rights['user_rights'] && $GLOBALS['reports_allow_public'] > 0) ? "" :
                        \RCView::a(array('href'=>($report['short_url'] == "" ? APP_PATH_WEBROOT_FULL.'surveys/index.php?__report='.$report['hash'] : $report['short_url']), 'target'=>'_blank', 'class'=>'text-primary fs12 nowrap mr-3 ml-1 align-middle'),
                            '<i class="fas fa-link"></i> ' .$lang['dash_35']
                        )
                    ) .
                    // View Report button
                    \RCView::button(array('class'=>'report_btn jqbuttonmed', 'style'=>'margin:0;color:#008000;font-size:12px;', 'onclick'=>"window.location.href = '".APP_PATH_WEBROOT."DataExport/index.php?pid=".PROJECT_ID."&report_id=$report_id'+getInstrumentsListFromURL()+getLiveFilterUrl();"),
                        '<i class="fas fa-search"></i> ' .$lang['report_builder_44']
                    ) .
                    \RCView::SP .
                    // Export Data button
                    ($user_rights['data_export_tool'] == '0' ? '' :
                        \RCView::button(array('class'=>'report_btn jqbuttonmed', 'onclick'=>"showExportFormatDialog('$report_id');", 'style'=>'color:#000066;font-size:12px;'),
                            '<i class="fas fa-file-download"></i> ' .$lang['report_builder_48']
                        )
                    ) .
                    \RCView::SP .
                    // Print link
                    \RCView::button(array('class'=>'report_btn jqbuttonmed', 'onclick'=>"window.print();", 'style'=>'font-size:12px;'),
                        \RCView::img(array('src'=>'printer.png', 'style'=>'vertical-align:middle;')) .
                        \RCView::span(array('style'=>'vertical-align:middle;'),
                            $lang['custom_reports_13']
                        )
                    ) .
                    (($report_id == 'ALL' || $report_id == 'SELECTED' || !$user_rights['reports'] || (is_numeric($report_id) && !$report_edit_access)) ? '' :
                        \RCView::SP .
                        // Edit report link
                        \RCView::button(array('class'=>'report_btn jqbuttonmed', 'onclick'=>"window.location.href = '".APP_PATH_WEBROOT."DataExport/index.php?pid=".PROJECT_ID."&report_id=$report_id&addedit=1';", 'style'=>'font-size:12px;'),
                            '<i class="fas fa-pencil-alt fs10"></i> ' .$lang['custom_reports_14']
                        )
                    )
                ) .
                // Build dynamic filter options (if any)
                $dynamic_filters
            ) .
            \RCView::div(array('class'=>'clear'), '')
        );
        // Report title
        $html .= \RCView::div(array('id'=>'this_report_title', 'style'=>'padding:5px 3px;color:#800000;font-size:18px;font-weight:bold;'),
            $report_name
        );
        // Report description (if has one)
        if ($report_description != '') {
            $html .= \RCView::div(array('id'=>'this_report_description', 'style'=>'max-width:1100px;padding:5px 3px 5px;line-height:15px;'),
                \Piping::replaceVariablesInLabel(filter_tags($report_description))
            );
            // Output the JavaScript to display all Smart Charts on the page
            $html .= \Piping::outputSmartChartsJS();
        }
        // Charts and stats
        $html .= $charts_html;
        // Return HTML
        return $html;
    }
}

?>