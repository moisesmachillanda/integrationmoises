<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

if(isset($_REQUEST['stateid']) && is_numeric($_REQUEST['stateid'])) {
	
	echo get_county_list_json($_REQUEST['stateid']);

}

