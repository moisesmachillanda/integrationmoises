<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";
require_once "../../../includes/app/bootstrap.php";

use MyReadyLink\Reports\AnalyticsReport;

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$analyticsStatStart = new DateTime('yesterday - 30 days');
$analyticsStatEnd   = new DateTime('yesterday');

$analyticsReportStart = new DateTime('yesterday - 30 days');
$analyticsReportEnd   = new DateTime('yesterday');

if (isset($_GET['analytics'])) {
    switch($_GET['analytics']) {
        case "90":
            $analyticsStatStart = new DateTime('yesterday - 90 days');
            break;
        
        case "180":
            $analyticsStatStart = new DateTime('yesterday - 180 days');
            break;
        
        case "360":
            $analyticsStatStart = new DateTime('yesterday - 360 days');
            break;
    }
}

$numOutput = '';
$addButton = '';

// region Community List
if (!isset($_COOKIE['viewCommunity'])) {
    if ($_SESSION['isSAdmin'] == true) {
        //see if we have access to a container
        $query = sprintf("select *
                          from tbluserrightsmap urm 
                          where (countryId is not null or stateId is not null or countyId is not null)
                          AND userId = %d",
                          $mysqli->real_escape_string($_SESSION['uid'])
        );
        if ($result = $mysqli->query($query))
            while ($row = $result->fetch_object()) {
                $addButton = '<a href="javascript:void(0)" id="addCommunity" class="addCommunity submit buttonStyle editCommunity" rel="0">Add Community</a>';
                break;
            }
    }
    
    //select communties
    $query = sprintf("SELECT distinct c.id, c.name as name, CAST(c.active AS unsigned integer) as active, c.isFeatured, county.name as countyName, state.abbr as stateAbbr, country.name as countryName, u.firstName, u.lastName, UNIX_TIMESTAMP(c.lastEdit) as lastEdit
            ,stats.pageViews as pageViews
            ,stats.uniqueVisitors as uniqueVisitors
        FROM tblcommunity c 
        left outer join tblgastats stats on (stats.communityId = c.id and (stats.startDate = '" . $analyticsStatStart->format('Y-m-d') . "' and stats.endDate = '" . $analyticsStatEnd->format('Y-m-d') . "')) 
        LEFT JOIN tblcounty county on county.id = c.countyId
        LEFT JOIN tblstate state on state.id = county.stateId
        LEFT JOIN tblcountry country on country.id = state.countryId
        LEFT JOIN tbluser u on u.id = c.ownerUID
        INNER JOIN tbluserrightsmap rightsMap on (rightsMap.communityId = c.id OR rightsMap.countyId = county.id OR rightsMap.stateId = state.Id OR rightsMap.countryId = country.Id)
        WHERE c.deleted = 0
          AND c.parentId = 1
          AND c.id != 1
          AND userId = %d
        ORDER BY c.name
    ;",
    $mysqli->real_escape_string($_SESSION['uid'])
    );
    
    $output = '';
    $active = '';
    $featured = '';
    $aadminBody = '';
    if ($result = $mysqli->query($query)) {
        if ($_SESSION['isSAdmin'] == true)
            $adminHead = '
            <th>Status</th>
            <!--<th>Featured</th>-->
            <!--<th>Unique Visitors</th>
            <th>Page Views</th>-->
            <th colspan="2" align="center" class="center not-sortable">Actions</th>';
        else
            $adminHead = '';

        $output = '<table style="width:900px;">
            <tr><td align="right">Search: <input id="tablefilter4" type="text" /><a href="javascript:void(0);" id="tablefilterclear4"><img src="http://' . STATIC_URL . '/images/admin/Delete.png" width="18" height="18" alt="" border="0"></a></td></tr>
            <tr><td align="right" style="padding-top: 1em;font-size: 12px;">
                   <a style="color: #c00;" href="?analytics=30" class="listing-analytics" data-interval="30">'   . ((isset($_GET['analytics']) && $_GET['analytics'] == "30")  ? '<strong style="font-weight: bold !important; ">30</strong>'  : '30')  . '</a>
                 | <a style="color: #c00;" href="?analytics=90" class="listing-analytics" data-interval="90">'   . ( isset($_GET['analytics']) && $_GET['analytics'] == "90"  ? '<strong style="font-weight: bold !important; ">90</strong>'  : '90')  . '</a>
                 | <a style="color: #c00;" href="?analytics=180" class="listing-analytics" data-interval="180">' . ( isset($_GET['analytics']) && $_GET['analytics'] == "180" ? '<strong style="font-weight: bold !important; ">180</strong>' : '180') . '</a>
                 | <a style="color: #c00;" href="?analytics=360" class="listing-analytics" data-interval="360">' . ( isset($_GET['analytics']) && $_GET['analytics'] == "360" ? '<strong style="font-weight: bold !important; ">360</strong>' : '360') . '</a>
            </td></tr>
            </table><br />
            <table style="width:900px;" class="my-community-sites tablesorter" id="tablesorter4">'. 
            "\n" . 
            '<thead><tr>
                <th>Site</th>
                <th>State</th>
                <th>Owner</th>
                <th>Last Edited</th>' . 
                $adminHead . 
                '<th>Pageviews</th>
                <th>Visitors</th>
            </thead>' . "\n" . 
            '<tbody>';
        while ($row = $result->fetch_object()) {
            if ($row->active == true)
                $active = '<a href="javascript:void(0)" class="disable-community" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/active.gif" alt=""></a>';
            else
                $active = '<a href="javascript:void(0)" class="enable-community" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/inactive.gif" alt=""></a>';
    
            if ($_SESSION['isSAdmin'] == true)
                $adminBody = '<td class="center">' . $active . '</td><!--<td>' . $featured . '</td>--><!--<td>0</td><td>0</td>--><td><a href="javascript:void(0)" class="editCommunity buttonEdit" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/admin/edit.png" class="buttonEdit" border="0" width="20" height="20" /></a></td>   <td><a href="javascript:void(0)" class="deleteCommunity deleteButtonSmall" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/admin/btnDelete.png" class="deleteButton" border="0" width="18" height="18" /></a></td>';
            else
                $adminBody = '';
            
            if ($row->isFeatured == true) 
                $featured = 'Yes';
            else
                $featured = 'No';
            if (is_null($row->lastEdit) || $row->lastEdit == 0 ) 
                $myDate = '';
            else  
                $myDate = date('Y-m-d',$row->lastEdit);
            
            $output .= '<tr>
                <td><a href="javascript:void(0)" class="viewCommunityListing" rel="' . $row->id . '">' . htmlentities($row->name) . '</a></td>
                <td>' . htmlentities($row->stateAbbr) .'</td>
                <td>' . htmlentities($row->firstName . ' ' .  $row->lastName) . '</td>
                <td>' . $myDate . '</td>' . 
                $adminBody . 
                '<td>' . (empty($row->pageViews) ? '-' : $row->pageViews) . '</td>' .
                '<td>' . (empty($row->uniqueVisitors) ? '-' : $row->uniqueVisitors) . '</td>' .
            '</tr>' . "\n" ; 
        }
        
        $output .= '</tbody>' . "\n";

        $output = '<div class="addCommunityWrapper"><h3 class="my-community-site">My Community Sites</h3>' . $addButton . '</div>' . $output;
        if ($result->num_rows > 10) 
            $output .= Pager(4, 15, array(5,15,25), 'communityList');
        $output .= '</table><br />' . "\n";
        
    }
}
// endregion
// region Specific Community
else {
    $community = array();
    $community = get_CommunityInfo(array('id'=>$_COOKIE['viewCommunity']));

    // region Community Analytics
    $analyticsOutput = '';
    
    $analyticsReport = AnalyticsReport::getTrafficReportDataForCommunity($community->id, $analyticsReportStart, $analyticsReportEnd, $connection);

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
    // endregion
    
    // region Community Business Listings
    $query = sprintf("
        SELECT 
             l.id as myid
            ,l.*
            ,GROUP_CONCAT( lc.name Order By lc.sortOrder, lc.name) as Categories
            ,UNIX_TIMESTAMP(l.lastEdit) as lastEdit
            ,stats.pageViews as pageViews 
            ,stats.uniqueVisitors as uniqueVisitors 
        FROM tbllisting l 
        left outer join tblgastats stats on (stats.listingId = l.id and (stats.startDate = '" . $analyticsStatStart->format('Y-m-d') . "' and stats.endDate = '" . $analyticsStatEnd->format('Y-m-d') . "'))
        inner join tblcommunitylistingmap listMap on listMap.listingId = l.id
        inner join tbllistingcategory lc on lc.id = listMap.listingCategoryId
        WHERE listMap.communityId = %d " . 
        ($_SESSION['roles'] == null || ($_SESSION['roles'] != null && !in_array(array('id' => 1, 'name' => 'Super Admin'), $_SESSION['roles'])) 
            ? "  AND l.ownerUID = " . $mysqli->real_escape_string($_SESSION['uid']) . ""
            : ""
        ) . 
          " AND l.deleted = 0
        GROUP by l.id
        ORDER BY l.name",
        $mysqli->real_escape_string($community->id)
    );

    //echo 'Roles: <pre>' . print_r($_SESSION['roles'], true) . '</pre>';
    //echo 'Query: <pre>' . $query . '</pre>';

    $output = '';
    $active = '';

    $output = '<div style="width:835px;">
            <a href="javascript:void(0)" class="buttonStyle mapNewListing"  rel="'. $community->id .'">Map Existing Listing</a>&nbsp;
            <a href="listing.php" class="buttonStyle editListingNew" rel="0">New Listing</a>
            </div>';

    if ($result = $mysqli->query($query)) {
        //if($_SESSION['isSAdmin'] == true) $adminextrahead = '<th class="not-sortable smallBox">Delete</th>';
        //else $adminextrahead = '';
        
        
        $output .= '' .
        '<table style="width:100%;">' .
        '<tr><td align="right">Search: <input id="tablefilter5" type="text" /><a href="javascript:void(0);" id="tablefilterclear5"><img src="http://' . STATIC_URL . '/images/admin/Delete.png" width="18" height="18" alt="" border="0"></a></td></tr>' .
        '<tr><td align="right" style=" font-size: .65em; padding-top: 1em;">' .
            '   <a href="?analytics=30"  class="listing-analytics" data-interval="30"  style="color: #c00; ">'   . ((isset($_GET['analytics']) && $_GET['analytics'] == "30")  ? '<strong style="font-weight: bold !important; ">30</strong>'  : '30')  . '</a>' .
            ' | <a href="?analytics=90"  class="listing-analytics" data-interval="90"  style="color: #c00; ">'   . ( isset($_GET['analytics']) && $_GET['analytics'] == "90"  ? '<strong style="font-weight: bold !important; ">90</strong>'  : '90')  . '</a>' .
            ' | <a href="?analytics=180" class="listing-analytics" data-interval="180" style="color: #c00; ">' . ( isset($_GET['analytics']) && $_GET['analytics'] == "180" ? '<strong style="font-weight: bold !important; ">180</strong>' : '180') . '</a>' .
            ' | <a href="?analytics=360" class="listing-analytics" data-interval="360" style="color: #c00; ">' . ( isset($_GET['analytics']) && $_GET['analytics'] == "360" ? '<strong style="font-weight: bold !important; ">360</strong>' : '360') . '</a>' .
        '</td></tr>' .
        '</table><br />        
        <table style="width:835px;" id="tablesorter5" class="my-community-listings tablesorter">'. "\n" .
            '<thead><tr>
                <th>Listing</th>
                <th>Categories</th>
                <th>Last Edited</th>
                <th>Status</th>
                <th class="not-sortable smallBox">Actions</th>
                <th>Page Views</th>
                <th>Visitors</th>
            </thead>' . "\n" . 
            '<tbody>';
        while ($row = $result->fetch_object()) {
            if($row->active == true)
                $active = '<a href="javascript:void(0)" class="disable-listing" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/active.gif" alt=""></a>';
            else
                $active = '<a href="javascript:void(0)" class="enable-listing" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/inactive.gif" alt=""></a>';
                
            if ($_SESSION['isSAdmin'] == true) 
                $adminextrabody = '';
                // $adminextrabody = '<a href="javascript:void(0)" class="deleteListing" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/admin/btnDelete.png" border="0" width="15" height="14" /></a>';
            else 
                $adminextrabody = '';
            
            if (is_null($row->lastEdit) || $row->lastEdit == 0 ) 
                $myDate = '';
            else  
                $myDate = date(DATE_FORMAT,$row->lastEdit);

            // Old Actions:
            // <td class="smallBox"><a class="editListing buttonEdit" href="listing.php?id=' . $row->id . '"><img src="http://' . STATIC_URL . '/images/admin/edit.png" class="buttonEdit" border="0" width="20" height="20" /></a><input type="checkbox" class="deleteListingMapping" value="' . $row->id . '" />' . $adminextrabody . '</td>
            $output .= '<tr>
                <td><a href="listing.php?id=' . $row->id . '" class="editListing" rel="' . $row->id . '">' . htmlentities($row->name) . '</a></td>
                <td>' . htmlentities($row->Categories) . '</td>
                <td>' . $myDate . '</td>
                <td class="center">' . $active . '</td>
                <td class="smallBox"><a class="editListing buttonEdit" href="listing.php?id=' . $row->id . '"><img src="http://' . STATIC_URL . '/images/admin/edit.png" class="buttonEdit" border="0" width="20" height="20" /></a>' . $adminextrabody . '</td>
                <td>' . (empty($row->pageViews) ? '-' : $row->pageViews) . '</td>
                <td>' . (empty($row->uniqueVisitors) ? '-' : $row->uniqueVisitors) . '</td>
            </tr>' . "\n" ;
        }
    
        $output .= '</tbody></table>' . "\n";

        if ($result->num_rows > 25) 
            $output .= Pager(5, 25, array(25,50,100), 'listingList');
        $output .= '<div style="width:835px;text-align:right;padding-top:10px;">';
        //$output .= '<a href="javascript:void(0)" class="buttonStyle removeListings" >Remove Selected Listings</a>';
        $output .= '</div>';
        
    }
    // endregion
    
    // region Community Number Listings
    $query = sprintf("SELECT GROUP_CONCAT(numCat.name order by numCat.sortOrder, numCat.name) as Categories, numMap.communityId,numMap.countyId,numMap.stateId, numMap.CountryId, n.*, numCat.name as catName, numCat.categorySlug, numCat.id as catId, s.name as stateName, s.abbr as stateAbbr, county.name as countyName, UNIX_TIMESTAMP(n.lastEdit) as lastEdit  
                FROM tblnumber n
                LEFT JOIN tblnumbercategorymap numMap on numMap.numberid = n.id
                LEFT JOIN tblcounty county on county.id = numMap.countyId
                LEFT JOIN tblstate s on s.id = numMap.stateId
                LEFT JOIN tblcountry country on country.id = numMap.countryId
                LEFT JOIN tblnumbercategory numCat on numCat.id = numMap.categoryId
                WHERE 
                (numMap.communityId = %d or numMap.countyId = %d or numMap.stateId = %d or numMap.CountryId=%d)
                AND n.id not in (SELECT numberId FROM tblcommunitynumberexcludes where communityId = %d)
                AND n.deleted = 0
                AND numCat.deleted = 0
                GROUP BY n.id
                order by numMap.communityId, numMap.countyId, numMap.stateId, numMap.CountryId, numCat.sortOrder, n.name",
                $community->id,
                $community->countyId,
                $community->stateId,
                $community->countryId,
                $community->id
                );
    
    $numOutput = '';
    $active = '';
    $adminextrabody = '';
    if ($result = $mysqli->query($query)) {
        //if($_SESSION['isSAdmin'] == true) $adminextrahead = '<th class="not-sortable smallBox">Delete</th>';
        //else $adminextrahead = '';
        
        $numOutput = '<div style="width:835px"><a href="javascript:void(0)" class="buttonStyle mapNewNumber" rel="'. $community->id .'">Map Existing Number</a> &nbsp; <a href="number.php" class="buttonStyle editListing" rel="0">New Number</a></div>';
        $numOutput .= '<table style="width:835px;"><tr><td align="right">Search: <input id="tablefilter6" type="text" /><a href="javascript:void(0);" id="tablefilterclear6"><img src="http://' . STATIC_URL . '/images/admin/Delete.png" width="18" height="18" alt="" class="deleteButton" border="0"></a></td></tr></table><br /><table style="width:835px;" id="tablesorter6" class="my-community-numbers tablesorter">'. "\n" .'<thead><tr><th>Number</th><th>Categories</th><th>Last Edited</th><th>Status</th><!--<th>Unique Visitors</th><th>Page Views</th>--><th class="not-sortable smallBox">Actions</th></thead>' . "\n" . '<tbody>';
        while ($row = $result->fetch_object())
        {
            if ($row->active == true) {
                $active = '<a href="javascript:void(0)" class="disable-number" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/active.gif" alt=""></a>';
            }
            else {
                $active = '<a href="javascript:void(0)" class="enable-number" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/inactive.gif" alt=""></a>';
            }
                
            
            if ($_SESSION['isSAdmin'] == true) 
                $adminextrabody = '<a href="javascript:void(0)" class="deleteNumber deleteButtonSmall" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/admin/btnDelete.png" border="0" width="14" height="15" class="deleteButton" /></a>';
            else 
                $adminextrabody = '';
            
            if (is_null($row->lastEdit) || $row->lastEdit == 0 ) 
                $myDate = '';
            else  
                $myDate = date(DATE_FORMAT,$row->lastEdit);
            
            $numOutput .= '<tr><td><a href="number.php?id=' . $row->id . '" class="editNumber" rel="' . $row->id . '">' . htmlentities($row->name) . '</a></td><td>' . $row->Categories . '</td><td>' . $myDate . '</td><td class="smallBox">' . $active . '</td><!--<td>0</td><td>0</td>--><td class="smallBox"><a class="editNumber buttonEdit" href="number.php?id=' . $row->id . '"><img src="http://' . STATIC_URL . '/images/admin/edit.png" class="buttonEdit" border="0" width="20" height="20" /></a><input type="checkbox" class="deleteNumberMapping" value="' . $row->id . '" />' . $adminextrabody . '</td></tr>' . "\n" ;
        }
    
        $numOutput .= '</tbody>' . "\n";
        $numOutput .= '</table>';
        if ($result->num_rows > 25) 
            $numOutput .= Pager(6, 25, array(25,50,100), 'numberList');
        $numOutput .= '<div style="width:835px;text-align:right;padding-top:10px;">';
        $numOutput .= '<a href="javascript:void(0)" class="buttonStyle removeNumbers" >Remove Selected Numbers</a>';
        $numOutput .= '</div>';
    
    }
    // endregion
    
}
// endregion

