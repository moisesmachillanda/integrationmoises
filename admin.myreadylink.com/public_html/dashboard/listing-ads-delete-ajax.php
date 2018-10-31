<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$listingUploadId = $listingId = 0;
$listingType = '';

if(isset($_POST['listingId']) 
	&& is_numeric($_POST['listingId']) 
	&& isset($_POST['listingUploadId']) 
	&& is_numeric($_POST['listingUploadId'])
	&& isset($_POST['listingType']) 
	&& ($_POST['listingType']=='listing photo' 
		|| $_POST['listingType']=='listing detail photo' 
		|| $_POST['listingType']=='coupon' 
		|| $_POST['listingType']=='flyer'
		|| $_POST['listingType']=='menu'
		|| $_POST['listingType']=='brochure')
		)
	{
	
	$listingUploadId = $_POST['listingUploadId'];
	$listingId = $_POST['listingId'];
	$listingType = $_POST['listingType'];
	
	}	

$query = sprintf("update tbllistingupload set active = 0  
					  WHERE listingId = %d
					  AND active=1
					  AND id= %d
					  AND type='%s'",
				$mysqli->real_escape_string($listingId),
				$mysqli->real_escape_string($listingUploadId),
				$mysqli->real_escape_string($listingType)
);
//echo $query;
$mysqli->query($query);
	$loggedData = array(
			'FUNCTION' => 'setinactiveUpload',
		'id' => $listingId,
		'listingId' => $listingUploadId,
		'type' => $listingType
	);
	
	adminLog('update',$loggedData);
	echo json_encode(array("result"=>true));

