<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$headerArgs = array(); 
$STATIC_URL = STATIC_URL;

$commId = 0; 
if(isset($_GET['commId']) && is_numeric($_GET['commId'])) $commId = $_GET['commId']; 

$buttonText = "Add Community";
$name = $areaName = $URLslug = $description = $metaTitle = $metaKeywords = $metaDescription = $isFeaturedChx = $childComm = $altClass= '';				
$sortOrder = $active = $countyId = $stateId = $isFeatured = $parentId = $lat = $lon = $ownerUID = 0;
if(isset($_GET['parentId'])) $parentId = $_GET['parentId']; 


if($commId > 0)
{
	$buttonText = "Save Community";
	
	
		$query = sprintf("SELECT c.*, CAST(c.active AS unsigned integer) as active, CAST(c.isFeatured AS unsigned integer) isFeatured, s.id as stateId 
							FROM tblcommunity c 
							inner join tblcounty cty on cty.id = c.countyId
							inner join tblstate s on s.id = cty.stateId  
							WHERE
							c.id=%d 
							AND deleted = 0
							limit 1",
				$mysqli->real_escape_string($commId)
				);
				
	
	if ($result = $mysqli->query($query)) 
	{
		while($row = $result->fetch_object())
		{
			$name = htmlentities($row->name);
			$areaName = $row->areaName;
			$description = $row->description;
			$countyId = $row->countyId;
			$stateId = $row->stateId;
			$lat = $row->latitude;
			$lon = $row->longitude;
			$URLslug = $row->url;
			if(!is_null($row->sortOrder)) $sortOrder = $row->sortOrder;
			$parentId = $row->parentId;
			if($row->active==true) $active = 'checked="yes"';
			if($row->isFeatured == true) $isFeaturedChx = 'checked="yes"';
			$metaTitle = htmlentities($row->metaTitle);
			$metaKeywords = htmlentities($row->metaKeywords);
			$metaDescription = htmlentities($row->metaDescription);
			$ownerUID = $row->ownerUID;
		}
		
		$query = sprintf("SELECT c.*
									FROM tblcommunity c 
									WHERE
									c.parentId=%d 
									AND deleted = 0",
									$mysqli->real_escape_string($commId)
						);
		if ($result = $mysqli->query($query))
		{
			while($row = $result->fetch_object())
			{
				$childComm .= '<tr ' . $altClass . '><td>' . htmlentities($row->name) . '</td><td><a rel="' . $row->id  . '" href="#deleteSubComm" class="deleteSubComm deleteButtonSmall"><img src="http://' . STATIC_URL . '/images/admin/deleteButton.gif" width="18" height="18" border="0"/></a></td></tr>';
				
				if($altClass=='') $altClass ='class="alt"';
				else $altClass='';
			}
			
		}
									
	}
}
else
{
	$active = 'checked="yes"';
}

$childComm .= '<tr ' . $altClass . '><td><a href="#addSubComm" class="addSubComm" rel="' . $commId . '">Add surounding community</a></td><td>&nbsp;</td></tr>';

//for new users to draw the county select
if($stateId == 0) $stateId=1;
if($parentId == 0) $parentId=1;
/* TODO: draw lists based on rights map
$query = sprintf("select *
					  from tbluserrightsmap urm 
					  where (countryId is not null or stateId is not null or countyId is not null)
					  AND userId = %d
					  Order by countyId, stateId, countryId
					  ",
$mysqli->real_escape_string($_SESSION['uid'])
);
if ($result = $mysqli->query($query))
{
	while($row = $result->fetch_object())
	{
		if($row->countryId == 1)
		{
		*/
			$stateSelect = '<select name="state" id="state">' . get_state_list($stateId) . '</select>';
			$countySelect = '<select name="county" id="county">' . get_county_list($stateId, $countyId) . '</select>';
			/*
			break;
		}		
	}
}
*/

$selectedUId = '';

