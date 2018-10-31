<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$listingId = 0;
$communityId = 0;

if (isset($_REQUEST['listingId']) && is_numeric($_REQUEST['listingId'])) 
    $listingId = $_REQUEST['listingId'];

if (isset($_REQUEST['communityId']) && is_numeric($_REQUEST['communityId'])) 
    $communityId = $_REQUEST['communityId'];


$responseData = array(
    "listinglevel" => array("listingid" => null, "communityid" => null, "listinglevelid" => null, "start" => 0, "end" => 0), 
    "categories" => array()
);

$listingLevelQuery = sprintf('
    select distinct 
        listingid, 
        communityid,
        listinglevelid,
        case when (start is null) then 0 else unix_timestamp(start) end as `start`,
        case when (end is null) then 0 else unix_timestamp(end) end as `end`
    from tblcommunitylistingmap 
    where listingId = %1$d
      and communityid = %2$d
    ;',
    $listingId,
    $communityId
);

if ($result = $mysqli->query($listingLevelQuery)) {
    while ($row = $result->fetch_object()) {
        $responseData["listinglevel"]["listingid"] = $row->listingid;
        $responseData["listinglevel"]["communityid"] = $row->communityid;
        $responseData["listinglevel"]["listinglevelid"] = $row->listinglevelid;
        $responseData["listinglevel"]["start"] = (int)$row->start;
        $responseData["listinglevel"]["end"] = (int)$row->end;
    }
}


$query = sprintf("
    select id, name, cast(active AS unsigned integer) as active 
    from tbllistingcategory
    where parentId = 1
      and id != 1
      and active = 1
      and deleted = 0
    order by name
");

$parentCats = array();

if ($result = $mysqli->query($query)) {
    $outercount = 1;
    while ($row = $result->fetch_object()) {
        $subcats = array();
        $innerquery = sprintf('
            select distinct
                lc.id, 
                lc.name, 
                case when (cml.listingLevelId is not null) then true else false end as `assigned`,
                case when (altml.listingLevelId is not null) then true else false end as `assignedToOtherCommunity`
            from tbllistingcategory lc
            left outer join tblcommunitylistingmap cml on cml.listingCategoryId = lc.id and (cml.listingId = %2$d and cml.listingId in (select id from tbllisting)) and cml.communityId = %3$d
            left outer join tblcommunitylistingmap altml on altml.listingCategoryId = lc.id and (altml.listingId = %2$d and altml.listingId in (select id from tbllisting)) and altml.communityId != %3$d
            where lc.parentId = %1$d
              and lc.active = 1
              and lc.deleted = 0
            order by lc.name;
            ',
            $row->id,
            $listingId,
            $communityId
        );
            /*
            select lc.id, lc.name, cml.communityId, cml.listingId, listingLevelId, cml.start, cml.end 
            from tbllistingcategory lc
            left join tblcommunitylistingmap cml on cml.listingCategoryId = lc.id
            where lc.parentId = %d
              and lc.active = 1
              and lc.deleted = 0
            order by lc.name 
            */
        
        if ($innerresult = $mysqli->query($innerquery)) {
            $key = '';
            $count = $oldcount = 1;
            while ($innerrow = $innerresult->fetch_object()) {
                if (!array_key_exists($oldcount .'-' . $innerrow->id, $subcats)) {	
                    $oldcount = $count;
                    $key = $count .'-' . $innerrow->id;
                    $subcats[$key] = array(
                        'name' => $innerrow->name, 
                        'active' => ($innerrow->assigned || $innerrow->assignedToOtherCommunity ? true : false)
                    );
                    $count++;
                }
            }
                
        }

        $parentCats[$outercount . '-' . $row->name] = $subcats;
        $outercount++;
    }

    $responseData["categories"] = $parentCats;

}

echo json_encode($responseData);