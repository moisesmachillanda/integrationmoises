<?php
require_once "../../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../../includes/functions.php";


requireLogin();

if(isset($_POST['listingid']) && is_numeric($_POST['listingid']))
{
	$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
	
	$sql = sprintf('SELECT distinct c.name AS communityName, ll.name as listingLevelName FROM tblcommunitylistingmap clm 
					INNER JOIN tbllistinglevel ll on ll.id = clm.listingLevelId
					INNER JOIN tblcommunity c on c.id = clm.communityId and c.deleted = 0
					WHERE clm.listingId = %d
					ORDER BY c.name',
					$mysqli->real_escape_string($_POST['listingid']));
	
	
	$output='';
	if ($result = $mysqli->query($sql))
	{
		while($row = $result->fetch_object())
		{
			$output .= '<div class="mappingPopUp"><span class="popup ' . str_replace(' listing','', strtolower($row->listingLevelName) ) . '">' . str_replace(' listing', '', strtolower($row->listingLevelName) )  . '</span> <span class="dashPopCommName"> ' . htmlentities($row->communityName) .  ' </span></div>';
			//$output .= htmlentities($row->communityName);
		}

		header('Content-type: application/json');
		if($output)
		{
			echo json_encode(array("result"=>true, "content"=>$output));
		}
		else
		{
			echo json_encode(array("result"=>true, "content"=>"<div>This listing has no community mappings.</div>"));
		}
	}
}
else
{
	header('Content-type: application/json');
	echo json_encode(array("result"=>false));
}

?>