<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";
require_once "../../../includes/app/bootstrap.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$listingLocationData = array(
    $mysqli->real_escape_string($_POST['id']),
    $mysqli->real_escape_string($_POST['listingid']),
    $mysqli->real_escape_string($_POST['name']),
    $mysqli->real_escape_string($_POST['contactName']),
    $mysqli->real_escape_string($_POST['contactPhone']),
    $mysqli->real_escape_string($_POST['contactEmail']),
    $mysqli->real_escape_string($_POST['contactFax']),
    $mysqli->real_escape_string($_POST['address1']),
    $mysqli->real_escape_string($_POST['address2']),
    $mysqli->real_escape_string($_POST['city']),
    $mysqli->real_escape_string($_POST['state']),
    $mysqli->real_escape_string($_POST['zip']),
    $mysqli->real_escape_string($_POST['geolatitude']),
    $mysqli->real_escape_string($_POST['geolongitude']),
    $mysqli->real_escape_string($_POST['website']),
    $mysqli->real_escape_string($_POST['website_facebook']),
    $mysqli->real_escape_string($_POST['hours']),
    $mysqli->real_escape_string($_POST['active']),
);

// Shift id to the end of the array - different order for update query.
$updateListingLocationData = $listingLocationData;
$updateListingLocationData[] = array_shift($updateListingLocationData);

$locationCommunityMappings = $_POST['communityids'];

//region Log Data
$locationLogData = array(
    'FUNCTION' => '',
    'id' => $_POST['id'],
    'listingid' => $_POST['listingid'],
    'name' => $_POST['name'],
    'contactname' => $_POST['contactName'],
    'contactphone' => $_POST['contactPhone'],
    'contactemail' => $_POST['contactEmail'],
    'contactFax' => $_POST['contactFax'],
    'address1' => $_POST['address1'],
    'address2' => $_POST['address2'],
    'city' => $_POST['city'],
    'state' => $_POST['state'],
    'zip' => $_POST['zip'],
    'geolatitude' => $_POST['geolatitude'],
    'geolongitude' => $_POST['geolongitude'],
    'active' => $_POST['active']
);

$locationCommunityMapLogData = array(
    'FUNCTION' => '',
    'id' => $_POST['id'],
    'listingid' => $_POST['listingid'],
    'communityid' => ''
);
//endregion Log Data

$insertQuery = vsprintf(
    'INSERT INTO tbllistinglocation (
        id, listingId, name, contactName, contactPhone, contactEmail, contactFax, address1, address2, city, stateId, zip, geolatitude, geolongitude, website, website_facebook, hoursOfOperation, active
    ) VALUES (
        %d, %d, \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', %d, \'%s\', %s, %s, \'%s\', \'%s\', \'%s\', %d
    );',
    $listingLocationData
);

$updateQuery = vsprintf(
    'UPDATE tbllistinglocation SET
        listingId = %d,
        name = \'%s\',
        contactName = \'%s\',
        contactPhone = \'%s\',
        contactEmail = \'%s\',
        contactFax = \'%s\',
        address1 = \'%s\',
        address2 = \'%s\',
        city = \'%s\',
        stateId = %d,
        zip = \'%s\',
        geolatitude = %s,
        geolongitude = %s,
        website = \'%s\',
        website_facebook = \'%s\',
        hoursOfOperation = \'%s\',
        active = %d
    WHERE id = %d;',
    $updateListingLocationData
);

$locationCommunityMapInsertQuery = 'INSERT INTO tblcommunitylistinglocationmap (listingId, listingLocationId, communityId, sortOrder) VALUES (%d, %d, %d, %d);';

$locationCommunityMapDeleteQuery = 'DELETE FROM tblcommunitylistinglocationmap WHERE listingId = %d AND listingLocationId = %d;';

$logType = '';

$listingLocationId = null;
$result = array('id' => null);

if (isset($_POST['submitted'])) {

    if (empty($_POST['id'])) {
        // Insert
        $mysqli->query($insertQuery);

        $logType = 'insert';
        $locationLogData['id'] = $result['id'] = $listingLocationId = $mysqli->insert_id;
        $locationLogData['FUNCTION'] = 'listingLocationAdd';
    }
    else {
        // Update
        $mysqli->query($updateQuery);

        $result['id'] = $listingLocationId = $_POST['id'];

        $logType = 'update';
        $locationLogData['FUNCTION'] = 'listingLocationEdit';

        // Clear Community Mappings
        $mysqli->query(vsprintf(
            $locationCommunityMapDeleteQuery, array(
                $mysqli->real_escape_string($_POST['listingid']),
                $mysqli->real_escape_string($_POST['id']),
            )
        ));
    }

    // Log transaction
    adminLog($logType, $locationLogData);

    // Add Community Mappings
    if (isset($locationCommunityMappings) && count($locationCommunityMappings) > 0) {
        foreach ($locationCommunityMappings as $communityid) {
            $mysqli->query(vsprintf(
                $locationCommunityMapInsertQuery,
                array(
                    $mysqli->real_escape_string($_POST['listingid']),
                    $mysqli->real_escape_string($listingLocationId),
                    $mysqli->real_escape_string($communityid),
                    $mysqli->real_escape_string(0)
                )
            ));

            $locationCommunityMapLogData['FUNCTION'] = 'listingLocationCommunityMapAddUpdate';
            $locationCommunityMapLogData['communityid'] = $communityid;
            adminLog('insert', $locationCommunityMapLogData);
        }
    }

}
header('Content-Type: application/json');
echo json_encode($result);

?>