<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$headerArgs = array(); 
$STATIC_URL = STATIC_URL;

$userId = 0;

if(isset($_GET['userId']) && is_numeric($_GET['userId'])) $userId = $_GET['userId']; 

$buttonText = "Add User";
$first = $last = $phone = $fax = $address1 = $address2 = $city = $state = $zip = $email = $password = $passwordconfirm = '';
$active = 0;
$checkstate = 0;

if($userId > 0)
{
	$buttonText = "Save User";
	
	$query = sprintf("select * 
							from tbluser 
							where
							deleted = 0
							AND id=%d
							limit 1",
				$mysqli->real_escape_string($userId)
				);
		if ($result = $mysqli->query($query)) 
		{
			while($row = $result->fetch_object())
			{
				$first = htmlentities($row->firstName); 
				$last = htmlentities($row->lastName);
				$phone = htmlentities($row->phone);
				$fax = htmlentities($row->fax);
				$address1 = htmlentities($row->address1);
				$address2 = htmlentities($row->address2);
				$city = htmlentities($row->city);
				$state = $row->state;
				$zip = htmlentities($row->zip);
				$email = htmlentities($row->email);
				$active = $row->active;
				$password = $passwordconfirm = "PASSWORDNOTCHANGED";
			}
		}
		

	$query = sprintf("Select c.name, urm.userId as userId ,(
			                select count(*) from tbluserrightsmap 
			                where userId = %d
			                ) as hasChildSelected
			        from tblcountry c
					left outer join tbluserrightsmap urm on c.id = urm.countryid
					where  c.id = 1 ",
				$mysqli->real_escape_string($userId)
				);
	if ($result = $mysqli->query($query)) 
	{
		while($row = $result->fetch_object())
		{
			if($row->userId == $userId)
			{
				$checkstate = 1;
				break;
			}
			else if($row->hasChildSelected > 0)
			{
				$checkstate = 2;
			}			
		}
	}
	
	
}
$mysqli->close();

$headerArgs['extraJS'] = <<<HEADJS
function loadTree() {        
   var o = {showcheck: true,
        onnodeclick:function(item){alert(item.text);},          
   		url: "data/communityHeir.php?uid=$userId",
   		cbiconpath : 'http://$STATIC_URL/images/admin/tree/',
   		oncheckboxclick: function(  tree,  item,  status){

   			//we are checking it here
   			if(tree.checkstate == 0)
   			{
   				var nodesRemoved = $("#nodesRemoved").val();
   				var newNodesRemoved = '';
   				arrRemoved = nodesRemoved.split(';')
   				
   				for (i=0; i <= arrRemoved.length; i++){
   					if(arrRemoved[i] != tree.value && arrRemoved[i]){
   						newNodesRemoved += arrRemoved[i] + ';'; 
   					}
   				}
   				
   				$("#nodesRemoved").val(newNodesRemoved);
   				
   				var toAdd = '';
   				if($("#nodesAdded").val()){
   					toAdd = $("#nodesAdded").val();
   				}

   				if(toAdd.indexOf(tree.value) == -1){
   					$("#nodesAdded").val(toAdd + tree.value + ';');
   				}
   				
   			}

   			//we are unchecking it here
   			if(tree.checkstate == 1)
   			{
   				var nodesAdded = $("#nodesAdded").val()
   				var newNodesAdded = '';
   				arrAdded = nodesAdded.split(';')
   				
   				for (i=0; i <= arrAdded.length; i++){
   					if(arrAdded[i] != tree.value && arrAdded[i]){
   						newNodesAdded += arrAdded[i] + ';'; 
   					}
   				}
   				
   				$("#nodesAdded").val(newNodesAdded);
   				
   				var toRemove = $("#nodesRemoved").val();
   				
   				if(toRemove.indexOf(tree.value) == -1){
   					$("#nodesRemoved").val(toRemove + tree.value +';');
   				}
   			}
   		}
    };
    
    o.data = [ {
        "id" : "country-1",
        "text" : "Full Admin",
        "value" : "country-1",
        "showcheck" : true,
        "complete" : false,
        "isexpand" : false,
        "checkstate" : $checkstate,
        "hasChildren" : true
    }];
                      
    $("#tree").treeview(o);
                        
    $("#reflashnode10").click(function(e) {
    	$("#tree").reflash("root-0"); //"root-0" is id of the node to be reloaded
	});

}
loadTree(); 

