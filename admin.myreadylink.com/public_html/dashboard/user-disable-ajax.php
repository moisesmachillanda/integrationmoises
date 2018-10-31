<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

header('Content-type: application/json');
$active = '';
$setactive = 0;
if(isset($_REQUEST['uid']) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'enable')
{
		$query = sprintf("select CAST(active AS unsigned integer) as active 
							from tbluser 
							where
							deleted = 0
							AND id = %d
							limit 1",
				$mysqli->real_escape_string($_REQUEST['uid'])
				);
		if ($result = $mysqli->query($query)) 
		{
			while($row = $result->fetch_object())
			{
				if($row->active == 0) $active = false;
				else $active = true;		
			}
		}
		else 
		{
			echo json_encode(array("result"=>false));
			exit;
		}
		
		if($active == true) $setactive = 0;
		else $setactive = 1;
		
		$query = sprintf("update tbluser set active = %d 
							where
							deleted = 0
							AND id = %d",
				$mysqli->real_escape_string($setactive),
				$mysqli->real_escape_string($_REQUEST['uid'])
				);
		$mysqli->query($query);
		$loggedData = array('FUNCTION' => 'userState','uid' => $_REQUEST['uid'], 'active'=>$setactive);
		adminLog('update',$loggedData);
		echo json_encode(array("result"=>true));
}
elseif(isset($_REQUEST['uid']) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete')
{
	$query = sprintf("update tbluser set deleted = 1 
							where
							id = %d",
				$mysqli->real_escape_string($_REQUEST['uid'])
				);
	$mysqli->query($query);
	$loggedData = array('FUNCTION' => 'userDelete','uid' => $_REQUEST['uid']);
		adminLog('update',$loggedData);
		echo json_encode(array("result"=>true));
}

$mysqli->close();