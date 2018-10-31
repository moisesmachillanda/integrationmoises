<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);


$community = array();
if (isset($_COOKIE['viewCommunity']))
    $community = get_CommunityInfo(array('id'=>$_COOKIE['viewCommunity']));

$communityId = 0;
if(isset($_COOKIE['viewCommunity'])) {
    $communityId = $_COOKIE['viewCommunity'];
}

$listingId = 0;
if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $listingId =$_GET['id'];
}


$query = sprintf("select l.*, s.name as stateName, s.abbr as stateAbbr, s.id as stateId
    from tbllisting l
    left join tblstate s on s.id = l.stateId
    where l.id = %d
    limit 1",
    $mysqli->real_escape_string($listingId)
);
$currentPage = "New Listing";
$buttonHref = "#";
$buttonId = "saveListingWithCommunityMapping";
$buttonText = "Create New Listing";


if ($result = $mysqli->query($query)) {
    while($row = $result->fetch_object()) {
        $currentPage = $row->name;

        $buttonHref = "javascript:void(0)";
        $buttonId = "saveCatMapping";
        $buttonText = "Save Category Mapping";
    }
}

$listingMapStart = $listingMapEnd = 0;

$query = sprintf("select * from tbllistinglevel order by sortOrder");

$listingLevelDDL = '<option value="" >None</option>\n';
if ($result = $mysqli->query($query)) {
    while($row = $result->fetch_object()) {
        $listingLevelDDL .= '<option value="' . $row->id . '" >' . $row->name . '</option>\n';
    }
}

$query = sprintf('
    select distinct c.id, c.name as name, c.active, map.listingLevelId, listinglevel.name as listingLevelName, county.name as countyName, state.abbr as stateAbbr, country.name as countryName
    from tblcommunity c
    left join tblcounty county on county.id = c.countyId
    left join tblstate state on state.id = county.stateId
    left join tblcountry country on country.id = state.countryId
    inner join tbluserrightsmap rightsMap  on (rightsMap.communityId = c.id OR rightsMap.countyId = county.id OR rightsMap.stateId = state.Id OR rightsMap.countryId = country.Id)
    left outer join tblcommunitylistingmap map on map.communityId = c.id and map.listingId = %2$d
    left outer join tbllistinglevel listinglevel on listinglevel.id = map.listingLevelId
    where c.active = 1
        and c.deleted = 0
        and c.parentId = 1
        and c.id != 1
        and rightsMap.userId = %1$d
    order by c.name
    ',
    $mysqli->real_escape_string($_SESSION['uid']),
    $mysqli->real_escape_string($listingId)
);
$selected ='';
$communityDDL = '';
if ($result = $mysqli->query($query)) {
    while($row = $result->fetch_object()) {

        if (isset($_COOKIE['viewCommunity']) && $_COOKIE['viewCommunity'] == $row->id) {
            $selected ='selected="selected"';
        }
        else
            $selected = '';

        // If an indicator is needed:
        // $communityDDL .= '<option value="' . $row->id . '"  ' . $selected . ' >' . (isset($row->listingLevelName) ? '&#9679;' : '&#9675;') . ' ' . $row->name . '</option>';

        $communityDDL .= '<option value="' . $row->id . '"  ' . $selected . ' >' . $row->name . '</option>';
    }
}



