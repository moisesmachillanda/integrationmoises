<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

if (isset($_POST['submitted'])) {	
    $loggedData = array(
        'FUNCTION' => 'categoryEdit',
        'name' => $_POST['name'],
        'catid' => $_POST['catid'],
        'categorySlug' => $_POST['categorySlug'],
        'description' =>$_POST['description'],
        'metaTitle' =>$_POST['metaTitle'],
        'metaDescription' =>$_POST['metaDescription'],
        'metaKeywords' =>$_POST['metaKeywords'],
        'catType' => $_POST['catType'],
        'sortOrder' => $_POST['sortOrder']
        );
    
    if (isset($_POST['parentid'])) {
        $loggedData['parentid'] = $_POST['parentid'];
    }
    
    $query = '';
    
    $categoryId = $_POST['catid'];
    $categoryoldslug = "";
    if ($_POST['catType'] =='listing') {
        $canUpdateSlugs = true;
        $newCatExists = false;
        $loggedData['schemaDotOrgType'] = $_POST['schemaDotOrgType'];
        // region add listing category
        if ($_POST['catid'] == 0) {
            // Check if category exits... 
            $existsQuery = sprintf("select name from tbllistingcategory where lower(name) = lower('%s') and deleted = 0;", $mysqli->real_escape_string($_POST['name']));
            $existsResult = $mysqli->query($existsQuery);
            if ($existsResult->num_rows > 0) {
                $canUpdateSlugs = false;
                $newCatExists = true;
            }

            if ($newCatExists == false) {
                //add listing category
                $query = sprintf("INSERT into tbllistingcategory (name, categorySlug,description,metaTitle, metaDescription, metaKeywords, schemaDotOrgType,parentId,sortOrder,active) values ('%s','%s','%s','%s','%s','%s','%s',%d,%d,1); ",
                $mysqli->real_escape_string($_POST['name']),
                $mysqli->real_escape_string($_POST['categorySlug']),
                $mysqli->real_escape_string($_POST['description']),
                $mysqli->real_escape_string($_POST['metaTitle']),
                $mysqli->real_escape_string($_POST['metaDescription']),
                $mysqli->real_escape_string($_POST['metaKeywords']),
                $mysqli->real_escape_string($_POST['schemaDotOrgType']),
                $mysqli->real_escape_string($_POST['parentid']),
                $mysqli->real_escape_string($_POST['sortOrder'])			
                );
                
                $mysqli->query($query);
                
                $loggedData['catid'] = $categoryId = $mysqli->insert_id;
                adminLog('insert',$loggedData);
            }
        }
        // endregion
        // region update listing category
        else
        {
            // Get old slug
            $stmt = $mysqli->prepare("select categorySlug from tbllistingcategory where id = ?");
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $stmt->bind_result($categoryoldslug);
            $stmt->fetch();
            $stmt->close();

            
            //update listing category here
            $query = sprintf("UPDATE tbllistingcategory set name='%s', categorySlug = '%s', description = '%s', metaTitle = '%s', metaDescription='%s', metaKeywords='%s', schemaDotOrgType='%s',parentId=%d,sortOrder=%d
                              WHERE id=%d",
            $mysqli->real_escape_string($_POST['name']),
            $mysqli->real_escape_string($_POST['categorySlug']),
            $mysqli->real_escape_string($_POST['description']),
            $mysqli->real_escape_string($_POST['metaTitle']),
            $mysqli->real_escape_string($_POST['metaDescription']),
            $mysqli->real_escape_string($_POST['metaKeywords']),
            $mysqli->real_escape_string($_POST['schemaDotOrgType']),
            $mysqli->real_escape_string($_POST['parentid']),
            $mysqli->real_escape_string($_POST['sortOrder']),
            $mysqli->real_escape_string($_POST['catid'])
            );
            $mysqli->query($query);
            adminLog('update',$loggedData);

        }
        // endregion
        
        // region update slug history
        // update tblslugs
        if ($canUpdateSlugs) {
            $slugsquery = sprintf('
                -- only insert if changed
                insert into tblslugs (listingCategoryId, slug, isprimary, dateCreated)
                select %1$d, \'%2$s\', 1, now()
                from tbllistingcategory
                where id = %1$d
                    and \'%3$s\' != categorySlug
                    and \'%2$s\' not in (select slug from tblslugs where listingCategoryId = %1$d)
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
                where listingCategoryId = %1$d;
            ',
                $categoryId, 
                $mysqli->real_escape_string($_POST['slug']),
                $mysqli->real_escape_string($categoryoldslug)
            );

            // Run the queries...
            if ($mysqli->multi_query($slugsquery)) {
                do {
                    
                } while ($mysqli->next_result());
            }
        }
        // endregion
        
        if (!$newCatExists) {
            echo json_encode(array("result"=>true));
        }
        else {
            echo json_encode(array("result"=>false,"exists"=>true));
        }
       
        
    }
    else if ($_POST['catType'] =='number') {
        $canUpdateSlugs = true;
        $newCatExists = false;

        // region add number category
        if($_POST['catid'] == 0)
        {
            $existsQuery = sprintf("select name from tblnumbercategory where lower(name) = lower('%s');", $mysqli->real_escape_string($_POST['name']));
            $existsResult = $mysqli->query($existsQuery);
            if ($existsResult->num_rows > 0) {
                $canUpdateSlugs = false;
                $newCatExists = true;
            }

            if ($newCatExists == false) {

                $query = sprintf("INSERT into tblnumbercategory (name, categorySlug, description, metaTitle, metaDescription, metaKeywords, sortOrder, active)
                                    values('%s','%s','%s','%s','%s','%s',%d,1)",
                $mysqli->real_escape_string($_POST['name']),
                $mysqli->real_escape_string($_POST['categorySlug']),
                $mysqli->real_escape_string($_POST['description']),
                $mysqli->real_escape_string($_POST['metaTitle']),
                $mysqli->real_escape_string($_POST['metaDescription']),
                $mysqli->real_escape_string($_POST['metaKeywords']),
                $mysqli->real_escape_string($_POST['sortOrder'])
                );
                $mysqli->query($query);
                $loggedData['catid'] = $categoryId = $mysqli->insert_id;
                adminLog('insert',$loggedData);
            }
        }
        // endregion
        // region update number category
        else
        {
            // Get old slug
            $stmt = $mysqli->prepare("select categorySlug from tblnumbercategory where id = ?");
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $stmt->bind_result($categoryoldslug);
            $stmt->fetch();
            $stmt->close();

            //update number category here
            $query = sprintf("UPDATE tblnumbercategory set name='%s', categorySlug = '%s', description = '%s', metaTitle = '%s', metaDescription='%s', metaKeywords='%s', sortOrder=%d
                                          WHERE id=%d",
            $mysqli->real_escape_string($_POST['name']),
            $mysqli->real_escape_string($_POST['categorySlug']),
            $mysqli->real_escape_string($_POST['description']),
            $mysqli->real_escape_string($_POST['metaTitle']),
            $mysqli->real_escape_string($_POST['metaDescription']),
            $mysqli->real_escape_string($_POST['metaKeywords']),
            $mysqli->real_escape_string($_POST['sortOrder']),
            $mysqli->real_escape_string($_POST['catid'])
            );
            $mysqli->query($query);
            adminLog('update',$loggedData);
            
        }
        // endregion

        // region update slug history
        // update tblslugs
        if ($canUpdateSlugs) {
            $slugsquery = sprintf('
                -- only insert if changed
                insert into tblslugs (numberCategoryId, slug, isprimary, dateCreated)
                select %1$d, \'%2$s\', 1, now()
                from tblnumbercategory
                where id = %1$d
                    and \'%3$s\' != categorySlug
                    and \'%2$s\' not in (select slug from tblslugs where numberCategoryId = %1$d)
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
                where numberCategoryId = %1$d;
            ',
                $categoryId, 
                $mysqli->real_escape_string($_POST['categorySlug']),
                $mysqli->real_escape_string($categoryoldslug)
            );

            // Run the queries...
            if ($mysqli->multi_query($slugsquery)) {
                do {
                    
                } while ($mysqli->next_result());
            }
            // endregion
        }

        if (!$newCatExists) {
            echo json_encode(array("result"=>true));
        }
        else {
            echo json_encode(array("result"=>false,"exists"=>true));
        }
        
    }
}
else {
    echo json_encode(array("result"=>false));	
}
