<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$analyticsStatStart = new DateTime('yesterday - 30 days');
$analyticsStatEnd =   new DateTime('yesterday');

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


echo '<h2>Welcome ' . htmlentities($_SESSION['firstName'] . ' ' . $_SESSION['lastName']) . '</h2>';

// my communities
$query = sprintf("SELECT distinct " .
    " c.id " .
    ",c.name as name " .
    ",CAST(c.active AS unsigned integer) as active " .
    ",county.name as countyName " .
    ",state.abbr as stateAbbr " .
    ",country.name as countryName " .
    ",stats.pageViews as pageViews " .
    ",stats.uniqueVisitors as uniqueVisitors " .
    "FROM tblcommunity c " .
    "left outer join tblgastats stats on (stats.communityId = c.id and (stats.startDate = '" . $analyticsStatStart->format('Y-m-d') . "' and stats.endDate = '" . $analyticsStatEnd->format('Y-m-d') . "'))" .
    "LEFT JOIN tblcounty county on county.id = c.countyId " .
    "LEFT JOIN tblstate state on state.id = county.stateId " .
    "LEFT JOIN tblcountry country on country.id = state.countryId " .
    "INNER JOIN tbluserrightsmap rightsMap  on (rightsMap.communityId = c.id OR rightsMap.countyId = county.id OR rightsMap.stateId = state.Id OR rightsMap.countryId = country.Id) " .
    "WHERE c.deleted = 0 " .
    "  AND c.parentId = 1 " .
    "  AND c.id != 1 " .
    "  AND rightsMap.userId = %d " .
    //"  and ((stats.communityId is not null and stats.startDate = '" . $analyticsStatStart->format('Y-m-d') . "' and stats.endDate = '" . $analyticsStatEnd->format('Y-m-d') . "') or stats.communityId is null) " .
    "ORDER BY c.name " .
    ";",
    $mysqli->real_escape_string($_SESSION['uid'])
);

$output = '';
$active = '';
if ($result = $mysqli->query($query)) {
    $output = '<table id="tablesorter1" class="my-community-sites tablesorter" style="width: 100%; ">'. "\n" .
        '<thead><tr>' .
            '<th>Site</th>' .
            '<th>State</th>' .
            '<th>Status</th>' .
            '<th>Pageviews</th>' .
            '<th>Visitors</th>' .
        '</thead>' . "\n" . '<tbody>';
    while ($row = $result->fetch_object()) {
        if ($row->active == true) {
            $active = '<a href="#active" class="disable-community" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/active.gif" alt=""></a>';
        }
        else {
            $active = '<a href="#active" class="enable-community" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/inactive.gif" alt=""></a>';
        }

        $output .= '<tr>
            <td><a href="#editCommunity" class="editCommunity" rel="' . $row->id . '">' . htmlentities($row->name) . '</a></td>
            <td>' . htmlentities($row->stateAbbr) .'</td>
            <td class="center">' . $active . '</td>
            <td>' . (empty($row->pageViews) ? '-' : $row->pageViews) . '</td>
            <td>' . (empty($row->uniqueVisitors) ? '-' : $row->uniqueVisitors) . '</td>
        </tr>' .
        "\n" ;
    }

    $output .= '</tbody>' . "\n";

    if (isset($_SESSION['isSAdmin'] ) && $_SESSION['isSAdmin'] == true) {
        echo '<div style="font-size: 12px; float: right; padding-top: 1em; margin-right: 1em;">' .
        '   <a style="color: #c00;" href="?analytics=30" class="listing-analytics" data-interval="30">'   . ((isset($_GET['analytics']) && $_GET['analytics'] == "30")  ? '<strong style="font-weight: bold !important; ">30</strong>'  : '30')  . '</a>' .
        ' | <a style="color: #c00;" href="?analytics=90" class="listing-analytics" data-interval="90">'   . ( isset($_GET['analytics']) && $_GET['analytics'] == "90"  ? '<strong style="font-weight: bold !important; ">90</strong>'  : '90')  . '</a>' .
        ' | <a style="color: #c00;" href="?analytics=180" class="listing-analytics" data-interval="180">' . ( isset($_GET['analytics']) && $_GET['analytics'] == "180" ? '<strong style="font-weight: bold !important; ">180</strong>' : '180') . '</a>' .
        ' | <a style="color: #c00;" href="?analytics=360" class="listing-analytics" data-interval="360">' . ( isset($_GET['analytics']) && $_GET['analytics'] == "360" ? '<strong style="font-weight: bold !important; ">360</strong>' : '360') . '</a>' .
        '</div>';
        echo '<div class="fieldset"><h3>My Community Sites</h3>' . $output;
        echo '</table>';
        if ($result->num_rows > 15) 
            echo Pager(1, 15, array(15,25,50), 'dashBoard');
        echo '</div>' . "\n";
    }
}