?>
<script type="text/javascript">

    $(document).ready(function () {
        var $listingId = null;
        $listingId = <?php echo $listingId; ?>;

        updateCategories();

        //$('#communityDDL').live('change', function(){
        $("#communityDDL").change(function() {
            updateCategories();
        });
    });

    function updateCategories() {
        $('#save > .ui-button-text').text('Save ' + $("#communityDDL option:selected").text());
        $.ajax({url: 'listing-categories-get-ajax.php',
            type: 'POST',
            async: false,
            cache: false,
            data: {
                communityId: $('#communityDDL').val(),
                listingId:<?php echo $listingId; ?>
            },
            success: function(data) {
                $('#categories').accordion("destroy");
                $('#categories').empty();
                var count = 0;
                var listingLevelId = 0,
                    listingStart = 0,
                    listingEnd = 0;

                // Set the listing level
                listingLevelId = data.listinglevel.listinglevelid;
                if (data.listinglevel.start > 0)
                    listingStart = moment(data.listinglevel.start*1000).format('MM/DD/YYYY');
                if (data.listinglevel.end > 0)
                    listingEnd = moment(data.listinglevel.end*1000).format('MM/DD/YYYY');

                $('#listingLevelDDL').val(listingLevelId);
                $("#listingMapStart").val(listingStart != 0 ? listingStart : '');
                $("#listingMapEnd").val(listingEnd != 0 ? listingEnd : '');

                $.each(data.categories, function(parentCat, subCats) {
                    var subCatTxt = '';
                    $.each(subCats, function(key, value) {
                        var active = '';
                        if (value.active == true) {
                            active = 'checked="checked"';
                        }

                        var keyParts = key.split('-');
                        subCatTxt += '<input type="checkbox" id="cbx-' + keyParts[1] +  '" name="cbx-' + keyParts[1] + '" value="' + keyParts[1] + '" ' + active +  ' />' + value.name + '<br />';
                    });

                    var parentKeyParts = parentCat.split('-');
                    //$('#categories').append('<div class="fieldset" style="float:left;"><h3>' + parentCat + '</h3>' + subCatTxt + '</div>');
                    $('#categories').append('<h3><a href="#">' + parentKeyParts[1] + '</a></h3><div>' + subCatTxt + '</div>');
                    //if(count > 2) $('#categories').append('<div class="clearfloat"></div>');

                    count++;
                    });

                    $("#categories").accordion({
                        autoHeight: false,
                        navigation: true,
                        collapsible: true,
                          active: false,
                    });
                    //$('#categories').append('<div class="clearfloat"></div>');

                },
            dataType:'json'
        });
    }

    $('#saveCatMapping').click(function() {
        var listingLevel = 0;
        var r = true;

        if ($("#listingLevelDDL").val()) {
            listingLevelDDL = $("#listingLevelDDL").val();

            if ($("input:checked").length < 1) {
                toastr["warning"]("At least one category must be selected along with the listing level");
                //alert("At least one category must be selected along with the listing level.");
                return true;
            }
        }
        else {
            r = confirm("Setting Listing Level to None will remove this listing from this community. Would you like to continue saving?");
        }

        r = $("#listing-cats").valid();

        if (r == true) {
            $.ajax({url: 'listing-cats-save-ajax.php',
                type: 'POST',
                async: false,
                data: $('#listing-cats').serialize(),
                success:	function(data) {
                    if (data.result) {
                        if ($('#listingid').val() == 0) {
                            window.location.href = window.location.href + '?id=' + data.id;
                        }
                        else {
                            toastr["success"]("Category mapping saved");
                            // $(".errSummary").removeClass('errorSevere').addClass('errorInfo').html('<ul><li>Category mapping saved</li></ul>').show();
                        }
                    }
                    else {
                        toastr["error"]("An error occurred while saving this data.")
                        // alert('An error occured saving this data');
                    }
                },
                dataType:'json'
            });
        }
    });

    $("#listingMapStart, #listingMapEnd").datepicker({
        changeMonth: true,
        changeYear: true,
        numberOfMonths: 1
    });

    $('#listing-cats').validate({
        errorPlacement: function (error, element) {

        },
        highlight: function(element) {
            $(element).parent('div').addClass('error');
        },
        unhighlight: function(element) {
            $(element).parent('div').removeClass('error');
        },
        messages: {
            listingMapStart: {
                required: "Listing contract start date is required",
                date: "Please enter a valid date for the listing contract start date."
            },
            listingMapEnd: {
                required: "Listing contract end date is required",
                date: "Please enter a valid date for the listing contract end date."
            }
        },
        //showErrors: function(errorMap, errorList) {
        //    if (errorList.length == 0) {
        //        //$("#listing-cats .errSummary").html('').hide();
        //    }
        //},
        invalidHandler: function (e, validator) {
            var errorMessage = '';

            for (var i = 0; i < validator.errorList.length; i++) {
                errorMessage += ('<li>' + validator.errorList[i].message + '</li>');
            }

            var errors = validator.numberOfInvalids();
            if (errors > 0) {
                toastr.options.onclick =  function() { $(window).scrollTo($("#listing-cats .errSummary"), 300); };
                toastr["error"]("There were errors while validating your category selection.<br><br>Details:<br><ul>" + errorMessage + "</ul>");
                //toastr["error"]("Details:\n" + errorMessage);
                window.setTimeout(function() { toastr.options.onclick = null; }, 500);
                //$("#listing-cats .errSummary").html('<ul>' + errorMessage + '</ul>');
                //$("#listing-cats .errSummary").show();
            }
            else {
                //$("#listing-cats .errSummary").html('').hide();
            }
        },
        onkeyup: function () { return true }
    });


    $("#saveListingWithCommunityMapping").click(function(e) {
        e.preventDefault();

        // Check if listing info is valid.
        $('#tabslisting').tabs('select', 0);
        if ($("#listing-info").valid() == true) {
            //console.info('listing info valid; continuing with save...')
            $('#tabslisting').tabs('select', 1);
            // Check if community/category mapping is valid
            if ($("#listing-cats").valid() == true) {
                //console.info('listing categories are valid; continuing with save...')
                $.ajax({
                    url: 'listing-info-save-ajax.php?c=' + new Date() * 1,
                    type: 'POST',
                    async: false,
                    data: $("#listing-info").serialize() + "&" + $("#listing-cats").serialize(),
                    success: function (data) {
                        if (data.result) {
                            var listId = 0;
                            if ($('#listingid').val()) {
                                listId = $('#listingid').val();
                            }


                            if (listId == 0) {
                                window.location.href = '/dashboard/listing.php?added=true&id=' + data.listingId + "#ui-tabs-3";
                            }
                            else {
                                toastr.options.onclick = null;
                                toastr["success"]("Listing saved");
                                // $(".errSummary").removeClass('errorSevere').addClass('errorInfo').html('<ul><li>Listing saved</li></ul>').show();
                            }
                        }
                        else {
                            toastr["error"]("An error occurred while saving this data");
                            //alert('An error occurred saving this data');
                        }
                    },
                    dataType: 'json'
                });

            }
        }

        // #listing-info

        // #listing-cats


        // $("#listing-info").serialize() + "&" + $("#listing-cats").serialize()

        return false;
    })


