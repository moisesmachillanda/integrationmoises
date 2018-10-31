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
if(isset($_REQUEST['listingid']) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'enable')
{
		$query = sprintf("select CAST(active AS unsigned integer) as active 
							from tbllisting 
							where
							id = %d
							limit 1",
				$mysqli->real_escape_string($_REQUEST['listingid'])
				);
		if ($result = $mysqli->query($query)) 
		{
			while($row = $result->fetch_object())
			{
				if($row->active == false) $active = false;
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
		
		$query = sprintf("update tbllisting set active = %d 
							where
							id = %d",
				$mysqli->real_escape_string($setactive),
				$mysqli->real_escape_string($_REQUEST['listingid'])
				);
		$mysqli->query($query);
		$loggedData = array('FUNCTION' => 'listing','listingId' => $_REQUEST['listingid'], 'active'=>$setactive);
		adminLog('update',$loggedData);
		echo json_encode(array("result"=>true));
}

$mysqli->close();