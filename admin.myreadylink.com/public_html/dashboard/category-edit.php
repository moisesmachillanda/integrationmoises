<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$headerArgs = array(); 
$STATIC_URL = STATIC_URL;

$catId = 0;
$type='';
if(isset($_GET['type'])) $type = $_GET['type']; 
if(isset($_GET['catId']) && is_numeric($_GET['catId'])) $catId = $_GET['catId']; 

$buttonText = "Add Category";
$sortOrder = $parentId = 0;

$name = $categorySlug = $description = $metaTitle = $metaKeywords = $metaDescription = $schemaDotOrgType = '';
                
$active = 0;
$checkstate = 0;
$parentId = 0;

if(isset($_GET['parentId'])) $parentId = $_GET['parentId']; 

if($catId > 0)
{
    $buttonText = "Save Category";
    
    if($type == 'number')
    {
        $query = sprintf("select * 
                            from tblnumbercategory 
                            where
                            id=%d
                            and deleted = 0
                            limit 1",
                $mysqli->real_escape_string($catId)
                );
    }
    else 
    {
        $query = sprintf("select * 
                            from tbllistingcategory 
                            where
                            id=%d
                            limit 1",
                $mysqli->real_escape_string($catId)
                );	
        
    }

    if ($result = $mysqli->query($query)) 
    {
        while($row = $result->fetch_object())
        {
            $name = htmlentities($row->name); 
            $categorySlug = $row->categorySlug;
            $description = $row->description;
            
            if(!is_null($row->sortOrder)) $sortOrder = $row->sortOrder;
            
            $metaTitle = htmlentities($row->metaTitle);
            $metaKeywords = htmlentities($row->metaKeywords);
            $metaDescription = htmlentities($row->metaDescription);
            
            if($type=='listing')
            {
                $schemaDotOrgType = $row->schemaDotOrgType;
                $parentId = $row->parentId;
            }
        }
    }
}

$mysqli->close();


$headerArgs['JS_Include']= '<script type="text/javascript" src="http://' . STATIC_URL . '/ckeditor/ckeditor.js"></script>
                            <script type="text/javascript" src="http://' . STATIC_URL . '/ckeditor/adapters/jquery.js"></script>';
$customConfig = 'http://' . STATIC_URL . '/ckeditor/min-config.js';

$headerArgs['extraJS'] = <<<HEADJS

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


$("#cancel").click(function(){
     window.parent.jQuery('#dialog').dialog('close');
});

var saving = false;
$('#save').click(function(){
    if($('#category-edit').valid())
    {
        $('#save').hide(function() {
            $("label[for='save']").show();
        });
        $.ajax({url: 'category-save-ajax.php', 
           type: 'POST',
           async: false,
            data: $("#category-edit").serialize(),
           success:	function(data){
                    if(data.result) {
                        window.parent.location.reload();
                    }
                    else {
                        if (data.exists) {
                            $(".errSummary").html('<ul><li>A category with that name already exists in the database.</li></ul>');
                            $(".errSummary").show();
                        }
                        else {
                            alert('An error occured saving this data');
                        }
                    }

                    $('#save').show(function() { 
                        $("label[for='save']").show();
                    });
                },
            error: function(jqXHR, textStatus, errorThrown) {
                
            },
            dataType:'json'
        });
    }
});

