<?php
$page ="Sites";
$subpage ="Sites";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($_REQUEST['id']) {
		$id = $_REQUEST['id'];
		$heading = "Update Site";
	} else {
		$heading = "New Site";
	}
	print "<p align = \"center\" class=\"heading\">SITES</p>\n";
	print "<p align = \"center\" class=\"larger\">$heading</p>\n";
	
	if ($accessRights[$page] > 1) {
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
		
			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$title       = $_POST['title'];
				$ipaddress   = $_POST['ipaddress'];
				$siteaddress = $_POST['siteaddress'];
				$latitude    = $_POST['latitude'];
				$longitude   = $_POST['longitude'];
				$floors      = $_POST['floors'];
				$training    = $_POST['training'];
				$hsdTitle    = $_POST['hsdTitle'];
				$hsdPath     = $_POST['hsdPath'];
				$hsdrNum     = $_POST['hsdrNum'];
				$hsdrMonths  = $_POST['hsdrMonths'];
				$hsdrChecker = $_POST['hsdrChecker'];
				$hsdrLastChk = $_POST['hsdrLastChk'];
				$xwEngineer1name  = $_POST['xwEngineer1name'];
				$xwEngineer1email = $_POST['xwEngineer1email'];
				$xwEngineer1phone = $_POST['xwEngineer1phone'];
                $xwEngineer1text  = $_POST['xwEngineer1text'];
				$xwEngineer2name  = $_POST['xwEngineer2name'];
				$xwEngineer2email = $_POST['xwEngineer2email'];
				$xwEngineer2phone = $_POST['xwEngineer2phone'];
                $xwEngineer2text  = $_POST['xwEngineer2text'];
				$xwEngineerDefault = $_POST['xwEngineerDefault'];
				$xwOOOWKFr = $_POST['xwOOOWKFr'];
				$xwOOOWKTo = $_POST['xwOOOWKTo'];
				$xwOOOWEFr = $_POST['xwOOOWEFr'];
				$xwOOOWETo = $_POST['xwOOOWETo'];
				$xwOOOBHFr = $_POST['xwOOOBHFr'];
				$xwOOOBHTo = $_POST['xwOOOBHTo'];

				if (!$title) { $ertitle = "Please give a site name"; $e++; }
				if (!$ipaddress) { $eripaddress = "Please give an IP address"; $e++; }
				if ($latitude) {
					if (!is_numeric($latitude)) {
						$erlatitude = "Latitude must be numeric"; $e++;
					} else {
						$latitude = number_format($latitude, 4, '.', '');
					}
				}
				if ($longitude) {
					if (!is_numeric($longitude)) {
						$erlongitude = "Longitude must be numeric"; $e++;
					} else {
						$longitude = number_format($longitude, 4, '.', '');
					}
				}
				if (!$floors) { $erfloors = "Please give number of floors"; $e++; }
				elseif (!is_numeric($floors)) { $erfloors = "Floors must be numeric"; $e++; }
				
				if ($hsdrMonths) {
					if (!$hsdTitle) { $erhsdTitle = "Enter a document title"; $e++; }
					if (!$hsdPath)  { $erhsdPath = "Enter a document path"; $e++; }
				}
				if ($xwEngineer1email) {
					if (!validate_email($xwEngineer1email)) { $erxwEngineer1email = "Email Address Invalid"; $e++; }
				}
				if ($xwEngineer2email) {
					if (!validate_email($xwEngineer2email)) { $erxwEngineer2email = "Email Address Invalid"; $e++; }
				}

				// error check out of hours times
				if ($xwOOOWKFr) {
					if (!preg_match("/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/", $xwOOOWKFr)) { $erxwOOOWK = "Invalid Time"; $e++; }
				}
				if ($xwOOOWKTo) {
					if (!preg_match("/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/", $xwOOOWKTo)) { $erxwOOOWK = "Invalid Time"; $e++; }
				}
				if ($xwOOOWEFr) {
					if (!preg_match("/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/", $xwOOOWEFr)) { $erxwOOOWE = "Invalid Time"; $e++; }
				}
				if ($xwOOOWETo) {
					if (!preg_match("/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/", $xwOOOWETo)) { $erxwOOOWE = "Invalid Time"; $e++; }
				}
				if ($xwOOOBHFr) {
					if (!preg_match("/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/", $xwOOOBHFr)) { $erxwOOOBH = "Invalid Time"; $e++; }
				}
				if ($xwOOOBHTo) {
					if (!preg_match("/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/", $xwOOOBHTo)) { $erxwOOOBH = "Invalid Time"; $e++; }
				}
				
			
				if (!$e) {
					
					list($d,$m,$y) = explode("-",$hsdrLastChk);
					$hsdrLastChk = "$y-$m-$d";
				
					$title       = addslashes($title);
					$ipaddress   = addslashes($ipaddress);
					$siteaddress = addslashes($siteaddress);
					$latitude    = addslashes($latitude);
					$longitude   = addslashes($longitude);
					$hsdTitle    = addslashes($hsdTitle);
					$hsdPath     = addslashes($hsdPath);
					$xwEngineer1name  = addslashes($xwEngineer1name);
					$xwEngineer1email = addslashes($xwEngineer1email);
					$xwEngineer1phone = addslashes($xwEngineer1phone);
                    $xwEngineer1text  = addslashes($xwEngineer1text);
					$xwEngineer2name  = addslashes($xwEngineer2name);
					$xwEngineer2email = addslashes($xwEngineer2email);
					$xwEngineer2phone = addslashes($xwEngineer2phone);
                    $xwEngineer2text  = addslashes($xwEngineer2text);
					$now = time();
					
					if ($id) {
						$sql = "UPDATE sites SET
							title='$title',
							ipaddress='$ipaddress',
							siteaddress='$siteaddress',
							latitude='$latitude',
							longitude='$longitude',
							floors='$floors',
							training='$training',
							hsdTitle='$hsdTitle',
							hsdPath='$hsdPath',
							hsdrNum='$hsdrNum',
							hsdrMonths='$hsdrMonths',
							hsdrChecker='$hsdrChecker',
							hsdrLastChk='$hsdrLastChk',
							xwEngineer1name='$xwEngineer1name',
							xwEngineer1email='$xwEngineer1email',
							xwEngineer1phone='$xwEngineer1phone',
                            xwEngineer1text='$xwEngineer1text',
							xwEngineer2name='$xwEngineer2name',
							xwEngineer2email='$xwEngineer2email',
							xwEngineer2phone='$xwEngineer2phone',
                            xwEngineer2text='$xwEngineer2text',
							xwEngineerDefault='$xwEngineerDefault',
                            xwOOOWK='$xwOOOWKFr-$xwOOOWKTo',
							xwOOOWE='$xwOOOWEFr-$xwOOOWETo',
							xwOOOBH='$xwOOOBHFr-$xwOOOBHTo'
							WHERE ID='$id'
						";
						if ($dbcnx->query($sql)) {
							$resultMessage = "Record successfully updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$title','Sites','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO sites VALUES (
							null,
							'$title',
							'$ipaddress',
							'$siteaddress',
							'$latitude',
							'$longitude',
							'$floors',
							'$training',
							'$hsdTitle',
							'$hsdPath',
							'$hsdrNum',
							'$hsdrMonths',
							'$hsdrChecker',
							'$hsdrLastChk',
							'$xwEngineer1name',
							'$xwEngineer1email',
							'$xwEngineer1phone',
                            '$xwEngineer1text',
							'$xwEngineer2name',
							'$xwEngineer2email',
							'$xwEngineer2phone',
                            '$xwEngineer2text',
							'$xwEngineerDefault',
                            '$xwOOOWKFr-$xwOOOWKTo',
							'$xwOOOWEFr-$xwOOOWETo',
							'$xwOOOBHFr-$xwOOOBHTo'
						)";
						if ($dbcnx->query($sql)) {
							$resultMessage = "Record successfully added";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$title','Sites','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {
			
  			$sql = "SELECT * FROM sites WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$title       = $row['title'];
					$ipaddress   = $row['ipaddress'];
					$siteaddress = $row['siteaddress'];
					$latitude    = $row['latitude'];
					$longitude   = $row['longitude'];
					$floors      = $row['floors'];
					$training    = $row['training'];
					$hsdTitle    = $row['hsdTitle'];
					$hsdPath     = $row['hsdPath'];
					$hsdrNum     = $row['hsdrNum'];
					$hsdrMonths  = $row['hsdrMonths'];
					$hsdrChecker = $row['hsdrChecker'];
					$hsdrLastChk = $row['hsdrLastChk'];
					$xwEngineer1name  = $row['xwEngineer1name'];
					$xwEngineer1email = $row['xwEngineer1email'];
					$xwEngineer1phone = $row['xwEngineer1phone'];
                    $xwEngineer1text  = $row['xwEngineer1text'];
					$xwEngineer2name  = $row['xwEngineer2name'];
					$xwEngineer2email = $row['xwEngineer2email'];
					$xwEngineer2phone = $row['xwEngineer2phone'];
                    $xwEngineer2text  = $row['xwEngineer2text'];
					$xwEngineerDefault = $row['xwEngineerDefault'];
					$xwOOOWK = $row['xwOOOWK'];
					$xwOOOWE = $row['xwOOOWE'];
					$xwOOOBH = $row['xwOOOBH'];

					list($xwOOOWKFr,$xwOOOWKTo) = explode("-",$xwOOOWK);
					list($xwOOOWEFr,$xwOOOWETo) = explode("-",$xwOOOWE);
					list($xwOOOBHFr,$xwOOOBHTo) = explode("-",$xwOOOBH);
                    
					list($y,$m,$d) = explode("-",$hsdrLastChk);
					$hsdrLastChk = "$d-$m-$y";

				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}
				
			} else {
                $xwOOOWKFr = "00:00";
				$xwOOOWKTo = "00:00";
				$xwOOOWEFr = "17:30";
				$xwOOOWETo = "20:00";
				$xwOOOBHFr = "17:30";
				$xwOOOBHTo = "20:00";
            }

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					print "<style>\n";
					include_once('slider-range.css');
					print "</style>\n";
					print "<form name=\"addform\" method=\"post\" action=\"sites-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					
					print "<p align=\"center\">Warning. You should not change the site name after you have<br />created telecoms, utilities or bookings for a site.</p>\n";
					
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					if ($id) {
						print "	<td align=\"right\">Site Name:</td>\n";
						print "	<td>$title</td>\n";
						print "	<input type=\"hidden\" name=\"title\" value=\"$title\" />\n";
					} else {
						print "<tr>\n";
						print "	<td align=\"right\">Site Name:</td>\n";
						print "	<td><input name=\"title\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"30\" value=\"$title\" />";
						if ($ertitle) { print "&nbsp;<span class=\"redtext\">$ertitle</span>"; }
						print "</td>\n";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "	<td align=\"right\">IP Address:</td>\n";
					print "	<td><input name=\"ipaddress\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"30\" value=\"$ipaddress\" />";
					if ($eripaddress) { print "&nbsp;<span class=\"redtext\">$eripaddress</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\">Site Address:</td>\n";
					print "	<td><textarea name=\"siteaddress\" wrap=\"virtual\" rows=\"5\" class=\"element_box_small\">$siteaddress</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Latitude:</td>\n";
					print "	<td><input name=\"latitude\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"10\" value=\"$latitude\" />";
					if ($erlatitude) { print "&nbsp;<span class=\"redtext\">$erlatitude</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Longitude:</td>\n";
					print " <td><input name=\"longitude\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"10\" value=\"$longitude\" />";
					if ($erlongitude) { print "&nbsp;<span class=\"redtext\">$erlongitude</span>"; }
					print "</td>\n";
					print "</tr>\n";
				
					print "<tr>\n";
					print "	<td align=\"right\">Floors:</td>\n";
					print "	<td><input name=\"floors\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"2\" value=\"$floors\" />";
					if ($erfloors) { print "&nbsp;<span class=\"redtext\">$erfloors</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\">Training Applies?:</td>\n";
					print "	<td><input type=\"checkbox\" name=\"training\""; if ($training) { print " CHECKED"; } print " /></td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"center\" colspan=\"2\" class=\"larger\"><br />H&amp;S Documents<br />&nbsp;</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\">Document Name:</td>\n";
					print "	<td><input name=\"hsdTitle\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$hsdTitle\" />";
					if ($erhsdTitle) { print "&nbsp;<span class=\"redtext\">$erhsdTitle</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\">Document Path:</td>\n";
					print "	<td><input name=\"hsdPath\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$hsdPath\" />";
					if ($erhsdPath) { print "&nbsp;<span class=\"redtext\">$erhsdPath</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Drawing Number:</td>\n";
					print "	<td><input name=\"hsdrNum\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$hsdrNum\" />";
					if ($erhsdrNum) { print "&nbsp;<span class=\"redtext\">$erhsdrNum</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\">Check Every:</td>\n";
					print "	<td><select name=\"hsdrMonths\" class=\"element\">\n";
					print "		<option value=\"0\"";  if ($hsdrMonths == "0")  { print " SELECTED"; } print ">No Check</option>\n";
					print "		<option value=\"1\"";  if ($hsdrMonths == "1")  { print " SELECTED"; } print ">Month</option>\n";
					print "		<option value=\"3\"";  if ($hsdrMonths == "3")  { print " SELECTED"; } print ">3 Months</option>\n";
					print "		<option value=\"6\"";  if ($hsdrMonths == "6")  { print " SELECTED"; } print ">6 Months</option>\n";
					print "		<option value=\"12\""; if ($hsdrMonths == "12") { print " SELECTED"; } print ">12 Months</option>\n";
					print "	</select>";
					if ($erhsdrMonths) { print "&nbsp;<span class=\"redtext\">$erhsdrMonths</span>"; }
					print "</td>\n";
					print "</tr>\n";
					   
					print "<tr>\n";
					print "	<td align=\"right\">Checker:</td>\n";
					print "	<td><select name=\"hsdrChecker\" class=\"element\">\n";
					print "		<option value=\"6\"";  if ($hsdrChecker == "6")  { print " SELECTED"; } print ">Mr Rajive Sachdev</option>\n";
					print "		<option value=\"5\"";  if ($hsdrChecker == "5")  { print " SELECTED"; } print ">Mr Nimesh Sachdev</option>\n";
					print "	</select>";
					if ($erhsdrChecker) { print "&nbsp;<span class=\"redtext\">$erhsdrChecker</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\">Last Check Date:</td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" name=\"hsdrLastChk\" value=\"$hsdrLastChk\" class=\"element\" />\n";
					if ($erhsdrLastChk) { print "&nbsp;<span class=\"redtext\">$erhsdrLastChk</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"center\" colspan=\"2\" class=\"larger\"><br />Cold Room Engineers<br />&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"center\" colspan=\"2\"><b>Office Hours</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Engineer Name:</td>\n";
					print "	<td><input name=\"xwEngineer1name\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$xwEngineer1name\" />";
					if ($erxwEngineer1name) { print "&nbsp;<span class=\"redtext\">$erxwEngineer1name</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Email:</td>\n";
					print "	<td><input name=\"xwEngineer1email\" type=\"text\" class=\"element\" maxlength=\"80\" value=\"$xwEngineer1email\" />";
					if ($erxwEngineer1email) { print "&nbsp;<span class=\"redtext\">$erxwEngineer1email</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Phone:</td>\n";
					print "	<td><input name=\"xwEngineer1phone\" type=\"text\" class=\"element\" maxlength=\"80\" value=\"$xwEngineer1phone\" />";
					if ($erxwEngineer1phone) { print "&nbsp;<span class=\"redtext\">$erxwEngineer1phone</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Message:</td>\n";
					print "	<td><input name=\"xwEngineer1text\" type=\"text\" class=\"element\" maxlength=\"250\" value=\"$xwEngineer1text\" />";
					if ($erxwEngineer1text) { print "&nbsp;<span class=\"redtext\">$erxwEngineer1text</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"center\" colspan=\"2\"><br /><b>Out Of Office Hours</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Engineer Name:</td>\n";
					print "	<td><input name=\"xwEngineer2name\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$xwEngineer2name\" />";
					if ($erxwEngineer2name) { print "&nbsp;<span class=\"redtext\">$erxwEngineer2name</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Email:</td>\n";
					print "	<td><input name=\"xwEngineer2email\" type=\"text\" class=\"element\" maxlength=\"80\" value=\"$xwEngineer2email\" />";
					if ($erxwEngineer2email) { print "&nbsp;<span class=\"redtext\">$erxwEngineer2email</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Phone:</td>\n";
					print "	<td><input name=\"xwEngineer2phone\" type=\"text\" class=\"element\" maxlength=\"80\" value=\"$xwEngineer2phone\" />";
					if ($erxwEngineer2phone) { print "&nbsp;<span class=\"redtext\">$erxwEngineer2phone</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Message:</td>\n";
					print "	<td><input name=\"xwEngineer2text\" type=\"text\" class=\"element\" maxlength=\"250\" value=\"$xwEngineer2text\" />";
					if ($erxwEngineer2text) { print "&nbsp;<span class=\"redtext\">$erxwEngineer2text</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"center\" colspan=\"2\"><br /><b>Out Of Office Times</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Weekdays:</td>\n";
					print "	<td><input name=\"xwOOOWKFr\" type=\"text\" class=\"element_tiny\" maxlength=\"5\" value=\"$xwOOOWKFr\" /> to <input name=\"xwOOOWKTo\" type=\"text\" class=\"element_tiny\" maxlength=\"5\" value=\"$xwOOOWKTo\" />";
					if ($erxwOOOWK) { print "&nbsp;<span class=\"redtext\">$erxwOOOWK</span>"; }
					print "</td>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Weekends:</td>\n";
					print "	<td><input name=\"xwOOOWEFr\" type=\"text\" class=\"element_tiny\" maxlength=\"5\" value=\"$xwOOOWEFr\" /> to <input name=\"xwOOOWETo\" type=\"text\" class=\"element_tiny\" maxlength=\"5\" value=\"$xwOOOWETo\" />";
					if ($erxwOOOWE) { print "&nbsp;<span class=\"redtext\">$erxwOOOWE</span>"; }
					print "</td>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Bank Holidays:</td>\n";
					print "	<td><input name=\"xwOOOBHFr\" type=\"text\" class=\"element_tiny\" maxlength=\"5\" value=\"$xwOOOBHFr\" /> to <input name=\"xwOOOBHTo\" type=\"text\" class=\"element_tiny\" maxlength=\"5\" value=\"$xwOOOBHTo\" />";
					if ($erxwOOOBH) { print "&nbsp;<span class=\"redtext\">$erxwOOOBH</span>"; }
					print "</td>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\">Use Out Of Office Hours:</td>\n";
					print "	<td>";
					print "<input type=\"radio\" name=\"xwEngineerDefault\""; if ($xwEngineerDefault == 2) { print " CHECKED"; } print " value=\"2\">Yes<br>";
					print "<input type=\"radio\" name=\"xwEngineerDefault\""; if ($xwEngineerDefault == 1) { print " CHECKED"; } print " value=\"1\">No<br>";
					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";

					print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";
	
					print "</form>\n";
					
					# jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
					print "	});\n";
					print "</script>\n";
					
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"sites-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

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
