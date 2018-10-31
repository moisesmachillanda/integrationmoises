<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
$commId = 0;


if(isset($_POST['submitted']))
{	
    $loggedData = array(
        'FUNCTION' => 'communityEdit',
    'name' => $_POST['name'],
    'description' => $_POST['description'],
    'countyId' => $_POST['county'],
    'lat' => $_POST['lat'],
    'lon' => $_POST['lon'],
    'URLslug' => $_POST['URLslug'],
    'sortOrder' => $_POST['sortOrder'],
    
    //'active' => $_POST['active'],
    //'isFeatured' => $_POST['isFeatured'],
    'metaTitle' => $_POST['metaTitle'],
    'metaKeywords' => $_POST['metaKeywords'],
    'metaDescription' => $_POST['metaDescription']
    );
    
    if(isset($_POST['parentId'])) $loggedData['parentId'] = $_POST['parentId'];
    
    if(isset($_POST['isFeatured'])) $isFeatured = 1;
    else $isFeatured = 0;
    if(isset($_POST['active'])) $active = 1;
    else $active = 0;
    
    $loggedData['isFeatured'] = $isFeatured;
    $loggedData['active'] = $active;

    if(isset($_POST['communityId']) && is_numeric($_POST['communityId'])) 
        $commId = $_POST['communityId']; 
    
    $communityoldslug = "";
    

    // region update
    if($commId > 0)
    {	
        // Get old slug
        $stmt = $mysqli->prepare("select url from tblcommunity where id = ?");
        $stmt->bind_param("i", $commId);
        $stmt->execute();
        $stmt->bind_result($communityoldslug);
        $stmt->fetch();
        $stmt->close();

        
        $loggedData['communityId'] = $commId;
        
        $query = sprintf("UPDATE tblcommunity set 
                        name = '%s',
                        description = '%s',
                        countyId = %d,
                        latitude = %f,
                        longitude = %f,
                        url = '%s',
                        sortOrder = %d,
                        parentId = %d,
                        active = %d,
                        isFeatured = %d,
                        metaTitle = '%s',
                        metaKeywords ='%s',
                        metaDescription ='%s',
                        ownerUID = %d
                        where
                        id=%d",
                            $mysqli->real_escape_string($_POST['name']),
                            $mysqli->real_escape_string($_POST['description']),
                            $mysqli->real_escape_string($_POST['county']),
                            $mysqli->real_escape_string($_POST['lat']),
                            $mysqli->real_escape_string($_POST['lon']),
                            $mysqli->real_escape_string($_POST['URLslug']),
                            $mysqli->real_escape_string($_POST['sortOrder']),
                            $mysqli->real_escape_string($_POST['parentid']),
                            $mysqli->real_escape_string($active),
                            $mysqli->real_escape_string($isFeatured),
                            $mysqli->real_escape_string($_POST['metaTitle']),
                            $mysqli->real_escape_string($_POST['metaKeywords']),
                            $mysqli->real_escape_string($_POST['metaDescription']),
                            $mysqli->real_escape_string($_POST['ownerUId']),
                            $mysqli->real_escape_string($commId)
                    );

        $mysqli->query($query);
        adminLog('update',$loggedData);
    }
    // endregion
    // region add new
    else //add new
    {
        $query = sprintf("INSERT into tblcommunity (
                        name,
                        description,
                        countyId,
                        latitude,
                        longitude,
                        url,
                        sortOrder,
                        parentId,
                        active,
                        isFeatured,
                        metaTitle,
                        metaKeywords,
                        metaDescription,
                        ownerUID)
                            values('%s','%s',%d,%f,%f,'%s',%d,%d,%d,%d,'%s','%s','%s',%d)",
                            $mysqli->real_escape_string($_POST['name']),
                            $mysqli->real_escape_string($_POST['description']),
                            $mysqli->real_escape_string($_POST['county']),
                            $mysqli->real_escape_string($_POST['lat']),
                            $mysqli->real_escape_string($_POST['lon']),
                            $mysqli->real_escape_string($_POST['URLslug']),
                            $mysqli->real_escape_string($_POST['sortOrder']),
                            $mysqli->real_escape_string($_POST['parentid']),
                            $mysqli->real_escape_string($active),
                            $mysqli->real_escape_string($isFeatured),
                            $mysqli->real_escape_string($_POST['metaTitle']),
                            $mysqli->real_escape_string($_POST['metaKeywords']),
                            $mysqli->real_escape_string($_POST['metaDescription']),
                            $mysqli->real_escape_string($_POST['ownerUId'])
                );

        $mysqli->query($query);
        $commId = $loggedData['communityId'] = $mysqli->insert_id;
        adminLog('insert',$loggedData);
        
        $additionalCommunities = array();
        
        if(isset($_POST['hdnAddCommunities']))
        {
            $additionalCommunities = explode(";",$_POST['hdnAddCommunities']);
                
            foreach($additionalCommunities as $newCommunity)
            {
                if(strlen($newCommunity) > 0)
                {
                    $query = sprintf("INSERT into tblcommunity (
                                    name,countyId,
                                    parentId,
                                    active)
                                    values('%s',%d,%d,%d)",
                    $mysqli->real_escape_string($newCommunity),
                    $mysqli->real_escape_string($_POST['county']),
                    $mysqli->real_escape_string($commId),
                    $mysqli->real_escape_string($active)
                    );
                    
                    $mysqli->query($query);
                    $loggedData = array();
                    $loggedData['FUNCTION'] = 'communityEdit';
                    $loggedData['communityId'] = $mysqli->insert_id;
                    $loggedData['parentId'] = $commId;
                    $loggedData['countyId'] = $_POST['county'];
                    $loggedData['active'] = $active;
                    
                    adminLog('insert',$loggedData);
                }
            }
        }
        
        
    }
    // endregion
    
    // region update slug history
    // update tblslugs
    $slugsquery = sprintf('
                -- only insert if changed
                insert into tblslugs (communityId, slug, isprimary, dateCreated)
                select %1$d, \'%2$s\', 1, now()
                from tblcommunity
                where id = %1$d
                    and \'%3$s\' != url
                    and \'%2$s\' not in (select slug from tblslugs where communityId = %1$d)
                ;

                -- reset  primary if changed
                update tblslugs set
                    isprimary = case 
                        when (slug = \'%2$s\') then 1 
                        else 0 
                    end,
                    dateModified = case
                        when (slug = \'%3$s\' and isprimary = 0) then now()
                        else dateModified
                    end
                where communityId = %1$d;
            ',
        $commId, 
        $mysqli->real_escape_string($_POST['URLslug']),
        $mysqli->real_escape_string($communityoldslug)
    );

    // Run the queries...
    if ($mysqli->multi_query($slugsquery)) {
        do {
            
        } while ($mysqli->next_result());
    }
    // endregion

    
    echo json_encode(array("result"=>true));
}
$mysqli->close();





?>