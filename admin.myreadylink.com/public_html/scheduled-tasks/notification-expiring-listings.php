<?php
error_reporting(E_ALL);
ini_set("display_errors", true);

require_once "../../../includes/app/bootstrap.php";
require_once "../../../includes/config.php";

if (isset($_GET['key']) && $_GET['key'] == base64_encode("MyReadyLink.com Scheduled Tasks")) {

    $daysInAdvance = 14;

    $stmtGetSalespeople = $connection->prepare("
select id, firstName, lastName, email
from tbluser
where active = 1
  and deleted = 0
  and id in (
    select l.ownerUID
    from tbllisting as l
    where l.id in (
        select map.listingId
        from tblcommunitylistingmap as map
        where DATEDIFF(end, DATE(NOW())) = :days
    )
  )
;
");

    $stmtGetSalespeople->bindValue("days", $daysInAdvance);
    $stmtGetSalespeople->execute();

    $salesPeople = $stmtGetSalespeople->fetchAll();

    foreach($salesPeople as $salesPerson) {
        echo sprintf("Found expiring listings for %1s %2s <%3s>.\nGetting listings:\n", $salesPerson['firstName'], $salesPerson['lastName'], $salesPerson['email']);

        // Get Expiring Listing...
        $stmtListings = $connection->prepare("
        SELECT distinct
         l.id
        ,comm.id as communityId
        ,l.name
        ,CONCAT(comm.name, ', ', st.abbr) as communityName
        ,llvl.name as communityListingLevel
        ,CASE WHEN (llvl.sortOrder is null) THEN -10 ELSE llvl.sortOrder END as communityListingLevelOrder
        ,map.start as listingStartDate
        ,map.end as listingEndDate
        ,u.firstName
        ,u.lastName
        ,CAST(l.active AS unsigned integer) as active
        FROM tbllisting l
        left outer join tblcommunitylistingmap map on map.listingId = l.id
        left outer join tblcommunity comm on comm.id = map.communityId
        left outer join tbllistinglevel llvl on llvl.id = map.listingLevelId
        left outer join tblcounty cnty on cnty.id = comm.countyId
        left outer join tblstate st on st.id = cnty.stateId
        INNER JOIN tbluser u on u.id = l.ownerUID
        WHERE l.deleted = 0
        AND l.ownerUID  = :userid
        AND DATEDIFF(end, DATE(NOW())) = :days
        ORDER BY map.end, l.name
        ;
    ");

        $stmtListings->bindValue("days", $daysInAdvance);
        $stmtListings->bindValue("userid", $salesPerson['id']);

        $stmtListings->execute();

        $listings = $stmtListings->fetchAll();

        $listingData = array(); // [];

        // Build Listing List:
        foreach ($listings as $listing) {
            $listingData[] = array(
                'html' => '<p><strong>' . $listing['name'] . '</strong> - ' . $listing['communityName'] . ' ' . $listing['communityListingLevel'] . '<br/>Expires: ' . date(DATE_FORMAT, strtotime($listing['listingEndDate'])) . '<br/><a href="http://admin.myreadylink.com/dashboard/listing.php?id=' . $listing['id'] . '&amp;communityid=' . $listing['communityId'] . '">Edit Listing</a></p>',
                'text' => $listing['name'] . " - " . $listing['communityName'] . " " . $listing['communityListingLevel'] . "\r\nExpires: " . date(DATE_FORMAT, strtotime($listing['listingEndDate'])) . "\r\nEdit Listing <http://admin.myreadylink.com/dashboard/listing.php?id=" . $listing['id'] . "&communityid=" . $listing['communityId'] . ">\r\n-------------------------------------------------------------------------------------------------------------\r\n",
            );
        }

        echo sprintf("Found %1d listing(s)\n", count($listingData));


        // Send Message...
        if ($salesPerson['email'] != null && !empty($salesPerson['email'])) {
            echo "Sending email...";
            $sent = \MyReadyLink\Notifications\AdminNotifications::sendExpirationNotice(array($salesPerson['email']), $listingData);
            echo ($sent ? " Success!" : "Failure!") . "\n\n";
        }
    }
}

?>
