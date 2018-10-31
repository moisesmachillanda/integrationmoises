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
if(isset($_COOKIE['viewCommunity']))
{
    $communityId = $_COOKIE['viewCommunity'];
}


if(isset($_GET['id']) && is_numeric($_GET['id']))
{
    $primaryListingId = $listingId =$_GET['id'];
    //$buttonText = "Save Listing";
    $listingAsset  = '';
    
    $query = sprintf("select l.* from tbllisting l
                        where l.id = %d
                        limit 1",
    $mysqli->real_escape_string($listingId)
    );
    
    if ($result = $mysqli->query($query))
    {
        while($row = $result->fetch_object())
        {
            $currentPage = $row->name;
            if(is_numeric($row->oldId) && $row->oldId > 0)
            {
                //$listingId=$row->oldId;
            }
        }
    }
    
    $query = sprintf("
        select * 
        from tbllistingupload  
        where listingId = %d
          and active=1
          and type !='listing photo' 
          and type != 'listing detail photo'
        order by type",
        $mysqli->real_escape_string($listingId)
    );

    $listingUploadId = 0;
    if ($UpResult = $mysqli->query($query)) 
    {
        while($UpRow = $UpResult->fetch_object())
        {	
            $ext = pathinfo($UpRow->fileName, PATHINFO_EXTENSION);
            
            $staticLink = 'http://' . STATIC_URL . '/assets/documents/listings/' . $listingId . '/' . $UpRow->fileName; 
            
            $listingAsset .= '<tr><td><a class="ext ' . $ext . '" href="' . $staticLink . '" target="_blank" >' . $UpRow->fileName . '</a></td><td>' . ucfirst($UpRow->type) . '</td><td><a href="#deleteDoc" rel="' . $UpRow->id . '" class="deleteDoc" ><img src="http://' . STATIC_URL . '/images/admin/btnDelete.png" class="deleteButton" border="0" width="18" height="18" alt="Delete" /></a></td></tr>';
            $listingUploadId = $UpRow->id;
        }
        
        $listingAsset = '<table class="my-listing-documents tablesorter">'. "\n" .'<thead><tr><th class="not-sortable">File Name</th><th class="not-sortable">Type</th><th class="not-sortable">Delete</th></thead>' . "\n" . '<tbody>' . $listingAsset;
        
        $listingAsset .= '</tbody>' . "\n";
        //echo '<h3 class="my-community-site">My Community Sites</h3>' . $output;
        //if($UpResult->num_rows > 10) $listingAsset .= Pager(5, 5, array(5,15,25));
        $listingAsset .= '</table>' . "\n";
    }
    
}
        



?>

<script type="text/javascript">


$(document).ready(function() {


    //setTimeout("initUploadify()", 200);
    initUploadifyDoc();
    
    if($('.my-listing-documents tr').length < 2){
        $('.my-listing-documents').hide();
        $('.noDocuments').show();
    }
    else{
        $('.my-listing-documents').show();
        $('.noDocuments').hide();
    }
    
    $(".deleteDoc").live('click', function(){
        var $this = $(this);
        var lType = $this.parent('td').prev('td').text().toLowerCase();
        var r=confirm("Are you sure you want to delete/remove this document permanently?");
        
        if(r==true)
        {

            $.ajax({url: 'listing-ads-delete-ajax.php', 
                   type: 'POST',
                   async: false,
                    data: {
                        listingId: <?php echo $listingId;?>,
                        listingUploadId: $this.attr('rel'),
                        listingType: lType
                        },
                   success:	function(data){
                            if(data.result)
                            {
                                $this.closest('tr').remove();
                            }
                            else
                            {
                                alert('An error occured deleting this data');
                            }
                        },
                    dataType:'json'
                });
        }
    });

    $('#uploadTypeDoc').change(function(){
        var uploadType= $('#uploadTypeDoc').val();
        
        if($('#uploadTypeDoc').val() != ''){
                $("#file_uploadDoc").uploadifySettings('buttonText','Browse ' + uploadType + 's');
                $("#file_uploadDoc").uploadifySettings('scriptData',{
                    'session_name' :'<?php echo session_id(); ?>',
                    'listingId' : <?php echo $listingId;?>,
                    'listingType' : uploadType			

                    }, true);
            }
        else
        {
            $("#file_uploadDoc").uploadifySettings('buttonText','Select a type');
        }
    });
    
});


