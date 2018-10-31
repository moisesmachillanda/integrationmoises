<?php
require_once "../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../includes/functions.php";
require_once "../../../includes/app/bootstrap.php";

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

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);




function array_flatten($array) {
  if (!is_array($array)) {
    return FALSE;
  }
  $result = array();
  foreach ($array as $key => $value) {
    if (is_array($value)) {
      $result = array_merge($result, array_flatten($value));
    }
    else {
      $result[$key] = $value;
    }
  }
  return $result;
}



$expiredQuery = "SELECT `listingId` FROM tblcommunitylistingmap WHERE `end` = DATE(NOW() + INTERVAL 14 DAY) + INTERVAL 0 SECOND";

if ($expiredResult = $mysqli->query($expiredQuery)) {

        if ($expiredResult->num_rows > 0) {
        $the_expired_users = $expiredResult->fetch_all();
        $the_listings = array_flatten($the_expired_users);
        $listingids_toexpire = implode (", ", $the_listings);
        $listingids_toexpire = "(".$listingids_toexpire.")";

        $ownerIDQuery = "SELECT `ownerUID` FROM `tbllisting` WHERE `id` IN $listingids_toexpire";



        if ($ownerIDResult = $mysqli->query($ownerIDQuery)) {
            if ($ownerIDResult->num_rows > 0) {

                    $the_owners = $ownerIDResult->fetch_all();
                    $the_owners_ids = array_flatten($the_owners);

                    $owners_arr = implode (", ",     $the_owners_ids);
                    $the_owners_ids_to = "(".$owners_arr.")";


                    $owneremails = "SELECT `id`,`email`,`firstName`,`lastName` FROM `tbluser` WHERE `id` IN $the_owners_ids_to";



                    if ($ownerEmailslist = $mysqli->query($owneremails)) {
                        if ($ownerEmailslist->num_rows > 0) {

                                $the_emails = $ownerEmailslist->fetch_all(MYSQLI_ASSOC);






foreach ($the_emails as $key) {

$devemail = '';
$theuser_id = $key['id'];
$theuser_email = $key['email'];
	// $query_details = "SELECT DISTINCT `firstName`,`lastName`,`email`,`listingId`,tbluser.id, tbllisting.name as listingname ,`end` as expire, tblcommunity.name AS community, tblcommunity.id AS commityid, tbllistingcategory.name AS category FROM `tbluser`
	// INNER JOIN tbllisting ON tbllisting.ownerUID = tbluser.id
	// INNER JOIN tblcommunitylistingmap ON tbllisting.id = tblcommunitylistingmap.listingId
	// INNER JOIN tblcommunity ON tblcommunitylistingmap.communityid = tblcommunity.id
	// INNER JOIN tbllistingcategory ON tblcommunitylistingmap.listingCategoryId = tbllistingcategory.id
	// WHERE `end` = DATE(NOW() + INTERVAL 14 DAY) + INTERVAL 0 SECOND  AND tbluser.id = ".$theuser_id."";

	$query_details = "SELECT DISTINCT `firstName`,`lastName`,`email`,`listingId`, tblstate.abbr as state,tbluser.id, tbllisting.name as listingname ,`end` as expire, tblcommunity.name AS community, tblcommunity.id AS commityid FROM `tbluser`
	INNER JOIN tbllisting ON tbllisting.ownerUID = tbluser.id
	INNER JOIN tblstate ON tblstate.id = tbllisting.stateId
	INNER JOIN tblcommunitylistingmap ON tbllisting.id = tblcommunitylistingmap.listingId
	INNER JOIN tblcommunity ON tblcommunitylistingmap.communityid = tblcommunity.id
	WHERE `end` = DATE(NOW() + INTERVAL 14 DAY) + INTERVAL 0 SECOND  AND tbluser.id =".$theuser_id."";

	if ($details = $mysqli->query($query_details)) {
		if ($details->num_rows > 0) {

		$the_details = $details->fetch_all(MYSQLI_ASSOC);


			echo '<pre>';
			print_r($the_details);
			echo '</pre>';


		 $output="";

			foreach($the_details as $the_details){
				$expiring_date = new DateTime($the_details['expire']);
				$expiring_date1 = $expiring_date ->format('Y-m-d');
				$expiring_date2 = $expiring_date ->format('l, F d, Y');
				$output.= "<p><strong>".$the_details['listingname']."</strong> - ".$the_details['community'].", ".$the_details['state']."<br>
				 Expires: ".$expiring_date1 ."<br/>
				 <a href='http://admin.myreadylink.com/dashboard/listing.php?id=".$the_details['listingId']."&communityid=".$the_details['commityid']."'>Edit Listing</a></p><hr>";
			}
			$body = $output;


			$sparkPostIn = [
			'content' => [
					'from' => [
							'name' => 'ReadyLink Mailer',
							'email' => 'no-reply@myreadylink.com',
					],
					'subject' => 'MyReadyLink - Listings Expiring Soon - '.$expiring_date2.'',
					'html' => '<p>The following listings are going to expire in two weeks:</p>'.$body,
					'text' => 'Email Expiring',
			],
			'recipients' => [
					[
							'address'=>[
									'email' => $theuser_email,
							]
					],
					// [
					//
					//     'address'=>[
					//         'email' => $devemail,
					//     ]
					// ],
			],
			];

			sparkpost('POST', 'transmissions', $sparkPostIn, $sparkHeaders);

		}
	}
    }








                        }
                    }


            }

        }

                }



        $lookupResult->close();
}
