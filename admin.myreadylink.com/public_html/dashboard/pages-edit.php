<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$headerArgs = array(); 
$output = '';

if(isset($_GET['pageKey']) && !empty($_GET['pageKey'])) $pageKey = $_GET['pageKey']; 

if(!empty($pageKey))
{
	$buttonText = "Save Page";
	
	$query = sprintf("select * 
							from tblmetadata 
							where
							keyName like '%s'
					",
				$mysqli->real_escape_string($pageKey . '%')
				);
		if ($result = $mysqli->query($query)) 
		{
			while($row = $result->fetch_object())
			{
				$derivedKey = ucwords(strtolower(str_replace('_',' ', str_replace($pageKey, '' ,$row->keyName))));
				
				if(strpos($row->keyName,'CONTENT'))
				{
					$output .= '<div class="row">' .
								'	<div class="colfull">' .
								'		<label for="' . $row->keyName . '">' . $derivedKey . ':</label><br />' .
								'		<textArea class="ckeditor1" name="' . $row->keyName . '" id="' . $row->keyName . '" >' . $row->value . '</textArea>' .
								'	</div>' .
								'</div>';
				}
				else if(strpos($row->keyName,'META_KEYWORDS') || strpos($row->keyName,'META_DESCRIPTION'))
				{
					$output .= '<div class="row">' .
								'	<div class="colfull">' .
								'		<label for="' . $row->keyName . '">' . $derivedKey . ':</label><br />' .
								'		<textArea style="height:150px;width:588px;" name="' . $row->keyName . '" id="' . $row->keyName . '" >' . $row->value . '</textArea>' .
								'	</div>' .
								'</div>';
				}
				else
				{
					$output .= '<div class="row">' .
								'	<div class="colfull">' .
								'		<label for="' . $row->keyName . '">' . $derivedKey . ':</label><br />' .
								'		<input name="' . $row->keyName . '" id="' . $row->keyName . '" type="text" value="' . $row->value . '" />' .
								'	</div>' .
								'</div>';
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
    	customConfig: '$customConfig'
    }
);
            
$("#cancel").click(function(){
 	window.parent.jQuery('#dialog').dialog('close');
});
			
$('#save').click(function(){
	
	$.ajax({url: 'pages-save-ajax.php', 
	   type: 'POST',
	   async: false,
		data: $("#pages-edit").serialize(),
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
	
});
HEADJS;

echo adminModalHeader($headerArgs);




?>
<form id="pages-edit" name="pages-edit" action="pages-edit.php">
	<div class="errSummary errorSevere modalError" style="display:none;"></div>
	<input type="hidden" name="submitted" id="submitted" value="1" />
	<input type="hidden" name="pageKey" id="pageKey" value="<?php echo $pageKey;?>" />
 <div class="floatleft" style="width:615px;">
	<div class="fieldset">
		<h3>Page Info</h3>
		
<?php echo $output; ?>
	</div>
</div>

<div class="clearfloat"></div>
<div class="modalButtonWrapper">
	<a href="#Submit" id="save" class="submit buttonStyle"><?php echo $buttonText;?></a>
	<!--<input type="submit" value="<?php echo $buttonText;?>" class="submit buttonStyle" />-->
	<a href="#Cancel" id="cancel" class="buttonStyle cancel" >Cancel</a>
</div>
</form>
<?php 
echo adminModalFooter(array());
?>