<?php
$time_start = microtime(true);

require_once "../../../../includes/config.php";
require_once "../../../../includes/app/bootstrap.php";

session_name(SESSIONNAME);
session_start();

require_once "../../../../includes/functions.php";

requireLogin();

use \Geocoder\Provider;
use \Ivory\HttpAdapter;

header('Content-type: application/json');
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$geoData = array();

// $adapter = new \Ivory\HttpAdapter\FileGetContentsHttpAdapter();
$adapter = new \Ivory\HttpAdapter\CurlHttpAdapter();
$geocoder = new \Geocoder\Provider\GoogleMaps($adapter, 'en', 'us', true, GOOGLE_API_SERVER_KEY);

$geocode = $geocoder->geocode($_POST['listingAddress']);

if ($geocode->count() > 0) {
    // Take first entry...
    $geoData = $geocode->first()->toArray();
}

$time_end = microtime(true);
$time_exec = doubleval(sprintf("%01.2f", $time_end - $time_start));

usleep(300);

echo json_encode(array("result"=>true, "geodata" => $geoData, "processingTime" => $time_exec));
exit();

?>
