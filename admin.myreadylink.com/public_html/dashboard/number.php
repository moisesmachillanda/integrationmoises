<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
$headerArgs = array();

$headerArgs['JS_Include']= '
<script type="text/javascript" src="http://' . STATIC_URL . '/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="http://' . STATIC_URL. '/ckeditor/adapters/jquery.js"></script>
<link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet"/>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script type="text/javascript">
toastr.options = {
  "closeButton": true,
  "debug": false,
  "newestOnTop": true,
  "progressBar": false,
  "positionClass": "toast-bottom-right",
  "preventDuplicates": false,
  "onclick": null,
  "showDuration": "300",
  "hideDuration": "450",
  "timeOut": "2500",
  "extendedTimeOut": "1000",
  "showEasing": "swing",
  "hideEasing": "linear",
  "showMethod": "fadeIn",
  "hideMethod": "fadeOut"
};
</script>
';

echo get_AdminHeader($headerArgs);

$id='';
$added = false;
if(isset($_GET['id']) && is_numeric($_GET['id']))
    $id ='?id=' . $_GET['id'];
if (isset($_GET['added']) && $_GET['added'] == 'true')
    $added = true;
?>
<div id="tabslisting">
    <ul>
        <li><a href="number-info.php<?php echo $id;?>">Number</a></li>
        <?php
        //if($id !='')
        //{

        //echo '<li><a href="number-categories.php'. $id. '">Categories</a></li>';

        //}
        ?>
    </ul>
</div>

<?php if ($added) { ?>
<script type="text/javascript">
    $(document).ready(function () {
        toastr["success"]("Number created successfully.");
    });
</script>
<?php } ?>

<div id="dialog"></div>

<?php

echo get_AdminFooter(array());