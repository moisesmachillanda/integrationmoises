<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

echo get_AdminHeader(array());

$analytics = !isset($_GET['analytics']) ? "30" : $_GET['analytics'];
?>
<!--[if IE]>
<style type="text/css">
.all-listings th.comm div span
{
    height:25px;
    left:-3px;
    font-weight:normal;
}
</style>
<![endif]-->
<div id="tabs">
    <ul>
        <li><a href="dashboard.php?analytics=<?php echo $analytics; ?>&amp;deletedlisting=<?php echo (isset($_GET['deletedlisting'])) ? $_GET['deletedlisting'] : '0'; ?>">Dashboard</a></li>
        <li><a href="communities.php?analytics=<?php echo $analytics; ?>&amp;deletedlisting=<?php echo (isset($_GET['deletedlisting'])) ? $_GET['deletedlisting'] : '0'; ?>">Communities</a></li>
  <?php if($_SESSION['isSAdmin'] == true) { ?>
        <li><a href="categories.php">Categories</a></li>
        <li><a href="pages.php">Pages</a></li>
        <li><a href="users.php">Users</a></li>
  <?php } ?>
    </ul>
</div>

<div id="dialog"></div>
<?php 
echo get_AdminFooter(array());
?>
