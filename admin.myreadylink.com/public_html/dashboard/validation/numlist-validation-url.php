<?php
require_once "../../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
$valid = true;



if(isset($_REQUEST['type']) && $_REQUEST['type'] == 'number') //number
{
	$query = sprintf("select * 
				from tblnumber 
				where
				SEOName='%s'
				AND deleted = 0
				AND id != %d
				limit 1",
	$mysqli->real_escape_string($_REQUEST['slug']),
	$mysqli->real_escape_string($_REQUEST['objId'])
	);
	//echo $query;
}
else if(isset($_REQUEST['type']) && $_REQUEST['type'] == 'listing') //listing
{
	$query = sprintf("select *
					from tbllisting 
					where
					SEOName='%s'
					AND deleted = 0
					AND id != %d
					limit 1",
	$mysqli->real_escape_string($_REQUEST['slug']),
	$mysqli->real_escape_string($_REQUEST['objId'])
	);
}
else // unknown request:
{
	$valid = false;
	header('Content-type: application/json');
	echo json_encode($valid);
	exit;
}

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

header('Content-type: application/json');
echo json_encode($valid);