if($_SESSION['isSAdmin'] == true)
{
	$owner = "";
	
	$query = sprintf("SELECT * FROM tbluser 
								WHERE
								deleted = 0
								AND active =1
								order by firstName, lastName");
	if ($result = $mysqli->query($query))
	{
		while($row = $result->fetch_object())
		{
			if($ownerUID == $row->id) $selectedUId = 'selected="selected"';
			else $selectedUId = '';
			$owner .= '<option value="' . $row->id . '" ' . $selectedUId . ' >' . $row->firstName . ' ' . $row->lastName  . '</option>';
		}
	}
	
	$owner = '<div class="row">
	<div class="colfull">
	<label for="description">Owner:</label><br />
	<select id="ownerUId" name="ownerUId">' . $owner . '</select>
				</div>
			</div>';
	 
}
else
{
	$owner = '<input type="hidden" id="ownerUId" name="ownerUId" value="' . $_SESSION["uid"] . '" />';
}

$mysqli->close();
$headerArgs['JS_Include']= '<script type="text/javascript" src="http://' . STATIC_URL . '/ckeditor/ckeditor.js"></script>
							<script type="text/javascript" src="http://' . STATIC_URL . '/ckeditor/adapters/jquery.js"></script>
							<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
							<script type="text/javascript" src="http://' . STATIC_URL . '/js/gmapsPointer.js"></script>
							';
$customConfig = 'http://' . STATIC_URL . '/ckeditor/min-config.js';
$staticUrl = STATIC_URL;

if($lat == 0 && $lon == 0) $loadGmap = "$('.areafieldset').hide();";
else $loadGmap = $loadGmap ='loadGMap();';

if($commId > 0) $surCommsClass = "addSub";
else $surCommsClass = "addSubNewComm";


$headerArgs['extraJS'] = <<<HEADJS
	var MapObj = {
    id: "aboutMap",
    element: $("#aboutMap"),
    mapViewPort: null,
    mapMarkers: [],
    mapInfoBubble: [],
    mapEvents: []
    };


$loadGmap

var insertid = 1;
var staticUrl = "$staticUrl"; 
$(".ckeditor1").ckeditor( 
	function () { },
    {
    	height: '90px',
    	customConfig: '$customConfig'
    }
);

$( "#slider" ).slider({
	value:$sortOrder,
	min: 0,
	max: 100,
	step: 1,
	slide: function( event, ui ) {
		$( "#sortOrder" ).val(ui.value);
	}
});


$('.deleteSubComm').live('click',function(){
	var deleteId = 0;
	var deletedObj = $(this);
	if($(this).attr('rel'))
	{
		deleteId = $(this).attr('rel');
	}
	
	if(deleteId > 0)
	{
		$.ajax({url: 'community-deleteSub-ajax.php', 
			   type: 'POST',
			   async: false,
				data: {deleteid: deleteId},
					   success:	function(data){
						if(data.result)
						{
							deletedObj.parents('tr').remove();
						}
						else
				    	{
				    		alert('An error occured saving this data');
				    	}
					},
				dataType:'json'
			});
	}
	else
	{
		$(this).parents('tr').remove();
	}
});


$(".addSub").live('click',function(){
	var newName = $(this).prev('input').val();
	$.ajax({url: 'community-addSub-ajax.php', 
		   type: 'POST',
		   async: false,
			data: {
					name: newName,
					parentId: $('#communityId').val()
					},
		   success:	function(data){
					if(data.result)
					{
						$('.suroundingComms tr:last').prev('tr').remove();
						var altclass='';
						if($('.suroundingComms tr').length % 2 == 0) altclass='alt'; 
						//if(!$('.suroundingComms tr:last').prev('tr').hasClass('alt')) altclass='alt';
						
						$('.suroundingComms tr:last').before('<tr class="' + altclass + '"><td class="suroundingCommTxt">' + newName + '</td><td><a href="#deleteSubComm" class="deleteSubComm deleteButtonSmall" rel="' + data.id + '"><img src="http://' + staticUrl + '/images/admin/deleteButton.gif" width="18" height="18" border="0"/></a></td></tr>');
					}
					else
			    	{
			    		alert('An error occured saving this data');
			    	}
				},
			dataType:'json'
		});

});

$(".addSubNewComm").live('click',function(){
	var newName = $(this).prev('input').val();
	$('.suroundingComms tr:last').prev('tr').remove();
	var altclass='';
	if($('.suroundingComms tr').length % 2 == 0) altclass='alt'; 
	$('.suroundingComms tr:last').before('<tr class="' + altclass + '"><td class="suroundingCommTxt">' + newName + '</td><td><a href="#deleteSubComm" class="deleteSubComm deleteButtonSmall"><img src="http://' + staticUrl + '/images/admin/deleteButton.gif" width="18" height="18" border="0"/></a></td></tr>');
});

$(".addSubComm").live('click',function(){
	$('.suroundingComms tr:last').before('<tr><td><input id="newSub-' + insertid + '" type="text" />&nbsp;<a href="#addSub" class="$surCommsClass">Add</a></td><td>&nbsp;</td></tr>')
	insertid++;
});

$("#cancel").click(function(){
	 window.parent.jQuery('#dialog').dialog('close');
});

$('#save').click(function(){
	if($('#community-edit').valid())
	{
		var addCommunities = '';
		
		$('.suroundingCommTxt').each(function(index) {
			addCommunities += $(this).text() + ';';		
		});
		
		$("#hdnAddCommunities").val(addCommunities);
		
		$.ajax({url: 'community-save-ajax.php', 
		   type: 'POST',
		   async: false,
			data: $("#community-edit").serialize(),
		   success:	function(data){
					if(data.result)
					{
    					window.parent.location.reload();
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

$('#community-edit').validate({
	errorPlacement: function(error, element) {
                        
     },
     messages: {
        name: {
        	required: "Name is required",
        	maxlength: jQuery.format("First Name can not exceed {0} characters in length.")
        },
        URLslug: {
        	required: "Community URL is required",
        	maxlength: jQuery.format("Community Url can not exceed {0} characters in length."),
        	remote: "Community URL must be a-z 0-9 or a dash and can't be used by another community"
        }
     },
     rules:{
        name : {
            required:true,
            maxlength:255
        },
        URLslug: {
        	required: true,
        	maxlength:255,
        	remote:{
        		url: "validation/community-validation-url.php",
        		data: {
        			commid: function(){
						return $("#communityId").val();
					}
        		}
        	}
        }
     },
    invalidHandler: function (e, validator) {
        var errorMessage = '';

        for (var i = 0; i < validator.errorList.length; i++)
        {
            errorMessage += ('<li>' + validator.errorList[i].message + '</li>');
        }

        var errors = validator.numberOfInvalids();
        if (errors) 
        {
            $(".errSummary").html('<ul>' + errorMessage + '</ul>');
            $(".errSummary").show();
        } 
    },
    onkeyup: false,
    submitHandler: function (){
    	
    }
	});

	
	$("#state").change(function(){
		$.ajax({url: 'countyload-ajax.php', 
		   type: 'POST',
		   data: {stateid: $(this).val()},
		   success:	function(data){
					$('#county').empty();
					$.each(data, function(i, item) {
					    $('#county').append(
					    	$('<option></option>').val(item.id).html(item.name)
					    );
					});
				},
			dataType:'json'
		});

	});



    function loadGMap() {
        var latLngBounds = new google.maps.LatLngBounds();

        MapObj.mapViewPort = new google.maps.Map(document.getElementById(MapObj.id), {
            zoom: 13,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            streetViewControl: false,
            overviewMapControl: false,
            mapTypeControl: false
        });

        var mapPoints = new Array(new Array("$lat","$lon"));

        for (var i = 0; i < mapPoints.length; i++)
        {
            var latLng = new google.maps.LatLng(mapPoints[i][0], mapPoints[i][1]);
            var marker = new google.maps.Marker({
                                map: MapObj.mapViewPort,
                                position: latLng,
                                title: mapPoints[i][2]
                            });

            MapObj.mapMarkers.push(marker);
            latLngBounds.extend(latLng);
            
            google.maps.event.addListener(marker, 'click', (function(marker, i) {
                return function() {

                     var myOptions = {
			         content: mapPoints[i][3]
			        ,disableAutoPan: false
			        ,maxWidth: 0
			        ,pixelOffset: new google.maps.Size(-75, 0)
			        ,zIndex: null
			        ,boxStyle: { 
			          background: "#165C93 no-repeat"
                      ,padding:"5px"
                      ,border:"1px solid #165C93"
                      ,color:"#ffffff"
			          ,opacity: 0.90
			          ,width: "150px"
			         }
			        ,closeBoxMargin: "1px 1px 1px 1px"
			        ,closeBoxURL: "/Content/Images/Dtd2/ButtonsCommon/close.gif"
			        ,infoBoxClearance: new google.maps.Size(1, 1)
			        ,isHidden: false
			        ,pane: "floatPane"
			        ,enableEventPropagation: false
		            };

                    var ib = new InfoBox(myOptions);
                    ib.open(MapObj.mapViewPort, this);

                }
              })(marker, i));   
        }

        MapObj.mapViewPort.setCenter(latLngBounds.getCenter()); 
        
        if(mapPoints.length > 1){
            MapObj.mapViewPort.fitBounds(latLngBounds);
        }
    }


HEADJS;

echo adminModalHeader($headerArgs);




?>
<form id="community-edit" class="community-edit" name="community-edit" action="community-edit.php">
	<div class="errSummary errorSevere modalError" style="display:none;"></div>
	<input type="hidden" name="communityId" id="communityId" value="<?php echo $commId?>" />
	<input type="hidden" name="parentid" id="parentid" value="<?php echo $parentId?>" />
	<input type="hidden" name="sortOrder" id="sortOrder" value="<?php echo $sortOrder?>" />
	<input type="hidden" name="submitted" id="submitted" value="1" />
	<input type="hidden" name="hdnAddCommunities" id="hdnAddCommunities" value="" />
 <div class="floatleft">
	<div class="fieldset">
		<h3>Community Info</h3>
		
		<div class="row">
			<div class="col1">
				<label for="name">Name:</label><br />
				<input name="name" id="name" type="text" value="<?php echo $name?>" />
			</div>
			<div class="col2">
				<label for="URLslug">Community Url:</label><br />
				<input name="URLslug" id="URLslug" type="text" value="<?php echo $URLslug?>" />
			</div>
		</div>
		<div class="clearfloat"></div>
		<!-- 
		<div class="row">
			<div class="colfull" style="height:200px">
				<label for="areaName">Area Description:</label><br />
				<textarea name="areaName" id="areaName" class="ckeditor1"><?php echo $areaName?></textarea>
			</div>
		</div>
		<div class="clearfloat"></div>
		-->
		<div class="row">
			<div class="colfull">
				<label for="description">Description:</label><br />
				<textarea name="description" id="description" class="ckeditor1"><?php echo $description?></textarea>
			</div>
		</div>
		<div class="row" style="margin-top:20px;">
			<div class="col1">
				<label for="state">State:</label><br />
				<?php echo $stateSelect?>
			</div>
			<div class="col2">
				<label for="county">County:</label><br />
				<?php echo $countySelect?>
				
			</div>
		</div>
		
		<div class="row">
			<div class="col1">
				<label for="lat">Latitude:</label><br />
				<input name="lat" id="lat" type="text" value="<?php echo $lat?>" />
			</div>
			<div class="col2">
				<label for="lon">Longitude:</label><br />
				<input name="lon" id="lon" type="text" value="<?php echo $lon?>" />
			</div>
		</div>
		<div class="clearfloat"></div>
		<?php echo $owner ?>
		<div class="clearfloat"></div>
		<div class="row" style="margin-top:15px;display:none;">
			<div class="colfull">
				<label for="sortOrder">Sort Weight:</label><br />
				<div id="slider"></div>
			</div>
		</div>
		<div class="clearfloat"></div>
		<div class="row" style="margin-top:15px;">
			<div class="colfull">
				<input type="checkbox" id="isFeatured" name="isFeatured" <?php echo $isFeaturedChx;?> /> <label for="isFeatured">This community is featured</label>
			</div>
		</div>
		<div class="clearfloat"></div>
		
		<div class="row" style="margin-top:15px;">
			<div class="colfull">
				<input type="checkbox" id="active" name="active" <?php echo $active;?> /> <label for="active">This community is active</label>
			</div>
		</div>
		<div class="clearfloat"></div>
	</div>
</div>
	<div class="fieldset floatright">
		<h3>SEO Info</h3>
		<div class="row">
			<div class="colfull">
				<label for="metaTitle">Page Title:</label><br />
				<input name="metaTitle" id="metaTitle" type="text" style="height:20px;width:320px;" value="<?php echo $metaTitle?>" />
			</div>
		</div>
		<div class="row">
			<div class="colfull">
				<label for="metaDescription">Meta Description:</label><br />
				<textArea style="width:320px;" name="metaDescription" id="metaDescription" ><?php echo $metaDescription?></textArea>
			</div>
		</div>
		<div class="row">
			<div class="colfull">
				<label for="metaKeywords">Meta Keywords:</label><br />
				<textArea style="width:320px;" name="metaKeywords" id="metaKeywords" ><?php echo $metaKeywords?></textArea>
			</div>
		</div>
	</div>
	
	<div class="areafieldset fieldset floatright" style="margin-top:10px;">
		<h3>Area</h3>
		<div id="aboutMap" style="height:160px;width:322px;"></div>
	</div>
	
	<div class="fieldset floatright" style="margin-top:10px;">
		<h3>Surounding Communities</h3>
		<table class="suroundingComms" style="width:322px;">
			<?php echo $childComm; ?>
		</table>
	</div>
</div>
<div class="clearboth"></div>

<div class="clearfloat"></div>
<div class="modalButtonWrapper">
	<a href="#Submit" id="save" class="submit buttonStyle"><?php echo $buttonText;?></a>
	<a href="#Cancel" id="cancel" class="buttonStyle cancel" >Cancel</a>
</div>
</form>
<?php 
echo adminModalFooter(array());
?>