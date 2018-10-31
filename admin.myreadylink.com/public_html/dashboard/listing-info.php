<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";
require_once "../../../includes/app/bootstrap.php";

use MyReadyLink\Reports\AnalyticsReport;

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$listingStatStart = new DateTime('yesterday - 30 days');
$listingStatEnd =   new DateTime('yesterday');


$name = $phone = $fax = $address1 = $address2 = $city = $zip = $description = $products = $services = $enabled = $contactname =
$contactemail = $website = $website_facebook = $website_twitter = $website_linkedin = $hours = $listingpassword = $listingpasswordconfirm = $slug = $metatitle = $metakeywords = $metadescription =
$imagealttext = $geolatitude = $geolongitude = '';


$stateId = $listingLevel = 0;
$foundCatMap = false;
$community = array();

//echo "\r\n".'<!--'; var_dump($_SESSION); echo '//-->'."\r\n";

if ((isset($_SESSION['isSAdmin']) && $_SESSION['isSAdmin'])) {
    // admin
    $hideMeta = '';
}
else {
    //normal User
    $hideMeta = 'style="display:none;"';

}

if (!isset($_COOKIE['viewCommunity']) && isset($_GET['id'])) {

    //find the first community this listing is in and this user has access to, if we're coming to this page without first selecting a community.
    $firstListedCommunity = 0;
    $query = sprintf('
        select communityId
        from tblcommunitylistingmap
        where listingId = %1$d
        and communityId in (
            select distinct c.id
            from tblcommunity c
            left join tblcounty county on county.id = c.countyId
            left join tblstate state on state.id = county.stateId
            left join tblcountry country on country.id = state.countryId
            inner join tbluserrightsmap rightsMap on (
                   rightsMap.communityId = c.id
                or rightsMap.countyId = county.id
                or rightsMap.stateId = state.Id
                or rightsMap.countryId = country.Id
                or rightsMap.userId = %2$d
            )
            where c.deleted = 0
              and c.parentId = 1
              and c.id != 1
        ) limit 1
        ;',
    $mysqli->real_escape_string($_GET['id']),
    $mysqli->real_escape_string($_SESSION['uid'])
    );

    if ($result = $mysqli->query($query)) {
        while($row = $result->fetch_object()) {
            $firstListedCommunity = $row->communityId;
        }
    }

    if ($firstListedCommunity > 0)
        $community = get_CommunityInfo(array('id'=>$firstListedCommunity));

    if (isset($community->id)) {
        setcookie('viewCommunity', $community->id, 0, "/dashboard/");
    }
}
else {
    $community = get_CommunityInfo(array('id'=>$_COOKIE['viewCommunity']));
}

$listingId = 0;