function initUploadifyDoc(){
    $('.uploadPanel').show();
    $("#file_uploadDoc").show().uploadify({
        'uploader'  : '/dashboard/uploadify.swf',
        'script'    : '/dashboard/data/upload.php',
        'buttonText': 'Select a type',
        'scriptData': {'session_name': '<?php echo session_id(); ?>',
                        'listingType': $('#uploadTypeDoc').val(), 
                        'listingId' : <?php echo $listingId;?>
                      },
        'cancelImg' : 'http://<?php echo STATIC_URL ?>/images/admin/cancel.png',
        'folder'    : '/uploads',
        'auto'      : true,
        'width'     : 150,
        'onSelectOnce'  : function(event,data) {
            if($('#uploadTypeDoc').val() == '')
            {
                alert("Please select a File Type first.");
                $("#file_uploadDoc").uploadifyClearQueue();
                return false;
            }
        },
        'onComplete': function(event, ID, fileObj, response, data) {

            var respObj = $.parseJSON(response);
            
            $('.my-listing-documents').append('<tr><td><a class="ext ' + respObj.FileExt + '" href="' + respObj.FileUrl + '" target="_blank" >' + respObj.FileName + '</a></td><td>' + respObj.Type + '</td><td><a href="#deleteDoc" rel="' + respObj.UploadId + '" class="deleteDoc" ><img src="http://<?php echo STATIC_URL ?>/images/admin/btnDelete.png" class="deleteButton" border="0" width="18" height="18" alt="Delete" /></a></td></tr>');
            if($('.my-listing-documents tr').length < 2){
                $('.my-listing-documents').hide();
                $('.noDocuments').show();
            }
            else{
                $('.my-listing-documents').show();
                $('.noDocuments').hide();
            }

         },
         'onError'     : function (event,ID,fileObj,errorObj) {
             alert(errorObj.type + ' Error: ' + errorObj.info);
         }
         //,
       //'onSelectOnce' : function(event,data) {
        //	if($('.uploadType').val() == '')
        //	{
        //		$('#file_upload').uploadifyClearQueue();
        //		alert("Please select a file type before selcting a file.");
        //	}
          
        //}
        });

    
}
</script>



<?php 
echo '<div class="breadCrumb">';
//if($_SESSION['isSAdmin'] == true){
    echo '<a class="clearMainDashTab" href="/dashboard/">Dashboard</a> &gt;';
//}

?>
<a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt; <a href="/dashboard/" class="communityClick" data-communityId="<?php echo $communityId?>"><?php echo $community->name ?></a> &gt; <?php echo  $currentPage ?></div><br /><div class="clearfloat"></div>




<div class="uploadPanel">
<select id="uploadTypeDoc" ><option value="">--Select File Type--</option><option value="coupon">Coupon</option><option value="flyer">Flyer</option><option value="menu">Menu</option><option value="brochure">Brochure</option></select><br /><br />
<input id="file_uploadDoc" name="file_uploadDoc" type="file" style="display:none;" /> 
</div>


<?php echo $listingAsset;?>
<div class="noDocuments" style="display:hidden"><h3>There are currently no documents</h3></div>

<?php 
echo '<div class="breadCrumb">';
//if($_SESSION['isSAdmin'] == true){
    echo '<a class="clearMainDashTab" href="/dashboard/">Dashboard</a> &gt;';
//}

?>
<a href="/dashboard/" class="clearViewCommunity">Communities</a> &gt; <a href="/dashboard/" class="communityClick" data-communityId="<?php echo $communityId?>"><?php echo $community->name ?></a> &gt; <?php echo  $currentPage ?></div><br /><div class="clearfloat"></div>


