<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$community = array();
$community = get_CommunityInfo(array('id'=>$_COOKIE['viewCommunity']));

$headerArgs = array();


if(isset($_SESSION['isSAdmin']) && $_SESSION['isSAdmin']==true)
{
	$query = sprintf("SELECT tl.id, tl.name, group_concat(lc.name) as Categories
					FROM tbllisting tl 
					inner JOIN tblcommunitylistingmap listMap on listMap.listingId = tl.id 
							 inner join tbllistingcategory lc on lc.id = listMap.listingCategoryId
						
						where tl.id not in (
							SELECT distinct l.id  
							FROM tbllisting l
                			inner join tblcommunitylistingmap commlistMap on commlistMap.listingId = l.id
							WHERE
							commlistMap.communityId = %d
							AND l.deleted = 0
							)
							AND tl.active =1
							AND tl.deleted = 0
							GROUP by tl.id
							Order by tl.name
							",
						$mysqli->real_escape_string($community->id)
			);
}
else
{
	$query = sprintf("SELECT tl.id, tl.name, group_concat(lc.name) as Categories
						FROM tbllisting tl 
						inner JOIN tblcommunitylistingmap listMap on listMap.listingId = tl.id 
								 inner join tbllistingcategory lc on lc.id = listMap.listingCategoryId
							
							where tl.id not in (
								SELECT distinct l.id  
								FROM tbllisting l
	                			inner join tblcommunitylistingmap commlistMap on commlistMap.listingId = l.id
								WHERE
								commlistMap.communityId = %d
								AND l.deleted = 0
								)
								AND tl.active =1
								AND tl.deleted = 0
								AND tl.ownerUID = %d
								GROUP by tl.id
								Order by tl.name
								",
	$mysqli->real_escape_string($community->id),
	$mysqli->real_escape_string($_SESSION['uid'])
	);
}
			
$headerArgs['extraJS'] = <<<HEADJS

if($("#tablesorter1").exists() && $("#pager1").exists() && $("#tablefilter1").exists())
	{
		
		$("#tablesorter1").tablesorter()
		.tablesorterPager({
				container: $("#pager1"), 
				positionFixed: false
				}).tablesorterFilter({ filterContainer: $("#tablefilter1"),
	                filterClearContainer: $("#tablefilterclear1"),
	                filterColumns: [0, 1, 2, 3],
	                filterCaseSensitive: false}).trigger("appendCache");

	}
	else if($("#tablesorter1").exists() && $("#tablefilter1").exists())
	{
		$("#tablesorter1").tablesorter()
		.tablesorterFilter({ filterContainer: $("#tablefilter1"),
	                filterClearContainer: $("#tablefilterclear1"),
	                filterColumns: [0, 1, 2, 3],
	                filterCaseSensitive: false}).trigger("appendCache");
	}
	else if($("#tablesorter1").exists() && $("#pager1").exists())
	{
		$("#tablesorter1").tablesorter()
		.tablesorterPager({
				container: $("#pager1"), 
				positionFixed: false
				}).trigger("appendCache");
	
	}
	else if($("#tablesorter1").exists())
	{
		$("#tablesorter1").tablesorter();
	}
$('.addSelectedListings').click(function(){

	$('.mapListing').each(function(index) {
		if($(this).is(':checked'))
		{
			var listingId = $(this).val();
			$.ajax({url: 'community-map-listing-add-ajax.php', 
			   type: 'POST',
			   async: false,
				data: {listingid : listingId, communityId: $.cookie('viewCommunity')},
			   success:	function(data){
						if(data.result)
						{
	    					
						}
						else
				    	{
				    		alert('An error occured saving this data');
				    	}
					},
				dataType:'json'
			});
		}
	});
	
	window.parent.location.reload();
});

HEADJS;

if ($result = $mysqli->query($query))
{

	$output = '<table style="width:740px;"><tr><td align="right">Search: <input id="tablefilter1" type="text" /><a href="javascript:void(0);" id="tablefilterclear1"><img src="http://' . STATIC_URL . '/images/admin/Delete.png" width="18" height="18" alt="" border="0"></a></td></tr></table><br /><table style="width:740px;" id="tablesorter1" class="my-community-listings tablesorter">'. "\n" .'<thead><tr><th class="not-sortable">&nbsp;</th><th>Number</th><th>Categories</th></thead>' . "\n" . '<tbody>';
	while($row = $result->fetch_object())
	{
		$output .= '<tr><td><input class="mapListing" type="checkbox" value="' . $row->id . '" /></td><td>' . $row->name  . '</td><td>' . $row->Categories .  '</td></tr>';
		
	}
	
	$output .= '</tbody></table>' . "\n";
	
	if($result->num_rows > 25) $output .= Pager(1, 25, array(25,50,100), 'mapNumberModal');
	$output .= '<div style="width:740px;text-align:right;padding-top:10px;">';
	$output .= '<a href="javascript:void(0)" class="buttonStyle addSelectedListings" >Add Selected Listings</a>';
	$output .= '</div>';
}
echo adminModalHeader($headerArgs);
echo $output;	