</script>
<div class="breadCrumb">
    <a class="clearMainDashTab" href="/dashboard/">Dashboard</a> &gt;
    <a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt; <?php
    if (!empty($community)) { ?>
    <a href="/dashboard/" class="communityClick" data-communityId="<?php echo $communityId?>"><?php echo $community->name ?></a> &gt; <?php
    } ?>
    <?php echo  $currentPage ?>
</div>
<br />
<div class="clearfloat"></div>

<div class="floatleft listing-categories">
    <form id="listing-cats" name="listing-cats">
    <div class="errSummary errorSevere modalError" style="display:none;"></div>
    <?php if ($listingId > 0) { ?>
    <input type="hidden" name="listingId" id="listingId" value="<?php echo $listingId; ?>" />
    <strong style="font-weight: 600 !important">Community: </strong><select name="communityDDL" id="communityDDL" style="width: 295px;"> <?php echo $communityDDL; ?></select>
    <?php }
          else { ?>
        <input type="hidden" name="communityDDL" id="communityDDL" value="<?php echo $communityId; ?>" />
        <strong style="font-weight: 600 !important">Community:</strong> <span style="display: inline-block; margin-right: 3em;"><?php echo $community->name; ?></span>
    <?php } ?>
    <div style="display: inline-block;">
        <label><strong style="font-weight: 600 !important">Listing Level: </strong></label>
        <select name="listingLevelDDL" id="listingLevelDDL"> <?php echo $listingLevelDDL ?></select>
        <input name="listingMapStart" id="listingMapStart" class="required date" value="<?php echo $listingMapStart != 0 ? date('m/d/Y', $listingMapStart) : ''; ?>" placeholder="Start" style="width: 87px; font-size: .8em; line-height: 1.65em;" />
        <input name="listingMapEnd" id="listingMapEnd" class="required date" value="<?php echo $listingMapEnd != 0 ? date('m/d/Y', $listingMapEnd) : ''; ?>" placeholder="End" style="width: 87px; font-size: .8em; line-height: 1.65em;" />
    </div>
    <p>&nbsp;</p>
    <div class="fieldset" style="width:875px;">
        <h3>Listing Categories</h3>
        <div id="categories"></div>
        <div class="clearfloat"></div>
    </div>
    </form>
</div>
<div class="clearfloat"></div>
<div class="modalButtonWrapper" style="width:100%;clear:both;text-align:right;padding-top:20px;">
    <a href="<?php echo $buttonHref; ?>" id="<?php echo $buttonId; ?>" class="submit buttonStyle"><?php echo $buttonText; ?></a>
    <a href="javascript:void(0)" id="cancel" class="buttonStyle cancel" >Cancel</a>
</div>
<p>&nbsp;</p>
<div class="breadCrumb">
    <a class="clearMainDashTab" href="/dashboard/">Dashboard</a> &gt;
    <a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt; <?php
    if (!empty($community)) { ?>
    <a href="/dashboard/" class="communityClick" data-communityId="<?php echo $communityId?>"><?php echo $community->name ?></a> &gt; <?php
    } ?>
    <?php echo  $currentPage ?>
</div>
<br />
<div class="clearfloat"></div>
