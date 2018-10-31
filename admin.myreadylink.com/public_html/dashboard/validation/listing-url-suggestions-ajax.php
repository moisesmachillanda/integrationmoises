<?php
require_once "../../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$existingURLS = array();
$suggestionURLS = array();
header('Content-type: application/json');
if(isset($_REQUEST['name']) && isset($_REQUEST['id'])) //number
{
	$query = sprintf("select *
				from tbllisting 
				where
				SEOName like '%s'
				AND deleted = 0
				AND id != %d",
	$mysqli->real_escape_string($_REQUEST['name']) . '%',
	$mysqli->real_escape_string($_REQUEST['id'])
	);
	
	if ($result = $mysqli->query($query))
	{
		while($row = $result->fetch_object())
		{
			array_push($existingURLS, $row->SEOName);
		}
	}
	
	for ($i = 1; $i <= 100; $i++) 
	{
		
		if(!in_array(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]+/', '-',strtolower(str_replace('&','and',trim($_REQUEST['name']) ) ))) .  '-' . $i, $existingURLS))
		{
			if(!in_array(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]+/', '-',strtolower(str_replace('&','and',trim($_REQUEST['name']) ) ))) .  '-' . $i, $suggestionURLS))
			{
				array_push($suggestionURLS, preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]+/', '-',strtolower(str_replace('&','and',trim($_REQUEST['name']))))) . '-' . $i);
			}
			
		}
		
		if(count($suggestionURLS) > 2) break;
		
	}
	
	echo json_encode(array("result"=>true, "suggestions" => $suggestionURLS));
}
else
{
	echo json_encode(array("result"=>false));
}

?>