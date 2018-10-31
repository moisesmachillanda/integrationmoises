<?php
require_once "../../../../includes/config.php";
session_name(SESSIONNAME);
session_start();
require_once "../../../../includes/functions.php";

// --- Removed SwiftMailer ---
// require_once "../../includes/swiftmailer/swift_required.php";

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

//get featured towns and current towns
$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);

$headerArgs = array();

//get metadata (header stuff and main content)
$query = "SELECT keyName, value FROM tblmetadata WHERE keyName like 'CONTACTUS_%'";
if ($result = $mysqli->query($query))
	while($row = $result->fetch_object())
	$headerArgs[str_replace('contactus_', '', strtolower($row->keyName))] = $row->value;
else db_die();

$headerArgs['hideSearch'] = true;
$headerArgs['bodyClass'] = 'fixed';
$headerArgs['JS_Include'] = '<script type="text/javascript" src="http://'. STATIC_URL . '/js/jquery.validate-1.8.1.min.js"></script>';
$headerArgs['extraJS'] = <<<extraJS

    $("#contactUsForm").validate({
        invalidHandler: function (e, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                var message = 'Fields denoted with an asterisk (*) are required.';
                $("div.formErrors").html(message);
                $("div.formErrors").show();
                $("div.formErrors:visible").delay(5000).slideToggle('slow');
            } else {
                $("div.formErrors").hide();
            }
        },
        onkeyup: false
    });

extraJS;

$template_tags = array();
$template_tags = array_merge(get_site_template_tags(), $template_tags);
$errorMessage = '';
$errorStyle = '';
$formSent = false;

// --- New Logic To Check Form ---

$trip = 0; $itn = Array('Kuwait', 'Moscow', 'Moskow');
$tripLabel = ''; $itld = Array('.ru', '.top', '.win', '.xyz', '.trade', '.men', '.online', '.ua','protonmail.com', 'yandex.com', 'namesilo.com');

if (strpos($_POST['pl_Name'],' ') === FALSE) { $trip++; }
foreach($itld AS $badDomain) { if (strpos($_POST['pl_Email'],$badDomain) !== FALSE) { $trip++; } }
if ($_POST['pl_YourTown'] == "") { $trip++; }
if (mb_check_encoding($_POST['pl_YourTown'], 'ASCII') === FALSE) { $trip++; }
foreach($itn AS $badTown) { if (strpos($_POST['pl_YourTown'],$badTown) !== FALSE) { $trip++; } }

if ($trip > 0) { $tripLabel = " [FLAGGED SPAM]"; }

// --- End New Logic ---

if(isset($_POST['F'])) {
    // If we have the hidden field and it's empty... continue, otherwise it's spam...
    if (isset($_POST['pl_Contact']) && empty($_POST['pl_Contact'])) {
        // Possible additional checks using the project honeypot: http://www.projecthoneypot.org/
        $errorMessage = verifyForm();
        if($errorMessage == '') {

// --- Removed SwiftMailer ---
//		$mailer = new Swift_Mailer(new Swift_MailTransport());

// --- Added SparkPost ---
		$bcc = "backups@triplestrength.com"; $bodySep = "\n"; // --- $_POST['pl_Email'] ---

            	// --- Create a message ---
		$mailBody = "A new submition from the Readylink Contact Us form was submitted on: " . date('M jS Y \a\t g:ia')
		. "Name: " . htmlentities($_POST['pl_Name']) . $bodySep
		. "Mail: " . htmlentities($_POST['pl_Email']) . $bodySep
		. "Town: " . htmlentities($_POST['pl_YourTown']) . $bodySep . $bodySep
		. "Comments or Questions: " . $bodySep . htmlentities($_POST['pl_CommentsOrQuestions']) . $bodySep . $bodySep;

            	$body = "<html><head><title>A new submition from the Readylink Contact Us form</title></head>"
		. "<body><img src=\"" . $mailHeader . "\" /><br /><br />"
		. "A new submition from the Readylink Contact Us form was submitted on: " . date('M jS Y \a\t g:ia')
                . "<hr />"
                . "<strong>Name:</strong> " . htmlentities($_POST['pl_Name']) . "<br />"
                . "<strong>Email:</strong> " . htmlentities($_POST['pl_Email']) . "<br />"
                . "<strong>Town:</strong> " . htmlentities($_POST['pl_YourTown']) . "<br />"
                . "<hr />"
                . "<strong>Comments or Questions:</strong> <br />"
                . htmlentities($_POST['pl_CommentsOrQuestions']) . "<br /><br />"
                . "</body></html>";


// --- Removed SwiftMailer ---
//		$message = Swift_Message::newInstance('Readylink Contact Us form', $body, 'text/html')
//		->setFrom(EmailCleanInput($_REQUEST['busEmail']))
//		->setTo(MAIL_FORM_TO);
//
//		if(MAIL_FORM_CC) {
//			$message->addCc(MAIL_FORM_CC);
//		}
//
//		---Send it---
//		$result = $mailer->send($message);
//
//		if ($result) {
//			$formSent = true;
//		}

// --- Added SparkPost ---

// ---

$sparkPostIn = [
    'content' => [
        'from' => [
            'name' => 'ReadyLink Mailer',
            'email' => 'no-reply@email.myreadylink.com',
        ],
        'subject' => 'Readylink Contact Us form' . $tripLabel,
        'html' => $body,
        'text' => $mailBody,
    ],
    'recipients' => [
        [
            'address'=>[
                'email' => MAIL_FORM_TO,
            ]
        ],
        [
            'address'=>[
                'email' => 'mmachillanda@triplestrength.com',
            ]
        ],
        // [
        //     'address'=>[
        //         'email' => $bcc,
        //     ]
        // ],
    ],
];

$email_results = sparkpost('POST', 'transmissions', $sparkPostIn, $sparkHeaders);

// ---
		$formSent = true;

	} else {
            $errorStyle='style="display:block;"';
        }
    }
    else {
        sleep(1);
        $formSent = true;
    }
}