/*

//my top ads
$query = sprintf("SELECT Distinct
l.id,
l.oldId,
l.name
FROM tbllisting l
INNER JOIN tbllistingupload lu on lu.listingId = l.id
INNER JOIN tblcommunitylistingmap clm on clm.listingId = l.id
WHERE
clm.CommunityId in
(
SELECT  c.id
FROM tblcommunity c
LEFT JOIN tblcounty county on county.id = c.countyId
LEFT JOIN tblstate state on state.id = county.stateId
LEFT JOIN tblcountry country on country.id = state.countryId
INNER JOIN tbluserrightsmap rightsMap
on (rightsMap.communityId = c.id OR rightsMap.countyId = county.id OR rightsMap.stateId = state.Id OR rightsMap.countryId = country.Id)
WHERE
c.deleted = 0
AND c.parentId = 1
AND c.id != 1
AND userId = %d
)
AND l.active = 1
AND lu.active = 1
AND lu.type = 'listing photo'
GROUP BY l.id
ORDER BY l.name
",
$mysqli->real_escape_string($_SESSION['uid'])
);
$output = '';
$active = '';
if ($result = $mysqli->query($query))
{
$output = '<table id="tablesorter2" class="my-community-ads tablesorter">'. "\n" .'<thead><tr><th>Business Listing</th><th>Unique Visitors</th><th>Page Views</th></thead>' . "\n" . '<tbody>';
while ($row = $result->fetch_object())
{
$output .= '<tr><td><a href="listing.php?id=' . $row->id . '" class="" >' . htmlentities($row->name) . '</a></td><td>0</td><td>0</td></tr>' . "\n" ;
}

$output .= '</tbody>' . "\n";
echo '<div class="fieldset my-community-ads"><h3>Top Performing Ads</h3>' . $output;
echo '</table>';
if ($result->num_rows > 15) echo Pager(2, 15, array(15,25,50), 'dashBoard');
echo '<a href="http://www.google.com/analytics/" target="_blank" >Click here</a> for more google analytics
</div>' . "\n";
}



//my top coupons
$query = sprintf("SELECT Distinct
l.id,
l.oldId,
l.name
FROM tbllisting l
INNER JOIN tbllistingupload lu on lu.listingId = l.id
INNER JOIN tblcommunitylistingmap clm on clm.listingId = l.id
WHERE
clm.CommunityId in
(
SELECT  c.id
FROM tblcommunity c
LEFT JOIN tblcounty county on county.id = c.countyId
LEFT JOIN tblstate state on state.id = county.stateId
LEFT JOIN tblcountry country on country.id = state.countryId
INNER JOIN tbluserrightsmap rightsMap
on (rightsMap.communityId = c.id OR rightsMap.countyId = county.id OR rightsMap.stateId = state.Id OR rightsMap.countryId = country.Id)
WHERE c.active = 1
AND c.deleted = 0
AND c.parentId = 1
AND c.id != 1
AND userId = %d
)
AND l.active = 1
AND l.deleted = 0
AND lu.active = 1
AND lu.type = 'coupon'
GROUP BY l.id
ORDER BY l.name
",
$mysqli->real_escape_string($_SESSION['uid'])
);
$output = '';
$active = '';


if ($result = $mysqli->query($query))
{
$output = '<table id="tablesorter3" class="my-community-coupons tablesorter">'. "\n" .'<thead><tr><th>Business Listing</th><th>Unique Visitors</th><th>Page Views</th></thead>' . "\n" . '<tbody>';
while ($row = $result->fetch_object())
{
$output .= '<tr><td><a href="listing.php?id=' . $row->id . '" class="" >' . htmlentities($row->name) . '</a></td><td>0</td><td>0</td></tr>' . "\n" ;
}

$output .= '</tbody>' . "\n";
echo '<div class="fieldset my-community-coupons"><h3>Top Performing Coupons</h3>' . $output;
echo '</table>';
if ($result->num_rows > 15) echo Pager(3, 15, array(15,25,50), 'dashBoard');
echo '<a href="http://www.google.com/analytics/" target="_blank" >Click here</a> for more google analytics
</div>' . "\n";
}
 */

