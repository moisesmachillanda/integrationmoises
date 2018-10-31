<?php
require_once "../../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
$valid = true;
$query = sprintf("select * 
							from tbluser 
							where
							deleted = 0
							AND email='%s'
							AND id != %d
							limit 1",
				$mysqli->real_escape_string($_REQUEST['email']),
				$mysqli->real_escape_string($_REQUEST['myuid'])
				);
		if ($result = $mysqli->query($query)) 
		{
			while($row = $result->fetch_object())
			{
				$valid = false;		
			}
		}
$mysqli->close();

header('Content-type: application/json');
echo json_encode($valid);



