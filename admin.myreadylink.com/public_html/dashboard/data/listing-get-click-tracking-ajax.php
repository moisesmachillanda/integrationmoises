<?php
ini_set('memory_limit', '512M');
$time_start = microtime(true);

// MySQL settings
ini_set('mysql.connect_timeout', 300);
ini_set('default_socket_timeout', 300);

require_once "../../../../includes/config.php";
require_once "../../../../includes/app/bootstrap.php";

session_name(SESSIONNAME);
session_start();

require_once "../../../../includes/functions.php";

requireLogin();

use MyReadyLink\Reports\AnalyticsReport;

header('Content-type: application/json');
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

// $listing = new MyReadyLink\Reports\ListingData($_POST['id'], $connection);

$analytics_start = $_POST['analytics_start'];
if (!empty($analytics_start))
    $analytics_start = new DateTime($analytics_start);

$analytics_end   = $_POST['analytics_end'];
if (!empty($analytics_end))
    $analytics_end = new DateTime($analytics_end);

$report = AnalyticsReport::getClickReportDataForLisitng(intval($_POST['listingId']), $analytics_start, $analytics_end, $connection);

$connection->close();

$time_end = microtime(true);
$time_exec = doubleval(sprintf("%01.2f", $time_end - $time_start));

usleep(300);

echo json_encode(array("result"=>true, "report" => $report, "processingTime" => $time_exec));
exit();





echo json_encode(array("result"=>false));


?>
