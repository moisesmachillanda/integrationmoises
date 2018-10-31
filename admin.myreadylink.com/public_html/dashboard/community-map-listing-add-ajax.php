<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

if(isset($_POST['listingid']) && is_numeric($_POST['listingid']) && isset($_POST['communityId']) && is_numeric($_POST['communityId']))
{
	//get existing category from other communities
	$query = sprintf("select * from tblcommunitylistingmap where listingId = %d",
				$mysqli->real_escape_string($_POST['listingid'])
				);
	$categories = array();
	$mysqli->query($query);

	if ($result = $mysqli->query($query))
	{
		while($row = $result->fetch_object())
		{
			if(!in_array($row->listingCategoryId,$categories))
			{
				array_push($categories, $row->listingCategoryId);
			}
		}
	}
	
	if(count($categories) > 0)
	{
		foreach($categories as &$cat)
		{
			$query = sprintf("insert into tblcommunitylistingmap (listingId,communityId,listingLevelId,listingCategoryId) values(%d,%d,%d,%d)",
			$mysqli->real_escape_string($_POST['listingid']),
			$mysqli->real_escape_string($_POST['communityId']),
			$mysqli->real_escape_string(5), //normal Listing
			$mysqli->real_escape_string($cat)
			);
			
			$mysqli->query($query);
			
			$loggedData = array(
						'FUNCTION' => 'addListingMapping',
					'listingid' => $_POST['listingid'],
					'communityid' => $_POST['communityId'],
					'listingLevelId' => 5,
					'listingCategoryId' => $cat
			);
			
			adminLog('insert',$loggedData);
		}
	}
	else
	{
		$query = sprintf("insert into tblcommunitylistingmap (listingId,communityId,listingLevelId) values(%d,%d,%d)",
		$mysqli->real_escape_string($_POST['listingid']),
		$mysqli->real_escape_string($_POST['communityId']),
		$mysqli->real_escape_string(5) //normal Listing
		);
			
		$mysqli->query($query);
			
		$loggedData = array(
								'FUNCTION' => 'addListingMapping',
							'listingid' => $_POST['listingid'],
							'communityid' => $_POST['communityId'],
							'listingLevelId' => 5
		);
			
		adminLog('insert',$loggedData);
		
	}
	
	echo json_encode(array("result"=>true));
	exit;
}

echo json_encode(array("result"=>false));