$('#category-edit').validate({
    errorPlacement: function(error, element) {
                        
     },
     messages: {
        name: {
            required: "Name is required",
            maxlength: jQuery.format("First Name can not exceed {0} characters in length.")
        },
        categorySlug: {
            required: "Category URL is required",
            maxlength: jQuery.format("Last Name can not exceed {0} characters in length.")
        }
     },
     rules:{
        name : {
            required:true,
            maxlength:255
        },
        categorySlug: {
            required: true,
            maxlength:255,
            remote:{
                url: "validation/category-validation-url.php",
                data: {
                    catid: function(){
                        return $("#catid").val();
                    },
                    catType: function(){
                        return $("#catType").val();
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
        return false;
    }
    });




HEADJS;

echo adminModalHeader($headerArgs);




?>
<form id="category-edit" name="category-edit" action="category-edit.php">
    <div class="errSummary errorSevere modalError" style="display:none;"></div>
    <input type="hidden" name="catType" id="catType" value="<?php echo $type?>" />
    <input type="hidden" name="catid" id="catid" value="<?php echo $catId?>" />
    <input type="hidden" name="parentid" id="parentid" value="<?php echo $parentId?>" />
    <input type="hidden" name="sortOrder" id="sortOrder" value="<?php echo $sortOrder?>" />
    <input type="hidden" name="submitted" id="submitted" value="1" />
 <div class="floatleft" style="width:450px;">
    <div class="fieldset">
        <h3>Category Info</h3>
        
        <div class="row">
            <div class="col1">
                <label for="name">Name:</label><br />
                <input name="name" id="name" type="text" value="<?php echo $name?>" />
            </div>
            <div class="col2">
                <label for="categorySlug">Category Url:</label><br />
                <input class="SeoUrl" name="categorySlug" id="categorySlug" type="text" value="<?php echo $categorySlug?>" />
            </div>
        </div>
        <div class="clearfloat"></div>
        <div class="row">
            <div class="colfull">
                <label for="description">Description:</label><br />
                <textarea name="description" id="description" class="ckeditor1"><?php echo $description?></textarea>
            </div>
        </div>
        <div class="clearfloat"></div>
        <div class="row" style="margin-top:15px;">
            <div class="colfull">
                <label for="sortOrder">Sort Weight:</label><br />
                <div id="slider"></div>
            </div>
        </div>
        <div class="clearfloat"></div>
    </div>
    
    <div class="fieldset" style="margin-top:10px;">
        <h3>SEO Info</h3>
        <div class="row">
            <div class="colfull">
                <label for="metaTitle">Page Title:</label><br />
                <input name="metaTitle" id="metaTitle" type="text" value="<?php echo $metaTitle?>" />
            </div>
        </div>
        <div class="row">
            <div class="colfull">
                <label for="metaDescription">Meta Description:</label><br />
                <textArea name="metaDescription" id="metaDescription" ><?php echo $metaDescription?></textArea>
            </div>
        </div>
        <div class="row">
            <div class="colfull">
                <label for="metaKeywords">Meta Keywords:</label><br />
                <textArea name="metaKeywords" id="metaKeywords" ><?php echo $metaKeywords?></textArea>
            </div>
        </div>
        <?php if($type=='listing'){?>
            <div class="row">
                <div class="colfull">
                    <label for="schemaDotOrgType"><a href="http://Schema.org/" target="_blank">Schema.org</a> Type:</label><br />
                    <input name="schemaDotOrgType" id="schemaDotOrgType" type="text" value="<?php echo $schemaDotOrgType?>" />
                </div>
            </div>
        <?php }?>
    </div>
</div>

    <div class="fieldset floatright templateGuide">
        <h3>Template Guide</h3>
        <div class="row">
            <p>Categories can be personalized between communities using template tags.  Template tags are in the form of <em>%TAGNAME%</em>, and can be used in any of the text fields.  Shown below are each tag and its corisponding value if viewing the category page in Hershey, PA:</p>
            <table>
            <tr><td>%SITE_STATIC_URL% => static.myreadylink.local</td></tr> 
            <tr class="alt"><td>%SITE_HOME_URL% => myreadylink.local</td></tr>
            <tr><td>%COMMUNITYID% => 2</td></tr>
            <tr class="alt"><td>%COMMUNITYNAME% => Hershey</td></tr> 
            <tr><td>%COMMUNITYAREANAME% => Hershey and Hummelstown-Derry Township</td></tr> 
            <tr class="alt"><td>%COMMUNITYDESCRIPTION% => This Hershey, PA community ....</td></tr>
            <tr><td>%COMMUNITYCOUNTYID% => 2257 </td></tr>
            <tr class="alt"><td>%COMMUNITYLATITUDE% => 40 </td></tr>
            <tr><td>%COMMUNITYLONGITUDE% => -77 </td></tr>
            <tr class="alt"><td>%COMMUNITYURL% => hershey-pa </td></tr>
            <tr><td>%COMMUNITYSORTORDER% => 0 </td></tr>
            <tr class="alt"><td>%COMMUNITYPARENTID% => 1 </td></tr>
            <tr><td>%COMMUNITYACTIVE% => 1 </td></tr>
            <tr class="alt"><td>%COMMUNITYMETATITLE% => Hershey, PA</td></tr> 
            <tr><td>%COMMUNITYMETAKEYWORDS% => Important Hershey PA ...</td></tr>
            <tr class="alt"><td>%COMMUNITYMETADESCRIPTION% => This Hershey, PA ...</td></tr>
            <tr><td>%COMMUNITYISFEATURED% => 1 </td></tr>
            <tr class="alt"><td>%COMMUNITYDELETED% => 0 </td></tr>
            <tr><td>%COMMUNITYCOUNTYNAME% => Dauphin County</td></tr> 
            <tr class="alt"><td>%COMMUNITYSTATEABBR% => PA </td></tr>
            <tr><td>%COMMUNITYSTATENAME% => Pennsylvania</td></tr> 
            <tr class="alt"><td>%COMMUNITYSTATEID% => 39 </td></tr>
            <tr><td>%COMMUNITYCOUNTRYID% => 1 </td></tr>
            <tr class="alt"><td>%COMMUNITYCOUNTRYNAME% => United States of America</td></tr> 
            <tr><td>%COMMUNITYCOUNTRYABBR% => US </td></tr>
            <tr class="alt"><td>%CATEGORYID% => 2 </td></tr>
            <tr><td>%CATEGORYNAME% => Food & Beverage</td></tr> 
            <tr class="alt"><td>%CATEGORYCATEGORYSLUG% => food-and-beverage</td></tr> 
            <tr><td>%CATEGORYDESCRIPTION% => Local Business Listings ...</td></tr> 
            <tr class="alt"><td>%CATEGORYMETATITLE% => Food & Beverage - Restaurants...</td></tr>
            <tr><td>%CATEGORYMETAKEYWORDS% => %COMMUNITYNAME% %COMMUNITYSTATEABBR% ....</td></tr>
            <tr class="alt"><td>%CATEGORYSCHEMADOTORGTYPE% => FoodEstablishment </td></tr>
            <tr><td>%CATEGORYPARENTID% => 1 </td></tr>
            <tr class="alt"><td>%CATEGORYSORTORDER% => 1 </td></tr>
            <tr><td>%CATEGORYACTIVE% => 1</td></tr>
            </table>
            
        
        </div>
    </div> 
<div class="clearboth"></div>
 
<div class="clearfloat"></div>
<div class="modalButtonWrapper">
    <a href="#Submit" id="save" class="submit buttonStyle"><?php echo $buttonText;?></a>
    <label for="save" class="saving" style="display: none;">Saving please wait</label>
    <a href="#Cancel" id="cancel" class="buttonStyle cancel" >Cancel</a>
</div>
</form>
<?php 
echo adminModalFooter(array());
?>