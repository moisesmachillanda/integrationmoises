<?php
require_once "../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../includes/functions.php";

$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$error = '';

if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    // delete session and kill tab cookies
    $_SESSION['isAdmin'] = false;
    setcookie("viewCommunity", "", time() -3600, "/dashboard/");
    setcookie("listingDash", "", time() -3600, "/dashboard/");
    setcookie("mainDash", "", time() -3600, "/dashboard/");
    session_destroy();
    echo get_AdminHeader(array());
    echo printLoginBox('', '', array("ErrorLevel" => ErrorLevel::Info, "ErrorMessage" => 'You have been logged out'));
}
elseif(isset($_POST['login']) && $_POST['login'] == '1') {
    $loggedData = array(
        'FUNCTION' => 'loginAttempt',
        'userName' => $_POST['username']
    );
    
    adminLog('update',$loggedData);
    
    $found = false;
    
    $query = sprintf("
        select * 
        from tbluser 
        where email = '%s'
        AND active = 1
        AND deleted = 0
        limit 1",
        $mysqli->real_escape_string($_POST['username'])
    );

    $user_query = sprintf("
        select * 
        from tbllisting 
        where contactEmail = '%s'
        AND deleted = 0
        limit 1",
        $mysqli->real_escape_string($_POST['username'])
    );
    
    if ($result = $mysqli->query($query)) {			
        while($row = $result->fetch_object()) {
            if (hasher($_POST['password']) == $row->password) {
                $_SESSION['isAdmin'] = true;
                $_SESSION['uid'] = $row->id;
                $_SESSION['firstName'] = $row->firstName;
                $_SESSION['lastName'] = $row->lastName;
                $_SESSION['phone'] = $row->phone;
                
                $isSA = false;
                $SAquery = sprintf("
                    select *
                    from tbluserrightsmap 
                    where userId = %d
                    and countryId = 1
                    ",
                    $mysqli->real_escape_string($row->id)
                );

                if ($saresult = $mysqli->query($SAquery)) {
                    while ($sarow = $saresult->fetch_object()) {
                        $isSA = true;
                    }
                }

                $rolesQuery = sprintf("select id, name from tblroles where id in (select roleid from tbluserroles where userid = %d)", $mysqli->real_escape_string($row->id));

                if ($rolesResult = $mysqli->query($rolesQuery)) {
                    while ($role = $rolesResult->fetch_array(MYSQLI_ASSOC)) {
                        $userRoles[] = $role;
                    }
                }

                $_SESSION['roles'] = $userRoles;

                $_SESSION['isSAdmin'] = $isSA;
                if ($isSA == false) { 
                    $_SESSION['isAffiliate'] = true; 
                }
                setcookie("viewCommunity", "", time() -3600, "/dashboard/");
                setcookie("listingDash", "", time() -3600, "/dashboard/");
                setcookie("mainDash", "", time() -3600, "/dashboard/");
                $found = true;
                header('Location: /dashboard/');
                
                $loggedData = array(
                    'FUNCTION' => 'loginSuccess',
                    'userName' => $_POST['username']
                );
                
                adminLog('update',$loggedData);
            }
            else {
                $loggedData = array(
                    'FUNCTION' => 'loginFailure',
                    'userName' => $_POST['username']
                );
                
                adminLog('update',$loggedData);
                $error = "Username or password were incorrect";
            }
        }
        
        if($found == false) {
            if ($result = $mysqli->query($user_query)) {
                while ($row = $result->fetch_object()) {
                    //echo($row->password);
                    //echo("<br/>");
                    //echo(hasher($_POST['password']));
                    if (hasher($_POST['password']) == $row->listingPassword) {
                        $_SESSION['isAdmin'] = true;
                        /*
                        $_SESSION['uid'] = $row->id;
                        $_SESSION['firstName'] = $row->firstName;
                        $_SESSION['lastName'] = $row->lastName;
                        $_SESSION['phone'] = $row->phone;
                         */
                        
                        $isSA = false;						
                        $_SESSION['isSAdmin'] = $isSA;
                        setcookie("viewCommunity", "", time() -3600, '/dashboard/listing.php?id='.$row->id);
                        setcookie("listingDash", "", time() -3600, '/dashboard/listing.php?id='.$row->id);
                        setcookie("mainDash", "", time() -3600, '/dashboard/listing.php?id='.$row->id);
                        $found = true;
                        header('Location: /dashboard/listing.php?id='.$row->id);
                        
                        $loggedData = array(
                            'FUNCTION' => 'listingLoginSuccess',
                            'userName' => $_POST['username']
                        );
                        
                        adminLog('update',$loggedData);
                    }
                    else {
                        $loggedData = array(
                                'FUNCTION' => 'listingLoginFailure',
                                'userName' => $_POST['username']
                        );
                        
                        adminLog('update',$loggedData);
                        $error = "Username or password were incorrect";
                    }
                }
                
                if ($found == false) {
                    $loggedData = array(
                        'FUNCTION' => 'loginFailure',
                        'userName' => $_POST['username']
                    );
                    
                    adminLog('update',$loggedData);
                    $error = "Username or password were incorrect";	
                }
                
                $mysqli->close();
            }
            else {					
                $loggedData = array(
                    'FUNCTION' => 'loginFailure',
                    'userName' => $_POST['username']
                );
                
                adminLog('update',$loggedData);
                $error = "Username or password were incorrect";
            }
        }
        
    }
    else {
        $loggedData = array(
                            'FUNCTION' => 'loginFailure',
                            'userName' => $_POST['username']
        );
        
        adminLog('update',$loggedData);
        
        $error = "Username or password were incorrect";
    }
    
    //show login screen
    $password = '';
    $username = '';
    if (isset($_POST['username'])) 
        $password = $_POST['username'];
    if (isset($_POST['password'])) 
        $password = $_POST['password'];

    if ($error) {
        echo get_AdminHeader(array());
        echo printLoginBox($username, $password, array("ErrorLevel" => ErrorLevel::Severe,"ErrorMessage" => $error));
    }
    elseif (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != true) {
        echo get_AdminHeader(array());
        echo printLoginBox($username, $password, array("ErrorLevel" => ErrorLevel::None, "ErrorMessage" => $error));
    }
}
elseif (!isLoggedIn()) {
    echo get_AdminHeader(array());
    echo printLoginBox('', '', array("ErrorLevel" => ErrorLevel::None, "ErrorMessage" => $error));
}
else {
    header('Location: /dashboard/');
}

echo get_AdminFooter(array());

?>
