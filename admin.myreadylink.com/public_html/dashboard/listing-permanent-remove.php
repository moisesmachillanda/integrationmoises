<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";
require_once "../../../includes/app/bootstrap.php";

requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Open MySQL Connection
    $mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

    // Is Admin User:
    $isAdminUser = (isset($_SESSION['isSAdmin']) && $_SESSION['isSAdmin']);

    // Has Super Admin Role:
    $isSuperAdmin = ($_SESSION['roles'] != null && in_array(array('id' => 1, 'name' => 'Super Admin'), $_SESSION['roles']));

    if (isset($_POST['submitted']) && isset($_POST['listingId']) && $isAdminUser && $isSuperAdmin) {
        $community = get_CommunityInfo(array('id'=>$_COOKIE['viewCommunity']));

        // Get Data for log
        $listingQuery = sprintf(
            'select \'listingPermanentRemove\' as `FUNCTION`, * from tbllisting where id = %1$d;',
            $_POST['listingId']
        );

        if ($result = $mysqli->query($listingQuery)) {
            while ($row = $result->fetch_array()) {
                $loggedData = $row;
            }
            
            $result->close();
        }

        // Get listing community mappings.
        $loggedData['communityMaps'] = array();
        
        $communityMapsQuery = sprintf(
            'select * from tblcommunitylistingmap where listingId = %1$d;',
            $_POST['listingId']
        );

        if ($result = $mysqli->query($communityMapsQuery)) {
            while ($row = $result->fetch_array()) {
                $loggedData['communityMaps'][] = $row;
            }

            $result->close();
        }

        // Get listing uploads. (Including meta)
        $loggedData['uploads'] = array();

        $uploadsQuery = sprintf(
            'select * from tblupload where listingId = %1$d;',
            $_POST['listingId']
        );
        
        if ($result = $mysqli->query($uploadsQuery)) {
            while ($row = $result->fetch_array()) {
                // Get upload meta, if any.
                $uploadMetaQuery = sprintf(
                    'select * from tbluploadmeta where listingUploadId = %1$d;',
                    $row['id']
                );


                if ($metaResult = $mysqli->query($uploadMetaQuery)) {
                    $row['meta'] = $metaResult->fetch_all(MYSQLI_NUM);

                    $metaResult->close();
                }

                $loggedData['uploads'][] = $row;
            }

            $result->close();
        }

        // Set listing as inactive and deleted
        $removalQueries = "";
        $updateListingQuery = sprintf(
            'update tbllisting set active = 0, delete = 1 where id = %1$d;',
            $_POST['listingId']  
        );

        $removalQueries .= $updateListingQuery . "\n";

        $mysqli->query($updateListingQuery);

        // Remove Uploads & meta
        foreach ($loggedData['uploads'] as $upload) {
            if (count($upload['meta']) > 0) {
                foreach ($uploadMeta as $upload['meta']) {
                    $deleteUploadMetaQuery = sprintf(
                        'delete from tbllistinguploadmeta where id = %1$d;',
                        $uploadMeta['id']
                    );

                    $removalQueries .= $deleteUploadMetaQuery . "\n";

                    $mysqli->query($deleteUploadMetaQuery);
                }
            }

            $deleteUploadQuery = sprintf(
                'delete from tbllistingupload where id = %1$d;',
                $upload['id']
            );

            $removalQueries .= $deleteUploadQuery . "\n";
            $mysqli->query($deleteUploadQuery);
        }

        // Remove Community Maps
        foreach ($loggedData['communityMaps'] as $communityMap) {
            $deleteCommunityMapQuery = sprintf(
                'delete from tblcommunitylistingmap where id = %1$d;',
                $communityMap['id']
            );

            $removalQueries .= $deleteCommunityMapQuery . "\n";
            $mysqli->query($deleteCommunityMapQuery);
        }

        // Remove Google Analytics Stats
        $deleteGaStatsQuery = sprintf(
            'delete from tblgastats where listingId = %1$d',
            $_POST['listingId']
        );

        $removalQueries .= $deleteGaStatsQuery . "\n";
        $mysqli->query($deleteGaStatsQuery);

        // Remove Listing
        $deleteListingQuery = sprintf(
            'delete from tbllisting where id = %1$d;',
            $_POST['listingId']
        );

        $removalQueries .= $deleteListingQuery . "\n";
        $mysqli->query($deleteListingQuery);

        adminLog('delete',$loggedData);

        echo json_encode(array(
            'success' => true,
            'queries' => $removalQueries
        ));
    } // isset($_POST['submitted']) && isset($_POST['listingId']) && $isAdminUser && $isSuperAdmin
    else {
        echo json_encode(array('success' => false));
    }

    $mysqli->close();
}


?>