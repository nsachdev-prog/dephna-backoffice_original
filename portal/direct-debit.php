<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
?>
<!DOCTYPE html>
<head>
<title>Dephna Direct Debit Instruction</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="direct-debit.css" rel="stylesheet" type="text/css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>
<body>

<table align="center" class="tblwidth" style="background-color: #35729f; padding: 4px;">
    <tr>
        <td class="hd1"><img src="images/dephna-logo-mobile.png" style="padding: 2px 2px 8px 2px"></td>
        <td class="hd2"><img src="images/DD_logo_landscape_300x95.png" width="100" style="padding: 8px 2px 2px 2px"></td>
    </tr>
</table>

<table align="center" class="tblwidth" style="background-color: #bbb; padding: 4px;">
    <tr>
        <td class="larger">
            Direct Debit Instruction
        </td>
    </tr>
</table>

<?php
if (isset($_REQUEST['custref'])) {
    $custref = $_REQUEST['custref'];
} else {
    $custref = '';
}
if (isset($_POST['submitform'])) {
    $title        = trim($_POST['title']);
    $firstName    = trim($_POST['firstName']);
    $lastName     = trim($_POST['lastName']);
    $phoneNumber  = trim($_POST['phoneNumber']);
    $emailAddress = trim($_POST['emailAddress']);
    $companyName  = trim($_POST['companyName']);
    $postCode     = trim($_POST['postCode']);
    $addressLine1 = trim($_POST['addressLine1']);
    $addressLine2 = trim($_POST['addressLine2']);
    $addressLine3 = trim($_POST['addressLine3']);
    $town         = trim($_POST['town']);
    $county       = trim($_POST['county']);
    $accountHolderName  = trim($_POST['accountHolderName']);
    $accountNumber      = trim($_POST['accountNumber']);
    $accountHolderCheck = trim($_POST['accountHolderCheck']);
    $sort1 = $_POST['sort1'];
    $sort2 = $_POST['sort2'];
    $sort3 = $_POST['sort3'];
    $back  = $_POST['back'];
    $send  = $_POST['send'];

    if ($back) {
        $e = 1;
    } else {
        $e = 0;
    }

    if (!$accountHolderCheck == 'on') {
        $accHolderStyle = 'display:none;';
        $notaccHolderStyle = 'display:display:table-cell;';
    } else {
        $accHolderStyle = 'display:display:table-cell;';
        $notaccHolderStyle = 'display:none;';
    }

    if (!$title) {
        $errorTitle = 'Please select your title'; $e++;
    }
    if (!$firstName) {
        $errorfirstName = 'Please enter your first name'; $e++;
    }
    if (!$lastName) {
        $errorlastName = 'Please enter your last name'; $e++;
    }
    if (!$phoneNumber) {
        $errorphoneNumber = 'Please enter your phone number'; $e++;
    }
    if (!$emailAddress) {
        $erroremailAddress = 'Please enter your email address'; $e++;
    } else {
        $emailAddress = strtolower($emailAddress);
        if (!validate_email($emailAddress)) {
            $erroremailAddress = 'Please enter a valid email address'; $e++;
        }
    }
    if (!$companyName) {
        $errorcompanyName = 'Please enter your company name'; $e++;
    }
    if (!$postCode) {
        $errorpostCode = 'Please enter your post code'; $e++;
    }
    if (!$addressLine1) {
        $erroraddressLine1 = 'Please enter your address line 1'; $e++;
    }
    if (!$town) {
        $errortown = 'Please enter your town'; $e++;
    }
    if (!$accountHolderName) {
        $erroraccountHolderName = 'Please enter your account name'; $e++;
    }
    if (!$accountNumber) {
        $erroraccountNumber = 'Please enter your account number'; $e++;
    } else {
        if (!is_numeric($accountNumber)) {
            $erroraccountNumber = 'Account number must be numeric'; $e++;
        } else {
            if (strlen($accountNumber) < 8 OR strlen($accountNumber) > 8) {
                $erroraccountNumber = 'Account number must be 8 digits<br>You can add leading zeros.'; $e++;
            }
        }
    }
    if (!$sort1 OR !$sort2 OR !$sort3) {
        $errorsortCode = 'Please enter your sort code'; $e++;
    } else {
        $sortCode = $sort1 . $sort2 . $sort3;
        if (!is_numeric($sortCode)) {
            $errorsortCode = 'Sort code must be numeric'; $e++;
        } else {
            if (strlen($sortCode) != 6) {
                $errorsortCode = 'Please enter a valid sort code'; $e++;
            }
        }
    }

    if (!$e) {

        if ($send) {

            if ($custref) {
                // include settings.php from backoffice
                include("../backoffice/settings.php");
                // open database connection
                $conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
                // check connection
                if (!$conn->connect_error) {
                    // add bank details to users table
                    $accountHolderName = $conn->real_escape_string($accountHolderName);
                    $accountNumber     = $conn->real_escape_string($accountNumber);
                    $sortCode          = $conn->real_escape_string($sortCode);
                    $sql = "UPDATE users SET
                                bank = 'Yes',
                                bnkAccName = '$accountHolderName',
                                bnkAccNum = '$accountNumber',
                                bnkSort = '$sortCode'
                            WHERE custref = '$custref'
                    ";
                    if ($conn->query($sql) === TRUE) {
                        $resultMessage .= "Bank details added to client account<br>";
                    } else {
                        $resultMessage .= "<span class=\"redtext\">Bank details could NOT updated in client account<br></span>" . $conn->error . "<br>";
                    }
                } else {
                    $resultMessage .= "<span class=\"redtext\">Could not connect to database<br></span>" . $conn->connect_error . "<br>";
                }
                // close database connection
                $conn->close();
            }

            require "../../PHPMailer/src/SMTP.php";
			require "../../PHPMailer/src/PHPMailer.php";
			require "../../PHPMailer/src/Exception.php";

            $from = "accounts@dephna.com";
            $fromname = "Dephna Accounts";
            $subject = "Dephna Direct Debit Instruction";

            // email message
            $message  = file_get_contents('../backoffice/email-header.html') . "\r\n";
            $message .= "<p>Dear $title $firstName $lastName,</p>";
            $message .= "<p>Thank you for setting up a Direct Debit with us.</p>";
            $message .= "<p>Please find attched a letter confirming the details you have provided.</p>";
            $message .= "<p>If you have any questions, please contact us on 020 8740 8833 or email accounts@dephna.com</p>";
            $message .= "<p>Kind Regards,<br>Dephna Accounts</p>";
            $message .= file_get_contents('../backoffice/email-footer.html') . "\r\n";

            $mail = new PHPMailer(true);

            try {
                include('../../cron/SMTP.php');

                $mail->setFrom($from, $fromname);
                $mail->addAddress($emailAddress, $firstName . ' ' . $lastName);
                $mail->addReplyTo($from, $fromname);
                $mail->addBCC($from, $fromname);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $message;

                // attach pdf letter
                $fileatt = "";
                $byemail = 1;
                include("direct-debit-letter.php");
                if ($fileatt) {
                    $mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
                }

                $mail->send();
                $resultMessage .= "Email Sent to $emailAddress<br>";

            } catch (Exception $e) {
                $resultMessage .= "<span class=\"redtext\">Email FAILED<br></span>Mailer Error: {$mail->ErrorInfo}";
            }

            $resultMessage .= 'Thank you for submitting your Direct Debit Instruction.<br>You will receive an email confirmation shortly.<br><br>';
            $resultMessage .= 'If you have any questions, please contact us on 020 8740 8833 or email accounts@dephna.com<br><br>';

        } else {

            print "<table align=\"center\" cellpadding=\"6\" class=\"tblwidth\" style=\"background-color: #eee;\">";
            print "<tr><td colspan=\"2\">";
            print "<p>Please check the details you have entered and read the Direct Debit Guarantee before submitting.</p>";
            print "</td></tr>";
            print "<tr><td colspan=\"2\">";
            print "<p><strong>Account Holder Details</strong></p>";
            print "</td></tr>";
            print "<tr><td class=\"col1\">Title</td><td class=\"col2\">$title</td></tr>";
            print "<tr><td class=\"col1\">First Name</td><td class=\"col2\">$firstName</td></tr>";
            print "<tr><td class=\"col1\">Last Name</td><td class=\"col2\">$lastName</td></tr>";
            print "<tr><td class=\"col1\">Phone Number</td><td class=\"col2\">$phoneNumber</td></tr>";
            print "<tr><td class=\"col1\">Email Address</td><td class=\"col2\">$emailAddress</td></tr>";
            print "<tr><td colspan=\"2\"><p><strong>Business Address</strong></p></td></tr>";
            print "<tr><td class=\"col1\">Company Name</td><td class=\"col2\">$companyName</td></tr>";
            print "<tr><td class=\"col1\">Address Line 1</td><td class=\"col2\">$addressLine1</td></tr>";
            print "<tr><td class=\"col1\">Address Line 2</td><td class=\"col2\">$addressLine2</td></tr>";
            print "<tr><td class=\"col1\">Address Line 3</td><td class=\"col2\">$addressLine3</td></tr>";
            print "<tr><td class=\"col1\">Town or City</td><td class=\"col2\">$town</td></tr>";
            print "<tr><td class=\"col1\">County</td><td class=\"col2\">$county</td></tr>";
            print "<tr><td class=\"col1\">Post Code</td><td class=\"col2\">$postCode</td></tr>";
            print "<tr><td colspan=\"2\"><p><strong>Bank Details</strong></p></td></tr>";
            print "<tr><td class=\"col1\">Account Name</td><td class=\"col2\">$accountHolderName</td></tr>";
            print "<tr><td class=\"col1\">Sort Code</td><td class=\"col2\">$sort1-$sort2-$sort3</td></tr>";
            print "<tr><td class=\"col1\">Account Number</td><td class=\"col2\">$accountNumber</td></tr>";
            print "</table>";

            print "<table align=\"center\" cellpadding=\"6\" class=\"tblwidth\" style=\"background-color: #eee;\">";
            print "<tr><td><br>";
            print "<table align=\"center\" cellpadding=\"6\" class=\"ddg\">";
            print "<tr><td>";
            print "<p><strong>Direct Debit Guarantee</strong></p>";
            print "</td></tr>";
            print "<tr><td>";
            print "<ul>";
            print "<li>This Guarantee is offered by all banks and building societies that accept instructions to pay Direct Debits.</li>";
            print "<li>If there are any changes to the amount, date or frequency of your Direct Debit, Dephna Estates Ltd will notify you 3 working days in advance of your account being debited or as otherwise agreed. If you request Dephna Estates Ltd to collect a payment, confirmation of the amount and date will be given to you at the time of the request.</li>";
            print "<li>If an error is made in the payment of your Direct Debit, by Dephna Estates Ltd or your bank or building society, you are entitled to a full and immediate refund of the amount paid from your bank or building society.</li>";
            print "<li>If you receive a refund you are not entitled to, you must pay it back when Dephna Estates Ltd asks you to.</li>";
            print "<li>You can cancel a Direct Debit at any time by simply contacting your bank or building society. Written confirmation may be required. Please also notify us.</li>";
            print "</ul>";
            print "</td></tr>";
            print "</table>";
            print "</td></tr>";
            print "</table>";

            print "<table align=\"center\" cellpadding=\"6\" class=\"tblwidth\" style=\"background-color: #eee;\">";
            print "<tr><td>";
            print "<p><strong>Other Details</strong></p>";
            print "</td></tr>";
            print "<tr><td>";
            print "<b>Advance Notice period:</b> 3 working days.<br><b>Payment Frequency:</b> Monthly<br><b>Payment Date:</b> 7th of each month<br>If the 7th is a weekend or bank holiday, the payment will be taken on the next working day.<br>The name that will appear on your bank statement will be Dephna Estates Ltd.<br>Confirmation of the Direct Debit set up will be sent within 3 working days.<br><br>";
            print "</td></tr>";
            print "</table>";

            print "<table align=\"center\" cellpadding=\"6\" class=\"tblwidth\" style=\"background-color: #eee;\">";
            print "<tr><td colspan=\"2\">";
            print "<p><strong>By clicking submit below, you are confirming that you are the account holder.<br>That you are the only person required to authorise debits from this account.<br>That you have read the direct debit guarantee above.<br>And that you understand the other details above.</strong></p>";
            print "</td></tr>";
            print "<tr><td style=\"text-align:right;\">";
            print "<form action=\"direct-debit.php\" method=\"post\">";
            print "<input type=\"hidden\" name=\"back\" value=\"1\">";
            print "<input type=\"hidden\" name=\"submitform\" value=\"1\">";
            print "<input type=\"hidden\" name=\"custref\" value=\"$custref\">";
            print "<input type=\"hidden\" name=\"title\" value=\"$title\">";
            print "<input type=\"hidden\" name=\"firstName\" value=\"$firstName\">";
            print "<input type=\"hidden\" name=\"lastName\" value=\"$lastName\">";
            print "<input type=\"hidden\" name=\"phoneNumber\" value=\"$phoneNumber\">";
            print "<input type=\"hidden\" name=\"emailAddress\" value=\"$emailAddress\">";
            print "<input type=\"hidden\" name=\"companyName\" value=\"$companyName\">";
            print "<input type=\"hidden\" name=\"postCode\" value=\"$postCode\">";
            print "<input type=\"hidden\" name=\"addressLine1\" value=\"$addressLine1\">";
            print "<input type=\"hidden\" name=\"addressLine2\" value=\"$addressLine2\">";
            print "<input type=\"hidden\" name=\"addressLine3\" value=\"$addressLine3\">";
            print "<input type=\"hidden\" name=\"town\" value=\"$town\">";
            print "<input type=\"hidden\" name=\"county\" value=\"$county\">";
            print "<input type=\"hidden\" name=\"accountHolderName\" value=\"$accountHolderName\">";
            print "<input type=\"hidden\" name=\"accountNumber\" value=\"$accountNumber\">";
            print "<input type=\"hidden\" name=\"sort1\" value=\"$sort1\">";
            print "<input type=\"hidden\" name=\"sort2\" value=\"$sort2\">";
            print "<input type=\"hidden\" name=\"sort3\" value=\"$sort3\">";
            print "<input type=\"hidden\" name=\"accountHolderCheck\" value=\"$accountHolderCheck\">";
            print "<input type=\"submit\" name=\"submit2\" value=\"Back\" class=\"button\">";
            print "</form>";
            print "</td>";
            print "<td style=\"text-align:left;\">";
            print "<form action=\"direct-debit.php\" method=\"post\">";
            print "<input type=\"hidden\" name=\"send\" value=\"1\">";
            print "<input type=\"hidden\" name=\"submitform\" value=\"1\">";
            print "<input type=\"hidden\" name=\"custref\" value=\"$custref\">";
            print "<input type=\"hidden\" name=\"title\" value=\"$title\">";
            print "<input type=\"hidden\" name=\"firstName\" value=\"$firstName\">";
            print "<input type=\"hidden\" name=\"lastName\" value=\"$lastName\">";
            print "<input type=\"hidden\" name=\"phoneNumber\" value=\"$phoneNumber\">";
            print "<input type=\"hidden\" name=\"emailAddress\" value=\"$emailAddress\">";
            print "<input type=\"hidden\" name=\"companyName\" value=\"$companyName\">";
            print "<input type=\"hidden\" name=\"postCode\" value=\"$postCode\">";
            print "<input type=\"hidden\" name=\"addressLine1\" value=\"$addressLine1\">";
            print "<input type=\"hidden\" name=\"addressLine2\" value=\"$addressLine2\">";
            print "<input type=\"hidden\" name=\"addressLine3\" value=\"$addressLine3\">";
            print "<input type=\"hidden\" name=\"town\" value=\"$town\">";
            print "<input type=\"hidden\" name=\"county\" value=\"$county\">";
            print "<input type=\"hidden\" name=\"accountHolderName\" value=\"$accountHolderName\">";
            print "<input type=\"hidden\" name=\"accountNumber\" value=\"$accountNumber\">";
            print "<input type=\"hidden\" name=\"sort1\" value=\"$sort1\">";
            print "<input type=\"hidden\" name=\"sort2\" value=\"$sort2\">";
            print "<input type=\"hidden\" name=\"sort3\" value=\"$sort3\">";
            print "<input type=\"hidden\" name=\"accountHolderCheck\" value=\"$accountHolderCheck\">";
            print "<input type=\"submit\" name=\"submit2\" value=\"Submit\" class=\"button\">";
            print "</form>";
            print "</td></tr>";
            print "</table>";

            $resultMessage = 'After submitting you will receive an email confirmation.';

        }

    }

} else {
    $resultMessage = '';
    $accHolderStyle = 'display:none;';
    $notaccHolderStyle = 'display:display:table-cell;';
}

