<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

if(isset($_POST['numberid']) && is_numeric($_POST['numberid']))
{
	
	$query = sprintf("UPDATE tblnumber set deleted = 1 where id = %d",
				$mysqli->real_escape_string($_POST['numberid'])
				);
	
	$mysqli->query($query);
	
	$loggedData = array(
			'FUNCTION' => 'deleteNumber',
		'numberid' => $_POST['numberid']
	);
	
	adminLog('update',$loggedData);
	
	echo json_encode(array("result"=>true));
	exit;
}

echo json_encode(array("result"=>false));