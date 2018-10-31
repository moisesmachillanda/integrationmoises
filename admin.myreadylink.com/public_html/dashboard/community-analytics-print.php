<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";
require_once "../../../includes/app/bootstrap.php";

use MyReadyLink\Reports\AnalyticsReport;

requireLogin();

$analyticsReportStart = new DateTime(isset($_GET['analytics_start']) ? $_GET['analytics_start'] : 'yesterday - 30 days');
$analyticsReportEnd =   new DateTime(isset($_GET['analytics_end'])   ? $_GET['analytics_end']   : 'yesterday');
$today = new DateTime('today');

$communityId = 0;
if(isset($_GET['id']) && is_numeric($_GET['id']))
    $communityId = $_GET['id'];

$analyticsOutput = '';

$analyticsReport = AnalyticsReport::getTrafficReportDataForCommunity($communityId, $analyticsReportStart, $analyticsReportEnd, $connection);

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

$analyticsOutput = $twig->render('@admin/reports/community-analytics.twig.php', array('report'=>$analyticsReport));
?><!DOCTYPE html>
<html dir="ltr" lang="en-us" class="print print-report">
<head>
    <title><?php echo $analyticsReport['title']; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="shortcut icon" href="//<?php echo STATIC_URL; ?>/images/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="//<?php echo STATIC_URL; ?>/min/f=css/adminMaster.css?<?php echo VERSIONSTRING; ?>" type="text/css" media="all" />
    <script src="//<?php echo STATIC_URL; ?>/min/f=js/jQuery-admin.js"></script>
</head>
<body>
    <div id="header">
        <h1><a href="//<?php echo HOME_URL; ?>/"><img src="//<?php echo STATIC_URL; ?>/images/admin/logo.png" border="0"/></a></h1>
    </div><!-- /#header -->
    <div id="bodyWrap">
        <div class="listing-analytics">
            <h2><?php echo $analyticsReport['title']; ?></h2>
            <div class="report-selection" style="font-size: 13px; margin: .35em 0;">
                <div class="report-interval" style="float: left; ">
                    Range: <span class="report-start"><?php echo $analyticsReportStart->format($analyticsReportStart->format('Y') == $today->format('Y') ? 'F jS' : 'F jS, Y'); ?></span>
                    -      <span class="report-end"><?php   echo $analyticsReportEnd->format($analyticsReportEnd->format('Y') == $today->format('Y') ? 'F jS' : 'F jS, Y');   ?></span>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="clearfix"></div>
            <?php echo $analyticsOutput; ?>
            <div class="clearfix"></div>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="clearfix"></div>
    <script type="text/javascript">
        $(document).ready(function () {
            window.print();
            //window.close();
        });
    </script>
    <div class="clearfix"></div>
</body>
</html>