<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);


$community = array();
$community = get_CommunityInfo(array('id'=>$_COOKIE['viewCommunity']));

$communityId = 0;
if(isset($_COOKIE['viewCommunity'])) {
    $communityId = $_COOKIE['viewCommunity'];
}

$useListingUrl = false;

if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $primaryListingId = $listingId =$_GET['id'];
    //$buttonText = "Save Listing";
    $listingPhoto = '';
    $listingUploadId = 0;
    $listingDetailPhoto = '';
    $listingDetailPhotoId = 0;
    
    $query = sprintf("select l.* from tbllisting l
                        where l.id = %d
                        limit 1",
        $mysqli->real_escape_string($listingId)
    );

    if ($result = $mysqli->query($query)) {
        while($row = $result->fetch_object()) {
            $currentPage = $row->name;
            if(is_numeric($row->oldId) && $row->oldId > 0) {
            //	$listingId=$row->oldId;
            }
        }
    }
    
    $query = sprintf("select * 
                      FROM tbllistingupload  
                      WHERE listingId = %d
                      AND active=1
                      AND (type='listing photo' OR type='listing detail photo')",
                      $mysqli->real_escape_string($listingId)
                    );

    if ($UpResult = $mysqli->query($query))  {
        while($UpRow = $UpResult->fetch_object()) {	
            if ($UpRow->type == 'listing photo') {
                $listingPhoto = '<a href="#deleteAd" id="deleteAd" class="deleteAd" ></a><img width="200" height="200" src="http://' . STATIC_URL . '/assets/photos/listings/' . $listingId . '/' . $UpRow->fileName . '" alt="' . $UpRow->fileName . '">';
                $listingUploadId = $UpRow->id;
                $useListingUrl = $UpRow->useListingUrl == 1 ? true : false;
            }
            if ($UpRow->type == 'listing detail photo') {
                $listingDetailPhoto = '<a href="#deleteDetailPhoto" id="deleteDetailPhoto" class="deleteAd" ></a><img src="http://' . STATIC_URL . '/assets/photos/listings/' . $listingId . '/' . $UpRow->fileName . '" alt="' . $UpRow->fileName . '">';
                $listingDetailPhotoId = $UpRow->id;
           }
        }
    }
}
        



?>


