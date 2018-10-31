<?php

require_once "../../../../includes/config.php";
session_name(SESSIONNAME);
session_id($_POST['session_name']);
session_start();
require_once "../../../../includes/functions.php";

error_log("Upload Info: " . print_r($_POST, true));
error_log("Upload Info: " . print_r($_FILES, true));

requireLogin();

$loggedData = array(
            'FUNCTION' => 'here1 =' .$_POST['listingId'], 'type' => $_POST['listingType'] ,'print_r' => print_r($_FILES,true) 
);
adminLog('insert',$loggedData);

if (!empty($_FILES) && isset($_POST['listingId']) && is_numeric($_POST['listingId'])) {
    $tempFile = $_FILES['Filedata']['tmp_name'];

    // Target path is different now on LWCS. -- Micah
    // Target path updated for new hosting w/ NetStride -- Micah
    $targetPath = $_SERVER['DOCUMENT_ROOT'] . '/../../static.myreadylink.com/public_html/assets/';

    if (ENV == "DEV")
        $targetPath = $_SERVER['DOCUMENT_ROOT'] . '/../../static/public_html/assets/';
    
    $targetFile =  str_replace('//','/',$targetPath) . $_FILES['Filedata']['name'];
    
    $loggedData = array(
        'FUNCTION' => 'here2'
    );
    
    switch ($_POST['listingType']) {
        case 'listing photo':
        case 'listing detail photo':
            $targetPath .= 'photos/listings/' . $_POST['listingId'];
            $targetDir = 'photos/listings/' . $_POST['listingId'];
            break;
        case 'coupon':
            $targetPath .= 'documents/listings/' . $_POST['listingId'];
            $targetDir = 'documents/listings/' . $_POST['listingId'];
            break;
        case 'flyer':
            $targetPath .= 'documents/listings/' . $_POST['listingId'];
            $targetDir = 'documents/listings/' . $_POST['listingId'];
            break;
        case 'menu':
            $targetPath .= 'documents/listings/' . $_POST['listingId'];
            $targetDir = 'documents/listings/' . $_POST['listingId'];
            break;
        case 'brochure';
            $targetPath .= 'documents/listings/' . $_POST['listingId'];
            $targetDir = 'documents/listings/' . $_POST['listingId'];
            break;
        default:
            exit;
    }
    
    $mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
    
    
    if (!is_dir (str_replace('//','/',$targetPath))) {
        mkdir(str_replace('//','/',$targetPath), 0755, true);
    }
    
    $loggedData = array(
        'FUNCTION' => 'here3'
    );
    
    adminLog('insert',$loggedData);	
    $ext = pathinfo($_FILES['Filedata']['name'], PATHINFO_EXTENSION);
    $fileName = time() . '.' . $ext;
    $targetPath .= '/' . $fileName;
    move_uploaded_file($tempFile,$targetPath);

    $query = sprintf("INSERT into tbllistingupload (listingId, fileName,type,active) values (%d,'%s','%s',1)",
    $mysqli->real_escape_string($_POST['listingId']),
    $mysqli->real_escape_string($fileName),
    $mysqli->real_escape_string($_POST['listingType'])
    );
    
    $mysqli->query($query);
    
    $loggedData = array(
            'FUNCTION' => 'uploadAsset',
            'listingUploadId' => $mysqli->insert_id,
            'listingId' => $_POST['listingId'],
            'listingType' => $_POST['listingType'],
            'filename' => $fileName,
            'originalFilename' => $_FILES['Filedata']['name'],
            'fileExt' => $ext
        );
    adminLog('insert',$loggedData);

    echo json_encode(
        array(
            "result"=>true, 
            "FileUrl"=>'http://' . STATIC_URL . '/assets/' . $targetDir . '/' . $fileName, 
            "FileName"=> $fileName, 
            "UploadId" => $mysqli->insert_id,
            "FileExt" => $ext,
            "Type" => ucwords($_POST['listingType'])
        )
    );
}
