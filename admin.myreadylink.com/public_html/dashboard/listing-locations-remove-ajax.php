<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";
require_once "../../../includes/app/bootstrap.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$deleteCommunityMapQuery = sprintf("DELETE FROM tblcommunitylistinglocationmap where listingLocationId = %d;", $mysqli->real_escape_string($_POST['id']));
$deleteLocationQuery = sprintf("DELETE FROM tbllistinglocation where id = %d;", $mysqli->real_escape_string($_POST['id']));

$locationLogData = array(
    'FUNCTION' => 'listingLocationRemove',
    'id' => $_POST['id'],
);


$result = array('id' => null, 'status' => null);
if (isset($_POST['submitted'])) {

    // Remove mappings
    $mysqli->query($deleteCommunityMapQuery);

    // Remove Location
    $mysqli->query($deleteLocationQuery);

    $result['id'] = $_POST['id'];
    $result['status'] = "deleted";

    // Log transaction
    adminLog('delete', $locationLogData);


}
header('Content-Type: application/json');
echo json_encode($result);
?>