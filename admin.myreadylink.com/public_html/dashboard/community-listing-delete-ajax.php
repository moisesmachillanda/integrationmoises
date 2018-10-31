<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

if(isset($_POST['listingid']) && is_numeric($_POST['listingid']))
{
	
	$query = sprintf("UPDATE tbllisting set deleted = 1 where id = %d",
				$mysqli->real_escape_string($_POST['listingid'])
				);
	
	$mysqli->query($query);
	echo $query;
	$loggedData = array(
			'FUNCTION' => 'deleteListing',
		'listingid' => $_POST['listingid']
	);
	
	adminLog('update',$loggedData);
	
	echo json_encode(array("result"=>true));
	exit;
}

echo json_encode(array("result"=>false));