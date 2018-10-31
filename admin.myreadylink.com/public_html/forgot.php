<?php
require_once "../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../includes/functions.php";
require_once "../../includes/swiftmailer/swift_required.php";
$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);


echo get_AdminHeader(array());

if (isset($_GET['i']) && isset($_GET['ih']) && isset($_GET['d']) && isset($_GET['dh'])) {
    try {	
        $myDate     = base64_decode(urldecode($_GET['d']));
        $myDateHash = base64_decode(urldecode($_GET['dh']));
        $myId       = base64_decode(urldecode($_GET['i']));
        $myIdHash   = base64_decode(urldecode($_GET['ih']));
        
        if (resethash($myDate) != $myDateHash || resethash($myId) != $myIdHash) { ?>
            <div id="loginBox">
                <div class=""></div>
                <div>You have followed an invalid link.  Please resend your password again <a href="/forgot.php">here</a>.</div>
                <div></div>
            </div>
        <?php 
        }
        else if ($myDate < time() - 86400) { ?>
            <div id="loginBox">
                <div class=""></div>
                <div>The link you have followed has expired.  Please resend your password again <a href="/forgot.php">here</a>. </div>
                <div></div>
            </div>
        <?php 
        }
        else { ?>
            <form method="post" action="/forgot.php">
                <div id="loginBox">
                    <div class="">
                    </div>
                    <div>
                        <label class="bold">New Password:</label>
                        <input type="password" id="password" name="password" value="" />
                    </div>
                    <div>
                        <label class="bold">Renter Password:</label>
                        <input type="password" id="passwordconfirm" name="passwordconfirm" value="" />
                    </div>
                    <div>
                        Enter your new password above (minimum of 6 characters).
                    </div>
                    <div>
                        <input type="submit" value="Reset My Password" />
                    </div>
                    <input type="hidden" id="d" name="d" value="<?php echo htmlentities($_GET['d'])?>" />
                    <input type="hidden" id="dh" name="dh" value="<?php echo htmlentities($_GET['dh'])?>" />
                    <input type="hidden" id="d" name="i" value="<?php echo htmlentities($_GET['i'])?>" />
                    <input type="hidden" id="dh" name="ih" value="<?php echo htmlentities($_GET['ih'])?>" />
                    <input type="hidden" id="reset" name="reset" value="1" />
                </div>
            </form>
        <?php 
        } // else, if (resethash($myDate) != $myDateHash || resethash($myId) != $myIdHash) {
    }
    catch(Exception $e) {
?>
<div id="loginBox">
    <div class=""></div>
    <div>You have followed an invalid link.  Please resend your password again <a href="/forgot.php">here</a>.</div>
    <div></div>
</div>
<?php 
    } // try/catch
    
} // if (isset($_GET['i']) && isset($_GET['ih']) && isset($_GET['d']) && isset($_GET['dh']))
else if (isset($_POST['i']) && isset($_POST['ih']) && isset($_POST['d']) && isset($_POST['dh'])) {
    //reset here
    $myDate     = base64_decode(urldecode($_POST['d']));
    $myDateHash = base64_decode(urldecode($_POST['dh']));
    $myId       = base64_decode(urldecode($_POST['i']));
    $myIdHash   = base64_decode(urldecode($_POST['ih']));
    
    if ($_POST['password'] != $_POST['passwordconfirm']) { ?>
        <form method="post" action="/forgot.php">
            <div id="loginBox">
                <div class="errorSevere">
                    The passwords do not match.
                </div>
                <div>
                    <label class="bold">New Password:</label>
                    <input type="password" id="password" name="password" value="" />
                </div>
                <div>
                    <label class="bold">Renter Password:</label>
                    <input type="password" id="passwordconfirm" name="passwordconfirm" value="" />
                </div>
                <div>
                    Enter your new password above (minimum of 6 characters).
                </div>
                <div>
                    <input type="submit" value="Reset My Password" />
                </div>
                <input type="hidden" id="d" name="d" value="<?php echo htmlentities($_GET['d'])?>" />
                <input type="hidden" id="dh" name="dh" value="<?php echo htmlentities($_GET['dh'])?>" />
                <input type="hidden" id="i" name="i" value="<?php echo htmlentities($_GET['i'])?>" />
                <input type="hidden" id="ih" name="ih" value="<?php echo htmlentities($_GET['ih'])?>" />
                <input type="hidden" id="reset" name="reset" value="1" />
            </div>
        </form>
<?php 
    } // if ($_POST['password'] != $_POST['passwordconfirm'])
    else if(strlen($_POST['password']) < 6) { ?>
        <form method="post" action="/forgot.php">
            <div id="loginBox">
                <div class="errorSevere">
                    The passwords must be at least 6 characters long.
                </div>
                <div>
                    <label class="bold">New Password:</label>
                    <input type="password" id="password" name="password" value="" />
                </div>
                <div>
                    <label class="bold">Renter Password:</label>
                    <input type="password" id="passwordconfirm" name="passwordconfirm" value="" />
                </div>
                <div>
                    Enter your new password above (minimum of 6 characters).
                </div>
                <div>
                    <input type="submit" value="Reset My Password" />
                </div>
                <input type="hidden" id="d" name="d" value="<?php echo htmlentities($_GET['d'])?>" />
                <input type="hidden" id="dh" name="dh" value="<?php echo htmlentities($_GET['dh'])?>" />
                <input type="hidden" id="i" name="i" value="<?php echo htmlentities($_GET['i'])?>" />
                <input type="hidden" id="ih" name="ih" value="<?php echo htmlentities($_GET['ih'])?>" />
                <input type="hidden" id="reset" name="reset" value="1" />
            </div>
        </form>
<?php 
    } // else if(strlen($_POST['password']) < 6)
    else {
        if (resethash($myDate) == $myDateHash && resethash($myId) == $myIdHash && $myDate > time() - 86400) {
            $query = sprintf("
                update tbluser set 
                    password='%s' 
                where id=%d 
                  and active = 1
                  and deleted = 0
                limit 1",
                $mysqli->real_escape_string(hasher($_POST['password'])),
                $mysqli->real_escape_string($myId)
            );
            
            $mysqli->query($query);
            $loggedData = array(
                'FUNCTION' => 'passwordreset',
                'userId' => $myId
            );
            
            adminLog('update',$loggedData);
        ?>
            <div id="loginBox">
                <div class="">
                </div>

                <div>
                    Your password has been updated.  You can login <a href="/">here</a>.
                </div>
                <div>
                </div>

            </div>
<?php 
        } // if (resethash($myDate) == $myDateHash && resethash($myId) == $myIdHash && $myDate > time() - 86400)
        else { ?>
            <div id="loginBox">
                <div class="">
                </div>

                <div>
                    An error occured reseting your password.
                </div>
                <div>
                </div>

            </div>
<?php 
        } // else, if (resethash($myDate) == $myDateHash && resethash($myId) == $myIdHash && $myDate > time() - 86400)
    } // else, if ($_POST['password'] != $_POST['passwordconfirm'])

}
else if (isset($_POST['reset'])) {
    $query = sprintf("
        SELECT id, email from tbluser 
        where email = '%s'
         and active = 1
         and deleted = 0
        limit 1",
        $mysqli->real_escape_string($_POST['username'])
    );

    if ($result = $mysqli->query($query)) {
        while($row = $result->fetch_object()) {
            sendEmail($row->id, $row->email);
        }
    }
    ?>
    <div id="loginBox">
        <div class=""></div>
        <div>You should receive an email shortly with a private link to reset your password.</div>
        <div></div>
    </div>
<?php 	
}
else { 
    ?>
    <form method="post" action="/forgot.php">
        <div id="loginBox">
            <div class=""></div>
            <div>
                <label class="bold">Email Address:</label>
                <input type="text" id="username" name="username" value="" />
            </div>
            <div>Enter your email address above and we will send you a private link to reset you password.</div>
            <div><input type="submit" value="Reset My Password" /></div>
            <input type="hidden" id="reset" name="reset" value="1" />
        </div>
    </form>
<?php
}
echo get_AdminFooter(array());

