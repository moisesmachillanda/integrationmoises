<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$name = $phone = $fax = $address1 = $address2 = $city = $zip = $description = $products = $services = $enabled =
$email = $website = $hours = $metatitle = $metakeywords = $metadescription = $slug = $imagealttext = '';

$stateId = $listingLevel = 0;
$community = array();
$community = get_CommunityInfo(array('id'=>$_COOKIE['viewCommunity']));
$numberId = 0;
$currentPage = $buttonText = "Add Number";
$catOutput = '';


if (isset($_SESSION['isSAdmin']) && $_SESSION['isSAdmin']) {
    //super admin
    $hideMeta = '';
}
else {
    //normal User
    $hideMeta = 'style="display:none;"';
}

//categoryList
$query = sprintf("Select name,id from tblnumbercategory where active =1 and deleted = 0 order by sortOrder");
$catList='';
if ($result = $mysqli->query($query)) {
    while ($row = $result->fetch_object()) {
        $catList .= '<option value="' .$row->id . '">' . $row->name . '</option>';
    }
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $numberId =$_GET['id'];
    $buttonText = "Save Number";
    $query = sprintf("
        select n.*, s.name as stateName, s.abbr as stateAbbr, s.id as stateId
        from tblnumber n
        left join tblstate s on s.id = n.stateId
        where n.id = %d
          and n.deleted = 0
        limit 1",
        $mysqli->real_escape_string($numberId)
    );

    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_object()) {
            $currentPage = $name = htmlentities(trim($row->name));
            $phone = htmlentities(trim($row->phoneNumber));
            $fax = htmlentities(trim($row->faxNumber));
            $email = htmlentities(trim($row->email));
            $website = htmlentities(trim($row->website));
            $address1 = htmlentities(trim($row->address1));
            $address2 = htmlentities(trim($row->address2));
            $city = htmlentities(trim($row->city));
            $stateId = $row->stateId;
            $zip = htmlentities(trim($row->zip));
            $description = $row->description;
            $hours = $row->hoursOfService;
            if ($row->active != true)
                $enabled = 'checked="checked"';
            $metatitle = trim(strip_tags($row->metaTitle));
            $metakeywords = trim(strip_tags($row->metaKeywords));
            $metadescription = trim(strip_tags($row->metaDescription));
            $slug = $row->SEOName;
            $imagealttext = htmlentities(trim($row->SEOImageAltText));
        }
    }

    // Number Categories:
    $isCountryAdmin = $isStateAdmin = $isCountyAdmin = $isCommunityAdmin = false;
    $query = sprintf("
        select * from tbluserrightsmap
        where userId = %d
          and (
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
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_object()) {
            if ($row->countryId == $community->countryId)
                $isCountryAdmin = true;
            if ($row->stateId == $community->stateId)
                $isCountryAdmin = true;
            if ($row->countyId == $community->countyId)
                $isCountryAdmin = true;
            if ($row->communityId == $community->id)
                $isCountryAdmin = true;
        }
    }

    $newNumCat = '';
    if ($isCountryAdmin == true)
        $newNumCat = '<input type="radio" id="rdoCountry" name="radio" value="country" /><label for="rdoCountry">Countrywide</label>';
    if ($isStateAdmin == true || $isCountryAdmin)
        $newNumCat .= '<input type="radio" id="rdoState" name="radio" value="state" /><label for="rdoState">Statewide</label>';
    if ($isCountyAdmin == true || $isStateAdmin == true || $isCountryAdmin)
        $newNumCat .= '<input type="radio" id="rdoCounty" name="radio" value="county" /><label for="rdoCounty">Countywide</label>';
    if ($isCommunityAdmin == true || $isCountyAdmin == true || $isStateAdmin == true || $isCountryAdmin)
        $newNumCat .= '<input type="radio" id="rdoCommunity" name="radio" checked="checked" value="community" /><label for="rdoCommunity">Communitywide</label>';

    $query = sprintf("
        select cnm.*,comm.name community, c.name county, s.abbr state, coun.abbr country, nc.name category
        from tblnumbercategorymap cnm
        left join tblcommunity comm on comm.id = cnm.communityId
        left join tblcounty c on c.id = cnm.countyId
        left join tblstate s on s.id = cnm.stateId
        left join tblcountry coun on coun.id = cnm.countryId
        left join tblnumbercategory nc on nc.id = cnm.categoryId
        where cnm.numberId = %d
          and (comm.deleted = 0 or communityId is null)
          and (nc.deleted = 0 or categoryId is null)
        order by comm.name, c.name, s.abbr, coun.abbr
        ",
        $mysqli->real_escape_string($numberId)
    );

    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_object()) {
            $deleteBlurb = '';
            if ($isCountryAdmin == true && isset($row->country))
                $deleteBlurb = '<a href="javascript:void(0)" class="deleteMapping deleteButtonSmall" rel="' . $row->id . '" ><img border="0" width="14" height="15" alt="Delete" src="http://' . STATIC_URL . '/images/admin/btnDelete.png" class="deleteButton"></a>';
            if (($isStateAdmin == true || $isCountryAdmin == true) && isset($row->state))
                $deleteBlurb = '<a href="javascript:void(0)" class="deleteMapping deleteButtonSmall" rel="' . $row->id . '" ><img  border="0" width="14" height="15" alt="Delete" src="http://' . STATIC_URL . '/images/admin/btnDelete.png" class="deleteButton"></a>';
            if (($isCountyAdmin == true || $isStateAdmin == true || $isCountryAdmin) && isset($row->county))
                $deleteBlurb = '<a href="javascript:void(0)" class="deleteMapping deleteButtonSmall" rel="' . $row->id . '" ><img  border="0" width="14" height="15" alt="Delete" src="http://' . STATIC_URL . '/images/admin/btnDelete.png" class="deleteButton"></a>';
            if (($isCommunityAdmin == true || $isCountyAdmin == true || $isStateAdmin == true || $isCountryAdmin) && isset($row->community))
                $deleteBlurb = '<a href="javascript:void(0)" class="deleteMapping deleteButtonSmall" rel="' . $row->id . '" ><img border="0" width="14" height="15" alt="Delete" src="http://' . STATIC_URL . '/images/admin/btnDelete.png" class="deleteButton"></a>';

            $catOutput .= "<tr><td>" . $row->category . "</td><td>" . $row->country . "</td><td>" . $row->state . "</td><td>" . $row->county . "</td><td>" . $row->community . '</td><td>' . $deleteBlurb . '</td></tr>';
        }
        $catOutput = '<table id="tablesorter1" style="margin-top:20px;" class="users tablesorter">'. "\n" .'<thead><tr><th>Category</th><th>Country</th><th>State</th><th>County</th><th>Community</th><th class="not-sortable">&nbsp;</th></thead>' . "\n" . '<tbody>' . $catOutput . '</tbody></table>';
    }
}
?>
<script type="text/javascript">
var CommunityId = <?php echo $community->id; ?>;