$("#cancel").click(function(){
	 window.parent.jQuery('#dialog').dialog('close');
});

$('#save').click(function(){
	if($('#user-edit').valid())
	{
		$.ajax({url: 'user-save-ajax.php', 
		   type: 'POST',
		   async: false,
			data: $("#user-edit").serialize(),
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

$('#user-edit').validate({
	errorPlacement: function(error, element) {
                        
     },
     messages: {
        first: {
        	required: "First Name is required",
        	maxlength: jQuery.format("First Name can not exceed {0} characters in length.")
        },
        last: {
        	required: "Last Name is required",
        	maxlength: jQuery.format("Last Name can not exceed {0} characters in length.")
        },
        email: {
        	email: "Must be a valid Email Address",
        	required: "Email Address is required",
        	remote: "That email address is already in use by another user"
        },
        password:{ 
        	required: "Password is required",
        	minlength: jQuery.format("Password must be at least {0} characters in length.")
        },
        passwordconfirm:{
        	equalTo: "Password confirm must match password."
        }
     },
     rules:{
        password : {
            required:true,
            minlength:5
        },
        passwordconfirm: {
        	equalTo: "#password"
        },
        email: {
        	required: true,
        	email: true,
        	maxlength:255,
        	remote:{
        		url: "validation/user-validation-email.php",
        		data: {
        			myuid: function(){
						return $("#uid").val();
					}
        		}
        	}
        },
        first:{
        	maxlength:255,
        	required:true
        },
        last:{
        	maxlength:255,
        	required:true
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




HEADJS;

echo adminModalHeader($headerArgs);




?>
<form id="user-edit" name="user-edit" action="user-edit.php">
	<div class="errSummary errorSevere modalError" style="display:none;"></div>
	<input type="hidden" name="submitted" id="submitted" value="1" />
	<input type="hidden" name="uid" id="uid" value="<?php echo $userId?>" />
 <div class="floatleft">
	<div class="fieldset">
		<h3>User Info</h3>
		
		<div class="row">
			<div class="col1">
				<label for="first">First Name:</label><br />
				<input name="first" id="first" type="text" value="<?php echo $first?>" />
			</div>
			<div class="col2">
				<label for="last">Last Name:</label><br />
				<input name="last" id="last" type="text" value="<?php echo $last?>" />
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
				<label for="address">Address:</label><br />
				<input name="address" id="address" type="text" value="<?php echo $address1?>" />
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
			<div class="col1">
				<label for="city">City:</label><br />
				<input name="city" id="city" type="text" value="<?php echo $city?>" />
			</div>
			<div class="col2">
				<label for="state">State:</label><br />
				<select id="state" name="state"><?php echo get_state_list($state)?></select>
			</div>
		</div>
		<div class="clearfloat"></div>
		<div class="row">
			<div class="col1">
				<label for="zip">Zip:</label><br />
				<input name="zip" id="zip" type="text" value="<?php echo $zip?>" />
			</div>
			<div class="col2">&nbsp;<br />&nbsp;</div>
		</div>
		<div class="clearfloat"></div>
	</div>
	
	<div class="fieldset">
		<h3>Login Info</h3>
		<div class="row">
			<div class="colfull">
				<label for="email">Email:</label><br />
				<input name="email" id="email" type="text" value="<?php echo $email?>" />
			</div>
		</div>
		<div class="row">
			<div class="colfull">
				<label for="password">Password:</label><br />
				<input name="password" id="password" type="password" value="<?php echo $password?>" />
			</div>
		</div>
		<div class="row">
			<div class="colfull">
				<label for="passwordconfirm">Password Confirm:</label><br />
				<input name="passwordconfirm" id="passwordconfirm" type="password" value="<?php echo $passwordconfirm?>" />
			</div>
		</div>
	</div>
</div>
	<div class="fieldset floatright userAccess">
		<h3>Access Level</h3>
		<div id="tree">
        	    
        </div>
	</div> 
<div class="clearboth"></div>
<input type="hidden" name="nodesAdded" id="nodesAdded" value="" />
<input type="hidden" name="nodesRemoved" id="nodesRemoved" value="" />


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