/*
if (isset($_SESSION['isSAdmin'] ) && $_SESSION['isSAdmin'] == true)
{
$communities = array();
$sql = '';
$sqlpred = '';
$query = sprintf("SELECT id, name from tblcommunity where deleted = 0 and parentId = 1 and id != 1 order by name");
if ($result = $mysqli->query($query))
{

while ($row = $result->fetch_object())
{
$communities[preg_replace('/[^a-z]/','', strtolower($row->name)) . $row->id] =  array('name' => $row->name, 'id' => $row->id);
//$row->id
//$row->name
$sql .= sprintf('%s.listingLevelId as %s, ',
preg_replace('/[^a-z]/','', strtolower($row->name)) . $row->id,
preg_replace('/[^a-z]/','', strtolower($row->name)) . $row->id
);

$sqlpred .= sprintf('left outer join tblcommunitylistingmap %s on %s.listingId = l.id AND (%s.communityId = %d OR %s.communityId is null) ',
preg_replace('/[^a-z]/','', strtolower($row->name)) . $row->id,
preg_replace('/[^a-z]/','', strtolower($row->name)) . $row->id,
preg_replace('/[^a-z]/','', strtolower($row->name)) . $row->id,
$row->id,
preg_replace('/[^a-z]/','', strtolower($row->name)) . $row->id
);

}
}

$listingLevels = array();
$active = '';
$query = sprintf("SELECT id, name from tbllistinglevel");
if ($result = $mysqli->query($query))
{
while ($row = $result->fetch_object())
{
$listingLevels[$row->id] = $row->name;
}
}

$sql = 'SELECT
distinct l.id,
l.name,
u.firstName,
u.lastName,
CAST(l.active AS unsigned integer) as active, ' .
substr($sql, 0, -2) .
' FROM tbllisting l
inner join tbluser u on u.id = l.ownerUID ' .
$sqlpred .
' where l.deleted = 0 ORDER BY l.name';

//echo $sql . "<br /><br /><br /><br /><br />";

$listingData = '';
if ($result = $mysqli->query($sql))
{
$header = '<table id="tablesorter4" class="all-listings tablesorter">'. "\n" .'<thead><tr><th>Business Listing</th><th>Owner</th><th class="comm"><div><span>Active</span></div></th>';

foreach($communities as $key=>$value)
{
$header .= '<th class="comm"><div><span>' . htmlentities($value['name'])  . '</span></div></th>';
}

$header .= '</thead>' . "\n" . '<tbody>';

while ($row = $result->fetch_object())
{
//print_r($row);
if ($row->active == 1)
{
$active = '<a href="#active" class="disable-listing" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/active.gif" alt=""></a>';
}
else
{
$active = '<a href="#active" class="enable-listing" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/inactive.gif" alt=""></a>';
}

$listingData .= '<tr><td><a href="/dashboard/listing.php?id=' . $row->id . '">'. $row->name . '</a></td><td>' . $row->firstName . ' ' . $row->lastName . '</td><td>' . $active . '</td>';

foreach($communities as $key=>$value)
{

if (isset($listingLevels[$row->$key]))
{
$listingData .= '<td class="comm"><span class="' . str_replace(' listing','', strtolower($listingLevels[$row->$key]) ) . '">' . substr(str_replace(' listing', '', strtolower($listingLevels[$row->$key]) ),0,1)  . '</span></td>';
}
else
{
$listingData .= '<td class="comm"></td>';

}
}

$listingData .= '</tr>';
}
$listingData .= '</tbody></table>';
}

echo '<br /><br />';
echo '<h3 style="padding-bottom:5px;">Business Listings</h3>';
echo $header;
echo $listingData;
echo Pager(4, 30, array(30,100,200), 'dashBoard');
}
 */