$(document).ready(function () {
    var STATIC_URL = '<?php echo STATIC_URL; ?>';
    $( "#newNumCat" ).buttonset();

    $(".ckeditor1").ckeditor(
            function () { },
            {
                height: '200px',
                customConfig: 'http://<?php echo STATIC_URL; ?>/ckeditor/min-config.js'
            }
        );
    $('#cancel').click(function (){
        window.location.href = 'http://' + location.hostname + '/dashboard/#Numbers';

    });

    $('.deleteMapping').live('click', function() {
        var r=confirm("Are you sure you want to delete/remove this number mapping permanently?");

        if (r==true)
        {
            var deleteId = 0;
            var deletedObj = $(this);
            if ($(this).attr('rel'))
            {
                deleteId = $(this).attr('rel');
            }

            $.ajax({url: 'number-category-delete-ajax.php',
                   type: 'POST',
                   async: false,
                    data: {
                        numberListingMapId:deleteId
                        },
                   success:	function(data){

                            if (data.result)
                            {
                                //window.location.reload();
                                deletedObj.parents('tr').remove();
                                toastr["success"]('Category mapping removed');

                            }
                            else
                            {
                                toastr["error"]('An error occured saving this data');
                            }
                        },
                    dataType:'json'
                });
        }
    });

    $('#saveMapping').click(function() {
        var saveLevel = $("input[name='radio']:checked").val();
        var Country = State = County = Community = '';

        if (saveLevel == 'country') {
            Country = '<?php echo $community->countryAbbr; ?>';
            State = County = Community = '';
        }

        if (saveLevel == 'state') {
            Country = '';
            State = '<?php echo $community->stateAbbr; ?>';
            County = Community = '';
        }

        if (saveLevel == 'county') {
            Country = State = '';
            County = '<?php echo $community->countyName; ?>';
            Community = '';
        }

        if (saveLevel == 'community') {
            Country = State = County = '';
            Community = '<?php echo $community->name; ?>';
        }

        $.ajax({
            url: 'number-category-save-ajax.php',
            type: 'POST',
            async: false,
            data: {
                numberId: <?php echo $numberId; ?>,
                numberLevel: $("input[name='radio']:checked").val(),
                categoryId: $("#category").val(),
                countryId: <?php echo $community->countryId; ?>,
                stateId: <?php echo $community->stateId; ?>,
                countyId: <?php echo $community->countyId; ?>,
                communityId: <?php echo $community->id; ?>
            },
            success: function(data){
                if (data.result) {
                    $('#tablesorter1 tr:last').before('<tr><td>' + $("#category option:selected").text() + '</td><td>' + Country  + '</td><td>' + State + '</td><td>' + County + "</td><td>" + Community + '</td><td>' + '<a href="javascript:void(0)" class="deleteMapping deleteButtonSmall" rel="' + data.numberMapId + '" ><img  border="0" width="14" height="15" alt="Delete" src="http://' + STATIC_URL + '/images/admin/btnDelete.png" class="deleteButton"></a></td></tr>');
                    toastr["success"]('Category mapping added');
                }
                else {
                    toastr["error"]('An error occured saving this data');
                }
            },
            dataType:'json'
        });
    });

    $('.url-suggestion').live('click', function() {
        $('#slug').val(this.text);
    });

    // Focus on form elements from toast notification.
    $('[data-focus="true"]').live('click', function(e) {
        e.preventDefault();
        $($(this).attr('href')).focus();
    });

    $('#name').blur(function() {
        //create url slug here
        if ($.trim($('#slug').val()) == '') {
            var url = $('#name').val().toLowerCase();
            url = url.replace(/[^a-z0-9\-]/g,'-');
            url = url.replace(/--+/g,'-');
            $('#slug').val(url);
            $('#slug').valid();
        }
    });

    $('#save').click(function() {
        if ($('#number-info').valid()) {
            $.ajax({
                url: 'number-info-save-ajax.php',
                type: 'POST',
                async: false,
                data: $("#number-info").serialize(),
                success:	function(data) {
                    if (data.result) {
                        var listId = 0;
                        if ($('#numberid').val()) {
                            listId = $('#numberid').val();
                        }

                        if (listId == 0) {
                            window.location.href = window.location.pathname + '?added=true&id=' + data.numberId;
                        }
                        else {
                            //$(".errSummary").removeClass('errorSevere').addClass('errorInfo').html('<ul><li>Number saved</li></ul>').show();
                            toastr["success"]('Number saved');
                        }
                    }
                    else {
                        toastr["error"]('An error occured saving this data');
                    }
                },
                dataType:'json'
            });
        }
    });

    $('#number-info').validate({
        errorPlacement: function(error, element) {
        },
        highlight: function(element) {
            $(element).parent('div').addClass('error');
        },
        unhighlight: function(element) {
            $(element).parent('div').removeClass('error');
        },
         messages: {
            name: {
                required: "<a href='#name' data-focus='true'>Name</a> is required",
                maxlength: jQuery.format("Name can not exceed {0} characters in length.")
            },
            phone: {
                required: "<a href='#phone' data-focus='true'>Phone</a> is required",
                maxlength: jQuery.format("Phone can not exceed {0} characters in length.")
            },
            fax: {
                maxlength: jQuery.format("Fax can not exceed {0} characters in length.")
            },
            address1: {
                maxlength: jQuery.format("Address1 field can not exceed {0} characters in length.")
            },
            city: {
                maxlength: jQuery.format("City field can not exceed {0} characters in length.")
            },
            //state: {
            //    required: "State is required"
            //},
            zip: {
                required: "<a href='#zip' data-focus='true'>Zip code</a> is required"
            },
            email: {
                email: "Must be a valid Email Address",
                maxlength: jQuery.format("Email field can not exceed {0} characters in length.")
            },
            slug:{
                required: "<a href='#slug' data-focus='true'>URL</a> is required.",
                remote: "Invalid URL, another listing is already using this url."
           }

         },
         rules:{
            name :{
                required:true,
                maxlength:255
            },
            phone:{
                required:true,
                maxlength:255
            },
            fax:{
                maxlength:255
            },
            address1:{
                maxlength:255
            },
            address2:{
                maxlength:255
            },
            city:{
                maxlength:255
            },
            //state:{
            //    required:true
            //},
            zip:{

            },
            email: {
                email: true,
                maxlength:255
            },
            slug:{
                SeoUrl: true,
                required: true,
                maxlength:255,
                remote: {
                    url: "validation/numlist-validation-url.php",
                    data: {
                        type: 'number',
                        objId: $("#numberid").val(),
                        activeCommunityId: CommunityId
                    }
                }
            }
        },
        invalidHandler: function (e, validator) {
            var errorMessage = '';

            for (var i = 0; i < validator.errorList.length; i++) {
                errorMessage += ('<li>' + validator.errorList[i].message + '</li>');

                if (validator.errorList[i].message == "Invalid URL, another listing is already using this url.") {
                    //lookup url suggestions here
                    $.ajax({
                        url: 'validation/number-url-suggestions-ajax.php',
                        type: 'POST',
                        async: false,
                        data: {
                            name: $("#name").val(),
                            id: $("#numberid").val(),
                            activeCommunityId: CommunityId
                        },
                        success: function(data){
                            if (data.result) {
                                $('#slug-suggestions').empty();
                                $('#slug-suggestions').append('<span style="font-weight:bold;">Available replacement URLS</span><br />');
                                $.each(data.suggestions, function(index, value) {
                                    $('#slug-suggestions').append('<a class="url-suggestion">' + value + '</a><br />');
                                });
                            }
                            else {
                                toastr["error"]('An error occured getting url suggestions.');
                            }
                        },
                        dataType:'json'
                    });
                }
            }

            var errors = validator.numberOfInvalids();
            if (errors) {
                toastr["error"]("There were errors while validating your input.<br><br>Details:<br><ul>" + errorMessage + "</ul>");
                //$(".errSummary").html('<ul>' + errorMessage + '</ul>');
                //$(".errSummary").show();
            }
        },
        onkeyup: function() { return true }

        });
});
</script>
<div class="breadCrumb">
    <a class="clearMainDashTab" href="/dashboard/">Dashboard</a> &gt;
    <a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt;
    <a href="/dashboard/"><?php echo $community->name; ?></a> &gt;
    <?php echo $currentPage; ?>
