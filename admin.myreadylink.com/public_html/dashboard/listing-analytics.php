<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";
require_once "../../../includes/app/bootstrap.php";

use MyReadyLink\Reports\AnalyticsReport;

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$listingStatStart = new DateTime('yesterday - 30 days');
$listingStatEnd =   new DateTime('yesterday');


$community = array();
$community = get_CommunityInfo(array('id'=>$_COOKIE['viewCommunity']));

$communityId = 0;
if(isset($_COOKIE['viewCommunity']))
{
    $communityId = $_COOKIE['viewCommunity'];
}

$listingId = 0;
$currentPage = "New Listing";
if(isset($_GET['id']) && is_numeric($_GET['id']))
{
    $listingId = $_GET['id'];
    $buttonText = "Save Listing";
    $query = sprintf("select CAST(l.active AS unsigned integer) as active1, l.*, s.name as stateName, s.abbr as stateAbbr, s.id as stateId
                                from tbllisting l 
                                left join tblstate s on s.id = l.stateId 
                                where l.id = %d
                                limit 1",
    $mysqli->real_escape_string($listingId)
    );

    if ($result = $mysqli->query($query))
    {
        while($row = $result->fetch_object())
        {
            // gather category metaStuff
            $currentPage = $name = htmlentities(trim($row->name));
        }
    }
}

$analyticsOutput = '';

$analyticsReport = AnalyticsReport::getTrafficReportDataForListing($listingId, $listingStatStart, $listingStatEnd, $connection);

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

$analyticsOutput = $twig->render('@admin/reports/listing-analytics.twig.php', array('report'=>$analyticsReport));


?>
<script type="text/javascript">
    $(document).ready(function () {
         $( "#analyticsStart" ).datepicker({
            defaultDate: "-31d",
            changeMonth: true,
            numberOfMonths: 3,
            maxDate: -1,
            onClose: function( selectedDate ) {
                $( "#analyticsEnd" ).datepicker( "option", "minDate", selectedDate );
            }
        });
        $( "#analyticsEnd" ).datepicker({
            defaultDate: "-1d",
            changeMonth: true,
            numberOfMonths: 3,
            maxDate: -1,
            onClose: function( selectedDate ) {
                $( "#analyticsStart" ).datepicker( "option", "maxDate", selectedDate );
                
            }
        });
        
        // Handlebars Helper
        Handlebars.registerHelper('interval', function(context, block) {
            var format = block.hash.format || '{HH?:}{MM}:{ss}';
            if (context != null && context != "") {
                var seconds = parseInt(context);
                return jintervals(seconds, format);
            }
            else
                return context;
        });
        
        // listing-analytics-report-template
        var reportSource = $("#listing-analytics-report-template").html();
        var reportTemplate = Handlebars.compile(reportSource);
        
        $(".listing-analytics .report-selection .quick-report").on('click', function(e) {
            e.preventDefault();
            $("#analyticsStart").datepicker("setDate", $(this).data('report-start'));
            $("#analyticsEnd").datepicker("setDate", $(this).data('report-end'));
            $("#analyticsSubmit").click();
        });
        
        // Run Report 
        $("#analyticsSubmit").click(function(e) {
            e.preventDefault();
  
            var startDate = $("#analyticsStart").datepicker("getDate");
            var endDate = $("#analyticsEnd").datepicker("getDate");
            
            $(".listing-analytics .report-start").text(dateFormat(startDate, (startDate.getYear() == new Date().getYear()) ? 'mmmm dS' : 'mmmm dS, yyyy'));
            $(".listing-analytics .report-end").text(dateFormat(endDate, (endDate.getYear() == new Date().getYear()) ? 'mmmm dS' : 'mmmm dS, yyyy'));

            // Display the interval.
            $('.listing-analytics .report-interval').show(); 
            $('.listing-analytics .report-form').hide();
            
            // Display the loader.
            // Hide the buttons:
            $('.listing-analytics .modalButtonWrapper').fadeOut();

            $("#analytics-report").fadeOut('normal', function() {
                $(this).remove();
                $(".listing-analytics .report-loader").fadeIn('fast', function() {
                    // Get the data.
                    $.ajax({
                        url: 'data/listing-get-analytics-ajax.php?c=' + new Date()*1, 
                        type: 'POST',
                        data: $('#listing-analytics-dates').serialize(),
                        success: function(data){
                            if(data.result) {
                                // console.log(data);
                                // Generate the report.
                                var output = reportTemplate(data);
                                // console.log(output);
                                $(output)
                                .hide()
                                .insertBefore(".listing-analytics .modalButtonWrapper")
                        
                                // Set the Print report link:
                                $("#btnAnalyticsPrintReport")
                                .attr('href', '/dashboard/listing-analytics-print.php'
                                    + '?id=' + $("#listingId").val() 
                                    + '&analytics_start=' + $( "#analyticsStart" ).val() 
                                    + '&analytics_end=' + $( "#analyticsEnd" ).val()
                                );
                            
                                // Set the CSV report link:
                                $("#btnAnalyticsExportCsv")
                                .attr('href', '/dashboard/listing-analytics-csv.php'
                                    + '?id=' + $("#listingId").val() 
                                    + '&analytics_start=' + $( "#analyticsStart" ).val() 
                                    + '&analytics_end=' + $( "#analyticsEnd" ).val()
                                );
                        
                                $(".listing-analytics .report-loader").fadeOut('normal', function() {
                                    $("#analytics-report").fadeIn();
                                    // Show the buttons
                                    $('.listing-analytics .modalButtonWrapper').fadeIn();
                                });
                        
                            }
                            else {
                                alert('An error occured retreving this listing\'s analytics report.');
                            }
                        },
                        dataType:'json'
                    }); // .ajax
                }); // .fadeIn
            }); // .fadeOut
        });
    });