$breadCrumbs =  (isset($_COOKIE['viewCommunity'])) ? '<a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt; ' . $community->name : 'Communities';

$connection->close();

usleep(250);
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
        if ($("#community-analytics-report-template").exists()) {
            var reportSource = $("#community-analytics-report-template").html();
            var reportTemplate = Handlebars.compile(reportSource);
        }
        
        $(".community-analytics .report-selection .quick-report").on('click', function(e) {
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
            
            $(".community-analytics .report-start").text(dateFormat(startDate, (startDate.getYear() == new Date().getYear()) ? 'mmmm dS' : 'mmmm dS, yyyy'));
            $(".community-analytics .report-end").text(dateFormat(endDate, (endDate.getYear() == new Date().getYear()) ? 'mmmm dS' : 'mmmm dS, yyyy'));

            // Display the interval.
            $('.community-analytics .report-interval').show(); 
            $('.community-analytics .report-form').hide();
            
            // Display the loader.
            // Hide the buttons:
            $('.community-analytics .modalButtonWrapper').fadeOut();
            
            $("#analytics-report").fadeOut('normal', function() {
                $(this).remove();

                $(".community-analytics .report-loader").fadeIn('fast', function() {
                    // Get the data.
                    $.ajax({
                        url: 'data/community-get-analytics-ajax.php?c=' + new Date()*1, 
                        type: 'POST',
                        data: $('#community-analytics-dates').serialize(),
                        success: function(data){
                            if(data.result) {
                                // console.log(data);
                                // Generate the report.
                                var output = reportTemplate(data);
                                // console.log(output);
                                $(output)
                                .hide()
                                .insertBefore(".community-analytics .modalButtonWrapper")
                        
                                // Set the Print report link:
                                $("#btnAnalyticsPrintReport")
                                .attr('href', '/dashboard/community-analytics-print.php'
                                    + '?id=' + $("#communityId").val() 
                                    + '&analytics_start=' + $( "#analyticsStart" ).val() 
                                    + '&analytics_end=' + $( "#analyticsEnd" ).val()
                                );
                            
                                // Set the CSV report link:
                                $("#btnAnalyticsExportCsv")
                                .attr('href', '/dashboard/community-analytics-csv.php'
                                    + '?id=' + $("#communityId").val() 
                                    + '&analytics_start=' + $( "#analyticsStart" ).val() 
                                    + '&analytics_end=' + $( "#analyticsEnd" ).val()
                                );
                        
                                $(".community-analytics .report-loader")
                                .fadeOut('normal', function() {
                                    $("#analytics-report")
                                    .fadeIn();
                                    // Show the buttons
                                    $('.community-analytics .modalButtonWrapper').fadeIn();
                                });
                        
                            }
                            else {
                                alert('An error occured retreving this community\'s analytics report.');
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
    <a class="dashboardClick" href="/dashboard/">Dashboard</a> &gt;
    <?php echo $breadCrumbs; ?>
</div>
<?php if (isset($_GET['deletedlisting']) && $_GET['deletedlisting'] == 1) { ?>
<div class="errSummary modalError errorInfo" style="width: calc(100% - 87px);"><ul><li>Listing permanently removed.</li></ul></div>
<script type="text/javascript">
    $(function () {
        window.setTimeout(function () { $(".errSummary").slideUp(); }, 7500);
    });
</script>
<?php } ?>
<?php
// region Specific Community
if (isset($_COOKIE['viewCommunity'])) {  ?>
    <div><h3 class="my-community-site "><?php echo $community->name  . '-' . $community->stateAbbr ?></h3> <a href="/dashboard/" id="backMyCommunities" class="floatright">Back to My Communities</a></div>
    <div class="clearfix"></div>
    <?php 
    // For later use in removing modoals...
    // When editing a business (listing) or number, the tab panel will be replaced and a ? (&#8624;)
    // <div id="CommunityTabs" class="clearfix ui-tabs ui-widget ui-widget-content ui-corner-all" style="margin-top: 3.25em; ">
    //     <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
    //         <li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#CommunityForm">General</a></li>
    //         <li class="ui-state-default ui-corner-top"><a href="#CommunityNumbers">Numbers</a></li>
    //         <li class="ui-state-default ui-corner-top"><a href="#CommunityBusinesses">Listings</a></li>
    //         <li class="ui-state-default ui-corner-top"><a href="#CommunityAnalytics">Analytics</a></li>
    //     </ul>
    //     <div id="tabContent">
    //         <div id="CommunityForm" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
    //             Form Here...
    //         </div>
    // 
    //         <div id="CommunityNumbers" class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide">
    //                 echo $numOutput;
    //             <br />
    //         </div>
    //         <div id="CommunityBusinesses" class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide">
    //             echo $output;
    //             <br />
    //         </div>
    // 
    //         <div id="CommunityAnalytics" class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide">
    //             echo $analyticsOutput;
    //         </div>
    // 
    //     </div>
    // </div>
    //
?>
<div id="categoryType">
    <h3><a href="#Analytics">Analytics</a></h3>
    <div class="community-analytics">
        <form id="community-analytics-dates" name="community-analytics-dates">
        <input type="hidden" name="communityId" id="communityId" value="<?php echo $community->id; ?>" />
        <div class="report-selection" style="font-size: 13px; margin: .35em 0;">
            <div class="report-interval" style="float: left; ">
                From <span class="report-start"><?php echo $analyticsReportStart->format('F jS'); ?></span>
                -    <span class="report-end"><?php   echo $analyticsReportEnd->format('F jS');   ?></span>
                | Quick Reports: <?php foreach (array(30, 90, 180, 360) as $days) { 
                    $quickStart = new DateTime(sprintf('yesterday - %d days', $days));
                    $quickEnd   = new DateTime('yesterday');
                                       
                    $link = ' <a style="color: #c00;" class="quick-report" href="#listing-report-%1$d" data-report-start="%2$s" data-report-end="%3$s">%1$d</a>';
                                       
                    echo sprintf($link, $days, $quickStart->format('m/d/Y'), $quickEnd->format('m/d/Y'));
                                       
                } ?>
                |    <a style="color: #c00;" href="#reportDate" onclick="$('.report-interval').hide(); $('.report-form').show(); return false;" >Custom Report</a>
            </div><!-- /.report-interval -->
            <div class="report-form" style="float: left; display: none;">
                <label for="analyticsStart">From</label> <input type="text" name="analytics.start" id="analyticsStart" value="<?php echo $analyticsReportStart->format('Y-m-d'); ?>" />
                <label for="analtyicsEnd">to</label> <input type="text" name="analytics.end" id="analyticsEnd" value="<?php   echo $analyticsReportEnd->format('Y-m-d');   ?>" />
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
            <a href="/dashboard/community-analytics-print.php?id=<?php echo $community->id; ?>&analytics_start=<?php echo $analyticsReportStart->format('m/d/Y'); ?>&analytics_end=<?php echo $analyticsReportEnd->format('m/d/Y'); ?>" target="print" id="btnAnalyticsPrintReport" class="submit buttonStyle">Print Report</a>
            <a href="/dashboard/community-analytics-csv.php?id=<?php echo $community->id; ?>&analytics_start=<?php echo $analyticsReportStart->format('m/d/Y'); ?>&analytics_end=<?php echo $analyticsReportEnd->format('m/d/Y'); ?>" target="csv" id="btnAnalyticsExportCsv" class="buttonStyle cancel" >Export CSV</a>
        </div><!-- /.modalButtonWrapper -->
        <script id="community-analytics-report-template" type="text/x-handlebars-template">
        <div id="analytics-report">
            <div class="stats">
                <table class="tablesorter" style="width: 100%; ">
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
        </div>
        </script>
    </div><!-- /analytics wrapper -->
    <h3><a href="#Numbers">Numbers</a></h3>
    <div>
        <?php echo $numOutput; ?>
        <br />
    </div>
    <h3><a href="#Listings">Listings</a></h3>
    <div>
        <?php echo $output; ?>
        <br />
    </div>
</div>
<br />

<?php 
}
// endregion
// region Community List
else {
    echo  $output;
}
// endregion
?>
<div class="breadCrumb">
    <a class="dashboardClick" href="/dashboard/">Dashboard</a> &gt;
    <?php echo $breadCrumbs; ?>
</div>