if (isset($_SESSION['isSAdmin'] ) && $_SESSION['isSAdmin'] == true) {
    $sql = 'select distinct
         l.id
        ,map.communityId
        ,l.name
        -- ,(select min(end) from tblcommunitylistingmap where listingid = l.id and listingLevelId != 0 order by end asc) as listingEndDate -- ,UNIX_TIMESTAMP(l.lastEdit) as lastEdit
        ,CONCAT(comm.name, \', \', st.abbr) as communityName
        ,llvl.name as communityListingLevel
        ,CASE WHEN (llvl.sortOrder is null) THEN -10 ELSE llvl.sortOrder END as communityListingLevelOrder
        ,map.start as listingStartDate
        ,map.end as listingEndDate
        ,u.firstName
        ,u.lastName
        ,CAST(l.active AS unsigned integer) as active
        -- ,stats.pageViews as pageViews
        -- ,stats.uniqueVisitors as uniqueVisitors
        FROM tbllisting l
        left outer join tblcommunitylistingmap map on map.listingId = l.id
        left outer join tblcommunity comm on comm.id = map.communityId
        left outer join tbllistinglevel llvl on llvl.id = map.listingLevelId
        left outer join tblcounty cnty on cnty.id = comm.countyId
        left outer join tblstate st on st.id = cnty.stateId
        -- left outer join tblgastats stats on (stats.listingId = l.id and (stats.startDate = \'' . $analyticsStatStart->format('Y-m-d') . '\' and stats.endDate = \'' . $analyticsStatEnd->format('Y-m-d') . '\'))
        inner join tbluser u on u.id = l.ownerUID
        where l.deleted = 0
        -- and ((stats.listingId is not null and stats.startDate = \'' . $analyticsStatStart->format('Y-m-d') . '\' and stats.endDate = \'' . $analyticsStatEnd->format('Y-m-d') . '\') or stats.listingId is null)
        ORDER BY CASE WHEN (map.end is null) then \'9999-12-31\' ELSE DATE_FORMAT(map.end, \'%%Y-%%m-%%d\') END asc -- , l.name;';

    $isSA = '<th>Owner</th>';
}
else {
    $sql = sprintf('SELECT distinct
         l.id
        ,map.communityId
        ,l.name
        -- ,UNIX_TIMESTAMP(l.lastEdit) as lastEdit
        -- ,(select min(end) from tblcommunitylistingmap where listingid = l.id and listingLevelId != 0) as listingEndDate
        ,CONCAT(comm.name, \', \', st.abbr) as communityName
        ,llvl.name as communityListingLevel
        ,CASE WHEN (llvl.sortOrder is null) THEN -10 ELSE llvl.sortOrder END as communityListingLevelOrder
        ,map.start as listingStartDate
        ,map.end as listingEndDate
        ,u.firstName
        ,u.lastName
        ,CAST(l.active AS unsigned integer) as active
        -- ,stats.pageViews as pageViews
        -- ,stats.uniqueVisitors as uniqueVisitors
        FROM tbllisting l
        left outer join tblcommunitylistingmap map on map.listingId = l.id
        left outer join tblcommunity comm on comm.id = map.communityId
        left outer join tbllistinglevel llvl on llvl.id = map.listingLevelId
        left outer join tblcounty cnty on cnty.id = comm.countyId
        left outer join tblstate st on st.id = cnty.stateId
        -- left outer join tblgastats stats on (stats.listingId = l.id and (stats.startDate = \'' . $analyticsStatStart->format('Y-m-d') . '\' and stats.endDate = \'' . $analyticsStatEnd->format('Y-m-d') . '\'))
        INNER JOIN tbluser u on u.id = l.ownerUID
        WHERE l.deleted = 0
            AND l.ownerUID  = %d
            -- and (stats.startDate = \'' . $analyticsStatStart->format('Y-m-d') . '\' and stats.endDate = \'' . $analyticsStatEnd->format('Y-m-d') . '\')
        ORDER BY CASE WHEN (map.end is null) then \'9999-12-31\' ELSE DATE_FORMAT(map.end, \'%%Y-%%m-%%d\') END asc -- , l.name'
        ,$mysqli->real_escape_string($_SESSION['uid']));
    
    $isSA = '';
}

// echo '<pre>' . $sql . '</pre>';

if ($result = $mysqli->query($sql)) {

    $header = '<table style="width:100%;">' .
        '<tr><td align="right">Search: <input id="tablefilter2Input" type="text" /><input id="tablefilter2" type="hidden" /><a href="javascript:void(0);" id="tablefilterclear2"><img src="http://' . STATIC_URL . '/images/admin/Delete.png" width="18" height="18" alt="" border="0"></a></td></tr>' .
        /*'<tr><td align="right" style="padding-top: 1em;">Analytics: ' .
            '   <a href="?analytics=30" class="listing-analytics" data-interval="30">'   . ((isset($_GET['analytics']) && $_GET['analytics'] == "30")  ? '<strong style="font-weight: bold !important; ">30</strong>'  : '30')  . '</a>' .
            ' | <a href="?analytics=90" class="listing-analytics" data-interval="90">'   . ( isset($_GET['analytics']) && $_GET['analytics'] == "90"  ? '<strong style="font-weight: bold !important; ">90</strong>'  : '90')  . '</a>' .
            ' | <a href="?analytics=180" class="listing-analytics" data-interval="180">' . ( isset($_GET['analytics']) && $_GET['analytics'] == "180" ? '<strong style="font-weight: bold !important; ">180</strong>' : '180') . '</a>' .
            ' | <a href="?analytics=360" class="listing-analytics" data-interval="360">' . ( isset($_GET['analytics']) && $_GET['analytics'] == "360" ? '<strong style="font-weight: bold !important; ">360</strong>' : '360') . '</a>' .
        '</td></tr>' .*/
        '<tr><td align="right" style="padding-top: 15px;">
            <i class="fa fa-fw fa-filter" title="Filter" />:
            <a href="#" class="active" data-action="table-filter" data-target="#tablefilter2" data-filter="">All</a> |
            <a href="#" data-action="table-filter" data-target="#tablefilter2" data-filter="State:Enabled">Active</a> |
            <a href="#" data-action="table-filter" data-target="#tablefilter2" data-filter="State:Disabled">Inactive</a>
        </td></tr>' .
        '</table><br />' .
        '<table id="tablesorter2" style="width:100%;" class="all-listings1 tablesorter' . ((isset($_SESSION['isSAdmin'] ) && $_SESSION['isSAdmin'] == true) ? ' has-owner' : '') . '">'.
        "\n" .
        '<thead><tr>' .
            '<th>Business Listing</th>' .
            '<th>Community</th>' .
            '<th>Listing Level</th>' .
            $isSA .
            '<th class="comm">Expiration</th>' .
            '<th class="comm"><div><span>Active</span></div></th>' .
            /*'<th class="not-sortable">Actions</th>' .
            '<th class="">Pageviews</th>' .
            '<th class="">Visitors</th>';*/
            '';
    $header .= '</thead>' .
        "\n" .
        '<tbody>';
    $body ='';
    $owner = '';
    $active = '';
    while ($row = $result->fetch_object()) {
        //if (is_null($row->lastEdit) || $row->lastEdit == 0 ) $myDate = '';
        //else  $myDate = date(DATE_FORMAT, $row->lastEdit);

        $myDate = !empty($row->listingEndDate) ? date(DATE_FORMAT, strtotime($row->listingEndDate)) : '<span class="sr-only">9999-12-31</span>Not set';
        $communityName = str_replace('\n', '<br/>', $row->communityName);
        $listingLevel = $row->communityListingLevel;
        $listingLevelOrder = $row->communityListingLevelOrder;
        if ($row->active == 1) {
            $active = '<a href="#active" class="disable-listing" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/active.gif" alt=""><span class="sr-only">State:Enabled</span></a>';
        }
        else {
            $active = '<a href="#active" class="enable-listing" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/inactive.gif" alt=""><span class="sr-only">State:Disabled</span></a>';
        }

        if ($isSA) {
            $owner = '<td style="white-space: nowrap;">' . htmlentities($row->firstName) . ' ' . htmlentities($row->lastName) . '</td>';
        }

        $body .= '<tr>' .
            '<td><a href="/dashboard/listing.php?id=' . $row->id . '"  class="dashListingHover-disabled" data-action="edit-listing" data-community-id="' . $row->communityId . '" data-listing-id="' . $row->id  . '" >' . htmlentities($row->name) . '</a></td>' .
            '<td>' . $communityName . '</td>' .
            '<td><span class="sr-only">' . $listingLevelOrder . '</span>' . str_replace(' Program', '', $listingLevel) . '</td>' .
            $owner .
            '<td>' . $myDate . '</td>' .
            '<td>' . $active . '</td>' .
            /*'<td class="not-sortable smallBox"><a href="/dashboard/listing.php?id=' . $row->id . '" class="editListing editListingDash" data-listingId="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/admin/edit.png" class="buttonEdit" border="0" width="20" height="20" /></a></td>' .
            '<td>' . (empty($row->pageViews) ? '-' : $row->pageViews) . '</td>' .
            '<td>' . (empty($row->uniqueVisitors) ? '-' : $row->uniqueVisitors) . '</td>' .*/
        '</tr>' ;
    }
    $body .= '</tbody></table>';

    echo '<div style="margin-top:30px;" class="fieldset"><h3>My Listings</h3>';
    echo $header;
    echo $body;
    echo Pager(2, 100, array(100,250,500), 'dashBoard');
    echo '</div>';
}


$mysqli->close();
?>



