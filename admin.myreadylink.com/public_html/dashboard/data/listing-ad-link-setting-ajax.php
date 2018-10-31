<?php
require_once "../../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../../includes/functions.php";

requireLogin();

if (isset($_POST['listingId']) && isset($_POST['listingUploadId']) && isset($_POST['useLisitngWebsite']) && is_numeric($_POST['listingId']) && is_numeric($_POST['listingUploadId'])) {

    $mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

    $query = sprintf("update tbllistingupload set useListingUrl = %d where id = %d and listingId = %d",
        $mysqli->real_escape_string($_POST['useLisitngWebsite'] == "true" ? 1 : 0),
        $mysqli->real_escape_string($_POST['listingUploadId']),
        $mysqli->real_escape_string($_POST['listingId'])
    );

    $mysqli->query($query);

    $loggedData = array(
        'FUNCTION' => 'setListingAdLink',
        'listingId' => $_POST['listingId'], 
        'listingUploadId' => $_POST['listingUploadId'],
        'useListingWebsite' => $_POST['useLisitngWebsite']
    );
    
    adminLog('update', $loggedData);

    echo json_encode( array("result"=>true) );
}
else {
    header('Content-type: application/json');
    echo json_encode( array("result"=>false) );
}

?>