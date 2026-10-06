<?php
$page = "Purchases";
$subpage = "Guide";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

        // open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";

            include('purchases-submenu.php');
            print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\" class=\"larger\">Guide</p>\n";

            print "<style>.para1, ol { padding:0px 50px 0px 50px; font-size:1.1em; list-style-position: inside;} </style>";

            print "<p class=\"para1\"><b>To enter a purchase and debit note:-</b></p>\n";

            print "<ol>\n";
            print "<li>Click on add transaction</li>\n";
            print "<li>Is it one off expense? Yes or no. Most of these are reoccurring purchases and are not a one off</li>\n";
            print "<li>Transaction type - credit or invoice</li>\n";
            print "<li>Supplier</li>\n";
            print "<li>Supplier ref</li>\n";
            print "<li>All you need to do is enter the gross number - the system does the rest</li>\n";
            print "<li>Upload the pdf on the system</li>\n";
            print "</ol>\n";


            print "<p class=\"para1\"><b>To enter a statement</b></p>\n";
            print "<p class=\"para1\">Add statement and upload</p>\n";

            print "<p class=\"para1\"><b>Settlement Discount</b></p>\n";
            print "<p class=\"para1\">The only account that has a settlement discount is Edmunsun UK - 2.5% - you just need to raise a manual credit note at the end of the month. This is on the basis that the payment is made on time.</p>\n";

            print "<p class=\"para1\"><b>VAT Quarter</b></p>\n";
            print "<p class=\"para1\">Vat quarters: Dec - Feb, Mar - May etc.</p>\n";

            print "<p class=\"para1\"><b>Payment Freqency</b></p>\n";
            print "<p class=\"para1\">I want to make payments evry month but sometimes this changes to every 2 weeks.<br />\n";
            print "We only make payments off the back of a statement.</p>\n";

            print "<p class=\"para1\"><b>To make a payment and reconcile the account:-</b></p>\n";
            print "<ol>\n";
            print "<li>Choose the supplier.</li>\n";
            print "<li>After the date click unpaid - this will give you all the unpaid invoices from that supplier.</li>\n";
            print "<li>Click approve</li>\n";
            print "<li>If I have disapproved an invoice you will see marked disapproved, thus cannot be paid.</li>\n";
            print "<li>Once you have done this please send an email to suppliers@dephna.com to let me know that the statement has been reconcilled and I will process accordingly.</li>\n";
            print "</ol>\n";  

        } else {
			$resultMessage = "<b>Could not connect to database</b><br />" . $dbcnx->connect_error;
        }
        
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

function utf16_to_utf8($str) {
    $c0 = ord($str[0]);
    $c1 = ord($str[1]);

    if ($c0 == 0xFE && $c1 == 0xFF) {
        $be = true;
    } else if ($c0 == 0xFF && $c1 == 0xFE) {
        $be = false;
    } else {
        return $str;
    }

    $str = substr($str, 2);
    $len = strlen($str);
    $dec = '';
    for ($i = 0; $i < $len; $i += 2) {
        $c = ($be) ? ord($str[$i]) << 8 | ord($str[$i + 1]) :
                ord($str[$i + 1]) << 8 | ord($str[$i]);
        if ($c >= 0x0001 && $c <= 0x007F) {
            $dec .= chr($c);
        } else if ($c > 0x07FF) {
            $dec .= chr(0xE0 | (($c >> 12) & 0x0F));
            $dec .= chr(0x80 | (($c >>  6) & 0x3F));
            $dec .= chr(0x80 | (($c >>  0) & 0x3F));
        } else {
            $dec .= chr(0xC0 | (($c >>  6) & 0x1F));
            $dec .= chr(0x80 | (($c >>  0) & 0x3F));
        }
    }
    return $dec;
}
?>
