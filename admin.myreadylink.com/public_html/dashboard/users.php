<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$query = sprintf("SELECT u.*, CAST(u.active AS unsigned integer) as active FROM tbluser u where deleted = 0");

$output = '';
$active = '';
if ($result = $mysqli->query($query)) 
{
	$output = '<table id="tablesorter1" class="users tablesorter">'. "\n" .'<thead><tr><th>User</th><th>Email</th><th>Phone</th><th class="not-sortable">Status</th><th class="not-sortable">&nbsp;</th></thead>' . "\n" . '<tbody>';
	while($row = $result->fetch_object())
	{
		if($row->active == true)
		{
			$active = '<a href="#active" class="disable-user" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/active.gif" alt=""></a>';
		}
		else
		{
			$active = '<a href="#active" class="enable-user" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/inactive.gif" alt=""></a>';
		}

		$output .= '<tr><td><a href="#editUser" class="editUser" rel="' . $row->id . '">' . htmlentities($row->firstName . ' ' . $row->lastName) . '</a></td><td>' . htmlentities($row->email) .'</td><td>' . htmlentities($row->phone) .'</td><td class="center">' . $active . '</td><td><a href="#editUser" class="editUser buttonEdit" rel="' . $row->id  . '"><img src="http://' . STATIC_URL . '/images/admin/edit.png" class="buttonEdit" width="20" height="20" border="0" alt="Edit" /></a>&nbsp;&nbsp;<a href="#deleteUser" class="deleteUser deleteButtonSmall" rel="' . $row->id  . '"><img src="http://' . STATIC_URL . '/images/admin/btnDelete.png"  width="14" height="15" class="deleteButton" border="0" alt="Delete" /></a></td></tr>' . "\n" ; 
	}
	$mysqli->close();
	echo '<h3 class="my-community-site">Users</h3><div class="clearfloat"></div>';
	$output .= '</tbody>' . "\n";
	echo $output;
//	if($result->num_rows > 10) echo Pager(1 ,5, 10, array(10,20,30,40,50));
	echo '</table>' . "\n";
}



?>
<br />
<a href="#addNewUser" class="editUser buttonStyle" rel="0">Add New User</a>