if (!$resultMessage OR $e) {

    ?>

    <form action="direct-debit.php" method="post">
    <input type="hidden" name="submitform" value="1">
    <input type="hidden" name="custref" value="<?php print $custref; ?>">

    <table align="center" cellpadding="6" class="tblwidth" style="background-color: #eee;">

        <tr>
            <td colspan="2">
                <p><strong>Account Holder Details</strong></p>
            </td>
        </tr>

        <tr>
            <td class="col1">
               Please tick the box below to comfirm that:<br><input type="checkbox" name="accountHolderCheck" style="padding:0px;margin:0px;" id="accchk"<?php if ($accountHolderCheck == "on") { print " CHECKED"; }?>>&nbsp;
            </td>
            <td class="col2">
                1. You are the account holder.<br>2. You are the only person required to authorise debits from this account.
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td colspan="2">
                <p><strong>You have confirmed that you are the account holder,<br>and that you are the only person required to authorise debits from this account.</strong></p>
                <p>Please complete the form below to set up a Direct Debit.</p>
            </td>
        </tr>

        <?php if ($e AND !$back) { ?>

        <tr>
            <td colspan="2">
                <span class="redtext">Please correct the errors indicated below.</span>
            </td>
        </tr>

        <?php } ?>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Title
            </td>
            <td class="col2">
                <select class="element" name="title">
                    <option></option>
                    <?php
                    $titles = array('Mr', 'Mrs', 'Miss', 'Ms', 'Dr');
                    foreach ($titles as $thistitle) {
                        print "<option value=\"$thistitle\""; if ($thistitle == $title) { print " SELECTED"; } print ">$thistitle</option>\n";
                    }
                    ?>
                </select>
                <?php if ($errorTitle) { print "<br><span class=\"redtext\">$errorTitle</span>"; } ?>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                First Name
            </td>
            <td class="col2">
                <input type="text" name="firstName" class="element" value="<?php print $firstName; ?>">
                <?php if ($errorfirstName) { print "<br><span class=\"redtext\">$errorfirstName</span>"; } ?>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Last Name
            </td>
            <td class="col2">
                <input type="text" name="lastName" class="element" value="<?php print $lastName; ?>">
                <?php if ($errorlastName) { print "<br><span class=\"redtext\">$errorlastName</span>"; } ?>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Phone Number
            </td>
            <td class="col2">
                <input type="text" name="phoneNumber" class="element" value="<?php print $phoneNumber; ?>">
                <?php if ($errorphoneNumber) { print "<br><span class=\"redtext\">$errorphoneNumber</span>"; } ?>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Email Address
            </td>
            <td class="col2">
                <input type="text" name="emailAddress" class="element" value="<?php print $emailAddress; ?>">
                <?php if ($erroremailAddress) { print "<br><span class=\"redtext\">$erroremailAddress</span>"; } ?>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td colspan="2">
                <p><strong>Business Address</strong></p>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Company Name
            </td>
            <td class="col2">
                <input type="text" name="companyName" class="element" value="<?php print $companyName; ?>">
                <?php if ($errorcompanyName) { print "<br><span class=\"redtext\">$errorcompanyName</span>"; } ?>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Address Line 1
            </td>
            <td class="col2">
                <input type="text" id="formatted_address_0" name="addressLine1" class="element" value="<?php print $addressLine1; ?>">
                <?php if ($erroraddressLine1) { print "<br><span class=\"redtext\">$erroraddressLine1</span>"; } ?>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Address Line 2 (Optional)
            </td>
            <td class="col2">
                <input type="text" id="formatted_address_1" name="addressLine2" class="element" value="<?php print $addressLine2; ?>">
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Address Line 3 (Optional)
            </td>
            <td class="col2">
                <input type="text" id="formatted_address_2" name="addressLine3" class="element" value="<?php print $addressLine3; ?>">
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Town or City
            </td>
            <td class="col2">
                <input type="text" id="town_or_city" name="town" class="element" value="<?php print $town; ?>">
                <?php if ($errortown) { print "<br><span class=\"redtext\">$errortown</span>"; } ?>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                County (Optional)
            </td>
            <td class="col2">
                <input type="text" id="county" name="county" class="element" value="<?php print $county; ?>">
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Post Code
            </td>
            <td class="col2">
                <input type="text" id="postcode" name="postCode" class="element" value="<?php print $postCode; ?>">
                <?php if ($errorpostCode) { print "<br><span class=\"redtext\">$errorpostCode</span>"; } ?>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td colspan="2">
                <p><strong>Bank Details</strong></p>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Account Name
            </td>
            <td class="col2">
                <input type="text" name="accountHolderName" class="element" value="<?php print $accountHolderName; ?>">
                <?php if ($erroraccountHolderName) { print "<br><span class=\"redtext\">$erroraccountHolderName</span>"; } ?>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Sort Code
            </td>
            <td class="col2">
                <input type="text" name="sort1" class="input-class input-1" maxlength="2" value="<?php print $sort1; ?>">
                &nbsp;-&nbsp;
                <input type="text" name="sort2" class="input-class input-2" maxlength="2" value="<?php print $sort2; ?>">
                &nbsp;-&nbsp;
                <input type="text" name="sort3" class="input-class input-3" maxlength="2" value="<?php print $sort3; ?>">
                <?php if ($errorsortCode) { print "<br><span class=\"redtext\">$errorsortCode</span>"; } ?>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td class="col1">
                Account Number
            </td>
            <td class="col2">
                <input type="text" name="accountNumber" class="element" maxlength="8" value="<?php print $accountNumber; ?>">
                <?php if ($erroraccountNumber) { print "<br><span class=\"redtext\">$erroraccountNumber</span>"; } ?>
            </td>
        </tr>

        <tr class="accchk-yes" style="<?php print $accHolderStyle; ?>">
            <td colspan="2">
                <p><input type="submit" name="submit" value="Submit" class="button"></p>
            </td>
        </tr>

        <tr class="accchk-no" style="<?php print $notaccHolderStyle; ?>">
            <td colspan="2">
                <p><strong>You cannot set up a direct debit unless you are the bank account holder,<br>and you are the only person required to authorise debits from this account.</strong></p>
                <p>If you are not the account holder or your account requires a second signatory,<br>please contact us on the number below to arrage an alternative payment method.</p>
            </td>
        </tr>

    </table>

    </form>

    <script type="text/javascript">
        $(document).ready(function() {
            $('#accchk').on('change', function() {
                $(".accchk-yes").toggle();
                $(".accchk-no").toggle();
            });
            var $inputs = $('.input-class');
            $inputs.on('keyup', function() {
            if ($(this).val().length == 2) {
                $inputs.eq($inputs.index(this) + 1).focus();
            }

});
        });
    </script>

    </body>
    </html>

<?php

}

if ($resultMessage) {
    print "<table align=\"center\" class=\"tblwidth\" style=\"background-color: #eee; padding: 4px;\">";
    print "<tr><td>";
    print "<p>$resultMessage</p>";
    print "</td></tr>";
    print "</table>";
}

?>

<table align="center" class="tblwidth" style="background-color: #35729f; padding: 10px;">
    <tr>
        <td class="hd1">
            Dephna Estates Ltd<br>2 Portal Way,<br> Gypsy Corner,<br>Acton,<br>London W3 6RT
        </td>
        <td class="hd2">
            <br><br>Tel: 020 8896 7575<br>Website: <a href="https://www.dephna.com" target="_blank">www.dephna.com</a><br>Email: <a href="mailto:accounts@dephna.com">accounts@dephna.com</a>
        </td>
    </tr>
</table>

<?php

function validate_email($email)
{
	$regexp = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,9})$/';
	$valid = 0;
	if (preg_match($regexp, $email)) {
		$valid = 1;
	} else {
		$valid = 0;
	}
	return $valid;
}


?>