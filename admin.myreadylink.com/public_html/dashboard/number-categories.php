<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$output= $currentPage = '';
$community = array();
$community = get_CommunityInfo(array('id'=>$_COOKIE['viewCommunity']));

if(isset($_GET['id']) && is_numeric($_GET['id']))
{
	$numberId =$_GET['id'];
	$query = sprintf("select n.*, s.name as stateName, s.abbr as stateAbbr, s.id as stateId
									from tblnumber n
									left join tblstate s on s.id = n.stateId
									where n.id = %d
									limit 1",
	$mysqli->real_escape_string($numberId)
	);

	if ($result = $mysqli->query($query))
	{
		while($row = $result->fetch_object())
		{
			$currentPage = htmlentities(trim($row->name));

		}
	}

	$isCountryAdmin = $isStateAdmin = $isCountyAdmin = $isCommunityAdmin = false;
	$query = sprintf("select * from tbluserrightsmap where userId = %d
						AND
						(
							countryId =%d
							or stateId=%d
							or countyId = %d
							or communityId = %d
						)
						order by communityId, countyId, stateId, countryId",
						$mysqli->real_escape_string($_SESSION['uid']),
					$mysqli->real_escape_string($community->countryId),
					$mysqli->real_escape_string($community->stateId),
					$mysqli->real_escape_string($community->countyId),
					$mysqli->real_escape_string($community->id)
					);
	if ($result = $mysqli->query($query))
	{
		while($row = $result->fetch_object())
		{
			if($row->countryId == $community->countryId ) $isCountryAdmin = true;
			if($row->stateId == $community->stateId ) $isCountryAdmin = true;
			if($row->countyId == $community->countyId ) $isCountryAdmin = true;
			if($row->communityId == $community->id ) $isCountryAdmin = true;
		}
	}

	$newNumCat = '';
	if($isCountryAdmin == true) $newNumCat = '<input type="radio" id="rdoCountry" name="radio" value="country" /><label for="rdoCountry">Countrywide</label>';
	if($isStateAdmin == true || $isCountryAdmin) $newNumCat .= '<input type="radio" id="rdoState" name="radio" value="state" /><label for="rdoState">Statewide</label>';
	if($isCountyAdmin == true || $isStateAdmin == true || $isCountryAdmin) $newNumCat .= '<input type="radio" id="rdoCounty" name="radio" value="county" /><label for="rdoCounty">Countywide</label>';
	if($isCommunityAdmin == true || $isCountyAdmin == true || $isStateAdmin == true || $isCountryAdmin) $newNumCat .= '<input type="radio" id="rdoCommunity" name="radio" checked="checked" value="community" /><label for="rdoCommunity">Communitywide</label>';

	$query = sprintf("Select name,id from tblnumbercategory where active =1 AND deleted = 0 order by sortOrder");
	$catList='';
	if ($result = $mysqli->query($query))
	{
		while($row = $result->fetch_object())
		{
			$catList .= '<option value="' .$row->id . '">' . $row->name . '</option>';
		}
	}



	$query = sprintf("select cnm.*,comm.name community, c.name county, s.abbr state, coun.abbr country, nc.name category
									from tblnumbercategorymap cnm
									left join tblcommunity comm on comm.id = cnm.communityId
									left join tblcounty c on c.id = cnm.countyId
									left join tblstate s on s.id = cnm.stateId
									left join tblcountry coun on coun.id = cnm.countryId
									left join tblnumbercategory nc on nc.id = cnm.categoryId
									where cnm.numberId = %d
									AND nc.deleted = 0
									AND comm.deleted = 0
									order by comm.name, c.name, s.abbr, coun.abbr
									",
									$mysqli->real_escape_string($numberId)
					);

	if ($result = $mysqli->query($query))
	{
		while($row = $result->fetch_object())
		{
			$deleteBlurb = '';
			if($isCountryAdmin == true && isset($row->country)) $deleteBlurb = '<a href="#" class="deleteMapping deleteButtonSmall" rel=' . $row->id . ' ><img border="0" width="14" height="15" alt="Delete" src="http://' . STATIC_URL . '/images/admin/btnDelete.png" class="deleteButton"></a>';
			if(($isStateAdmin == true || $isCountryAdmin == true) && isset($row->state))  $deleteBlurb = '<a href="#" class="deleteMapping deleteButtonSmall" rel=' . $row->id . ' ><img  border="0" width="14" height="15" alt="Delete" src="http://' . STATIC_URL . '/images/admin/btnDelete.png" class="deleteButton"></a>';
			if(($isCountyAdmin == true || $isStateAdmin == true || $isCountryAdmin) && isset($row->county)) $deleteBlurb = '<a href="#" class="deleteMapping deleteButtonSmall" rel=' . $row->id . ' ><img  border="0" width="14" height="15" alt="Delete" src="http://' . STATIC_URL . '/images/admin/btnDelete.png" class="deleteButton"></a>';
			if(($isCommunityAdmin == true || $isCountyAdmin == true || $isStateAdmin == true || $isCountryAdmin) && isset($row->community)) $deleteBlurb = '<a href="#" class="deleteMapping deleteButtonSmall" rel=' . $row->id . ' ><img border="0" width="14" height="15" alt="Delete" src="http://' . STATIC_URL . '/images/admin/btnDelete.png" class="deleteButton"></a>';

			$output .= "<tr><td>" . $row->category . "</td><td>" . $row->country . "</td><td>" . $row->state . "</td><td>" . $row->county . "</td><td>" . $row->community . '</td><td>' . $deleteBlurb . '</td></tr>';
		}
	}

?>

	<script type="text/javascript">
	$(document).ready(function () {

		$( "#newNumCat" ).buttonset();

		$('.deleteMapping').click(function(){
			$.ajax({url: 'number-category-delete-ajax.php',
				   type: 'POST',
				   async: false,
					data: {
						numberListingMapId:$(this).attr('rel')
						},
				   success:	function(data){

							if(data.result)
							{
								window.location.reload();
							}
							else
					    	{
							    toastr["error"]('An error occured saving this data');
					    	}
						},
					dataType:'json'
				});

		});

		$('#save').click(function(){

			$.ajax({url: 'number-category-save-ajax.php',
			   type: 'POST',
			   async: false,
				data: {
					numberId:<?php echo $numberId;?>,
					numberLevel: $("input[name='radio']:checked").val(),
					categoryId: $("#category").val(),
					countryId: <?php echo $community->countryId;?>,
					stateId: <?php echo $community->stateId;?>,
					countyId: <?php echo $community->countyId;?>,
					communityId: <?php echo $community->id;?>
					},
			   success:	function(data){

						if(data.result)
						{
							window.location.reload();
						}
						else
				    	{
						    toastr["error"]('An error occured saving this data');
				    	}
					},
				dataType:'json'
			});

		});


	});
	</script>
	<?php
	echo '<table id="tablesorter1" class="users tablesorter">'. "\n" .'<thead><tr><th>Category</th><th>Country</th><th>State</th><th>County</th><th>Community</th><th class="not-sortable">&nbsp;</th></thead>' . "\n" . '<tbody>' . $output . '</tbody></table>';
}
?>
<p>&nbsp;</p>
<table width="660" cellspacing="5" cellpadding="5" style="border:1px solid #ABABAB;">
	<tr><td style="padding:10px;">Add New Mapping:</td><td style="padding:10px;"><div id="newNumCat"><?php echo $newNumCat;?></div></td></tr>
	<tr><td style="padding:10px;"><select id="category"><?php echo $catList;?></select></td><td style="padding:10px;"><a href="#Submit" id="save" class="submit buttonStyle">Add</a></td></tr>
	<tr><td colspan="2"><span style="font-size:10px;padding-left:12px;font-style:italic;">Note: A number will cascade down to all lower level entites</span></td></tr>
</table>
<div> <div style="width:400px;float:right;" id="newNumCat"></div> </div>

<?php
echo '<br /><div class="breadCrumb"><a href="/dashboard/" class="clearMainDashTab">Dashboard</a> &gt; <a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt; <a href="/dashboard/" >' . $community->name . '</a> &gt; ' . $currentPage . '</div>';
?>