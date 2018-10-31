<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$query = sprintf("UPDATE tblcommunity set deleted = 1 WHERE id=%d",
$mysqli->real_escape_string($_POST['deleteid'])
);

if ($result = $mysqli->query($query))
{
	$loggedData = array(
			'FUNCTION' => 'deleteCommunity',
			'id' => $_POST['deleteid']
	);
	
	adminLog('update',$loggedData);
	echo json_encode(array("result"=>true));
}
else 
{
	echo json_encode(array("result"=>false));
}