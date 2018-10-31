<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
{
	foreach($_POST as $key => $value)
	{

		if(strpos($key,$_POST['pageKey']) === 0)
		{
			$query = sprintf("UPDATE tblmetadata set value='%s' where keyName = '%s'", 
				$mysqli->real_escape_string($value),
				$mysqli->real_escape_string($key)
			);
			$mysqli->query($query);
			
			$loggedData['pageKey'] = $key;
			$loggedData['value'] = $value;
			adminLog('update',$loggedData);
		}
	}
	

}
$mysqli->close();
echo json_encode(array("result"=>true));