/////////////

$headerArgs['bodyClass']='contact-us fixed';
echo get_header($headerArgs);
?>
<!--  START: #body -->
<div id="body">
    <div class="inner_wrap">
        <!-- START: #navigation -->
        <?php echo get_navigation('',false); ?>
        <!-- END: #navigation -->
        <!-- START: #sidebar -->
        <div id="sidebar">
            <a href="http://<?php echo HOME_URL; ?>/feedback/" id="lnkFeedback"><div>Feedback Form</div></a>
            <a href="http://<?php echo HOME_URL; ?>/get-listed/" id="lnkAdvertise"><div>Advertise With ReadyLink</div></a>
        </div>
        <!-- END: #sidebar -->
        <div id="viewport">
            <h3><?php echo template_replace($headerArgs['title'],$template_tags);?></h3>
            <div class="formErrors" <?php echo $errorStyle;?>><?php echo $errorMessage; ?></div>
            <?php if(!$formSent) {
            ?><div id="contact_us">
                <?php echo template_replace($headerArgs['main_content'],$template_tags);?>
                <h6 class="h6Caps">Contact Us</h6>
                <div class="publicForm">
                    <div class="publicFormRight">
                        <p>If you prefer to write to us:</p>
                        <p class="address"><strong>ReadyLink</strong></p>
                        <p class="address">P.O. Box 618</p>
                        <p class="address">Hershey, PA 17033</p>
                        <hr />
                        <cite>* Fields denoted with Asterisk(<span class="asterisk">*</span>) are required.<br /><br />
                        <strong>Note:</strong> We promise that your Name and Contact Information will never be sold or given out.</cite>
                    </div>
                    <div class="publicFormLeft">
                        <form method="post" action="http://admin.myreadylink.com/dashboard/sparkpost/test-contact.php" name="contactUsForm" id="contactUsForm">
                            <span>
                                <label for="pl_Name">Name</label>
                                <input type="text" name="pl_Name" id="pl_Name" value="<?php if(isset($_POST['pl_Name'])) echo htmlentities($_POST['pl_Name']);?>" />
                            </span>
                            <span>
                                <label for="pl_Email">E-Mail <span class="asterisk">*</span></label>
                                <input type="text" name="pl_Email" id="pl_Email" value="<?php if(isset($_POST['pl_Email'])) echo htmlentities($_POST['pl_Email']);?>" class="required email" />
                            </span>
                            <span>
                                <label for="pl_YourTown">Your Town <span class="asterisk">*</span></label>
                                <input type="text" name="pl_YourTown" id="pl_YourTown" value="<?php if(isset($_POST['pl_YourTown'])) echo htmlentities($_POST['pl_YourTown']);?>" class="required" />
                            </span>
                            <span>
                                <label for="pl_CommentsOrQuestions">Comments or Questions <span class="asterisk">*</span></label>
                                <textarea rows="3" cols="20" name="pl_CommentsOrQuestions" id="pl_CommentsOrQuestions" class="required"><?php if(isset($_POST['pl_CommentsOrQuestions'])) echo htmlentities($_POST['pl_CommentsOrQuestions']);?></textarea>
                            </span>
                            <span style="display: none;">
                                <input type="text" name="pl_Contact" id="pl_Contact" value="<?php if (isset($_POST['pl_Contactdata'])) echo htmlentities($_POST['pl_Contactdata']); ?>" />
                            </span>
                            <span>
                                <input type="hidden" name="F" id="F" value="Submit_Contact_Us" />
                                <input id="btnSubmit" type="submit" value="" />
                            </span>
                        </form>
                    </div>
                </div>
                <br />
            </div>
            <?php }
            else {
            ?><div id="contact_us">
                <p>Your request was successfully submitted.  Thank you for your interest in ReadyLink.</p>
            </div><?php
            } ?>
            <div id="viewport_footer">&nbsp;</div>
        </div>
    </div>
</div>
<!--  END: #body -->
<?php
echo get_footer(array());

function verifyForm() {
    $returnVal = '';

    if(!isset($_POST['pl_Email']))	$returnVal .= 'The \'email\' field is required<br />';
    if(!isset($_POST['pl_YourTown'])) $returnVal .= 'The \'your town\' field is required<br />';
    if(!isset($_POST['pl_CommentsOrQuestions'])) 'The \'comments or questions\' field is required<br />';
}

?>

<script type="text/javascript">
$(function () {
    $('a:contains("Go to Other Towns")').parent('li').hide();
    $('a:contains("My Town")').parent('li').hide();
});
</script>
