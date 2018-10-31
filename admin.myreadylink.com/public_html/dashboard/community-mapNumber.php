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

$query = sprintf("SELECT num.id, num.name, group_concat(numCat.name) as Categories from tblnumber num 
					
				LEFT JOIN tblnumbercategorymap numMap on numMap.numberid = num.id
				LEFT JOIN tblnumbercategory numCat on numCat.id = numMap.categoryId
				where 
				num.id not in
				(
					SELECT distinct n.id
					FROM tblnumber n
					LEFT JOIN tblnumbercategorymap numMap on numMap.numberid = n.id
					LEFT JOIN tblcounty county on county.id = numMap.countyId
					LEFT JOIN tblstate s on s.id = numMap.stateId
					LEFT JOIN tblcountry country on country.id = numMap.countryId
					LEFT JOIN tblnumbercategory numCat on numCat.id = numMap.categoryId
					WHERE 
					(numMap.communityId = %d or numMap.countyId = %d or numMap.stateId = %d or numMap.CountryId=%d)
					AND n.id not in (SELECT numberId FROM tblcommunitynumberexcludes where communityId = %d)
					AND n.deleted = 0
					AND n.active = 1
					AND numCat.deleted = 0
				)
				AND num.deleted = 0
				AND num.active = 1
				AND numCat.deleted = 0
				Group by num.id
				Order by num.name
				",
$community->id,
$community->countyId,
$community->stateId,
$community->countryId,
$community->id
);


$headerArgs['extraJS'] = <<<HEADJS

	if($("#tablesorter7").exists() && $("#pager7").exists() && $("#tablefilter7").exists())
	{
		
		$("#tablesorter7").tablesorter()
		.tablesorterPager({
				container: $("#pager7"), 
				positionFixed: false
				}).tablesorterFilter({ filterContainer: $("#tablefilter7"),
	                filterClearContainer: $("#tablefilterclear7"),
	                filterColumns: [0, 1, 2, 3],
	                filterCaseSensitive: false}).trigger("appendCache");
	}
	else if($("#tablesorter7").exists() && $("#tablefilter7").exists())
	{
		$("#tablesorter7").tablesorter()
		.tablesorterFilter({ filterContainer: $("#tablefilter7"),
	                filterClearContainer: $("#tablefilterclear7"),
	                filterColumns: [0, 1, 2],
	                filterCaseSensitive: false}).trigger("appendCache");
	}
	else if($("#tablesorter7").exists() && $("#pager7").exists())
	{
		$("#tablesorter7").tablesorter()
		.tablesorterPager({
				container: $("#pager6"), 
				positionFixed: false
				}).trigger("appendCache");
	
	}
	else if($("#tablesorter7").exists())
	{
		$("#tablesorter7").tablesorter();
	}

//$("#tablesorter7").tablesorter()
//		.tablesorterPager({
//				container: $("#pager1"), 
//				positionFixed: false
//				}).tablesorterFilter({ filterContainer: $("#tablefilter7"),
//	                filterClearContainer: $("#tablefilterclear1"),
//	                filterColumns: [0, 1, 2, 3],
//	                filterCaseSensitive: false}).trigger("appendCache");

$('.addSelectedNumbers').click(function(){

	$('.mapNumber').each(function(index) {
		if($(this).is(':checked'))
		{
			var numberId = $(this).val();
			$.ajax({url: 'community-map-number-add-ajax.php', 
			   type: 'POST',
			   async: false,
				data: {numberid: numberId, communityId: $.cookie('viewCommunity')},
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

	$output = '<table style="width:740px;"><tr><td align="right">Search: <input id="tablefilter7" type="text" /><a href="javascript:void(0);" id="tablefilterclear7"><img src="http://' . STATIC_URL . '/images/admin/Delete.png" width="18" height="18" alt="" border="0"></a></td></tr></table><br /><table style="width:740px;" id="tablesorter7" class="my-community-listings tablesorter">'. "\n" .'<thead><tr><th class="not-sortable">&nbsp;</th><th>Number</th><th>Categories</th></tr></thead>' . "\n" . '<tbody>';
	while($row = $result->fetch_object())
	{

		$output .= '<tr><td><input class="mapNumber" type="checkbox" value="' . $row->id . '" /></td><td>' . $row->name  . '</td><td>' . $row->Categories .  '</td></tr>';
		
	}
	
	$output .= '</tbody></table>' . "\n";
	
	if($result->num_rows > 25) $output .= Pager(7, 25, array(25,50,100), 'mapNumberModal');
	$output .= '<div style="width:740px;text-align:right;padding-top:10px;">';
	$output .= '<a href="javascript:void(0)" class="buttonStyle addSelectedNumbers" >Add Selected Numbers</a>';
	$output .= '</div>';
}
echo adminModalHeader($headerArgs);
echo $output;	
echo adminModalFooter();