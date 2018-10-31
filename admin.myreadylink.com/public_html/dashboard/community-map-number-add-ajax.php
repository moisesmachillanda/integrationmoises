<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

if(isset($_POST['numberid']) && is_numeric($_POST['numberid']) && isset($_POST['communityId']) && is_numeric($_POST['communityId']))
{
	$community = array();
	$community = get_CommunityInfo(array('id'=>$_COOKIE['viewCommunity']));
	
	$query=sprintf("DELETE from tblcommunitynumberexcludes WHERE numberId = %d AND communityId = %d",
		$mysqli->real_escape_string($_POST['numberid']),
		$mysqli->real_escape_string($_POST['communityId'])
	);
	
	$mysqli->query($query);
	
	if($mysqli->affected_rows > 0)
	{
		$loggedData = array(
							'FUNCTION' => 'addNumberMappingDeleteExcludes',
							'numberid' => $_POST['numberid'],
							'communityid' => $_POST['communityId'],
		);
		adminLog('delete',$loggedData);
	}
	
	//get existing category from other communities
	$query = sprintf("select * from tblnumbercategorymap where numberId = %d",
				$mysqli->real_escape_string($_POST['numberid'])
				);
	$categories = array();
	$mysqli->query($query);
	$inMyCountry = $inMyState = $inMyCounty = $inMyCommunity = false;

	if ($result = $mysqli->query($query))
	{
		while($row = $result->fetch_object())
		{
			if(!in_array($row->categoryId,$categories))
			{
				array_push($categories, $row->categoryId);
			}

			
			if(isset($row->countryId) && $row->countryId == $community->countryId) $inMyCountry = true;
			if(isset($row->stateId) && $row->stateId == $community->stateId) $inMyState = true;
			if(isset($row->countyId) && $row->countyId == $community->countyId) $inMyCounty = true;
			if(isset($row->communityId) && $row->communityId == $community->id) $inMyCommunity = true;
		}
	}

	
	if($inMyCountry == false && $inMyState == false && $inMyCounty == false && $inMyCommunity == false)
	{
		if(count($categories) > 0)
		{
			foreach($categories as &$cat)
			{
				$query = sprintf("insert into tblnumbercategorymap (numberId,communityId,categoryId) values(%d,%d,%d)",
				$mysqli->real_escape_string($_POST['numberid']),
				$mysqli->real_escape_string($_POST['communityId']),
				$mysqli->real_escape_string($cat)
				);

				$mysqli->query($query);
				echo $query;
				$loggedData = array(
										'FUNCTION' => 'addNumberMapping',
									'listingid' => $_POST['numberid'],
									'communityid' => $_POST['communityId'],
									'categoryId' => $cat
				);
					
				adminLog('insert',$loggedData);
				
			}
			
		}
		else
		{
			$query = sprintf("insert into tblnumbercategorymap (numberId,communityId) values(%d,%d)",
			$mysqli->real_escape_string($_POST['numberid']),
			$mysqli->real_escape_string($_POST['communityId'])
			);
				
			$mysqli->query($query);
				
			$loggedData = array(
													'FUNCTION' => 'addNumberMapping',
												'listingid' => $_POST['listingid'],
												'communityid' => $_POST['communityId']
			);
				
			adminLog('insert',$loggedData);
			
		}
		
	}
	
	echo json_encode(array("result"=>true));
	exit;
}

echo json_encode(array("result"=>false));

