<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

if(isset($_POST['submitted']))
{	
	$loggedData = array(
		'FUNCTION' => 'userEdit',
		'first' => $_POST['first'],
	    'last' => $_POST['last'],
		'email' => $_POST['email'],
		'phone' => $_POST['phone'],
		'fax' => $_POST['fax'],
		'address1' => $_POST['address'],
		'address2' => $_POST['address2'],
		'city' => $_POST['city'],
		'state' => $_POST['state'],
		'zip' => $_POST['zip'],
		'uid' => $_POST['uid']
	);
	
	$uid = $_POST['uid'];
	//update
	if($_POST['uid'] > 0)
	{
		if($_POST['password'] == 'PASSWORDNOTCHANGED')
		{
			
			$query = sprintf("UPDATE tbluser set 
							firstName='%s', 
							lastName='%s', 
							email='%s', 
							phone='%s',
							fax='%s',
							address1='%s',
							address2='%s',
							city='%s',
							state= %d,
							zip = '%s'
							where
							id=%d",
							$mysqli->real_escape_string($_POST['first']),
							$mysqli->real_escape_string($_POST['last']),
							$mysqli->real_escape_string($_POST['email']),
							$mysqli->real_escape_string($_POST['phone']),
							$mysqli->real_escape_string($_POST['fax']),
							$mysqli->real_escape_string($_POST['address']),
							$mysqli->real_escape_string($_POST['address2']),
							$mysqli->real_escape_string($_POST['city']),
							$mysqli->real_escape_string($_POST['state']),
							$mysqli->real_escape_string($_POST['zip']),
							$mysqli->real_escape_string($_POST['uid'])
				);
			
			
			$loggedData['password']	= 'unchanged';
		}
		else 
		{
			$query = sprintf("UPDATE tbluser set 
							firstName='%s', 
							lastName='%s',
							password ='%s', 
							email='%s', 
							phone='%s',
							fax='%s',
							address1='%s',
							address2='%s',
							city='%s',
							state=%d,
							zip = '%s'
							where
							id=%d",
							$mysqli->real_escape_string($_POST['first']),
							$mysqli->real_escape_string($_POST['last']),
							$mysqli->real_escape_string(hasher($_POST['password'])),
							$mysqli->real_escape_string($_POST['email']),
							$mysqli->real_escape_string($_POST['phone']),
							$mysqli->real_escape_string($_POST['fax']),
							$mysqli->real_escape_string($_POST['address']),
							$mysqli->real_escape_string($_POST['address2']),
							$mysqli->real_escape_string($_POST['city']),
							$mysqli->real_escape_string($_POST['state']),
							$mysqli->real_escape_string($_POST['zip']),
							$mysqli->real_escape_string($_POST['uid'])
				);
			$loggedData['password']	= 'updated';
		}
		
		$mysqli->query($query);
		adminLog('update',$loggedData);
	}
	else //add new
	{
		$query = sprintf("INSERT into tbluser (firstName,lastName,password,email,phone,fax,address1,address2,city,state,zip)
							values('%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s')",
							$mysqli->real_escape_string($_POST['first']),
							$mysqli->real_escape_string($_POST['last']),
							$mysqli->real_escape_string(hasher($_POST['password'])),
							$mysqli->real_escape_string($_POST['email']),
							$mysqli->real_escape_string($_POST['phone']),
							$mysqli->real_escape_string($_POST['fax']),
							$mysqli->real_escape_string($_POST['address']),
							$mysqli->real_escape_string($_POST['address2']),
							$mysqli->real_escape_string($_POST['city']),
							$mysqli->real_escape_string($_POST['state']),
							$mysqli->real_escape_string($_POST['zip']),
							$mysqli->real_escape_string($_POST['uid'])
				);
		$mysqli->query($query);
		$loggedData['uid'] = $uid = $mysqli->insert_id;
		$loggedData['password'] = 'MUNGED';
		adminLog('insert',$loggedData);
	}
	
	//update user rightsmap
	//$uid
	
	if(isset($_POST['nodesRemoved']) && !empty($_POST['nodesRemoved']))
	{
		$removeNodes = explode(';',$_POST['nodesRemoved']);
		
		foreach($removeNodes as $removeMe)
		{
			$removeParts = explode('-',$removeMe);
			$query = '';
			switch($removeParts[0])
			{
				case 'country':
					$query = sprintf("DELETE from tbluserrightsmap where userId=%d and countryId=%d",
									$mysqli->real_escape_string($uid),
									$mysqli->real_escape_string($removeParts[1])
									);
					$loggedData = array('FUNCTION' => 'userRightsMap','uid' => $uid, 'countryId'=>$removeParts[1]);
					break;
				case 'state':
					$query = sprintf("DELETE from tbluserrightsmap where userId=%d and stateId=%d",
									$mysqli->real_escape_string($uid),
									$mysqli->real_escape_string($removeParts[1])
									);
					$loggedData = array('FUNCTION' => 'userRightsMap','uid' => $uid, 'stateId'=>$removeParts[1]);
					break;
				case 'county':
					$query = sprintf("DELETE from tbluserrightsmap where userId=%d and countyId=%d",
									$mysqli->real_escape_string($uid),
									$mysqli->real_escape_string($removeParts[1])
									);
					$loggedData = array('FUNCTION' => 'userRightsMap','uid' => $uid, 'countyId'=>$removeParts[1]);
					break;
				case 'community':
					$query = sprintf("DELETE from tbluserrightsmap where userId=%d and communityId=%d",
									$mysqli->real_escape_string($uid),
									$mysqli->real_escape_string($removeParts[1])
									);
					$loggedData = array('FUNCTION' => 'userRightsMap','uid' => $uid, 'communityId'=>$removeParts[1]);
					break;
			}
			
			if(!empty($query))
			{
				$mysqli->query($query);
				adminLog('delete',$loggedData);
			}
		}
	}
	
	
	
	if(isset($_POST['nodesAdded']) && !empty($_POST['nodesAdded']))
	{
		$addedNodes = explode(';',$_POST['nodesAdded']);
		
		foreach($addedNodes as $addMe)
		{
			$addParts = explode('-',$addMe);
			$query = '';
			$isMappedQuery = '';
			switch($addParts[0])
			{
				case 'country':
					$query = sprintf("INSERT into tbluserrightsmap (userId,countryId) values (%d,%d)",
									$mysqli->real_escape_string($uid),
									$mysqli->real_escape_string($addParts[1])
									);
					$isMappedQuery = sprintf("Select * from tbluserrightsmap where userId = %d and countryId=%d",
									$mysqli->real_escape_string($uid),
									$mysqli->real_escape_string($addParts[1])
									);				
					$loggedData = array('FUNCTION' => 'userRightsMap','uid' => $uid, 'countryId'=>$addParts[1]);
					break;
				case 'state':
					$query = sprintf("INSERT into tbluserrightsmap (userId,stateId) values (%d,%d)",
									$mysqli->real_escape_string($uid),
									$mysqli->real_escape_string($addParts[1])
									);
					$isMappedQuery = sprintf("Select * from tbluserrightsmap where userId = %d and stateId=%d",
									$mysqli->real_escape_string($uid),
									$mysqli->real_escape_string($addParts[1])
									);				
					$loggedData = array('FUNCTION' => 'userRightsMap','uid' => $uid, 'stateId'=>$addParts[1]);
					break;
				case 'county':
					$query = sprintf("INSERT into tbluserrightsmap (userId,countyId) values (%d,%d)",
									$mysqli->real_escape_string($uid),
									$mysqli->real_escape_string($addParts[1])
									);
					$isMappedQuery = sprintf("Select * from tbluserrightsmap where userId = %d and countyId=%d",
									$mysqli->real_escape_string($uid),
									$mysqli->real_escape_string($addParts[1])
									);
					$loggedData = array('FUNCTION' => 'userRightsMap','uid' => $uid, 'countyId'=>$addParts[1]);
					break;
				case 'community':
					$query = sprintf("INSERT into tbluserrightsmap (userId,communityId) values (%d,%d)",
									$mysqli->real_escape_string($uid),
									$mysqli->real_escape_string($addParts[1])
									);
					$isMappedQuery = sprintf("Select * from tbluserrightsmap where userId = %d and communityId=%d",
									$mysqli->real_escape_string($uid),
									$mysqli->real_escape_string($addParts[1])
									);
					$loggedData = array('FUNCTION' => 'userRightsMap','uid' => $uid, 'communityId'=>$addParts[1]);
					break;
			}
			
			//map sure we're not adding them twice to mapping table
			if(!empty($isMappedQuery))
			{
				if(!isAlreadyMapped($isMappedQuery))
				{
					if(!empty($query))
					{
						$mysqli->query($query);
						adminLog('insert',$loggedData);
					}
				}
			}
		}
	}
	
	
	
	echo json_encode(array("result"=>true));
}
$mysqli->close();

function isAlreadyMapped($query)
{
	$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
	$returnVal = false;
	if ($result = $mysqli->query($query)) {

	    if($result->num_rows)
	    {
	    	$returnVal = true;
	    }
	    $result->close();
	}
	$mysqli->close();
	return $returnVal;
}




