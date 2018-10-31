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
if(isset($_REQUEST['catid']) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'enable' && isset($_REQUEST['type']) && $_REQUEST['type'] =='number')
{
		$query = sprintf("select CAST(active AS unsigned integer) as active 
							from tblnumbercategory 
							where
							id = %d
							AND deleted = 0
							limit 1",
				$mysqli->real_escape_string($_REQUEST['catid'])
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
		
		$query = sprintf("update tblnumbercategory set active = %d 
							where
							id = %d",
				$mysqli->real_escape_string($setactive),
				$mysqli->real_escape_string($_REQUEST['catid'])
				);
		$mysqli->query($query);
		$loggedData = array('FUNCTION' => 'numberCategory','catId' => $_REQUEST['catid'], 'active'=>$setactive);
		adminLog('update',$loggedData);
		echo json_encode(array("result"=>true));
}
elseif(isset($_REQUEST['catid']) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'enable' && isset($_REQUEST['type']) && $_REQUEST['type'] =='listing')
{
		$query = sprintf("select CAST(active AS unsigned integer) as active 
							from tbllistingcategory 
							where
							id = %d
							AND deleted = 0
							limit 1",
				$mysqli->real_escape_string($_REQUEST['catid'])
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
		
		$query = sprintf("update tbllistingcategory set active = %d 
							where
							id = %d",
				$mysqli->real_escape_string($setactive),
				$mysqli->real_escape_string($_REQUEST['catid'])
				);
		$mysqli->query($query);

		$loggedData = array('FUNCTION' => 'listingCategory','catId' => $_REQUEST['catid'], 'active'=>$setactive);
		adminLog('update',$loggedData);
		echo json_encode(array("result"=>true));
}
elseif(isset($_REQUEST['catid']) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete' && isset($_REQUEST['type']) && $_REQUEST['type'] =='listing')
{
	$query = sprintf("update tbllistingcategory set deleted = 1 
							where
							id = %d",
				$mysqli->real_escape_string($_REQUEST['catid'])
				);
		$mysqli->query($query);
		
	$loggedData = array('FUNCTION' => 'listingCategoryDelete','catId' => $_REQUEST['catid']);
		adminLog('update',$loggedData);
		echo json_encode(array("result"=>true));
}
elseif(isset($_REQUEST['catid']) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete' && isset($_REQUEST['type']) && $_REQUEST['type'] =='number')
{
	$query = sprintf("update tblnumbercategory set deleted = 1
								where
								id = %d",
	$mysqli->real_escape_string($_REQUEST['catid'])
	);
	$mysqli->query($query);
	
	$loggedData = array('FUNCTION' => 'listingCategoryDelete','catId' => $_REQUEST['catid']);
	adminLog('update',$loggedData);
	echo json_encode(array("result"=>true));
	
}

$mysqli->close();