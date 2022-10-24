<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
// If we have a allowlist of records/events due to report filtering, unserialize it
$includeRecordsEvents = (isset($_POST['includeRecordsEvents'])) ? unserialize(decrypt($_POST['includeRecordsEvents']), ['allowed_classes'=>false]) : array();
// Set flag if there are no records returned for a filter (so we can disguish this from a full data set with no filters)
$hasFilterWithNoRecords = (isset($_POST['hasFilterWithNoRecords']) && $_POST['hasFilterWithNoRecords'] == '1');

print \DataExport::chartData($_POST['fields'], $user_rights['group_id'], $includeRecordsEvents, $hasFilterWithNoRecords);
?>