<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";
require_once "../../../includes/app/bootstrap.php";

requireLogin();

use MyReadyLink\Reports\AnalyticsReport;

$analyticsReportStart = new DateTime(isset($_GET['analytics_start']) ? $_GET['analytics_start'] : 'yesterday - 30 days');
$analyticsReportEnd =   new DateTime(isset($_GET['analytics_end'])   ? $_GET['analytics_end']   : 'yesterday');
$listingId = 0;
if(isset($_GET['id']) && is_numeric($_GET['id']))
    $listingId = $_GET['id'];

$analyticsOutput = '';

$analyticsReport = AnalyticsReport::getTrafficReportDataForListing($listingId, $analyticsReportStart, $analyticsReportEnd, $connection);

$twigLoader = new Twig_Loader_Filesystem(APP_BASE_PATH . "/views/admin");
$twigLoader->addPath(APP_BASE_PATH . "/views/admin/default", "admin"); // theming
$twig = new Twig_Environment($twigLoader, array('debug' => false, 'cache' => APP_BASE_PATH . "/cache/views/admin", 'auto_reload' => true));

$timeIntervalFilter = new Twig_SimpleFilter("interval", function($value, $format = "%I:%S") {
    $dstart = new DateTime('today');
    $dend = new DateTime('today');
    $dend->add(new DateInterval(sprintf("PT%dS", round(floatval($value)))));
    
    return $dend->diff($dstart)->format($format);
    
});
$twig->addFilter($timeIntervalFilter);


$analyticsOutput = $twig->render('@admin/reports/listing-analytics-csv.twig.php', array('report'=>$analyticsReport));

$connection->close();


$fileName = normalizeString(sprintf('myreadylink-%1$s-%2$s-%3$s.csv', str_replace('-','',$analyticsReport['title']), $analyticsReportStart->format('Y-m-d'), $analyticsReportEnd->format('Y-m-d')));

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
header('Cache-Control: private');
header('Pragma: private');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
echo $analyticsOutput;
?>