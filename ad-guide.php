<?php
$page = "Debtors";
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

            print "<p align=\"center\" class=\"heading\">DEBTORS</p>\n";
            include('ad-submenu.php');
            print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\" class=\"larger\">Guide</p>\n";

            print "<style>.para1 { padding:0px 50px 0px 50px; font-size:1.2em;} </style>";

            // get info
            $sql = "SELECT * FROM ad_settings WHERE ID='1'";
            $result = $dbcnx->query($sql);
            if ($result->num_rows) {
                $row = $result->fetch_assoc();
                $warnings = $row['warnings'];
                $level1   = $row['level1'];
                $level2   = $row['level2'];
                $level3   = $row['level3'];
                $lastupd  = $row['lastupd'];
                $updby    = $row['updby'];
                $lastupd  = date("jS M Y H:i",$lastupd);

                $posdays = array(
                    "3rd"  => "03","4th"  => "04","5th"  => "05","6th"  => "06","7th"  => "07","8th"  => "08",
                    "9th"  => "09","10th" => "10","11th" => "11","12th" => "12","13th" => "13","14th" => "14",
                    "15th" => "15","16th" => "16","17th" => "17","18th" => "18","19th" => "19","20th" => "20",
                    "21st" => "21","22nd" => "22","23rd" => "23","24th" => "24","25th" => "25","26th" => "26",
                    "27th" => "27","28th" => "28"
                );

                $level1 = array_search($level1, $posdays);
                $level2 = array_search($level2, $posdays);
                $level3 = array_search($level3, $posdays);

                print "<p class=\"para1\"><b>Debtor Warnings</b></p>\n";
                print "<p class=\"para1\">Emails are automatically sent to customers with outstanding balances three times per month on the days chosen in the settings tab above.</p>\n";
                print "<p class=\"para1\">Currently $level1, $level2 and $level3</p>\n";
                print "<p class=\"para1\">Templates for each message can be found in the mailouts section and are labelled DebtorsWarnings1, 2 and 3. This corresponds with the 1st, 2nd and 3rd days selected in settings.</p>\n";
                print "<p class=\"para1\">The first mailout is not sent to customers that pay by direct debit as this serves to inform non direct debit customers that payment is due by the 7th of the month.</p>\n";
                print "<p class=\"para1\">An extra message is included for customers that are restricted to remind that that their services are currently restricted.</p>\n";
            
                print "<p class=\"para1\"><b><i>Current Templates</i></b></p>\n";

                $templates = array(
                    "DebtorsWarning1",
                    "DebtorsWarning2",
                    "DebtorsWarning3"
                );

                foreach($templates AS $template) {
                    $sql = "SELECT * FROM mailouts WHERE mref = '$template'";
                    $result = $dbcnx->query($sql);
                    if ($result->num_rows) {
                        $row  = $result->fetch_assoc();
                        $subject = $row['subject'];
                        $body    = $row['html'];
                        $body = nl2br($body);
                        print "<p class=\"para1\"><b>$template</b></p>\n";
                        print "<p class=\"para1\">Subject: $subject</p>\n";
                        print "<p class=\"para1\">$body</p>\n";

                    }
                }

            } else {
                $resultMessage = "<b>Could not get current settings.</b>";
            }

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
