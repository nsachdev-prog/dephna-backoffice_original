<?php
$page = "Debtors";
$subpage = "Settings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// print heading and submenu
			print "<p align=\"center\" class=\"heading\">DEBTORS</p>\n";
			include('ad-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			print "<p align=\"center\" class=\"larger\">Settings</p>\n";

			$posdays = array(
				"3rd"  => "03","4th"  => "04","5th"  => "05","6th"  => "06","7th"  => "07","8th"  => "08",
				"9th"  => "09","10th" => "10","11th" => "11","12th" => "12","13th" => "13","14th" => "14",
				"15th" => "15","16th" => "16","17th" => "17","18th" => "18","19th" => "19","20th" => "20",
				"21st" => "21","22nd" => "22","23rd" => "23","24th" => "24","25th" => "25","26th" => "26",
				"27th" => "27","28th" => "28"
			);

			if ($_REQUEST['update']) {

				if ($_POST['posted']) {
					$warnings = $_POST['warnings'];
					$days     = $_POST['days'];
					sort($days);
					$level1 = $days[0];
					$level2 = $days[1];
					$level3 = $days[2];
					$level4 = $days[3];

					if (count($days) != 4) {
						$erdays = "You must select 3 days";
						$e++;
					}

					if (!$e) {
						$now = time();
						$sql = "UPDATE ad_settings SET 
							warnings = '$warnings',
							level1 = '$level1',
							level2 = '$level2',
							level3 = '$level3',
							level4 = '$level4',
							lastupd = '$now',
							updby = '{$_SESSION['username']}' 
							WHERE ID='1'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Record successfully updated</b>";
							// save and email rdb
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							include_once("rdb-save.php");
    						include_once("rdb-mail.php");
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Debtors Settings','$page','$now')");
						} else {
							$resultMessage = "<b>Could not update record</b>" . $dbcnx->error;
						}
					}

				} else {
					// get details
					$sql = "SELECT * FROM ad_settings WHERE ID='1'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$warnings = $row['warnings'];
						$level1   = $row['level1'];
						$level2   = $row['level2'];
						$level3   = $row['level3'];
						$level4   = $row['level4'];
						$lastupd  = $row['lastupd'];
					} else {
						$resultMessage = "<b>Could not get current settings.</b>";
					}
				}

				if (!$resultMessage) {
					if ((!$posted) || ($e)) {
						$daysarray = array($level1,$level2,$level3,$level4);
						print "<form name=\"addform\" method=\"post\" action=\"ad-settings.php?$qString\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"update\" value=\"1\" />\n";

						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "	<tr><td>\n";

						print "<table border=\"0\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
						print "	<tr>\n";
						print "		<td colspan=\"3\"><b>Debtor Warnings</b><br>Switch debtor warnings on or off.</td>\n";
						print "	<tr>\n";
						print "	</tr>\n";
						print "		<td align=\"center\">On<br><input type=\"radio\" name=\"warnings\" value=\"On\""; if ($warnings == "On") { print " CHECKED"; } print " ></td>\n";
						print "		<td align=\"center\">Off<br><input type=\"radio\" name=\"warnings\" value=\"Off\""; if ($warnings == "Off") { print " CHECKED"; } print " ></td>\n";
						print "		<td width=\"100px\">&nbsp;</td>\n";
						print "	</tr>\n";
						print "	<tr>\n";
						print "		<td colspan=\"3\">&nbsp;</td>\n";
						print "	<tr>\n";
						print "</table>\n";

						print "<table border=\"0\" cellspacing=\"4px\" cellpadding=\"2px\">\n";
						print "	<tr>\n";
						print "		<td colspan=\"27\"><b>Warning Days</b><br>Select the four days on which warning levels 1,2, 3 &amp; 4 will be sent.";
						if ($erdays) { print "<br><span class=\"redtext\">$erdays</span>"; }
						print "</td>\n";
						print "	</tr>\n";
						print "	<tr>\n";
						foreach ($posdays AS $keyday => $valday) {
							if ($valday == "16") {
								print "	</tr>\n";
								print "	<tr>\n";
							}
							print "		<td align=\"center\">$keyday <br><input  type=\"checkbox\" name=\"days[]\" value=\"$valday\""; if (in_array($valday,$daysarray)) { print " CHECKED"; } print " ></td>\n";
						}
						print "	</tr>\n";
						print "</table>\n";

						print "	</td></tr>\n";
						print "</table>\n";

						print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";
						print "</form>\n";
					}
				}

			} else {
				// get and display current settings
				// get details
				$sql = "SELECT * FROM ad_settings WHERE ID='1'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$warnings = $row['warnings'];
					$level1   = $row['level1'];
					$level2   = $row['level2'];
					$level3   = $row['level3'];
					$level4   = $row['level4'];
					$lastupd  = $row['lastupd'];
					$updby    = $row['updby'];
					$lastupd  = date("jS M Y H:i",$lastupd);

					$level1txt = array_search($level1, $posdays);
					$level2txt = array_search($level2, $posdays);
					$level3txt = array_search($level3, $posdays);
					$level4txt = array_search($level4, $posdays);

					print "<table align=\"center\" cellspacing=\"4px\" cellpadding=\"2px\">\n";
					print "	<tr>\n";
					print "		<td align=\"right\"><b>Debtor Warnings:</b></td>\n";
					print "		<td>$warnings</td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td align=\"right\"><b>Warning Days:</b></td>\n";
					print "		<td>$level1txt, $level2txt, $level3txt &amp; $level4txt of the month.</td>\n";
					print "	</tr>\n";
					print "</table>\n";

					print "<p align=\"center\">Last Updated By $updby on $lastupd</p>\n";

					print "<p align=\"center\"><a href=\"ad-settings.php?update=1\">Update</a></p>\n";

					/* print "<p align=\"center\" class=\"larger\">This Month</p>\n";

					print "<p align=\"center\">CAM will change the above dates if they are on a weekend or bank holiday.</p>\n";

					$thismonth = date("Y-m");

					// if levels are not weekdays this month change them to the next monday
					if (date("N", strtotime("$thismonth-$level1")) > 5) {
						$level1 = date("d", strtotime("Next Monday",strtotime("$thismonth-$level1")));
						$resultMessage .= "Level 1 is Weekend, Changed to Monday<br>";
					};
					if (date("N", strtotime("$thismonth-$level2")) > 5) {
						$level2 = date("d", strtotime("Next Monday",strtotime("$thismonth-$level2")));
						$resultMessage .= "Level 2 is Weekend, Changed to Monday<br>";
					};
					if (date("N", strtotime("$thismonth-$level3")) > 5) {
						$level3 = date("d", strtotime("Next Monday",strtotime("$thismonth-$level3")));
						$resultMessage .= "Level 3 is Weekend, Changed to Monday<br>";
					};
					if (date("N", strtotime("$thismonth-$level4")) > 5) {
						$level4 = date("d", strtotime("Next Monday",strtotime("$thismonth-$level4")));
						$resultMessage .= "Level 4 is Weekend, Changed to Monday<br>";
					};
				
					// get bank holidays uk/england and wales from https://www.gov.uk/bank-holidays.json
					$bankholidays = file_get_contents("https://www.gov.uk/bank-holidays.json");
					$bankholidays = json_decode($bankholidays, true);
					$bankholidays = $bankholidays['england-and-wales']['events'];
				
					// are any of the levels bank holidays?
					foreach ($bankholidays as $bankholiday) {
						if ($bankholiday['date'] == "$thismonth-$level1") {
							$level1 = date("d", strtotime("+1 Day",strtotime("$thismonth-$level1")));
							$resultMessage .= "Level 1 is Bank Holiday, Changed to Tuesday<br>";
						};
						if ($bankholiday['date'] == "$thismonth-$level2") {
							$level2 = date("d", strtotime("+1 Day",strtotime("$thismonth-$level2")));
							$resultMessage .= "Level 2 is Bank Holiday, Changed to Tuesday<br>";
						};
						if ($bankholiday['date'] == "$thismonth-$level3") {
							$level3 = date("d", strtotime("+1 Day",strtotime("$thismonth-$level3")));
							$resultMessage .= "Level 3 is Bank Holiday, Changed to Tuesday<br>";
						};
						if ($bankholiday['date'] == "$thismonth-$level4") {
							$level4 = date("d", strtotime("+1 Day",strtotime("$thismonth-$level4")));
							$resultMessage .= "Level 4 is Bank Holiday, Changed to Tuesday<br>";
						};
					};

					$level1txt = array_search($level1, $posdays);
					$level2txt = array_search($level2, $posdays);
					$level3txt = array_search($level3, $posdays);
					$level4txt = array_search($level4, $posdays);

					print "<table align=\"center\" cellspacing=\"4px\" cellpadding=\"2px\">\n";
					print "	<tr>\n";
					print "		<td align=\"right\"><b>Warning Days ".date("M Y").":</b></td>\n";
					print "		<td>$level1txt, $level2txt, $level3txt &amp; $level4txt.</td>\n";
					print "	</tr>\n";
					print "</table>\n"; */


				} else {
					$resultMessage = "<b>Could not get current settings.</b>";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database</b><br />" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	if ($_REQUEST['update']) {
		print "<p align=\"center\"><a href=\"ad-settings.php\">Return</a></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
// limit checkbox checks to 3
print "<script type=\"text/javascript\">\n";
print "	var theCheckboxes = $(\"input[type='checkbox']\");\n";
print "	theCheckboxes.click(function()\n";
print "	{\n";
print "	    if (theCheckboxes.filter(\":checked\").length > 4)\n";
print "	        $(this).removeAttr(\"checked\");\n";
print "	});\n";
print "</script>\n";
include('footer.php');
?>
