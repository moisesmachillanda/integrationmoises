<?php
require_once "../../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../../includes/functions.php";

requireLogin();


$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$nodeSelect = explode('-',$_POST['id']);
$query ='';
$hasChildren = 'false';
$nodename = '';
$output = '[';
$checkstate = 0;
$uid = $_GET['uid'];
switch($nodeSelect[0])
{
	case 'country':
		$query = sprintf("SELECT
		s.name as name,
		s.abbr as abbr,
		s.id as id,
		urm.userId as userid,
		1 as childCount,
		(select count(*) from tbluserrightsmap
		where userId = %d
		AND (countyId in (Select id from tblcounty where stateid = s.id)
		or communityId in (Select id from tblcommunity comm where comm.countyId = countyId and comm.deleted=0)
		)) as hasChildSelected
		FROM tblstate s
		left outer join tbluserrightsmap urm on urm.stateId = s.id and (urm.userId = %d or urm.userId is null)
		where s.countryId = %d
		order by s.name",
		$mysqli->real_escape_string($uid),
		$mysqli->real_escape_string($uid),
		$mysqli->real_escape_string($nodeSelect[1])
		);
		$nodename = 'state';		
		break;
		
	case 'state':
		$query = sprintf("Select distinct c.name as name, c.id as id, urm.userId as userid,
				(Select count(*) as mycount FROM tblcommunity where countyId = c.id and deleted=0 and id != 1) as childCount,
				(select count(*) from tbluserrightsmap 
					where userId = %d 
					AND (communityId in (Select id from tblcommunity comm where comm.countyId = c.Id and comm.deleted=0)
					)) as hasChildSelected
				from tblcounty c 
				left outer join tbluserrightsmap urm on urm.countyId = c.id and (urm.userId = %d or urm.userId is null)
				where c.stateId = %d
				order by c.name",
				$mysqli->real_escape_string($uid),
				$mysqli->real_escape_string($uid),
				$mysqli->real_escape_string($nodeSelect[1])
				
		);
	
		$nodename = 'county';
		break;
	case 'county':
		$query = sprintf("Select c.name as name, c.id as id, urm.userId as userid, 0 as childCount, 0 as hasChildSelected from tblcommunity c 
							left outer join tbluserrightsmap urm on urm.communityId = c.id AND (urm.userId = %d or urm.userId is null)
							where c.countyId = %d
							AND c.parentId = 1
							AND c.deleted = 0
							AND c.Id != 1
							order by c.name",
				$mysqli->real_escape_string($uid),
				$mysqli->real_escape_string($nodeSelect[1])
		);
		//echo $query;
		$nodename = 'community';
		break;
	default:
}


if ($result = $mysqli->query($query)) 
{
	while($row = $result->fetch_object())
	{
		if($row->childCount > 0) $hasChildren = 'true';
		else $hasChildren = 'false';
		
		$checkstate = 0;
		
		if($row->userid == $uid)
		{
			$checkstate = 1;
		}
		elseif($row->hasChildSelected > 0) 
		{
			
			
			$checkstate = 2;
			//see if any children have selections
			/*
			if($nodeSelect[0] == 'country')
			{
				
				$countyquery = sprintf("select c.name, c.id, urm.userId as userId from tblCounty c
				left outer join tbluserrightsmap urm on urm.countyId = c.id
				where c.stateId = %d",
				$mysqli->real_escape_string($row->id)
				);
				if ($countyresult = $mysqli->query($countyquery)) 
				{
					while($countyrow = $countyresult->fetch_object())
					{
						if($countyrow->userId == $uid)
						{
							$checkstate = 2;
							break;				
						}
						else 
						{
							$communityquery = sprintf("select c.name, c.id, urm.userId as userId from tblcommunity c
							left outer join tbluserrightsmap urm on urm.communityId = c.id
							where c.countyid = %d
							AND c.deleted = 0
							and c.parentId = 1",
							$mysqli->real_escape_string($countyrow->id)
							);

							if($communityresult = $mysqli->query($communityquery))
							{
								while($communityrow = $communityresult->fetch_object())
								{
									if($communityrow->userId == $uid)
									{
										$checkstate = 2;
										break;				
									}	
								}
							}
							
							if($checkstate == 2) break;
						}
					}	
				}
			}
			else if($nodeSelect[0] == 'state' && $hasChildren == true)
			{
				$communityquery = sprintf("select c.name, c.id, urm.userId as userId from tblcommunity c
					left outer join tbluserrightsmap urm on urm.communityId = c.id
					where c.countyid = %d
					AND c.deleted = 0
					and c.parentId = 1",
					$mysqli->real_escape_string($row->id)
					);

				if($communityresult = $mysqli->query($communityquery))
				{
					while($communityrow = $communityresult->fetch_object())
					{
						if($communityrow->userId == $uid)
						{
							$checkstate = 2;
							break;				
						}	
					}
				}
		
			}
			*/
		}
				
		$output .= '{"id":"' . $nodename . '-' . $row->id . '","text":"' . $row->name . '","value":"' . $nodename . '-' . $row->id . '","showcheck":true,"complete":false,"isexpand":false,"checkstate":' . $checkstate . ',"hasChildren":' . $hasChildren . '},';
	}
	$output = substr($output, 0, -1) . ']';
}
else 
{
	$output = "[]";
}

echo $output;