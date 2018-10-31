<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$query = sprintf("INSERT into tblcommunity (name,parentId,active,ownerUID) values ('%s',%d,1,0)",
$mysqli->real_escape_string($_POST['name']),
$mysqli->real_escape_string($_POST['parentId'])
);

if ($result = $mysqli->query($query))
{
	$loggedData = array(
				'FUNCTION' => 'insertCommunity',
				'id' => $mysqli->insert_id,
				'name' => $_POST['name'],
				'parentId' => $_POST['parentId']
	);
	
	adminLog('insert',$loggedData);
	echo json_encode(array("result"=>true,"name"=>$_POST['name'],"parentId"=>$_POST['parentId'],"id"=>$mysqli->insert_id));
}
else 
{
	echo json_encode(array("result"=>false));
}