<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";
require_once "../../../includes/app/bootstrap.php";


requireLogin();

// --- Added SparkPost via cURL ---
function sparkpost($method, $uri, $payload = [], $headers = [])
{
    $defaultHeaders = [ 'Content-Type: application/json' ];
    $curl = curl_init();
    $method = strtoupper($method);
    $finalHeaders = array_merge($defaultHeaders, $headers);
    $url = 'https://api.sparkpost.com:443/api/v1/'.$uri;
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    if ($method !== 'GET') {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $finalHeaders);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}

$sparkHeaders = [ 'Authorization: 3646dbb03a38df0d55441775155e745259338628' ];
$mailHeader = "http://www.myreadylink.com/images/mailHeader.png";

function rmTags($data){
  $string = strip_tags($data, '<p><a><ul><ol><li><strong><i><b>');
  return $string;
}


$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

if (isset($_POST['submitted']) && ((isset($_SESSION['isSAdmin']) && $_SESSION['isSAdmin']) || (isset($_SESSION['isAffiliate']) && $_SESSION['isAffiliate']))) {
    $show_specials = 0;
    if (key_exists('show_specials', $_POST) && $_POST['show_specials'] == 1){
        $show_specials = 1;
    }

    $loggedData = array(
        'FUNCTION' => 'listingEdit',
        'name' => $_POST['name'],
        'contactPhone' => $_POST['phone'],
        'contactFax' => $_POST['fax'],
        'address1' => $_POST['address1'],
        'address2' => $_POST['address2'],
        'city' => $_POST['city'],
        'state' => $_POST['state'],
        'zip' => $_POST['zip'],
        'description' => $_POST['description'],
        'products' => $_POST['products'],
        'services' => $_POST['services'],
        'specials' => $_POST['specials'],
        'show_specials' => $show_specials,
        'contactname' => $_POST['contactname'],
        'contactemail' => $_POST['contactemail'],
        'website' => $_POST['website'],
        'website_facebook' => $_POST['website_facebook'],
        'website_twitter' => $_POST['website_twitter'],
        'website_linkedin' => $_POST['website_linkedin'],
        'hoursofoperation' => $_POST['hours'],
        'metatitle' => $_POST['metatitle'],
        'metakeywords' => $_POST['metakeywords'],
        'metadescription' => $_POST['metadescription'],
        'listingId' => $_POST['listingid'],
        'SEOName' => $_POST['slug'],
        'SEOImageAltText' => $_POST['imagealttext'],
        'OwnerUID' => $_POST['ownerUID'],
        'geolatitude' => $_POST['geolatitude'],
        'geolongitude' => $_POST['geolongitude']
    );

    $listingQuery = "";

    if (isset($_POST['enabled'])) {
        $loggedData['active'] = false;
        $active = 0;
    }
    else
    {
        $loggedData['active'] = true;
        $active = 1;
    }

    $listingId = $_POST['listingid'];
    $listingoldslug = "";

    // region update
    if ($listingId > 0) {
        // Get old slug
        $stmt = $mysqli->prepare("select SEOName from tbllisting where id = ?");
        $stmt->bind_param("i", $listingId);
        $stmt->execute();
        $stmt->bind_result($listingoldslug);
        $stmt->fetch();
        $stmt->close();

        if ($_POST['listingpassword'] == 'PASSWORDDIDNOTCHANGE') {
            $listingQuery = sprintf("
                UPDATE tbllisting set
                name = '%s',
                contactPhone= '%s',
                contactFax= '%s',
                address1= '%s',
                address2= '%s',
                city= '%s',
                stateId= %d,
                zip= '%s',
                description= '%s',
                products= '%s',
                services= '%s',
                specials= '%s',
                show_specials= '%d',
                active= %d,
                contactname= '%s',
                contactemail= '%s',
                website = '%s',
                website_facebook = '%s',
                website_twitter = '%s',
                website_linkedin = '%s',
                hoursofoperation= '%s',
                metaTitle= '%s',
                metaKeywords = '%s',
                metaDescription = '%s',
                SEOName = '%s',
                SEOImageAltText = '%s',
                ownerUID = %d,
                geolatitude = %s,
                geolongitude = %s
                where id = %d",
                $mysqli->real_escape_string($_POST['name']),
                $mysqli->real_escape_string($_POST['phone']),
                $mysqli->real_escape_string($_POST['fax']),
                $mysqli->real_escape_string($_POST['address1']),
                $mysqli->real_escape_string($_POST['address2']),
                $mysqli->real_escape_string($_POST['city']),
                $mysqli->real_escape_string($_POST['state']),
                $mysqli->real_escape_string($_POST['zip']),
                $mysqli->real_escape_string($_POST['description']),
                $mysqli->real_escape_string($_POST['products']),
                $mysqli->real_escape_string($_POST['services']),
                $mysqli->real_escape_string($_POST['specials']),
                $mysqli->real_escape_string($show_specials),
                $mysqli->real_escape_string($active),
                $mysqli->real_escape_string($_POST['contactname']),
                $mysqli->real_escape_string($_POST['contactemail']),
                $mysqli->real_escape_string($_POST['website']),
                $mysqli->real_escape_string($_POST['website_facebook']),
                $mysqli->real_escape_string($_POST['website_twitter']),
                $mysqli->real_escape_string($_POST['website_linkedin']),
                $mysqli->real_escape_string($_POST['hours']),
                $mysqli->real_escape_string($_POST['metatitle']),
                $mysqli->real_escape_string($_POST['metakeywords']),
                $mysqli->real_escape_string($_POST['metadescription']),
                $mysqli->real_escape_string($_POST['slug']),
                $mysqli->real_escape_string($_POST['imagealttext']),
                $mysqli->real_escape_string($_POST['ownerUID']),
                empty($_POST['geolatitude']) ? 'null' : $mysqli->real_escape_string($_POST['geolatitude']),
                empty($_POST['geolongitude']) ? 'null' : $mysqli->real_escape_string($_POST['geolongitude']),
                $mysqli->real_escape_string($listingId)
            );

            $loggedData['listingpassword']	= 'unchanged';
        }
        else
        {
            $listingQuery = sprintf("
                UPDATE tbllisting set
                name = '%s',
                contactPhone= '%s',
                contactFax= '%s',
                address1= '%s',
                address2= '%s',
                city= '%s',
                stateId= %d,
                zip= '%s',
                description= '%s',
                products= '%s',
                services= '%s',
                specials= '%s',
                show_specials= '%d',
                active= %d,
                contactname= '%s',
                contactemail= '%s',
                website = '%s',
                website_facebook = '%s',
                website_twitter = '%s',
                website_linkedin = '%s',
                hoursofoperation= '%s',
                metatitle= '%s',
                metakeywords= '%s',
                metadescription= '%s',
                listingPassword = '%s',
                SEOName = '%s',
                SEOImageAltText = '%s',
                ownerUID = %d,
                geolatitude = %s,
                geolongitude = %s
                where id = %d",
                $mysqli->real_escape_string($_POST['name']),
                $mysqli->real_escape_string($_POST['phone']),
                $mysqli->real_escape_string($_POST['fax']),
                $mysqli->real_escape_string($_POST['address1']),
                $mysqli->real_escape_string($_POST['address2']),
                $mysqli->real_escape_string($_POST['city']),
                $mysqli->real_escape_string($_POST['state']),
                $mysqli->real_escape_string($_POST['zip']),
                $mysqli->real_escape_string($_POST['description']),
                $mysqli->real_escape_string($_POST['products']),
                $mysqli->real_escape_string($_POST['services']),
                $mysqli->real_escape_string($_POST['specials']),
                $mysqli->real_escape_string($show_specials),
                $mysqli->real_escape_string($active),
                $mysqli->real_escape_string($_POST['contactname']),
                $mysqli->real_escape_string($_POST['contactemail']),
                $mysqli->real_escape_string($_POST['website']),
                $mysqli->real_escape_string($_POST['website_facebook']),
                $mysqli->real_escape_string($_POST['website_twitter']),
                $mysqli->real_escape_string($_POST['website_linkedin']),
                $mysqli->real_escape_string($_POST['hours']),
                $mysqli->real_escape_string($_POST['metatitle']),
                $mysqli->real_escape_string($_POST['metakeywords']),
                $mysqli->real_escape_string($_POST['metadescription']),
                $mysqli->real_escape_string(hasher($_POST['listingpassword'])),
                $mysqli->real_escape_string($_POST['slug']),
                $mysqli->real_escape_string($_POST['imagealttext']),
                $mysqli->real_escape_string($_POST['ownerUID']),
                empty($_POST['geolatitude']) ? 'null' : $mysqli->real_escape_string($_POST['geolatitude']),
                empty($_POST['geolongitude']) ? 'null' : $mysqli->real_escape_string($_POST['geolongitude']),
                $mysqli->real_escape_string($listingId)
            );

            $loggedData['password']	= 'updated';
        }

        $mysqli->query($listingQuery);
        adminLog('update',$loggedData);
    }
    // endregion
    // region add new
    //add new
    else {

        $listingQuery = sprintf("
            INSERT into tbllisting (
                name,contactPhone,contactFax,address1,address2,city,stateId,zip,description,products,services,specials,show_specials,active,contactName,contactEmail,website,website_facebook,website_twitter,website_linkedin,hoursofoperation,metaTitle,metaKeywords,metaDescription,listingPassword, SEOName, SEOImageAltText, ownerUID, geolatitude, geolongitude
            ) values (
                '%s','%s','%s','%s','%s','%s',%d,'%s','%s','%s','%s','%s',%d,%d,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s', '%s', %d, %s, %s)",
            $mysqli->real_escape_string($_POST['name']),
            $mysqli->real_escape_string($_POST['phone']),
            $mysqli->real_escape_string($_POST['fax']),
            $mysqli->real_escape_string($_POST['address1']),
            $mysqli->real_escape_string($_POST['address2']),
            $mysqli->real_escape_string($_POST['city']),
            $mysqli->real_escape_string($_POST['state']),
            $mysqli->real_escape_string($_POST['zip']),
            $mysqli->real_escape_string($_POST['description']),
            $mysqli->real_escape_string($_POST['products']),
            $mysqli->real_escape_string($_POST['services']),
            $mysqli->real_escape_string($_POST['specials']),
            $mysqli->real_escape_string($show_specials),
            $mysqli->real_escape_string($active),
            $mysqli->real_escape_string($_POST['contactname']),
            $mysqli->real_escape_string($_POST['contactemail']),
            $mysqli->real_escape_string($_POST['website']),
            $mysqli->real_escape_string($_POST['website_facebook']),
            $mysqli->real_escape_string($_POST['website_twitter']),
            $mysqli->real_escape_string($_POST['website_linkedin']),
            $mysqli->real_escape_string($_POST['hours']),
            $mysqli->real_escape_string($_POST['metatitle']),
            $mysqli->real_escape_string($_POST['metakeywords']),
            $mysqli->real_escape_string($_POST['metadescription']),
            $mysqli->real_escape_string(hasher($_POST['listingpassword'])),
            $mysqli->real_escape_string($_POST['slug']),
            $mysqli->real_escape_string($_POST['imagealttext']),
            $mysqli->real_escape_string($_POST['ownerUID']),
            empty($_POST['geolatitude']) ? 'null' : $mysqli->real_escape_string($_POST['geolatitude']),
            empty($_POST['geolongitude']) ? 'null' : $mysqli->real_escape_string($_POST['geolongitude'])
        );

        $mysqli->query($listingQuery);

        // Log it
        $loggedData['listingid'] = $listingId = $mysqli->insert_id;
        $loggedData['listingpassword'] = '**MUNGED**';
        adminLog('insert',$loggedData);

        // Add Community/Category Mapping w/ Start/End Date
        $communityDDL =  $listingLevelDDL = $listingStart = $listingEnd = 0;
        if (isset($_POST['communityDDL']) && is_numeric($_POST['communityDDL']))
            $communityDDL = $_POST['communityDDL'];

        if (isset($_POST['listingLevelDDL']) && is_numeric($_POST['listingLevelDDL']))
            $listingLevelDDL = $_POST['listingLevelDDL'];

        if (isset($_POST['listingMapStart']))
            $listingStart = strtotime($_POST['listingMapStart']);

        if (isset($_POST['listingMapEnd']))
            $listingEnd = strtotime($_POST['listingMapEnd']);

        //Add new mappings
        foreach ($_POST as $key=>$value) {
            if (startsWith($key,'cbx-') && is_numeric($value)) {
                $query = sprintf(
                    "INSERT into tblcommunitylistingmap (listingId,communityId,listingLevelId,listingCategoryId,start,end) values(%d,%d,%d,%d,'%s','%s')",
                    $mysqli->real_escape_string($listingId),
                    $mysqli->real_escape_string($communityDDL),
                    $mysqli->real_escape_string($listingLevelDDL),
                    $mysqli->real_escape_string($value),
                    $mysqli->real_escape_string(date('Y-m-d H:i:s', $listingStart)),
                    $mysqli->real_escape_string(date('Y-m-d H:i:s', $listingEnd))
                );

                $mysqli->query($query);

                $loggedData = array(
                        'FUNCTION' => 'listingCategoriesEdit',
                        'listingId' => $listingId,
                        'communityId' => $communityDDL,
                        'listingLevel' => $listingLevelDDL,
                        'categoryId' => $value,
                        'start' => date('Y-m-d H:i:s', $listingStart),
                        'end' => date('Y-m-d H:i:s', $listingEnd)
                );

                adminLog('insert',$loggedData);
            }
        }

        // Send notification to activities@myreadylink.com

        // Lookups
        $lookupQuery = sprintf("
            SELECT
            (SELECT name FROM tblstate WHERE id = %d) as stateName,
            (SELECT name FROM tblcommunity WHERE id = %d) as communityName,
            CONCAT_WS(' ', firstName, lastName) as userFullName,
            email as userEmail
            FROM tbluser
            WHERE id = %d;
        ",
            $mysqli->real_escape_string($_POST['state']),
            $mysqli->real_escape_string($communityDDL),
            $mysqli->real_escape_string($_SESSION['uid'])


        );

        $lookupData = array("stateName"=>"", "communityName"=>"", "userFullName"=>"", "userEmail"=>"");
        if ($lookupResult = $mysqli->query($lookupQuery)) {
            if ($lookupResult->num_rows > 0) {
                $row = $lookupResult->fetch_object();
                $lookupData['stateName'] = $row->stateName;
                $lookupData['communityName'] = $row->communityName;
                $lookupData['userFullName'] = $row->userFullName;
                $lookupData['userEmail'] = $row->userEmail;
            }

            $lookupResult->close();
        }





        $showthespecials = $show_specials == 1 ? 'Yes' : 'No';
        $is_active = $active == 1 ? 'Yes' : 'No';

        $body = "<h3 style='margin-bottom: 0!important;'>A new listing mapping was created.</h3>
        <hr>
        <h3><strong>Listing Details</strong></p></h3>
        <p><strong>Name:</strong><br>".$_POST['name']."</p>
        <p><strong>Contact Phone:</strong><br>".$_POST['phone']."</p>
        <p><strong>Contact Fax:</strong><br>".$_POST['fax']."</p>
        <p><strong>Address1:</strong><br>".$_POST['address1']."</p>
        <p><strong>Address2:</strong><br>".$_POST['address2']."</p>
        <p><strong>City:</strong><br>".$_POST['city']."</p>
        <p><strong>State:</strong><br>".$lookupData['stateName']."</p>
        <p><strong>Zip:</strong><br>".$_POST['zip']."</p>
        <p><strong>Description:</strong><br>".rmTags($_POST['description'])."</p>
        <p><strong>Products:</strong><br>".rmTags($_POST['products'])."</p>
        <p><strong>Services:</strong><br>".rmTags($_POST['services'])."</p>
        <p><strong>Specials:</strong><br>".$_POST['specials']."</p>
        <p><strong>Display Specials:</strong><br>".$showthespecials."</p>
        <p><strong>Active:</strong><br>".$is_active."</p>
        <p><strong>Contact Name:</strong><br>".$_POST['contactname']."</p>
        <p><strong>Contact Email:</strong><br>".$_POST['contactemail']."</p>
        <p><strong>Website:</strong><br>".$_POST['website']."</p>
        <p><strong>Facebook:</strong><br>".$_POST['website_facebook']."</p>
        <p><strong>Twitter:</strong><br>".$_POST['website_twitter']."</p>
        <p><strong>LinkedIn:</strong><br>".$_POST['website_linkedin']."</p>
        <p><strong>Hours of Operation:</strong><br>".rmTags($_POST['hours'])."</p>
        <p><strong>Meta Title:</strong><br>".$_POST['metatitle']."</p>
        <p><strong>Meta Keywords:</strong><br>".$_POST['metakeywords']."</p>
        <p><strong>Meta Description:</strong><br>".$_POST['metadescription']."</p>
        <p><strong>Listing Password:</strong><br>".$_POST['listingpassword']."</p>
        <p><strong>SEO Name:</strong><br>".$_POST['slug']."</p>
        <p><strong>Image Alt Text:</strong><br>".$_POST['imagealttext']."</p>
        <p><strong>System Listing Owner:</strong><br>".$lookupData['userFullName']." <".$lookupData['userEmail']."></p>
        <h3 style='margin-bottom: 0!important;'>Mapping Details</h3>
        <hr>
        <p><strong>Community:</strong><br>".$lookupData['communityName']."</p>
        <p><strong>Start:</strong><br>".date('m/d/Y', $listingStart)."</p>
        <p><strong>End:</strong><br>".date('m/d/Y', $listingEnd)."</p>";


        $sparkPostIn = [
        'content' => [
            'from' => [
                'name' => 'My Ready Link',
                'email' => 'no-reply@email.myreadylink.com',
            ],
            'subject' => 'A new listing mapping was created',
            'html' => $body,
            'text' => 'A new listing mapping was created',
        ],
        'recipients' => [
            // [
            //     'address'=>[
            //         'email' => 'activities@myreadylink.com',
            //     ]
            // ],
            [
              //Developer Email
                'address'=>[
                    'email' => 'mmachillanda@triplestrength.com',
                ]
            ],
        ],
        ];

        sparkpost('POST', 'transmissions', $sparkPostIn, $sparkHeaders);



        // Add initial mapping
        // TO DO: Use CONFIG CONSTANTS

        $listingData = array();
        foreach ($_POST as $k => $v) {
            $listingData[$k] = $v;
        }

        $listingData = array_merge($listingData, $lookupData);

        $listingData['showSpecials'] = $show_specials == 1 ? 'Yes' : 'No';
        $listingData['active'] = $active == 1 ? 'Yes' : 'No';

        // Send Email Notification
        \MyReadyLink\Notifications\AdminNotifications::sendNewListingNotification($listingData);








    }
    // endregion

    // region update slug history
    // update tblslugs
    $slugsquery = sprintf('
                -- only insert if changed
                insert into tblslugs (listingId, slug, isprimary, dateCreated)
                select %1$d, \'%2$s\', 1, now()
                from tbllisting
                where id = %1$d
                    and \'%3$s\' != SEOName
                    and \'%2$s\' not in (select slug from tblslugs where listingId = %1$d)
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
                where listingId = %1$d;
            ',
        $listingId,
        $mysqli->real_escape_string($_POST['slug']),
        $mysqli->real_escape_string($listingoldslug)
    );

    // Run the queries...
    if ($mysqli->multi_query($slugsquery)) {
        do {

        } while ($mysqli->more_results() && $mysqli->next_result());
    }
    // endregion

    //update listing level
    if (isset($_POST['listinglevel']) && isset($_COOKIE['viewCommunity'])) {
        $query = sprintf(
            "UPDATE tblcommunitylistingmap set listingLevelId=%d where listingId=%d AND communityId=%d",
            $mysqli->real_escape_string($_POST['listinglevel']),
            $mysqli->real_escape_string($listingId),
            $mysqli->real_escape_string($_COOKIE['viewCommunity'])
        );
        $mysqli->query($query);
        $loggedData = array(
                'FUNCTION' => 'updateListingLevel',
                'listingId' => $listingId,
                'listingLevelId' => $_POST['listinglevel'],
                'communityId' => $_COOKIE['viewCommunity']
                );
        adminLog('update',$loggedData);
    }

    if ($listingId == 0) {
        echo json_encode(array("result"=>false, "listingId"=>$listingId));
    }
    else {
        echo json_encode(array("result"=>true, "listingId"=>$listingId));
    }
}
elseif (isset($_POST['submitted'])) {
    if ($_POST['show_specials'] == 1) {
        $show_specials = 1;
    }
    else {
        $show_specials = 0;
    }

    $loggedData = array(
        'FUNCTION' => 'userListingEdit',
        'specials' => $_POST['specials'],
        'show_specials' => $show_specials,
        'listingId' => $_POST['listingid'],
    );

    $listingId = $_POST['listingid'];
    //update
    if ($listingId > 0) {
        $query = sprintf("UPDATE tbllisting set
                        specials= '%s',
                        show_specials= '%d'
                        where
                        id=%d",
                        $mysqli->real_escape_string($_POST['specials']),
                        $show_specials,
                        $mysqli->real_escape_string($listingId)
        );

        $mysqli->query($query);
        adminLog('update',$loggedData);

    }

    if ($listingId == 0) {
        echo json_encode(array("result"=>false, "listingId"=>$listingId));
    }
    else {
        echo json_encode(array("result"=>true, "listingId"=>$listingId));
    }
}


function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}
?>
