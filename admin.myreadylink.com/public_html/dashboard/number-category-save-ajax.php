<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

switch($_POST['numberLevel'])
{
	case 'country':
		$query = sprintf("INSERT INTO tblnumbercategorymap (numberId, categoryId, countryId) values(%d,%d,%d)",
		$mysqli->real_escape_string($_POST['numberId']),
		$mysqli->real_escape_string($_POST['categoryId']),
		$mysqli->real_escape_string($_POST['countryId'])
		);
		
		$loggedData = array(
				'FUNCTION' => 'numberCategoryAdd',
				'numberId' => $_POST['numberId'],
			    'categoryId' => $_POST['categoryId'],
				'countryId' => $_POST['countryId']
		);
		break;
		
	case 'state':
		$query = sprintf("INSERT INTO tblnumbercategorymap (numberId, categoryId, stateId) values(%d,%d,%d)",
		$mysqli->real_escape_string($_POST['numberId']),
		$mysqli->real_escape_string($_POST['categoryId']),
		$mysqli->real_escape_string($_POST['stateId'])
		);
		
		$loggedData = array(
						'FUNCTION' => 'numberCategoryAdd',
						'numberId' => $_POST['numberId'],
					    'categoryId' => $_POST['categoryId'],
						'stateId' => $_POST['stateId']
		);
		break;
		
	case 'county':
		$query = sprintf("INSERT INTO tblnumbercategorymap (numberId, categoryId, countyId) values(%d,%d,%d)",
		$mysqli->real_escape_string($_POST['numberId']),
		$mysqli->real_escape_string($_POST['categoryId']),
		$mysqli->real_escape_string($_POST['countyId'])
		);
		
		$loggedData = array(
						'FUNCTION' => 'numberCategoryAdd',
						'numberId' => $_POST['numberId'],
					    'categoryId' => $_POST['categoryId'],
						'countyId' => $_POST['countyId']
		);
		break;
	case 'community':
		$query = sprintf("INSERT INTO tblnumbercategorymap (numberId, categoryId, communityId) values(%d,%d,%d)",
		$mysqli->real_escape_string($_POST['numberId']),
		$mysqli->real_escape_string($_POST['categoryId']),
		$mysqli->real_escape_string($_POST['communityId'])
		);
		
		$loggedData = array(
						'FUNCTION' => 'numberCategoryAdd',
						'numberId' => $_POST['numberId'],
					    'categoryId' => $_POST['categoryId'],
						'communityId' => $_POST['communityId']
		);
		
		break;
	default:
		exit;
}

$mysqli->query($query);
$loggedData['numbercategorymapId'] = $mysqli->insert_id;
adminLog('insert',$loggedData);
echo json_encode(array("result"=>true, "numberMapId"=>$mysqli->insert_id));