</div>
<div class="listingWrapper">
    <form id="number-info" name="number-info" action="number-info.php">
        <div class="errSummary errorSevere modalError" style="display: none;"></div>
        <input type="hidden" name="numberid" id="numberid" value="<?php echo $numberId; ?>" />
        <input type="hidden" name="communityId" id="communityId" value="<?php echo $community->id; ?>" />
        <input type="hidden" name="submitted" id="submitted" value="1" />
        <div class="floatleft">
            <div class="fieldset" style="width: 480px;">
                <h3>Number Add/Edit</h3>
                <div class="row">
                    <div class="colfull">
                        <label for="name">Number Name:</label><br />
                        <input name="name" id="name" type="text" value="<?php echo $name; ?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="col1">
                        <label for="phone">Phone:</label><br />
                        <input name="phone" id="phone" type="text" value="<?php echo $phone; ?>" />
                    </div>
                    <div class="col2">
                        <label for="fax">Fax:</label><br />
                        <input name="fax" id="fax" type="text" value="<?php echo $fax; ?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull">
                        <label for="address1">Address 1:</label><br />
                        <input name="address1" id="address1" type="text" value="<?php echo $address1; ?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull">
                        <label for="address2">Address 2:</label><br />
                        <input name="address2" id="address2" type="text" value="<?php echo $address2; ?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="col13">
                        <label for="city">City:</label><br />
                        <input name="city" id="city" type="text" value="<?php echo $city; ?>" />
                    </div>
                    <div class="col23">
                        <label for="state">State:</label><br />
                        <select name="state" id="state">
                            <option value=""></option>
                            <?php echo get_state_list($stateId); ?>
                        </select>
                    </div>
                    <div class="col33">
                        <label for="zip">Zip:</label><br />
                        <input name="zip" id="zip" type="text" value="<?php echo $zip; ?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull">
                        <label for="email">Email:</label><br />
                        <input name="email" id="email" type="text" value="<?php echo $email; ?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull">
                        <label for="website">Website:</label><br />
                        <input name="website" id="website" type="text" value="<?php echo $website; ?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull" style="min-height: 300px; width: 485px;">
                        <label for="description">Description:</label><br />
                        <textarea name="description" id="description" class="ckeditor1"><?php echo $description; ?></textarea>
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull" style="min-height: 300px; width: 485px;">
                        <label for="hours">Hours of Service:</label><br />
                        <textarea name="hours" id="hours" class="ckeditor1"><?php echo $hours; ?></textarea>
                    </div>
                </div>
                <div class="clearfloat"></div>
                <?php if ($numberId == 0) { ?>
                <div class="row">
                    <div class="colfull">
                        <label for="hours">Number Category:</label><br />
                        <select name="categoryId" id="categoryId"><?php echo $catList; ?></select>
                    </div>
                </div>
                <div class="clearfloat"></div>
                <?php
                } ?>
                <br />
                <div class="row">
                    <div class="colfull">
                        <input name="enabled" id="enabled" type="checkbox" <?php echo $enabled; ?>>
                        <label for="enabled">Disabled</label>
                    </div>
                </div>
                <div class="clearfloat"></div>
            </div><!-- /.fieldset -->
        </div><!-- /.floatleft -->

        <div class="fieldset floatright" style="width: 350px;">
            <h3>SEO</h3>
            <div class="row">
                <div class="colfull">
                    <label for="slug">URL:</label><br />
                    <input name="slug" id="slug" type="text" value="<?php echo $slug; ?>" />
                    <div id="slug-suggestions">
                    </div>
                </div>
            </div>
            <div class="clearfloat"></div>

            <div class="row" <?php echo $hideMeta; ?>>
                <div class="colfull">
                    <label for="metatitle">Title Tag:</label><br />
                    <input name="metatitle" id="metatitle" type="text" value="<?php echo $metatitle; ?>" />
                </div>
            </div>
            <div class="clearfloat"></div>
            <div class="row" <?php echo $hideMeta; ?>>
                <div class="colfull">
                    <label for="metakeywords">Meta Keywords:</label><br />
                    <textarea name="metakeywords" id="metakeywords"><?php echo $metakeywords; ?></textarea>
                </div>
            </div>
            <div class="clearfloat"></div>
            <div class="row" <?php echo $hideMeta; ?>>
                <div class="colfull">
                    <label for="metadescription">Meta Description:</label><br />
                    <textarea name="metadescription" id="metadescription"><?php echo $metadescription; ?></textarea>
                </div>
            </div>
            <div class="clearfloat"></div>
            <div class="row" <?php echo $hideMeta; ?>>
                <div class="colfull">
                    <label for="metadescription">Image Alt Text:</label><br />
                    <input type="text" name="imagealttext" id="imagealttext" value="<?php echo $imagealttext; ?>" />
                </div>
            </div>
            <div class="clearfloat"></div>
            <br />
            <br />
        </div><!-- /.fieldset.floatright -->
        <div class="clearfloat"></div>
    </form>

    <?php if ($catOutput) {
        ?>
        <h3 style="margin-top:15px;">Number Categories</h3>
        <?php echo $catOutput; ?>
        <table width="660" cellspacing="5" cellpadding="5" style="margin-top:15px; border:1px solid #ABABAB;">
            <tr>
                <td style="padding:10px;">Add New Mapping:</td>
                <td style="padding:10px;"><div id="newNumCat"><?php echo $newNumCat; ?></div></td>
            </tr>
            <tr>
                <td style="padding:10px;"><select id="category"><?php echo $catList; ?></select></td>
                <td style="padding:10px;"><a href="javascript:void(0)" id="saveMapping" class="submit buttonStyle">Add</a></td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="font-size:10px;padding-left:12px;font-style:italic;">Note: A number will cascade down to all lower level entites.</span><br/>
                    <span style="font-size:10px;padding-left:12px;font-style:italic;">i.e. a number mapped to a state will appear in all communities in that state. (Unless specifically unmapped from the community).</span>
                </td>
            </tr>
        </table>
    <?php
    } // if ($catOutput)
    ?>
</div><!-- /.listingWrapper -->
<div class="clearboth"></div>
<div class="modalButtonWrapper" style="width:100%; clear:both; text-align:right; padding-top:20px;">
    <a href="javascript:void(0)" id="save" class="submit buttonStyle"><?php echo $buttonText; ?></a>
    <a href="javascript:void(0)" id="cancel" class="buttonStyle cancel" >Cancel</a>
</div>
<div class="breadCrumb">
    <a class="clearMainDashTab" href="/dashboard/">Dashboard</a> &gt;
    <a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt;
    <a href="/dashboard/"><?php echo $community->name; ?></a> &gt;
    <?php echo $currentPage; ?>
</div>
