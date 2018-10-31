<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$active = '';

echo '<h3 class="my-community-site">Categories</h3><div class="clearfloat"></div>';


$query = sprintf("SELECT id, name, CAST(active AS unsigned integer) as active from tblnumbercategory where deleted = 0
                order by name"
                );
$output = '';
if ($result = $mysqli->query($query)) 
{
    $output = '<table class="my-community-numberCategories tablesorter">'. "\n" .'<thead><tr><th>Category</th><th style="width:30px;">Status</th><th class="center" style="width:30px;">Actions</th></thead>' . "\n" . '<tbody>';
    while($row = $result->fetch_object())
    {
        if($row->active == true)
        {
            $active = '<a href="javascript:void(0)" class="disable-number-category" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/active.gif" alt=""></a>';
        }
        else
        {
            $active = '<a href="javascript:void(0)" class="enable-number-category" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/inactive.gif" alt=""></a>';
        }

        
        $output .= '<tr><td><a href="javascript:void(0)" class="editNumCategory" rel="' . $row->id . '">' . htmlentities($row->name) . '</a></td><td class="center">' . $active . '</td><td class="center"><a href="javascript:void(0)" class="delete-number-category" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/admin/btnDelete.png" border="0" width="15" height="14" /></a></td></tr>' . "\n" ;
        
    }
}

$output .= '<tr><td colspan="3"><a href="javascript:void(0)" class="addNumCategory" rel="1">Add New Category</a></td></tr>';
$numOutput = $output . '</tbody></table>' . "\n";

$output = '';

$query = sprintf("SELECT id, name, cast(active AS unsigned integer) active from tbllistingcategory
                where parentId =1
                AND id != 1
                and deleted = 0
                order by name"
                );


$featured = '';
$parentActive = '';

if ($result = $mysqli->query($query)) 
{
    $output = '<table class="my-community-categories tablesorter">'. "\n" .'<thead><tr><th>Category</th><th>SubCategory</th><th>Status</th><th class="center">Actions</th></thead>' . "\n" . '<tbody>';
    while($row = $result->fetch_object())
    {
        if($row->active == true)
        {
            $active = '<a href="javascript:void(0)" class="disable-listing-category" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/active.gif" alt=""></a>';
        }
        else
        {
            $active = '<a href="javascript:void(0)" class="enable-listing-category" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/inactive.gif" alt=""></a>';
        }

        
        $output .= '<tr><td><a href="javascript:void(0)" class="editCategory" rel="' . $row->id . '">' . htmlentities($row->name) . '</a></td><td></td><td class="center">' . $active . '</td><td class="center"><a href="javascript:void(0)" class="delete-listing-category" rel="' . $row->id . '"><img src="http://' . STATIC_URL . '/images/admin/btnDelete.png" border="0" width="15" height="14" /></a></td></tr>' . "\n" ;

        $innerquery = sprintf("SELECT id, name, CAST(active AS unsigned integer) as active from tbllistingcategory
        where parentId = %d
        and deleted = 0
        order by name",
        $row->id);

        if ($innerresult = $mysqli->query($innerquery)) 
        {
            while($innerrow = $innerresult->fetch_object())
            {
                if($row->active == true)
                {
                    $parentActive = '';
                }
                else
                {
                    $parentActive = "parentNotActive";
                }
                
                if($innerrow->active == true)
                {
                    $active = '<a href="javascript:void(0)" class="disable-listing-category" rel="' . $innerrow->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/active.gif" alt=""></a>';
                }
                else
                {
                    $active = '<a href="javascript:void(0)" class="enable-listing-category" rel="' . $innerrow->id . '"><img src="http://' . STATIC_URL . '/images/icons/16/inactive.gif" alt=""></a>';
                }
                    
                $output .= '<tr class="' . $parentActive . '"><td></td><td><a href="javascript:void(0)" class="editCategory" rel="' . $innerrow->id . '">' . htmlentities($innerrow->name) . '</a></td><td class="center">' . $active . '</td><td class="center"><a href="javascript:void(0)" class="delete-listing-category" rel="' . $innerrow->id . '"><img src="http://' . STATIC_URL . '/images/admin/btnDelete.png" border="0" width="15" height="14" /></a></td></tr>' . "\n" ;
                
            }
            
        }
        $output .= '<tr><td>&nbsp;</td><td><a href="javascript:void(0)" class="addCategory" rel="' . $row->id. '">Add New Subcategory</a></td><td colspan="2">&nbsp;</td></tr>';
    }
    $output .= '<tr><td colspan="4"><a href="javascript:void(0)" class="addCategory" rel="1">Add New Category</a></td></tr>';
    $output .= '</tbody></table>' . "\n";

}


?>


<div id="categoryType">
    <h3><a href="#">Numbers</a></h3>
    <div>
        <?php echo $numOutput; ?>
    </div>
    <h3><a href="#">Listings</a></h3>
    <div>
        <?php echo $output; ?>
    </div>
</div>