$buttonText = "Next";
$buttonId = "newNext";
$buttonHref = "#next";
$currentPage = "New Listing";
$show_specials = false;
$specials = "";
$clickAnalyticsOutput = '';
$ownerUID = $_SESSION['uid'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $listingId = $_GET['id'];
    $buttonText = "Save Listing";
    $buttonId = "save";
    $buttonHref = "#Submit";

    $query = sprintf("
        select
            cast(l.active AS unsigned integer) as active1,
            l.*,
            s.name as stateName,
            s.abbr as stateAbbr,
            s.id as stateId
        from tbllisting l
        left join tblstate s on s.id = l.stateId
        where l.id = %d
        limit 1
        ;",
        $mysqli->real_escape_string($listingId)
    );

    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_object()) {
            // gather category metaStuff

            if ($row->metaTitle)
                $metaTitle = trim($row->metaTitle);
            else
                $metaTitle = trim($row->metaTitle);

            if ($row->metaDescription)
                $metaDescription = trim($row->metaDescription);
            else
                $metaDescription = trim($row->description);

            $currentPage = $name = htmlentities(trim($row->name));
            $description = $row->description;
            $address1 = htmlentities(trim($row->address1));
            $address2 = htmlentities(trim($row->address2));
            $city = htmlentities(trim($row->city));
            $stateId = $row->stateId;
            $zip = htmlentities(trim($row->zip));
            $contactname = htmlentities(trim($row->contactName));
            $phone = htmlentities(trim($row->contactPhone));
            $fax = htmlentities(trim($row->contactFax));
            $contactemail = htmlentities(trim($row->contactEmail));
            $website = htmlentities(trim($row->website));
            $website_facebook = htmlentities(trim($row->website_facebook));
            $website_twitter = htmlentities(trim($row->website_twitter));
            $website_linkedin = htmlentities(trim($row->website_linkedin));
            $hours = $row->hoursOfOperation;
            $products = $row->products;
            $services = $row->services;
            $specials = $row->specials;
            $show_specials = $row->show_specials;
            $metatitle = trim(strip_tags($row->metaTitle));
            $metakeywords = trim(strip_tags($row->metaKeywords));
            $metadescription = trim(strip_tags($row->metaDescription));
            $ownerUID = $row->ownerUID;
            $geolatitude = $row->geolatitude;
            $geolongitude = $row->geolongitude;
            if ($row->active1 != 1)
                $enabled = 'checked="checked"';

            $listingpassword = $listingpasswordconfirm = "PASSWORDDIDNOTCHANGE";
            $slug = $row->SEOName;
            $imagealttext = $row->SEOImageAltText;
        }

        if (isset($community->id)) {
            $query = sprintf("select * from tblcommunitylistingmap where listingId=%d and communityId=%d order by sortOrder limit 1",
            $mysqli->real_escape_string($listingId),
            $mysqli->real_escape_string($community->id)
            );

            if ($llresult = $mysqli->query($query)) {
                while($llrow = $llresult->fetch_object()) {
                    $foundCatMap = true;
                    $listingLevel = $llrow->listingLevelId;
                }
            }
        }
    }

    $clickAnalyticsReport = AnalyticsReport::getClickReportDataForLisitng($listingId, $listingStatStart, $listingStatEnd, $connection);

    $clickAnalyticsOutput = "<ul>";

    foreach ($clickAnalyticsReport['data']['totals'] as $evtName => $evtClicks)
        $clickAnalyticsOutput .= sprintf('<li>%1$s: %2$d</li>', $evtName, $evtClicks);

    $clickAnalyticsOutput .= "</ul>";
}
?>
<script type="text/javascript">
    var CommunityId = <?php echo isset($community->id) ? $community->id : "null"; ?>;

    function limitChars(textid, limit, infodiv) {
        var text = jQuery('#' + textid).val();
        var textlength = text.length;
        if (textlength > limit) {
            jQuery('#' + infodiv).html('You cannot write more then ' + limit + ' characters!');
            jQuery('#' + textid).val(text.substr(0, limit));
            return false;
        }
        else {
            jQuery('#' + infodiv).html('You have ' + (limit - textlength) + ' characters left.');
            return true;
        }
    }

    $(document).ready(function () {

        $('#specials').keyup(function () {
            limitChars('specials', 140, 'charlimitinfo');
        });

        for (var i in CKEDITOR.instances) {
            CKEDITOR.remove(CKEDITOR.instances[i]);
        }


        $(".ckeditor1").ckeditor(
                function () { },
                {
                    height: '200px',
                    customConfig: 'http://<?php echo STATIC_URL?>/ckeditor/min-config.js'
                }
        );

        $('#save').click(function () {

            if ($('#listing-info').valid()) {
                // TODO: Need to do server-side validation...
                $.ajax({
                    url: 'listing-info-save-ajax.php?c=' + new Date() * 1,
                    type: 'POST',
                    async: false,
                    data: $("#listing-info").serialize(),
                    success: function (data) {
                        if (data.result) {
                            var listId = 0;
                            if ($('#listingid').val()) {
                                listId = $('#listingid').val();
                            }


                            if (listId == 0) {
                                window.location.href = '/dashboard/listing.php?added=true&id=' + data.listingId + "#<?php echo ((isset($_SESSION['isSAdmin']) && $_SESSION['isSAdmin']) || (isset($_SESSION['isAffiliate']) && $_SESSION['isAffiliate'])) ? "ui-tabs-3" : "ui-tabs-2"; ?>"
                            }
                            else {
                                toastr["success"]("Listing saved");
                                //$(".errSummary").removeClass('errorSevere').addClass('errorInfo').html('<ul><li>Listing saved</li></ul>').show();
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
            else {
                return false;
            }
        });

        $('.url-suggestion').live('click', function () {
            $('#slug').val(this.text);
        });

        // Focus on form elements from toast notification.
        $('[data-focus="true"]').live('click', function(e) {
            e.preventDefault();
            $($(this).attr('href')).focus();
        });

        $('#name').blur(function () {
            //create url slug here
            if ($.trim($('#slug').val()) == '') {
                var url = $('#name').val().toLowerCase();
                url = url.replace(/[^a-z0-9\-]/g, '-');
                url = url.replace(/--+/g, '-');
                $('#slug').val(url);
                $('#slug').valid();
            }

            genTitle();
        });

        $('#city, #state, #zip').blur(function () {
            genTitle();
        });

        function genTitle() {
            if ($("#metatitle").val() == '' && $('#name').val() != '' && $('#city').val() != '' && $('#state').val() != '' && $('#zip').val() != '') {
                var stateAbbr = '';
                $.ajax({
                    url: 'data/getStateAbbrById.php',
                    type: 'POST',
                    data: { stateId: $('#state').val() },
                    success: function (data) {
                        if (data.result) {
                            data.StateAbbr;
                            $("#metatitle").val($('#name').val() + " " + $('#city').val() + " " + data.StateAbbr + " " + $('#zip').val());
                        }
                        else {
                            toastr["error"]("An error occurred generated title tag");
                            //alert('An error occurred generating title tag.');
                        }
                    },
                    dataType: 'json'
                });

            }

        }

        <?php if ((isset($_SESSION['isSAdmin']) && $_SESSION['isSAdmin']) || (isset($_SESSION['isAffiliate']) && $_SESSION['isAffiliate'])) { ?>
        $('#listing-info').validate({
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
                    required: "<a href='#name' data-focus='true'>Business Name</a> is required",
                    maxlength: jQuery.format("Business Name can not exceed {0} characters in length.")
                },
                phone: {
                    required: "<a href='#phone' data-focus='true'>Phone</a> is required",
                    maxlength: jQuery.format("Phone can not exceed {0} characters in length.")
                },
                fax: {
                    maxlength: jQuery.format("<a href='#fax' data-focus='true'>Fax</a> can not exceed {0} characters in length.")
                },
                address1: {
                    required: "<a href='#address1' data-focus='true'>Address 1</a> is required",
                    maxlength: jQuery.format("<a href='#address1' data-focus='true'>Address</a> field can not exceed {0} characters in length.")
                },
                city: {
                    required: "<a href='#city' data-focus='true'>City</a> is required",
                    maxlength: jQuery.format("<a href='#city' data-focus='true'>City</a> field can not exceed {0} characters in length.")
                },
                state: {
                    required: "<a href='#state' data-focus='true'>State</a> is required"
                },
                zip: {
                    required: "Zip code is required"
                },
                contactemail: {
                    email: "Must be a valid Email Address",
                    required: "Email Address is required",
                    remote: "That email address is already in use by another user"
                },
                listingpassword: {
                    minlength: jQuery.format("Listing Management Password must be at least {0} characters in length.")
                },
                listingpasswordconfirm: {
                    equalTo: "Listing Management Password confirm must match password."
                },
                slug: {
                    remote: "Invalid URL, another listing is already using this url.",
                    required: "URL is required."
                },
                specials: {
                    maxlength: "Specials must be 140 characters or less."
                }
            },
            rules: {
                name: {
                    required: true,
                    maxlength: 255
                },
                phone: {
                    required: true,
                    maxlength: 255
                },
                fax: {
                    maxlength: 255
                },
                address1: {
                    //required:true,
                    maxlength: 255
                },
                address2: {
                    maxlength: 255
                },
                city: {
                    //required:true,
                    maxlength: 255
                },
                state: {
                    //required:true
                },
                zip: {
                    //required:true
                },
                listingpassword: {
                    minlength: 5
                },
                listingpasswordconfirm: {
                    equalTo: "#listingpassword"
                },
                contactemail: {
                    //email: true,
                    maxlength: 255
                },
                slug: {
                    SeoUrl: true,
                    required: true,
                    maxlength: 255,
                    remote: {
                        url: "validation/numlist-validation-url.php",
                        data: {
                            type: "listing",
                            objId: $("#listingid").val(),
                            url: $("#slug").val(),
                            activeCommunityId: CommunityId
                        }
                    }
                },
                specials: {
                    maxlength: 140
                }

            },
            //showErrors: function(errorMap, errorList) {
            //    if (errorList.length == 0) {
            //        //$("#listing-info .errSummary").html('').hide();
            //    }
            //},
            invalidHandler: function (e, validator) {
                var errorMessage = '';

                for (var i = 0; i < validator.errorList.length; i++) {
                    errorMessage += ('<li>' + validator.errorList[i].message + '</li>');

                    if (validator.errorList[i].message == "Invalid URL, another listing is already using this url.") {
                        //lookup url suggestions here
                        $.ajax({
                            url: 'validation/listing-url-suggestions-ajax.php',
                            type: 'POST',
                            async: false,
                            data: {
                                name: $("#name").val(),
                                id: $("#listingid").val(),
                                activeCommunityId: CommunityId
                            },
                            success: function (data) {
                                if (data.result) {
                                    $('#slug-suggestions').empty();
                                    $('#slug-suggestions').append('<span style="font-weight:bold;">Available replacement URLS</span><br />');
                                    $.each(data.suggestions, function (index, value) {
                                        $('#slug-suggestions').append('<a class="url-suggestion">' + value + '</a><br />');
                                    });
                                }
                                else {
                                    toastr["error"]("An error occurred while getting URL suggestions");
                                    //alert('An error occurred getting url suggestions.');
                                }
                            },
                            dataType: 'json'
                        });
                    }
                }

                var errors = validator.numberOfInvalids();
                if (errors > 0) {
                    //toastr.options.onclick =  function() {
                        // Check if this tab is active; if not set it, then scroll.
                        //$(window).scrollTo($("#listing-info .errSummary"), 300);
                    //};
                    toastr["error"]("There were errors while validating your input.<br><br>Details:<br><ul>" + errorMessage + "</ul>");
                    //window.setTimeout(function() { toastr.options.onclick = null; }, 500);

                    //$("#listing-info .errSummary").html('<ul>' + errorMessage + '</ul>');
                    //$("#listing-info .errSummary").show();
                }
                else {
                    //$("#listing-info .errSummary").html('').hide();
                }

            },
            onkeyup: function () { return true }
        });
        <?php
}
else {
        ?>
        $('#listing-info').validate({
            errorPlacement: function(error, element) {

            },
            highlight: function(element) {
                $(element).parent('div').addClass('error');
            },
            unhighlight: function(element) {
                $(element).parent('div').removeClass('error');
            },
            messages: {
                specials: {
                    maxlentgh: "Specials must be 140 characters or less."
                }
            },
            rules: {
                specials: {
                    maxlength: 140
                }

            },
            invalidHandler: function (e, validator) {
                var errorMessage = '';

                for (var i = 0; i < validator.errorList.length; i++) {
                    errorMessage += ('<li>' + validator.errorList[i].message + '</li>');
                }

                var errors = validator.numberOfInvalids();
                if (errors) {
                    toastr["error"]("There were errors while validating your input.<br><br>Details:<br><ul>" + errorMessage + "</ul>");
                    //$(".errSummary").html('<ul>' + errorMessage + '</ul>');
                    //$(".errSummary").show();
                }
            },
            onkeyup: function () { return true }
        });
        <?php
}
// ?>
        // ?>
<?php   if ($listingId == 0) { ?>
        $("#tabslisting").tabs("option", "cache", true);
        $("#tabslisting").tabs("option", "cookie", null);
<?php
        } ?>
        // ?>

        // Click Analytics
        $("#clickAnalyticsStart").datepicker({
            defaultDate: "-31d",
            changeMonth: true,
            numberOfMonths: 3,
            maxDate: -1,
            onClose: function (selectedDate) {
                $("#clickAnaltyicsEnd").datepicker("option", "minDate", selectedDate);
            }
        });
        $("#clickAnaltyicsEnd").datepicker({
            defaultDate: "-1d",
            changeMonth: true,
            numberOfMonths: 3,
            maxDate: -1,
            onClose: function (selectedDate) {
                $("#clickAnalyticsStart").datepicker("option", "maxDate", selectedDate);

            }
        });

        // listing-analytics-report-template
        var clickReportSource = $("#listing-click-analytics-report-template").html();
        var clickReportTemplate = Handlebars.compile(clickReportSource);

        $(".listing-analytics-click-tracking .report-selection .click-quick-report").on('click', function (e) {
            e.preventDefault();

            $("#clickAnalyticsStart").datepicker("setDate", $(this).data('report-start'));
            $("#clickAnaltyicsEnd").datepicker("setDate", $(this).data('report-end'));
            $("#clickAnalyticsSubmit").click();
        });

        // Run Report
        $("#clickAnalyticsSubmit").click(function (e) {
            e.preventDefault();

            var startDate = $("#clickAnalyticsStart").datepicker("getDate");
            var endDate = $("#clickAnaltyicsEnd").datepicker("getDate");

            $(".listing-analytics-click-tracking .report-start").text(dateFormat(startDate, (startDate.getYear() == new Date().getYear()) ? 'mmmm dS' : 'mmmm dS, yyyy'));
            $(".listing-analytics-click-tracking .report-end").text(dateFormat(endDate, (endDate.getYear() == new Date().getYear()) ? 'mmmm dS' : 'mmmm dS, yyyy'));

            // Display the interval.
            $('.listing-analytics-click-tracking .report-interval').show();
            $('.listing-analytics-click-tracking .report-form').hide();

            // Display the loader.
            $(".listing-analytics-click-tracking ul").fadeOut('normal', function () {
                $(this).remove();
                $(".listing-analytics-click-tracking .report-loader").fadeIn('fast', function () {
                    // Get the data.
                    $.ajax({
                        url: 'data/listing-get-click-tracking-ajax.php',
                        type: 'POST',
                        data: {
                            listingId: $("#listing-info :hidden[name='listingid']").val(),
                            analytics_start: $("#clickAnalyticsStart").val(),
                            analytics_end: $("#clickAnaltyicsEnd").val()
                        },
                        success: function (data) {
                            if (data.result) {
                                // Generate the report.
                                var output = clickReportTemplate(data);
                                $(output)
                                .hide()
                                .appendTo(".listing-analytics-click-tracking")

                                $(".listing-analytics-click-tracking  .report-loader").fadeOut('normal', function () {
                                    $(".listing-analytics-click-tracking ul").fadeIn();
                                });

                            }
                            else {
                                toastr["error"]("An error occurred retrieving this listing's click tracking");
                                //alert('An error occurred retrieving this listing\'s click tracking.');
                            }
                        },
                        dataType: 'json'
                    }); // .ajax
                }); // .fadeIn
            }); // .fadeOut
        });

        $("#geoCodeAddress").click(function (e) {
            $.ajax({
                url: 'data/listing-geocode-address-ajax.php',
                type: 'POST',
                data: {
                    listingAddress: $("#address1").val() + ($("#address2").val() != "" ? " " + $("#address2").val() : "") + " " + $("#city").val() + ", " + $("#state").val() + " " + $("#zip").val()
                },
                success: function(data) {
                    if (data.result) {
                        $("#geolatitude").val(data.geodata.latitude);
                        $("#geolongitude").val(data.geodata.longitude);
                    }
                    else {
                        toastr["errror"]("An error occurred while attempting to geo-code the listing address");
                        //alert('An error occurred while attempting to geo-code the listing address.');
                    }
                },
                dataType: 'json'
            }); // .ajax
        });

        $("#newNext").click(function(e) {
            e.preventDefault();
            if ($('#listing-info').valid()) {
                $('#tabslisting').tabs('select', 1);
            }

            return false;
        })

        // Listing start/end dates
        /*$("#listingstart, #listingend").datepicker({
            defaultDate: "-31d",
            changeMonth: true,
            numberOfMonths: 2
        });*/

    });
</script>
<?php
$communityId = 0;
if (isset($community->id)) {
    $communityId = $community->id;
}

if (($_SESSION['isSAdmin'] == true) || (isset($_SESSION['isAffiliate']) && $_SESSION['isAffiliate'] == true)) {
    echo '<div class="breadCrumb">';
    echo '<a class="clearMainDashTab" href="/dashboard/">Dashboard</a> &gt; ';
?>
<a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt; <a class="communityClick" href="/dashboard/" data-communityId="<?php echo $communityId?>">
    <?php
    if (isset($community->name)) {
        echo $community->name;
    }
    else {
        echo 'No Community';
    }
    ?>
</a> &gt; <?php echo  $currentPage ?></div><br />
<div class="clearfloat"></div>
<?php
}
?>
<div class="listingWrapper">
    <form id="listing-info" name="listing-info" action="listing-info.php" autocomplete="off">
        <!-- FIX FOR Chrome 34+ -->
        <input type="text" id="_fake_username_" style="display: none;" />
        <input type="password" id="_fake_password_" style="display: none;" />
        <div class="errSummary errorSevere modalError" style="display: none;"></div>
        <input type="hidden" name="listingid" id="listingid" value="<?php echo $listingId?>" />
        <input type="hidden" name="submitted" id="submitted" value="1" />
        <div class="floatleft">
            <div class="fieldset" style="width: 480px;">
                <h3>Listing Add/Edit</h3>
                <?php
                if ((isset($_SESSION['isSAdmin']) && $_SESSION['isSAdmin']) || (isset($_SESSION['isAffiliate']) && $_SESSION['isAffiliate'])) {
                ?>

                <div class="row">
                    <div class="colfull">
                        <label for="name">Business Name:</label><br />
                        <input name="name" id="name" type="text" value="<?php echo $name?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="col1">
                        <label for="phone">Phone:</label><br />
                        <input name="phone" id="phone" type="text" value="<?php echo $phone?>" />
                    </div>
                    <div class="col2">
                        <label for="fax">Fax:</label><br />
                        <input name="fax" id="fax" type="text" value="<?php echo $fax?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull">
                        <label for="address1">Address 1:</label><br />
                        <input name="address1" id="address1" type="text" value="<?php echo $address1?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull">
                        <label for="address2">Address 2:</label><br />
                        <input name="address2" id="address2" type="text" value="<?php echo $address2?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="col13">
                        <label for="city">City:</label><br />
                        <input name="city" id="city" type="text" value="<?php echo $city?>" />
                    </div>
                    <div class="col23">
                        <label for="state">State:</label><br />
                        <select name="state" id="state">
                            <?php echo get_state_list($stateId)?>
                        </select>
                    </div>
                    <div class="col33">
                        <label for="zip">Zip:</label><br />
                        <input name="zip" id="zip" type="text" value="<?php echo $zip?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="col1">
                        <label for="geolatitude">Latitude:</label>
                        <input name="geolatitude" id="geolatitude" type="text" value="<?php echo $geolatitude; ?>" />
                    </div>
                    <div class="col2">
                        <label for="geolongitude">Longitude:</label>
                        <input type="text" name="geolongitude" id="geolongitude" value="<?php echo $geolongitude; ?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull">
                        <button id="geoCodeAddress" type="button">Get Map Location</button>
                    </div>
                </div>
                <div class="clearfloat"></div>

                <div class="row">
                    <div class="colfull">
                        <label for="contactname">Contact Name:</label><br />
                        <input name="contactname" id="contactname" type="text" value="<?php echo $contactname?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull">
                        <label for="contactemail">Contact Email:</label><br />
                        <input name="contactemail" id="contactemail" type="text" value="<?php echo $contactemail?>" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull">
                        <label for="website">Website:</label><br />
                        <input name="website" id="website" type="text" value="<?php echo $website; ?>" autocomplete="off" />
                    </div>
                </div>
                <div class="clearfloat"></div>

                <div class="row">
                    <div class="colfull">
                        <label for="website">Facebook:</label><br />
                        <input name="website_facebook" id="website_facebook" type="text" value="<?php echo $website_facebook; ?>" autocomplete="off" />
                    </div>
                </div>
                <div class="clearfloat"></div>

                <div class="row">
                    <div class="colfull">
                        <label for="website">Twitter:</label><br />
                        <input name="website_twitter" id="website_twitter" type="text" value="<?php echo $website_twitter; ?>" autocomplete="off" />
                    </div>
                </div>
                <div class="clearfloat"></div>

                <div class="row">
                    <div class="colfull">
                        <label for="website">LinkedIn:</label><br />
                        <input name="website_linkedin" id="website_linkedin" type="text" value="<?php echo $website_linkedin; ?>" autocomplete="off" />
                    </div>
                </div>
                <div class="clearfloat"></div>

                <div class="row">
                    <div class="col1">
                        <label for="listingpassword">Listing Management Password:</label><br />
                        <input name="listingpassword" id="listingpassword" type="password" value="<?php echo $listingpassword?>" autocomplete="off" />
                    </div>
                    <div class="col2">
                        <label for="listingpasswordconfirm">Confirm:</label><br />
                        <input name="listingpasswordconfirm" id="listingpasswordconfirm" type="password" value="<?php echo $listingpasswordconfirm?>" autocomplete="off" />
                    </div>
                </div>
                <div class="clearfloat"></div>
                <?php
                }
                ?>
                <div class="row">
                    <div class="colfull" style="min-height: 300px; width: 485px;">
                        <input type="checkbox" name="show_specials" value="1" <?php if($show_specials == 1){ echo 'checked'; }?> / >
                        <label for="show_specials">Show Specials?</label><br />
                        <label for="specials">Specials:</label><br />
                        <textarea name="specials" id="specials"><?php echo $specials?></textarea>
                        <p id="charlimitinfo">140 characters maximum.</p>
                    </div>
                </div>
                <div class="clearfloat"></div>
                <?php
                if ((isset($_SESSION['isSAdmin']) && $_SESSION['isSAdmin']) || (isset($_SESSION['isAffiliate']) && $_SESSION['isAffiliate'])) {
                ?>

                <div class="row">
                    <div class="colfull" style="min-height: 300px; width: 485px;">
                        <label for="description">Description:</label><br />
                        <textarea name="description" id="description" class="ckeditor1"><?php echo $description?></textarea>
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull" style="min-height: 300px; width: 485px;">
                        <label for="products">Products:</label><br />
                        <textarea name="products" id="products" class="ckeditor1"><?php echo $products?></textarea>
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull" style="min-height: 300px; width: 485px;">
                        <label for="services">Services:</label><br />
                        <textarea name="services" id="services" class="ckeditor1"><?php echo $services?></textarea>
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull" style="width: 485px;">
                        <label for="hours">Hours of Operation:</label><br />
                        <textarea name="hours" id="hours" class="ckeditor1"><?php echo $hours?></textarea>
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <br />
                    <div class="colfull">
                        <input name="enabled" id="enabled" type="checkbox" <?php echo $enabled?>>
                        <label for="enabled">Disabled</label>
                    </div>
                </div>
                <div class="clearfloat"></div>
                <div class="row">
                    <div class="colfull">
                        <label for="listinglevel">Listing Level:</label><br />

                        <?php if($foundCatMap == true)
                              {
                        ?>

                        <select id="listinglevel" name="listinglevel">
                            <?php echo get_listing_level($listingLevel)?>
                        </select>
                        <?php
                              }
                              else
                              {
                        ?>
                        This listing must first be mapped to a category before the advertising level can be set.
                    <?php
                              }

                    ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
        if ($_SESSION['isSAdmin'] == true) { ?><div class="fieldset floatright" style="width: 350px; margin-bottom: 2em;">
            <h3>Owner</h3>
                <div class="row">
                    <div class="colfull">
                        <label for="ownerUID">Owner: </label><br />
                        <select id="ownerUID" name="ownerUID"><?php echo get_admin_users($ownerUID); ?></select>
                    </div>
                </div>
                <div class="clearfloat"></div>
        </div><?php
        }
        else {
            ?><input type="hidden" id="ownerUID" name="ownerUID" value="<?php echo $ownerUID; ?>" /><?php
        }
        ?>

        <div class="fieldset floatright" style="width: 350px;">
            <h3>SEO</h3>

            <div class="row">
                <div class="colfull">
                    <label for="slug">URL:</label><br />
                    <input name="slug" id="slug" type="text" value="<?php echo $slug?>" />
                    <div id="slug-suggestions">
                    </div>
                    <!-- <a style="display:block;width:330px;word-wrap:break-word;" href="http://<?php echo $community->url . '.' . HOME_URL?>/business/<?php echo preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]+/', '-',strtolower(str_replace('&','and',trim($name)))) . '-' . $listingId) . '/' ?>" target="_blank">http://<?php echo $community->url . '.' . HOME_URL?>/business/<?php echo preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]+/', '-',strtolower(str_replace('&','and',trim($name)))) . '-' . $listingId) . '/' ?></a>-->
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="row" <?php echo $hideMeta?>>
                <div class="colfull">
                    <label for="metatitle">Title Tag:</label><br />
                    <input name="metatitle" id="metatitle" type="text" value="<?php echo $metatitle?>" />
                </div>
            </div>
            <div class="clearboth"></div>
            <div class="row" <?php echo $hideMeta?>>
                <div class="colfull">
                    <label for="metakeywords">Meta Keywords:</label><br />
                    <textarea name="metakeywords" id="metakeywords"><?php echo $metakeywords?></textarea>
                </div>
            </div>
            <div class="clearboth"></div>
            <div class="row" <?php echo $hideMeta?>>
                <div class="colfull">
                    <label for="metadescription">Meta Description:</label><br />
                    <textarea name="metadescription" id="metadescription"><?php echo $metadescription?></textarea>
                </div>
            </div>
            <div class="clearboth"></div>
            <div class="row" <?php echo $hideMeta; ?>>
                <div class="colfull">
                    <label for="metadescription">Image Alt Text:</label><br />
                    <input type="text" name="imagealttext" id="imagealttext" value="<?php echo $imagealttext; ?>" />
                </div>
            </div>
            <div class="clearfloat"></div>
            <br />
            <br />
        </div>

        <div class="fieldset floatright listing-analytics-click-tracking" style="width: 350px; margin-top: 2em;">
            <h3>Click Tracking</h3>
            <div class="report-selection" style="font-size: 13px; margin: .35em 0;">
                <div class="report-interval" style="float: left;">
                    From <span class="report-start"><?php echo $listingStatStart->format('F jS'); ?></span>
                    -    <span class="report-end"><?php   echo $listingStatEnd->format('F jS');   ?></span>
                    |    <a style="color: #c00;" href="#reportDate" onclick="$('.report-interval').hide(); $('.report-form').show(); return false;">Custom Report</a><br />
                    Quick Reports: <?php foreach (array(30, 90, 180, 360) as $days) {
                                             $quickStart = new DateTime(sprintf('yesterday - %d days', $days));
                                             $quickEnd   = new DateTime('yesterday');

                                             $link = ' <a style="color: #c00;" class="click-quick-report" href="#listing-report-%1$d" data-report-start="%2$s" data-report-end="%3$s">%1$d</a>';
                                             echo sprintf($link, $days, $quickStart->format('m/d/Y'), $quickEnd->format('m/d/Y'));
                                         } ?>
                </div>
                <!-- /.report-interval -->
                <div class="report-form" style="float: left; display: none;">
                    <label for="clickAnalyticsStart">From</label>
                    <input type="text" name="analytics.start" id="clickAnalyticsStart" value="<?php echo $listingStatStart->format('Y-m-d'); ?>" />
                    <label for="clickAnaltyicsEnd">to</label>
                    <input type="text" name="analytics.end" id="clickAnaltyicsEnd" value="<?php   echo $listingStatEnd->format('Y-m-d');   ?>" />
                    <a id="clickAnalyticsSubmit" style="color: #c00;" href="#updateReport" onclick="">Run Report</a> |
                <a style="color: #c00;" href="#closeReport" onclick="$('.report-form').hide(); $('.report-interval').show(); return false;">Close</a><br />
                    &nbsp;
                </div>
                <!-- /.report-form -->
                <div class="clearfix"></div>
                <div class="report-loader">
                    <div id="circularG">
                        <div id="circularG_1" class="circularG"></div>
                        <div id="circularG_2" class="circularG"></div>
                        <div id="circularG_3" class="circularG"></div>
                        <div id="circularG_4" class="circularG"></div>
                        <div id="circularG_5" class="circularG"></div>
                        <div id="circularG_6" class="circularG"></div>
                        <div id="circularG_7" class="circularG"></div>
                        <div id="circularG_8" class="circularG"></div>
                    </div>
                </div>
                <?php echo $clickAnalyticsOutput; ?>
            </div>
            <!-- /.report-selection -->

            <div class="clearfix"></div>
            <script id="listing-click-analytics-report-template" type="text/x-handlebars-template">
                <ul>{{#each report.data.totals }}<li>{{@key}}: {{this}}</li>
                    {{/each}}</ul>
            </script>
        </div>
        <!-- /.listing-analytics-click-tracking -->
        <div class="clearboth"></div>

        <?php
                }
        ?>
    </form>

</div>
<div class="clearboth"></div>
<div class="modalButtonWrapper" style="width: 100%; clear: both; text-align: right; padding-top: 20px;">
    <a href="<?php echo $buttonHref; ?>" id="<?php echo $buttonId; ?>" class="submit buttonStyle"><?php echo $buttonText; ?></a>
    <a href="/dashboard#Listings" id="cancel" class="buttonStyle cancel listingCancel">Cancel</a>
    <?php if ($listingId != 0 && ($_SESSION['roles'] != null && in_array(array('id' => 1, 'name' => 'Super Admin'), $_SESSION['roles']))) { // Delete Permanently ?>
    <a href="#" id="btnDeletePermanently" class="buttonStyle">Delete Listing</a>
    <div id="confirmPermanentDelete" title="Delete Listing?">
        <p>Are you sure you want to permanently remove this listing?</p>
        <form action="/dashboard/listing-permanent-remove.php" method="post" onsubmit="return false;">
            <input type="hidden" name="listingId" value="<?php echo $listingId; ?>" />
            <div style="padding: 15px 0;">
                <input type="text" id="deleteListingConfirm" class="form-control" placeholder="Enter the word DELETE as shown to confirm." style="padding: 3px; width: 100%; width: calc(100% - 6px); line-height: 1.5em;" />
            </div>
        </form>
    </div>
    <script type="text/javascript">
        $(document).ready(function () {
            var deleteListing = function() {
                var $form = $("#confirmPermanentDelete").find('form'),
                    formData = $form.serialize() + '&submitted=true';

                var $jqxhr = $.ajax({
                    url: $form.attr('action'),
                    type: $form.attr('method').toUpperCase(),
                    data: formData,
                    dataType: 'json'
                })
                .done(function(data, status, xhr) {
                    // Success

                    if (!data.success || data.success == false) {
                        if (confirm('An error occurred while removing this lisitngThis listing. Would you like to try again?')) {
                            deleteListing();
                        }
                    }

                    if (data.success == true) {
                        $("#confirmPermanentDelete").dialog('close');
                        window.location = '/dashboard?deletedlisting=1';
                    }
                })
                .fail(function() {
                    if (confirm('An error occurred while removing this lisitngThis listing. Would you like to try again?')) {
                        deleteListing();
                    }
                })
                .always(function() {
                    $("#confirmPermanentDelete").dialog('close');
                });
            };

            $permanentDeleteConfirm = $("#confirmPermanentDelete").dialog({
                autoOpen: false,
                modal: true,
                buttons: [
                    {
                        id: "dialogBtnDeleteListing",
                        text: "DELETE",
                        disabled: true,
                        click: function() {
                            if ($("#deleteListingConfirm").val() == "DELETE") {
                                deleteListing();
                            }
                        }
                    },
                    {
                        text: "Cancel",
                        click: function() {
                            $("#confirmPermanentDelete").dialog('close');
                        }
                    }
                ],
                close: function() {
                    $("#deleteListingConfirm").val('');
                    $("#dialogBtnDeleteListing").button('disable');
                }
            });

            $("#deleteListingConfirm").on('keyup', function(e) {
                if ($(this).val() == "DELETE") {
                    // Enable Button
                    $("#dialogBtnDeleteListing").button('enable');
                }
                else {
                    // Disable button
                    $("#dialogBtnDeleteListing").button('disable');
                }
            });


            $permanentDeleteConfirm.find("form").on("submit", function(e) {
                e.preventDefault();

                if ($("#deleteListingConfirm").val() == "DELETE") {
                    deleteListing();
                }
            });

            $("#btnDeletePermanently").on("click", function(e) {
                e.preventDefault();
                $permanentDeleteConfirm.dialog('open');
            });
        });
    </script>
    <?php } ?>
</div>
<?php
if ($_SESSION['isSAdmin'] == true) {
    echo '<div class="breadCrumb">';
    echo '<a class="clearMainDashTab" href="/dashboard/">Dashboard</a> &gt; ';
?>
<a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt; <a class="communityClick" href="/dashboard/" data-communityId="<?php echo $communityId?>">
    <?php
    if (isset($community->name)) {
        echo $community->name;
    }
    else {
        echo 'No Community';
    }
    ?>
</a> &gt; <?php echo  $currentPage ?></div><br />
<div class="clearfloat"></div>
<?php
}


function get_listing_level($selectedLevel) {
    $mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
    $returnVal = '<option value="">None</option>';
    $query = sprintf("select * from tbllistinglevel");
    $selected = '';
    if ($result = $mysqli->query($query))
    {
        while($row = $result->fetch_object())
        {
            if($row->id == $selectedLevel) $selected = ' selected="selected" ';
            else $selected ='';

            $returnVal .= '<option value="' . $row->id . '"' . $selected .'>' . $row->name  . '</option>';
        }
    }
    $mysqli->close();
    return $returnVal;
}

function get_admin_users($selectedUserId) {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $query = sprintf("select id, firstName, lastName, email from tbluser where active = true order by firstName, lastName");
    $options = "";
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_array()) {
            $options .= sprintf("<option value=\"%s\"%s>%s %s</option>", $row['id'], $row['id'] == $selectedUserId ? " selected=\"selected\"" : "", $row['firstName'], $row['lastName']);
        }
        $result->close();
    }
    $mysqli->close();

    return $options;
}
?>