</script>

<div class="breadCrumb">
    <a class="clearMainDashTab" href="/dashboard/">Dashboard</a> &gt; 
    <a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt; 
    <a href="/dashboard/" class="communityClick" data-communityId="<?php echo $communityId?>" ><?php echo $community->name ?></a> &gt; 
    <?php echo  $currentPage ?>
</div><br />
<div class="clearfloat"></div>
<div class="errSummary errorSevere modalError" style="display:none;"></div>
<div class="floatleft listing-analytics">
    <h3>Analytics</h3>
    <form id="listing-analytics-dates" name="listing-analytics-dates">
    <input type="hidden" name="listingId" id="listingId" value="<?php echo $listingId?>" />
    <div class="report-selection" style="font-size: 13px; margin: .35em 0;">
        <div class="report-interval" style="float: left; ">
            From <span class="report-start"><?php echo $listingStatStart->format('F jS'); ?></span>
            -    <span class="report-end"><?php   echo $listingStatEnd->format('F jS');   ?></span>
            | Quick Reports: <?php foreach (array(30, 90, 180, 360) as $days) { 
                      $quickStart = new DateTime(sprintf('yesterday - %d days', $days));
                      $quickEnd   = new DateTime('yesterday');
                      
                      $link = ' <a style="color: #c00;" class="quick-report" href="#listing-report-%1$d" data-report-start="%2$s" data-report-end="%3$s">%1$d</a>';
                      
                      echo sprintf($link, $days, $quickStart->format('m/d/Y'), $quickEnd->format('m/d/Y'));
                      
            } ?>
            |    <a style="color: #c00;" href="#reportDate" onclick="$('.report-interval').hide(); $('.report-form').show(); return false;" >Custom Report</a>
        </div><!-- /.report-interval -->
        <div class="report-form" style="float: left; display: none;">
            <label for="analyticsStart">From</label> <input type="text" name="analytics.start" id="analyticsStart" value="<?php echo $listingStatStart->format('Y-m-d'); ?>" />
            <label for="analtyicsEnd">to</label> <input type="text" name="analytics.end" id="analyticsEnd" value="<?php echo $listingStatEnd->format('Y-m-d');   ?>" />
            <a id="analyticsSubmit" style="color: #c00;" href="#updateReport" onclick="">Run Report</a> |
            <a style="color: #c00;" href="#closeReport" onclick="$('.report-form').hide(); $('.report-interval').show(); return false;">Close</a>
        </div><!-- /.report-form -->
        <div class="clearfix"></div>
    </div><!-- /report-selection -->
    <div class="clearfix"></div>
    </form>
    <div class="report-loader">
        <div id="circularG">
            <div id="circularG_1" class="circularG"></div>
            <div id="circularG_2" class="circularG"></div>
            <div id="circularG_3" class="circularG"></div>
            <div id="circularG_4" class="circularG"></div>
            <div id="circularG_5" class="circularG"></div>
            <div id="circularG_6" class="circularG"></div>
            <div id="circularG_7" class="circularG"></div>
            <div id="circularG_8" class="circularG"></div>
        </div>
    </div><!-- /.report-loader -->
    <?php echo $analyticsOutput; ?>
    <div class="clearfloat"></div>
    <div class="modalButtonWrapper" style="width:100%;clear:both;text-align:left;padding-top:20px;">
        <a href="/dashboard/listing-analytics-print.php?id=<?php echo $listingId; ?>&analytics_start=<?php echo $listingStatStart->format('m/d/Y'); ?>&analytics_end=<?php echo $listingStatEnd->format('m/d/Y'); ?>" target="print" id="btnAnalyticsPrintReport" class="submit buttonStyle">Print Report</a>
        <a href="/dashboard/listing-analytics-csv.php?id=<?php echo $listingId; ?>&analytics_start=<?php echo $listingStatStart->format('m/d/Y'); ?>&analytics_end=<?php echo $listingStatEnd->format('m/d/Y'); ?>" target="csv" id="btnAnalyticsExportCsv" class="buttonStyle cancel" >Export CSV</a>
    </div><!-- /.modalButtonWrapper -->
