<?php
require_once "../../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
$valid = true;


$query = sprintf("select * 
			from tblnumbercommunity 
			where
			url='%s'
			AND id != %d
			limit 1",
$mysqli->real_escape_string($_REQUEST['URLslug']),
$mysqli->real_escape_string($_REQUEST['commid'])
);

if ($result = $mysqli->query($query))
{
	while($row = $result->fetch_object())
	{
		$valid = false;
		header('Content-type: application/json');
		echo json_encode($valid);
		exit;
	}
}

$mysqli->close();

if (!preg_match("/[a-z0-9-]/i", $_REQUEST['URLslug'])) 
{
	$valid = false;
	header('Content-type: application/json');
	echo json_encode($valid);
	exit;
}	

header('Content-type: application/json');
echo json_encode($valid);

