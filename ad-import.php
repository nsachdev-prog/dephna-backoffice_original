<?php
$page = "Debtors";
$subpage = "Import";
$csv_dir = "../../dis2_db/csv";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\" class=\"heading\">IMPORT DEBTORS</p>\n";

			# print submenu
			include('ad-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			# get uploaded csv file
			if ($_FILES['newdebtors']['tmp_name']) {

				# convert to utf8 and save
				$uploaded = file_get_contents($_FILES['newdebtors']['tmp_name']);
				$uploaded = utf16_to_utf8($uploaded);
				$userno   = sprintf("%03s",$_SESSION['user']);
				$filename = "AD-".date("Y")."-".date("m")."-".date("d")."-".date("H")."-".date("i")."-".date("s")."-".$userno.".csv";
				$handle   = fopen("$csv_dir/$filename", "w");
				fwrite($handle, $uploaded);
				fclose($handle);

				# get it back as an array
				$newindex = file("$csv_dir/$filename");

				print "<p align=\"center\" class=\"heading\">Integrity Check</p>\n";
				print "<p align=\"center\">Check to see if customers exist</p>\n";

				for ($n=0; $n < count($newindex); $n++) {
					list($date,$tref,$customer,$amount,$pending,$date2,$days) = explode(",",$newindex[$n]);
					$customer = trim($customer," \t\n\r\0\x0B\"");

					# extract custref and custname
					list($custref,$custname) = explode("(",$customer);
					$custref = str_replace("DP","",$custref);
					$custname = str_replace(")","",$custname);

					# check customer exists
					$sql = "SELECT ID FROM users WHERE custref='$custref'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						# $row  = $result->fetch_assoc();
						# $id   = $row['ID'];
						# print $custref." - ".$id." - ".$result->num_rows."<br>";
					} else {
						$resultMessage .= "Customer DP$custref Does not exist\n";
						$e++;
					}
				}

				if (!$e) {

					print "<p align=\"center\">All customers found...</p>\n";

					# delete existing aged debtors
					$sql = "DELETE FROM debtors WHERE 1";
					$result = $dbcnx->query($sql);

					# extract and post each line
					$now = time();
					$added = 0;
					$failed = 0;
					$totalPending = 0;
					$months = array("Jan"=>"01","Feb"=>"02","Mar"=>"03","Apr"=>"04","May"=>"05","Jun"=>"06","Jul"=>"07","Aug"=>"08","Sep"=>"09","Oct"=>"09","Nov"=>"10","Dec"=>"12");
					for ($n=0; $n < count($newindex); $n++) {
						list($date,$tref,$customer,$amount,$pending,$date2,$days) = explode(",",$newindex[$n]);
						$date     = trim($date," \t\n\r\0\x0B\"");
						$tref     = trim($tref," \t\n\r\0\x0B\"");
						$customer = trim($customer," \t\n\r\0\x0B\"");
						$amount   = trim($amount," \t\n\r\0\x0B\"");
						$pending  = trim($pending," \t\n\r\0\x0B\"");
						$date2    = trim($date2," \t\n\r\0\x0B\"");
						$days     = trim($days," \t\n\r\0\x0B\"");

						$amount *= -1;
						$pending *= -1;

						# extract custref and custname from customer
						list($custref,$custname) = explode("(",$customer);
						$custref  = str_replace("DP","",$custref);
						$custname = str_replace(")","",$custname);

						# extract type and ref
						if (is_numeric($tref)) {
							$type = "I";
						} else {
							list($tref,$type) = explode('/',$tref);
							if ($type == "CN") {
								$type = "C";
							} elseif ($type == "PT") {
								$type = "P";
							} elseif ($type == "RP") {
								$type = "R";
							} else {
								$type = "X";
							}

							# I = Invoice
							# C = Credit Note
							# P = Payment
							# R = Receipt
							# X = Unknown

						}

						# reformat date
						list($day,$month,$year) = explode("-",$date);
						$month = $months[$month];
						$day   = sprintf("%02s",$day);
						$date  = "$year-$month-$day";

						$sql = "INSERT INTO debtors VALUES (
							null,
							'$custref',
							'$custname',
							'$date',
							'$type',
							'$tref',
							'$amount',
							'$pending',
							'$days',
							'$now',
							'{$_SESSION['username']}'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$added++;
							$totalPending += $pending;
						} else {
							$failed++;
						}
					}
					print "<p align=\"center\">$added lines added $failed lines failed<br />";
					print "With total value of <b>&pound;$totalPending Pending</b></p>";
					$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Imported','$added Lines - Value $totalPending','$page','$now')");

				} else {
					print "<p align=\"center\" class=\"heading\">ERROR</p>\n";
					if ($e > 1) {
						print "<p align=\"center\"><b>There are $e errors</b><br />Fix the problems and then try again.</p>";
					} else {
						print "<p align=\"center\"><b>There is $e error</b><br />Fix the problem and then try again.</p>";
					}
				}

			} else {
				print "<form name=\"newindexform\" method=\"post\" enctype=\"multipart/form-data\" action=\"ad-import.php\">\n";
				print "<p align=\"center\">Use this program to upload debtors csv file.</p>\n";
				print "<p align=\"center\">Make sure you have already created any new customers.</p>\n";
				print "<p align=\"center\">New Debtors File: <input type=\"file\" name=\"newdebtors\" size=\"35\" class=\"element\" /></p>\n";
				print "<p align=\"center\"><input type=\"submit\" name=\"submit\" value=\"Submit\" class=\"button\"></p>\n";
				print "</form>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
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
