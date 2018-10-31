<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";
require_once "../../../includes/app/bootstrap.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$listingId = $communityDDL =  $listingLevelDDL = $listingStart = $listingEnd = 0;

if(isset($_POST['listingId']) && is_numeric($_POST['listingId']))
    $listingId = $_POST['listingId'];

if(isset($_POST['communityDDL']) && is_numeric($_POST['communityDDL']))
    $communityDDL = $_POST['communityDDL'];

if(isset($_POST['listingLevelDDL']) && is_numeric($_POST['listingLevelDDL']))
    $listingLevelDDL = $_POST['listingLevelDDL'];

if (isset($_POST['listingMapStart']) && !empty($_POST['listingMapStart']))
    $listingStart = strtotime($_POST['listingMapStart']);

if (isset($_POST['listingMapEnd']) && !empty($_POST['listingMapEnd']))
    $listingEnd = strtotime($_POST['listingMapEnd']);


if($listingId > 0 && $communityDDL > 0 && $listingLevelDDL == 0)
{
    //delete old mappings
    $query = sprintf("DELETE from tblcommunitylistingmap WHERE listingId=%d AND communityId=%d",
    $mysqli->real_escape_string($listingId),
    $mysqli->real_escape_string($communityDDL)
    );

    $loggedData = array(
                'FUNCTION' => 'listingCategoriesEdit',
            'listingId' => $listingId,
            'communityId' => $communityDDL
    );


    $mysqli->query($query);
    adminLog('delete',$loggedData);
    echo json_encode(array("result"=>true));
}
elseif($listingId > 0 && $communityDDL > 0 && $listingLevelDDL > 0)
{
    // Get existing mapping start/end dates.
    $listingMappingDatesQuery = sprintf("
        select distinct
            unix_timestamp(start) as start,
            unix_timestamp(end) as end
        from tblcommunitylistingmap
        where listingId = %d
          and communityId = %d;
    ",
    $listingId,
    $communityDDL);

    $datesChanged = false;
    if ($listingMappingDatesResult = $mysqli->query($listingMappingDatesQuery)) {
        if ($listingMappingDatesResult->num_rows == 1) {
            // Pre exists, checking dates
            $row = $listingMappingDatesResult->fetch_assoc();

            if ($row['start'] != $listingStart || $row['end'] != $listingEnd) {
                $datesChanged = true;
            }
        }
    }

    //delete old mappings
    $query = sprintf("DELETE from tblcommunitylistingmap WHERE listingId=%d AND communityId=%d",
            $mysqli->real_escape_string($listingId),
            $mysqli->real_escape_string($communityDDL)
    );

    $loggedData = array(
            'FUNCTION' => 'listingCategoriesEdit',
        'listingId' => $listingId,
        'communityId' => $communityDDL
    );


    $removeOldMappingsResult = $mysqli->query($query);
    adminLog('delete',$loggedData);

    //Add new mappings
    foreach($_POST as $key=>$value) {
        if(startsWith($key,'cbx-') && is_numeric($value)) {
            $query = sprintf("INSERT into tblcommunitylistingmap (listingId,communityId,listingLevelId,listingCategoryId,start,end) values(%d,%d,%d,%d,'%s','%s')",
            $mysqli->real_escape_string($listingId),
            $mysqli->real_escape_string($communityDDL),
            $mysqli->real_escape_string($listingLevelDDL),
            $mysqli->real_escape_string($value),
            $mysqli->real_escape_string(date('Y-m-d H:i:s', $listingStart)),
            $mysqli->real_escape_string(date('Y-m-d H:i:s', $listingEnd))
            );

            $mysqli->query($query);

            $loggedData = array(
                    'FUNCTION' => 'listingCategoriesEdit',
                    'listingId' => $listingId,
                    'communityId' => $communityDDL,
                    'listingLevel' => $listingLevelDDL,
                    'categoryId' => $value,
                    'start' => date('Y-m-d H:i:s', $listingStart),
                    'end' => date('Y-m-d H:i:s', $listingEnd)
            );

            adminLog('insert',$loggedData);
        }
    }

    if ($listingMappingDatesResult->num_rows == 0 || $datesChanged == true) {
        // Lookup Listing Details...
        $listingLookupQuery = sprintf("
            select distinct
                listing.id,
                listing.name,
                listing.contactPhone as phone,
                listing.contactFax as fax,
                listing.address1,
                listing.address2,
                listing.city,
                states.name as stateName,
                listing.zip,
                listing.description,
                listing.products,
                listing.services,
                listing.specials,
                case when (listing.show_specials = 1) then 'Yes' else 'No' end as showSpecials,
                case when (listing.active = 1) then 'Yes' else 'No' end as active,
                listing.contactName as contactname,
                listing.contactEmail as contactemail,
                listing.website,
                listing.website_facebook,
                listing.website_twitter,
                listing.website_linkedin,
                listing.hoursOfOperation as hours,
                listing.metaTitle as metatitle,
                listing.metaKeywords as metakeywords,
                listing.metaDescription as metadescription,
                listing.listingPassword as listingpassword,
                listing.SEOName as slug,
                listing.SEOImageAltText as imagealttext,
                concat_ws(' ', users.firstName, users.lastName) as userFullName,
                users.email as userEmail,
                comm.name as communityName,
                date_format(map.start, '%%m/%%d/%%Y') as communityMapStart,
                date_format(map.end, '%%m/%%d/%%Y') as communityMapEnd
            from tbllisting as listing
            inner join tblstate as states on states.id = listing.stateId
            inner join tbluser as users on users.id = %d
            inner join tblcommunitylistingmap as map on map.listingId = listing.id
            inner join tblcommunity as comm on comm.id = map.communityId
            where listing.id = %d
              and map.communityId = %d
            ",
            $mysqli->real_escape_string($_SESSION['uid']),
            $mysqli->real_escape_string($listingId),
            $mysqli->real_escape_string($communityDDL)
        );

        $listingData = array();

        if ($listingLookupResult = $mysqli->query($listingLookupQuery)) {
            if ($listingLookupResult->num_rows > 0) {
                $listingData = $listingLookupResult->fetch_assoc();
            }

            $listingLookupResult->close();
        }


        // Send Email Notification
        if ($datesChanged == false)
            \MyReadyLink\Notifications\AdminNotifications::sendNewListingMappingNotification($listingData);
        else
            \MyReadyLink\Notifications\AdminNotifications::sendNewListingMappingNotification($listingData, 'MyReadyLink Admin - Listing Mapping Date Changed: %s');
    }

    echo json_encode(array("result"=>true));
}



function startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}