<script type="text/javascript">
$(document).ready(function() {
    <?php if (!strlen($listingPhoto)) { ?> 
    $(".uploadPanel-ad").show();
    $("#adSettings").hide();
    <?php } ?>
    <?php if (!strlen($listingDetailPhoto)) { ?>
    $(".uploadPanel-detail-photo").show();
    <?php } ?>

    initUploadifyAd();
    initUploadifyDetailPhoto();

    $(".next").live('click', function () {
        $.cookie("listingDash",3);
        window.reload();
    });

    $('[data-action="listing-ad-edit"]').on("click", function(e) {
        $.ajax({
            url: '/dashboard/data/listing-ad-link-setting-ajax.php', 
            type: 'POST',
            async: false,
            data: {
                listingId: <?php echo $listingId; ?>,
                listingUploadId: <?php echo $listingUploadId; ?>,
                useLisitngWebsite: $("#as_useListingUrl").is(":checked")
            },
            success: function(data) {
                if(data.result) { }
                else {
                    alert('An error occured saving this data');
                }
            },
            dataType:'json'
        });
    });
    
    $("#deleteAd").live('click', function() {
        var r = confirm("Are you sure you want to delete/remove this image permanently?");
        
        if (r == true) {
            $.ajax({url: 'listing-ads-delete-ajax.php', 
                type: 'POST',
                async: false,
                data: {
                    listingId: <?php echo $listingId;?>,
                    listingUploadId: <?php echo $listingUploadId;?>,
                    listingType: 'listing photo'
                },
                success: function(data){
                    if (data.result) {
                        $('div.adimage').empty();
                        //initUploadifyAd();
                        $(".uploadPanel").show();
                        $("#adSettings").hide();

                    } 
                    else {
                        alert('An error occured saving this data');
                    }
                },
                dataType:'json'
            });
        }
    });

    $("#deleteDetailPhoto").live('click', function() {
        var r = confirm("Are you sure you want to delete this image permanently?");

        if (r == true) {
            $.ajax({
                url: 'listing-ads-delete-ajax.php',
                type: 'POST',
                async: false,
                data: {
                    listingId: <?php echo $listingId; ?>,
                    listingUploadId: <?php echo $listingDetailPhotoId; ?>,
                    listingType: 'listing detail photo'
                },
                success: function(data){
                    if (data.result) {
                        $('div.detailphoto').empty();
                        $(".uploadPanel-detail-photo").show();
                    } 
                    else {
                        alert('An error occured saving this data');
                    }
                },
                dataType:'json'
            })
        }
    });

    function initUploadifyAd(){
        var listingId = getQuerystring('id');
        var sessionId = '';
            
        if ($.cookie("myreadylink")) 
            sessionId = $.cookie("myreadylink");

        $("#file_upload_ad").uploadify({
            'uploader'  : '/dashboard/uploadify.swf',
            'script'    : '/dashboard/data/upload.php',
            'scriptData': {
                'session_name': sessionId,
                'listingType': 'listing photo',
                'listingId' : listingId
            },
            'cancelImg' : 'http://<?php echo STATIC_URL ?>/images/admin/cancel.png',
            'folder'    : '/uploads',
            'auto'      : true,
            'buttonText': 'Select Ad',
            'onComplete': function(event, ID, fileObj, response, data) {
                var respObj = $.parseJSON(response);
                $('.adimage').append('<a href="#deleteAds" id="deleteAd" class="deleteAd" ></a><img width="200" height="200" src="' + respObj.FileUrl + '" alt="' + respObj.FileName + '">');
                $("#adSettings").show();
                $('.uploadPanel-ad').hide();

            },
            'onError': function (event,ID,fileObj,errorObj) {
                alert(errorObj.type + ' Error: ' + errorObj.info);
            }
        });
    }

    function initUploadifyDetailPhoto(){
        var listingId = getQuerystring('id');
        var sessionId = '';
            
        if ($.cookie("myreadylink")) 
            sessionId = $.cookie("myreadylink");

        $("#file_upload_detail_photo").uploadify({
            'uploader'  : '/dashboard/uploadify.swf',
            'script'    : '/dashboard/data/upload.php',
            'scriptData': { 
                'session_name': sessionId,
                'listingType': 'listing detail photo', 
                'listingId' : listingId 
            },
            'cancelImg' : 'http://<?php echo STATIC_URL ?>/images/admin/cancel.png',
            'folder'    : '/uploads',
            'auto'      : true,
            'buttonText': 'Select Photo',
            'onComplete': function(event, ID, fileObj, response, data) {
                var respObj = $.parseJSON(response);
                $('.detailphoto').append('<a href="#deleteDetailPhoto" id="deleteDetailPhoto" class="deleteAd" ></a><img src="' + respObj.FileUrl + '" alt="' + respObj.FileName + '">');
                $('.uploadPanel-detail-photo').hide();

            },
            'onError': function (event,ID,fileObj,errorObj) {
                alert(errorObj.type + ' Error: ' + errorObj.info);
            }
        });
    }
});
</script>
<?php 
echo '<div class="breadCrumb">';
echo '<a class="clearMainDashTab" href="/dashboard/">Dashboard</a> &gt; ';
?>
<a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt; <a href="/dashboard/" class="communityClick" data-communityId="<?php echo $communityId?>"><?php echo $community->name ?></a> &gt; <?php echo  $currentPage ?></div><br /><div class="clearfloat"></div>
<br />
<div class="ad" style="width: 50%; float: left;">
    <h3>Ad</h3>
    <div class="uploadPanel uploadPanel-ad" style="display:none;">
        <input id="file_upload_ad" name="file_upload" type="file"  />
    </div>
    <div class="adimage">
        <?php echo $listingPhoto;?>
    </div>
    <div id="adSettings">
        <div class="form-group">
            <label><input type="checkbox" id="as_useListingUrl" name="ad.useListingUrl" <?php echo $useListingUrl ? 'checked="checked"':'' ?>/>Link to Website</label>
        </div>
        <button type="button" id="saveUseListingUrl" class="buttonStyle" data-action="listing-ad-edit">Save</button>
    </div>
</div>

<div class="detail-photo" style="width: 50%; float: left;">
    <h3>Detail Photo</h3>
    <div class="uploadPanel uploadPanel-detail-photo" style="display:none;">
        <input id="file_upload_detail_photo" name="file_upload" type="file"  />
    </div>
    <div class="detailphoto">
        <?php echo $listingDetailPhoto; ?>
    </div>
</div>

<div class="modalButtonWrapper" style="width:100%;clear:both;text-align:right;padding-top:20px;">
    <a href="" id="save" class="next buttonStyle">Next</a>
</div>
<?php 
echo '<div class="breadCrumb">';
echo '<a class="clearMainDashTab" href="/dashboard/">Dashboard</a> &gt;';
?>
<a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt; <a href="/dashboard/" class="communityClick" data-communityId="<?php echo $communityId?>"><?php echo $community->name ?></a> &gt; <?php echo  $currentPage ?></div><br /><div class="clearfloat"></div>