</div><!-- /.listing-analytics -->
<div class="clearfloat"></div>
<p>&nbsp;</p>
<div class="breadCrumb">
    <a class="clearMainDashTab" href="/dashboard/">Dashboard</a> &gt;
    <a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt; 
    <a href="/dashboard/" class="communityClick" data-communityId="<?php echo $communityId?>"><?php echo $community->name ?></a> &gt; 
    <?php echo  $currentPage ?>
</div><br />
<div class="clearfloat">
</div>
<script id="listing-analytics-report-template" type="text/x-handlebars-template">
<div id="analytics-report">
    <div class="stats">
        <table class="tablesorter" style="width: 900px; ">
            <thead>
                <th>Page</th>
                <th>Pageviews (PV)</th>
                <th>Unique (PV)</th>
                <th>Avg Time on Page</th>
                <th>Entrances</th>
            </thead>
            <tbody>{{#each report.data.sections }}{{#each this.rows }}<tr>
                <td>{{ [ga:pagePath] }}</td>
                <td>{{ [ga:pageviews] }}</td>
                <td>{{ [ga:uniquePageviews] }}</td>
                <td>{{interval [ga:avgTimeOnPage] }}</td>{{!-- |interval --}}
                <td>{{ [ga:entrances] }}</td>
            </tr>{{/each}}<tr class="summary">
                    <td>{{ this.title }}</td>
                    <td>{{ this.summary.[ga:pageviews] }}</td>
                    <td>{{ this.summary.[ga:uniquePageviews] }}</td>
                    <td>{{interval this.summary.[ga:avgTimeOnPage] }}</td>{{!-- |interval --}}
                    <td>{{ this.summary.[ga:entrances] }}</td>
            </tr>
            {{/each}}
            <tr><td colspan="5">&nbsp;</td></tr>
            <tr class="totals">
                <td>{{ report.data.totals.title }}</td>
                <td>{{ report.data.totals.summary.[ga:pageviews] }}</td>
                <td>{{ report.data.totals.summary.[ga:uniquePageviews] }}</td>
                <td>{{interval report.data.totals.summary.[ga:avgTimeOnPage] }}</td>{{!-- |interval --}}
                <td>{{ report.data.totals.summary.[ga:entrances] }}</td>
            </tr></tbody>
        </table>
    </div>
    <div class="levels">
        <h4>{{ report.data.advertisinglevels.title }}</h4>
        <ul>{{#each report.data.advertisinglevels.rows }}
            <li>{{ name }} - {{ level }}</li>
        {{/each}}</ul>
    </div>
</div>
</script>
