<?php
require_once "../../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../../includes/functions.php";


requireLogin();
header('Content-type: application/json');
if(isset($_POST['stateId']) && is_numeric($_POST['stateId']))
{
	$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
	
	$sql = sprintf('SELECT abbr FROM tblstate
					WHERE id = %d',
					$mysqli->real_escape_string($_POST['stateId'])
	);
	
	
	$output='';
	if ($result = $mysqli->query($sql))
	{
		while($row = $result->fetch_object())
		{
			echo json_encode(array("result"=>true,"StateAbbr"=>$row->abbr));
		}
	}
	else
	{
		echo json_encode(array("result"=>false));
	}
	
}
else
{
	echo json_encode(array("result"=>false));
}