function resethash($toEnc) {
    $salt="qisdDGSESWR3fDSFSDsdfiUwwcftgsdbwtbRvBVRTbrtbB2TBDSfrf";
    $md5_salt = "\$1\$". substr($salt, 0, CRYPT_SALT_LENGTH);
    return crypt($toEnc, $md5_salt);
}

function sendEmail($id, $emailAddr) {
    //echo 'id:' . $row->id;
    $mailer = new Swift_Mailer(new Swift_MailTransport());
    $myTime = time();

    //Create a message
    $body = '<html><head><title>A request has been submitted to reset your Readylink management password</title></head><body>A request has been submitted to reset your Readylink management password on: ' . date('M jS Y \a\t g:ia')
    . '<hr />'
    . 'Click the following link to reset your password:<br /><br />'
    . '<a href="http://admin.myreadylink.com/forgot.php?i=' . urlencode(base64_encode($id)) . '&ih=' . urlencode(base64_encode(resethash($id))) . '&d=' . urlencode(base64_encode($myTime)) . '&dh=' . urlencode(base64_encode(resethash($myTime))) . '">http://admin.' . HOME_URL . '/forgot.php?i=' . urlencode(base64_encode($id)) . '&ih=' . urlencode(base64_encode(resethash($id))) . '&d=' . urlencode(base64_encode($myTime)) . '&dh=' . urlencode(base64_encode(resethash($myTime))) . '</a>'
    . '<br />This link is valid for 24 hours.<br />'
    . '</body></html>';
    
    $message = Swift_Message::newInstance('Readylink management password reset', $body, 'text/html')
        ->setFrom(MAIL_FORM_FROM)
        ->setTo($emailAddr);
    
    //Send it
    $result = $mailer->send($message);
    
    $loggedData = array(
        'FUNCTION' => 'passwordresetrequest',
        'userId' => $emailAddr
    );
    
    adminLog('update',$loggedData);
}
?>