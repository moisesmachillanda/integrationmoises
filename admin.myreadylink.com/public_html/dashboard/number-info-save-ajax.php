<?php

require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";

requireLogin();

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

if(isset($_POST['submitted']))
{
    $loggedData = array(
        'FUNCTION' => 'listingEdit',
        'name' => $_POST['name'],
        'phoneNumber' => $_POST['phone'],
        'faxNumber' => $_POST['fax'],
        'address1' => $_POST['address1'],
        'address2' => $_POST['address2'],
        'city' => $_POST['city'],
        'state' => $_POST['state'],
        'zip' => $_POST['zip'],
        'email' => $_POST['email'],
        'website' => $_POST['website'],
        'description' => $_POST['description'],
        'hours' => $_POST['hours'],
        'metatitle' => $_POST['metatitle'],
        'metakeywords' => $_POST['metakeywords'],
        'metadescription' => $_POST['metadescription'],
        'numberId' => $_POST['numberid'],
        'seoName' => $_POST['slug'],
        'SEOImageAltText' => $_POST['imagealttext']
    );

    if(isset($_POST['enabled']))
    {
        $loggedData['active'] = false;
        $active = 0;
    }
    else
    {
        $loggedData['active'] = true;
        $active = 1;
    }

    $numberId = $_POST['numberid'];
    $numberoldslug = "";
    $query = "";

    // region update
    if($numberId > 0)
    {
        // Get old slug
        $stmt = $mysqli->prepare("select SEOName from tblnumber where id = ?");
        $stmt->bind_param("i", $numberId);
        $stmt->execute();
        $stmt->bind_result($numberoldslug);
        $stmt->fetch();
        $stmt->close();

        $query = sprintf("
            update tblnumber set
                name = '%s',
                phoneNumber= '%s',
                faxNumber= '%s',
                address1= '%s',
                address2= '%s',
                city= '%s',
                stateId= %s,
                zip= '%s',
                email= '%s',
                website= '%s',
                description= '%s',
                hoursOfService= '%s',
                active= %d,
                metatitle= '%s',
                metakeywords= '%s',
                metadescription= '%s',
                SEOName = '%s',
                SEOImageAltText = '%s'
            where id = %d",
            $mysqli->real_escape_string($_POST['name']),
            $mysqli->real_escape_string($_POST['phone']),
            $mysqli->real_escape_string($_POST['fax']),
            $mysqli->real_escape_string($_POST['address1']),
            $mysqli->real_escape_string($_POST['address2']),
            $mysqli->real_escape_string($_POST['city']),
            empty($_POST['state']) ? 'null' : $mysqli->real_escape_string($_POST['state']),
            $mysqli->real_escape_string($_POST['zip']),
            $mysqli->real_escape_string($_POST['email']),
            $mysqli->real_escape_string($_POST['website']),
            $mysqli->real_escape_string($_POST['description']),
            $mysqli->real_escape_string($_POST['hours']),
            $mysqli->real_escape_string($active),
            $mysqli->real_escape_string($_POST['metatitle']),
            $mysqli->real_escape_string($_POST['metakeywords']),
            $mysqli->real_escape_string($_POST['metadescription']),
            $mysqli->real_escape_string($_POST['slug']),
            $mysqli->real_escape_string($_POST['imagealttext']),
            $mysqli->real_escape_string($numberId)
        );

        $mysqli->query($query);

        adminLog('update',$loggedData);
    }
    // endregion
    // region add new
    else //add new
    {
        $query = sprintf("
            insert into tblnumber (
                name, phoneNumber, faxNumber, address1, address2, city, stateId, zip, email, website, description, hoursOfService, active, metaTitle, metaKeywords, metaDescription, SEOName, SEOImageAltText
            ) values (
                '%s', '%s', '%s', '%s', '%s', '%s', %s, '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s'
            );",
            $mysqli->real_escape_string($_POST['name']),
            $mysqli->real_escape_string($_POST['phone']),
            $mysqli->real_escape_string($_POST['fax']),
            $mysqli->real_escape_string($_POST['address1']),
            $mysqli->real_escape_string($_POST['address2']),
            $mysqli->real_escape_string($_POST['city']),
            empty($_POST['state']) ? 'null' : $mysqli->real_escape_string($_POST['state']),
            $mysqli->real_escape_string($_POST['zip']),
            $mysqli->real_escape_string($_POST['email']),
            $mysqli->real_escape_string($_POST['website']),
            $mysqli->real_escape_string($_POST['description']),
            $mysqli->real_escape_string($_POST['hours']),
            $mysqli->real_escape_string($active),
            $mysqli->real_escape_string($_POST['metatitle']),
            $mysqli->real_escape_string($_POST['metakeywords']),
            $mysqli->real_escape_string($_POST['metadescription']),
            $mysqli->real_escape_string($_POST['slug']),
            $mysqli->real_escape_string($_POST['imagealttext'])
        );

        $mysqli->query($query);

        // Log it
        $loggedData['numberid'] = $numberId = $mysqli->insert_id;
        adminLog('insert',$loggedData);

        // insert a default category mapping
        $category_query = sprintf(
            "INSERT INTO tblnumbercategorymap (numberId, categoryId, communityId) values(%d,%d,%d)",
            $mysqli->real_escape_string($numberId),
            $mysqli->real_escape_string($_POST['categoryId']),
            $mysqli->real_escape_string($_POST['communityId'])
        );

        $mysqli->query($category_query);

        $loggedData = array(
            'FUNCTION' => 'numberCategoryAdd',
            'numberId' => $numberId,
            'categoryId' => $_POST['categoryId'],
            'communityId' => $_POST['communityId']
        );
        adminLog('insert',$loggedData);
    }
    // endregion

    // region update slug history
    // update tblslugs
    $slugsquery = sprintf('
                -- only insert if changed
                insert into tblslugs (numberId, slug, isprimary, dateCreated)
                select %1$d, \'%2$s\', 1, now()
                from tblnumber
                where id = %1$d
                    and \'%3$s\' != SEOName
                    and \'%2$s\' not in (select slug from tblslugs where numberId = %1$d)
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
                where numberId = %1$d;
            ',
        $numberId,
        $mysqli->real_escape_string($_POST['slug']),
        $mysqli->real_escape_string($numberoldslug)
    );

    // more_results()

    // Run the queries...
    if ($mysqli->multi_query($slugsquery)) {
        do {
            $mysqli->next_result();
        }
        while ($mysqli->more_results());
    }
    // endregion

    echo json_encode(array(
        "result" => true,
        "numberId" => $numberId,
        "query" => $query
    ));
}