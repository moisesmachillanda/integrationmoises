<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

echo '<h3 class="my-community-site">Pages</h3><div class="clearfloat"></div>';

$query = "SELECT keyName from tblmetadata
				WHERE keyName like '%_TITLE' 
				AND keyName not like '%_META_TITLE'";
				
$output = '';
$keyRoot = array();
if ($result = $mysqli->query($query)) 
{
	$output = '<table class="my-pages tablesorter">'. "\n" .'<thead><tr><th>Page</th><th>&nbsp;</th></thead>' . "\n" . '<tbody>';
	while($row = $result->fetch_object())
	{
		$keyRoot = explode('_',$row->keyName);
		$output .= '<tr><td><a href="#editPages" class="editPages" rel="' . htmlentities($keyRoot[0]) . '">' . htmlentities(ucfirst(strtolower($keyRoot[0]))) . '</a></td><td><a href="#editPage" class="editPages" rel="' . htmlentities($keyRoot[0]) . '"><img src="http://' . STATIC_URL . '/images/admin/edit.png" class="buttonEdit buttonEdit" width="20" height="20" border="0" alt="Edit" /></a></td></tr>' . "\n" ;
		
	}
}

$output .= '</table>';


echo $output;