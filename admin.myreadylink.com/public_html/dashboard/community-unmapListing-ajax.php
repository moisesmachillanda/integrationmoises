<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

if(isset($_POST['listingid']) && is_numeric($_POST['listingid']) && isset($_POST['communityId']) && is_numeric($_POST['communityId']))
{
	
	$query = sprintf("DELETE FROM tblcommunitylistingmap where listingId = %d AND communityId=%d",
				$mysqli->real_escape_string($_POST['listingid']),
				$mysqli->real_escape_string($_POST['communityId'])
				);
	
	$mysqli->query($query);
	
	$loggedData = array(
			'FUNCTION' => 'removeListingMapping',
		'listingid' => $_POST['listingid'],
		'communityid' => $_POST['communityId']
	);
	
	adminLog('update',$loggedData);
	
	echo json_encode(array("result"=>true));
	exit;
}

echo json_encode(array("result"=>false));