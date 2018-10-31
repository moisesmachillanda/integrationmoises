<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

require_once "../../../includes/app/bootstrap.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$id='';
$added = false;

$communityId = 0;
$listing = null;
$stateId = 1;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id ='?id=' . $_GET['id'];

    $listingId = $_GET['id'];

    // Get Listing Information:
    $query = sprintf("
        select CAST(l.active AS unsigned integer) as active1, l.*, s.name as stateName, s.abbr as stateAbbr, s.id as stateId
        from tbllisting l
        left join tblstate s on s.id = l.stateId
        where l.id = %d
        limit 1",
    $mysqli->real_escape_string($listingId)
    );

    if ($result = $mysqli->query($query))
    {
        while($row = $result->fetch_object())
        {
            $listing = $row;
            // gather category metaStuff
            $currentPage = $name = htmlentities(trim($row->name));

            $stateId = $listing->stateId;
        }
    }

    // Listing assigned Communities
    $communityOptions = '';
    $query = sprintf('
    select distinct c.id, c.name as name, state.abbr as stateAbbr
    from tblcommunity c
    left join tblcounty county on county.id = c.countyId
    left join tblstate state on state.id = county.stateId
    left join tblcountry country on country.id = state.countryId
    inner join tbluserrightsmap rightsMap  on (rightsMap.communityId = c.id OR rightsMap.countyId = county.id OR rightsMap.stateId = state.Id OR rightsMap.countryId = country.Id)
    inner join tblcommunitylistingmap map on map.communityId = c.id and map.listingId = %2$d
    where c.active = 1
        and c.deleted = 0
        and c.parentId = 1
        and c.id != 1
        and rightsMap.userId = %1$d
    order by c.name
    ',
        $mysqli->real_escape_string($_SESSION['uid']),
        $mysqli->real_escape_string($listingId)
    );

    if ($result = $mysqli->query($query)) {
        while($row = $result->fetch_object()) {
            // $communityOptions .= ' <option value="' . $row->id . '">' . $row->name . '</option>';
            $communityOptions .= '<label><input type="checkbox" id="location_communityids_' . $row->id . '" name="communityids[]" value="' . $row->id . '" /> ' . $row->name . '</label><br />';
        }
    }
}

// Inbound links from notification email:
if (isset($_GET['communityid']) && is_numeric($_GET['communityid'])) {
    setcookie("viewCommunity", $_GET['communityid'], 0, "/dashboard/");
}

if (isset($_GET['added']) && $_GET['added'] == 'true')
    $added = true;


$templateData = array(
    'site' => array(
        'homeurl' => "//" . HOME_URL,
        'staticurl' => "//" . STATIC_URL,
        'scriptversion' => VERSIONSTRING,
        'googleanalyticsid' => GOOGLE_ANALTYICS_ID,
        'user' => array('isAuthenticated' => true),

    ),
    'data' => array(
        'headerJS' => '
<script type="text/javascript" src="http://' . STATIC_URL . '/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="http://' . STATIC_URL . '/ckeditor/adapters/jquery.js"></script>
<script type="text/javascript" src="http://' . STATIC_URL . '/js/swfobject.js"></script>
<script type="text/javascript" src="http://' . STATIC_URL . '/js/jquery.uploadify.v2.1.4.min.js"></script>
<link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet"/>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery-scrollTo/1.4.14/jquery.scrollTo.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.6/moment.min.js"></script>
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
',
    'added' => (isset($_GET['added']) && $_GET['added'] == 'true') ? true : false,
    'id' => (isset($_GET['id']) && is_numeric($_GET['id'])) ? '?id=' . $_GET['id'] : '',
    'isAdmin' =>  (isset($_SESSION['isSAdmin']) && $_SESSION['isSAdmin']) ? true : false,
    'isAdminOrAffiliate' => ((isset($_SESSION['isSAdmin']) && $_SESSION['isSAdmin']) || (isset($_SESSION['isAffiliate']) && $_SESSION['isAffiliate']))  ? true : false,
    'listing' => $listing,
    'communityOptions' => $communityOptions,
    'stateOptions' => get_state_list($stateId)
    )
);



$mysqli->close();

$twigLoader = new Twig_Loader_Filesystem("../../../includes/app/views/admin");
$twigLoader->addPath(APP_BASE_PATH . "/views/admin/", "admin");
$twig = new Twig_Environment($twigLoader, array('debug' => ENV == 'DEV' ? true : false, 'cache' => APP_BASE_PATH . '/cache/views/admin', 'auto_reload' => true));
$twig->addGlobal("site", $templateData['site']);

echo $twig->render('@admin/default/dashboard/listings/details.twig.php', $templateData);
?>
