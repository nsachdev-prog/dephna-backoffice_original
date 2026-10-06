<?php
$page = "Staff";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($_REQUEST['id']) {
		$id = $_REQUEST['id'];
		$heading = "UPDATE STAFF";
	} else {
		$heading = "NEW STAFF";
	}
	print "<p class=\"heading\" align=\"center\">$heading</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$username     = $_POST['username'];
				$email        = $_POST['email'];
				$thispassword = $_POST['thispassword'];
				$telephone    = $_POST['telephone'];
				$telephone2   = $_POST['telephone2'];
				$site         = $_POST['site'];
				$firetester   = $_POST['firetester'];
				$fireadmin    = $_POST['fireadmin'];
				$onpayroll    = $_POST['onpayroll'];
				$onaccountant = $_POST['onaccountant'];
				$onsupport    = $_POST['onsupport'];
				$cladmin      = $_POST['cladmin'];
				$allowEngMode = $_POST['allowEngMode'];
				$pemail       = $_POST['pemail'];
				$allowAccess  = $_POST['allowAccess'];
				$accessTimeFr = $_POST['accessTimeFr'];
				$accessTimeTo = $_POST['accessTimeTo'];

				$accessDays = array();
				$accessDays[0] = "D";
				if ($_POST['accessDays1']) {$accessDays[1] = "1"; } else { $accessDays[1] = "0"; }
				if ($_POST['accessDays2']) {$accessDays[2] = "1"; } else { $accessDays[2] = "0"; }
				if ($_POST['accessDays3']) {$accessDays[3] = "1"; } else { $accessDays[3] = "0"; }
				if ($_POST['accessDays4']) {$accessDays[4] = "1"; } else { $accessDays[4] = "0"; }
				if ($_POST['accessDays5']) {$accessDays[5] = "1"; } else { $accessDays[5] = "0"; }
				if ($_POST['accessDays6']) {$accessDays[6] = "1"; } else { $accessDays[6] = "0"; }
				if ($_POST['accessDays7']) {$accessDays[7] = "1"; } else { $accessDays[7] = "0"; }
				$accessDays = implode("-",$accessDays);

				foreach($sections AS $key => $value) {
					if ($value == "Home") {
						$levelpost[] = "Home-3";
					} elseif ($value == "Logout") {
						$levelpost[] = "Logout-3";
					} else {
						$newsections[$value] = $_POST[$value];
						$levelpost[] = $value . "-" . $newsections[$value];
						if ($newsections[$value] == "") { $erlevels++; $e++; }
					}
				}

				foreach($homePage AS $key => $value) {
					$newhomepage[$value] = $_POST[$value];
					$homepost[] = $value . "-" . $newhomepage[$value];
				}

				if (!$username) { $erUsername++; $e++; }  else { $username = titlecase($username); }
				if (!$email) { $erEmail++; $e++; }
				elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $erEmail++; $e++; }
				else {
					# check if email address already exists
					$sql = "SELECT ID FROM staff WHERE email = '$email'";
					$eresult = $dbcnx->query($sql);
					if (($eresult) && ($eresult->num_rows)) {
						$erow = $eresult->fetch_assoc();
						$foundid = $erow['ID'];
						if ($foundid != $id) {
							$erEmail++; $e++;
						}
					}
				}
				if ($pemail) { if (!filter_var($pemail, FILTER_VALIDATE_EMAIL)) { $erpEmail++; $e++; } }
				if (!$id) { if (!$thispassword) { $erThispassword++; $e++; } }
				if ($site == "Select") { $ersite++; $e++; }
				if (!$e) {
					$username   = addslashes($username);
					$email      = addslashes($email);
					$telephone  = addslashes($telephone);
					$telephone2 = addslashes($telephone2);
					$levelpost  = implode(",",$levelpost);
					$levelpost  = addslashes($levelpost);
					$homepost   = implode(",",$homepost);
					$homepost   = addslashes($homepost);
					$pemail     = addslashes($pemail);
					if ($thispassword) {
						$thispassword = $dbcnx->real_escape_string($thispassword);
						$Salt = generateRandomString();
						$Algo = '6';
						$Rounds = '5000';
						$CryptSalt = '$' . $Algo . '$rounds=' . $Rounds . '$' . $Salt;
						$hashed_password = crypt($thispassword, $CryptSalt);
						$expires = strtotime("+$changePasswordMonths months");
					}

					if ($id) {
						if ($thispassword) {
							$sql = "UPDATE staff SET
								username='$username',
								email='$email',
								password='$hashed_password',
								expires='$expires',
								telephone='$telephone',
								telephone2='$telephone2',
								site='$site',
								firetester='$firetester',
								fireadmin='$fireadmin',
								onpayroll='$onpayroll',
								onaccountant='$onaccountant',
								onsupport='$onsupport',
								cladmin='$cladmin',
								allowEngMode='$allowEngMode',
								allowAccess='$allowAccess',
								levels='$levelpost',
								accessDays='$accessDays',
								accessTime='$accessTimeFr-$accessTimeTo',
								pemail='$pemail',
								homepage='$homepost'
							WHERE ID='$id'";
						} else {
							$sql = "UPDATE staff SET
								username='$username',
								email='$email',
								telephone='$telephone',
								telephone2='$telephone2',
								site='$site',
								firetester='$firetester',
								fireadmin='$fireadmin',
								onpayroll='$onpayroll',
								onaccountant='$onaccountant',
								onsupport='$onsupport',
								cladmin='$cladmin',
								allowEngMode='$allowEngMode',
								allowAccess='$allowAccess',
								levels='$levelpost',
								accessDays='$accessDays',
								accessTime='$accessTimeFr-$accessTimeTo',
								pemail='$pemail',
								homepage='$homepost'
							WHERE ID='$id'";
						}
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Record successfully updated</b>";
							$now = time();
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$username','$page','$now')");
						} else {
							$resultMessage = "<b>Could not update record</b><br />" . $dbcnx->error;
						}
					} else {
						$expires = strtotime("+$changePasswordMonths months");
						$sql = "INSERT INTO staff VALUES (
							null,
							'$hashed_password',
							'$expires',
							'$username',
							'$email',
							'$telephone',
							'$telephone2',
							'$site',
							'$firetester',
							'$fireadmin',
							'$onpayroll',
							'$onaccountant',
							'$onsupport',
							'$cladmin',
							'0',
							'',
							'',
							'',
							'$allowEngMode',
							'$allowAccess',
							'$levelpost',
							'$accessDays',
							'$accessTimeFr-$accessTimeTo',
							'0',
							'$pemail',
							'$homepost'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Record successfully added</b>";
							$now = time();
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Added','$username','$page','$now')");
						} else {
							$resultMessage = "<b>Could not add new record to database</b><br />" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {

  				$sql = "SELECT * FROM staff WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$username     = $row['username'];
					$email        = $row['email'];
					$telephone    = $row['telephone'];
					$telephone2   = $row['telephone2'];
					$site         = $row['site'];
					$allowAccess  = $row['allowAccess'];
					$levels       = $row['levels'];
					$accessDays   = $row['accessDays'];
					$accessTime   = $row['accessTime'];
					$firetester   = $row['firetester'];
					$fireadmin    = $row['fireadmin'];
					$onpayroll    = $row['onpayroll'];
					$onaccountant = $row['onaccountant'];
					$onsupport    = $row['onsupport'];
					$cladmin      = $row['cladmin'];
					$allowEngMode = $row['allowEngMode'];
					$pemail       = $row['pemail'];
					$homepage     = $row['homepage'];
					$levels = stripslashes($levels);
					$levels = explode(",",$levels);
					$homepage = stripslashes($homepage);
					$homepage = explode(",",$homepage);
					list($accessTimeFr,$accessTimeTo) = explode("-",$accessTime);
					$minsTo = $hours * 60 + $mins;
					foreach($levels AS $key => $value) {
						list($k,$v) = explode("-",$value);
						$newsections[$k] = $v;
					}
					foreach($homepage AS $key => $value) {
						list($k,$v) = explode("-",$value);
						$newhomepage[$k] = $v;
					}
				} else {
					$resultMessage = "<b>User Not Found</b><br />" . $dbcnx->error;
				}

			} else {
				$accessDays = "D-1-1-1-1-1-0-0";
				$accessTimeFr = "09:00";
				$accessTimeTo = "17:00";
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					print "<style>\n";
					include_once('slider-range.css');
					print "</style>\n";
					print "<form name=\"addform\" method=\"post\" action=\"staff-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"5\" cellpadding=\"3\">\n";
		  			make_form_text("Full Name","username","text","50",$username,$erUsername);
		  			make_form_text("email","email","text","60",$email,$erEmail);
		  			make_form_text("Personal Email","pemail","text","60",$pemail,$erpEmail);
		  			print "<tr>\n";
					print "	<td colspan=\"2\">Leave password box blank to keep the existing password.<br>If you change your own password you will need to log back in again.</td>\n";
					print "</tr>\n";
		  			make_form_text("Password","thispassword","text","20",$thispassword,$erThispassword);
		  			make_form_text("Personal Telephone","telephone","text","50",$telephone,$ertelephone);
		  			make_form_text("Work Telephone","telephone2","text","50",$telephone2,$ertelephone2);

					$sql = "SELECT * FROM sites ORDER BY title";
					$sitesresult = $dbcnx->query($sql);
					if ($ersite) { $class = "element_error"; } else { $class = "element"; }
		  			print "<tr>\n";
					print "	<td align=\"right\">Site:</td>\n";
					print "	<td><select name=\"site\" class=\"$class\">\n";
					print "		<option value=\"Select\">Select</option>\n";
					while ($sitesrow = $sitesresult->fetch_assoc()) {
						$title = $sitesrow['title'];
						print "		<option value=\"$title\"";
						if ($title == $site) {
							print " SELECTED";
						}
						print ">$title</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Fire Alarm Tester:</td>\n";
					print "	<td align=\"left\"><input type=\"checkbox\" name=\"firetester\""; if ($firetester == "on") { print " CHECKED"; } print ">&nbsp;Not Used. Hard Coded as Sorin for all sites.</td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Fire Alarm Admin:</td>\n";
					print "	<td align=\"left\"><input type=\"checkbox\" name=\"fireadmin\""; if ($fireadmin == "on") { print " CHECKED"; } print "></td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">On Payroll:</td>\n";
					print "	<td align=\"left\"><input type=\"checkbox\" name=\"onpayroll\""; if ($onpayroll == "on") { print " CHECKED"; } print "></td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">On Accountant Summary:</td>\n";
					print "	<td align=\"left\"><input type=\"checkbox\" name=\"onaccountant\""; if ($onaccountant == "on") { print " CHECKED"; } print "></td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">On Support:</td>\n";
					print "	<td align=\"left\"><input type=\"checkbox\" name=\"onsupport\""; if ($onsupport == "on") { print " CHECKED"; } print "></td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Cleaning Admin:</td>\n";
					print "	<td align=\"left\"><input type=\"checkbox\" name=\"cladmin\""; if ($cladmin == "on") { print " CHECKED"; } print "></td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Allow CR Engineer Mode:</td>\n";
					print "	<td align=\"left\"><input type=\"checkbox\" name=\"allowEngMode\""; if ($allowEngMode == "on") { print " CHECKED"; } print "></td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Allow Access:</td>\n";
					print "	<td align=\"left\"><input type=\"checkbox\" name=\"allowAccess\""; if ($allowAccess == "on") { print " CHECKED"; } print "></td>";
					print "</tr>\n";

		  			print "</table>\n";

					print "<p align=\"center\"><b>Access Rights</b></p>\n";
					if ($erlevels) { print "<p class=\"redtext\" align=\"center\">You did not complete all the access rights</p>"; }
					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"3\">\n";
					print "<tr>\n";
					print "	<td class=\"colHead\">Section</td>\n";
					print "	<td class=\"colHead\" width=\"90\" align=\"center\">No Access</td>\n";
					print "	<td class=\"colHead\" width=\"90\" align=\"center\">View Only</td>\n";
					print "	<td class=\"colHead\" width=\"90\" align=\"center\">View &amp; Update</td>\n";
					print "	<td class=\"colHeadEnd\" width=\"90\" align=\"center\">View, Update &amp; Delete</td>\n";
					print "</tr>\n";

	  	 			foreach($sections AS $key => $value) {
		  	 			if (($value != "Home") && ($value != "Logout")) {
		  	 				print "<tr>\n";
		  	 				print "	<td class=\"col\">$value</td>\n";
		  	 				print "	<td class=\"col\" align=\"center\"><input name=\"$value\" type=\"radio\" value=\"0\""; if ($newsections[$value] == 0) { print " CHECKED"; } print "></td>\n";
		  	 				print "	<td class=\"col\" align=\"center\"><input name=\"$value\" type=\"radio\" value=\"1\""; if ($newsections[$value] == 1) { print " CHECKED"; } print "></td>\n";
		  	 				print "	<td class=\"col\" align=\"center\"><input name=\"$value\" type=\"radio\" value=\"2\""; if ($newsections[$value] == 2) { print " CHECKED"; } print "></td>\n";
		  	 				print "	<td class=\"colEnd\" align=\"center\"><input name=\"$value\" type=\"radio\" value=\"3\""; if ($newsections[$value] == 3) { print " CHECKED"; } print "></td>\n";
		  	 				print "</tr>\n";
						}
					}
	  	 			print "</table>\n";

					print "<p align=\"center\"><b>Home Page Sections</b></p>\n";
					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"3\">\n";
					print "<tr>\n";
					print "	<td class=\"colHead\">Section</td>\n";
					print "	<td class=\"colHeadEnd\" align=\"center\">Select</td>\n";
					print "</tr>\n";
					foreach($homePage AS $key => $value) {
						print "<tr>\n";
						print "	<td class=\"col\">$value</td>\n";
						print "	<td class=\"colEnd\" align=\"center\"><input name=\"$value\" type=\"checkbox\""; if ($newhomepage[$value] == 'on') { print " CHECKED"; } print "></td>\n";
						print "</tr>\n";
				 	}
					print "</table>\n";

	  	 			print "<p align=\"center\"><b>Access Days</b></p>\n";
	  	 			print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"3\">\n";
	  				print "	<tr><td>Mon</td><td>Tue</td><td>Wed</td><td>Thu</td><td>Fri</td><td>Sat</td><td>Sun</td></tr>\n";
	  				print "	<tr>\n";
	  				$accessDays = explode("-",$accessDays);
	  				for ($n=1; $n <= 7; $n++) {
	  					if ($accessDays[$n]) {
							print "		<td><input type=\"checkbox\" name=\"accessDays{$n}\" CHECKED></td>\n";
						} else {
							print "		<td><input type=\"checkbox\" name=\"accessDays{$n}\"></td>\n";
						}
					}
		  			print "	</tr>\n";
					print "	</table>";

					print "<p align=\"center\"><b>Access Time</b></p>\n";

					print "<input type=\"hidden\" name=\"accessTimeFr\" class=\"slider-time\" value=\"$accessTimeFr\">\n";
					print "<input type=\"hidden\" name=\"accessTimeTo\" class=\"slider-time2\" value=\"$accessTimeTo\">\n";

					print "<table align=\"center\" cellspacing=\"10\" cellpadding=\"0\">\n";
					print "<tr>\n";
					print "<td align=\"center\">\n";
					print "<div id=\"time-range\">\n";
					print "    <p>From: <span class=\"slider-time1\">$accessTimeFr</span> To: <span class=\"slider-time3\">$accessTimeTo</span></p>\n";
					print "    <div class=\"sliders_step1\">\n";
					print "        <div id=\"slider-range\"></div>\n";
					print "    </div>\n";
					print "</div>\n";
					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";

	  	 			print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"16\">\n";
					print "<tr>\n";
					print "	<td align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"buttonSmall\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";

					list($hours,$mins) = explode(":",$accessTimeFr);
					$minsFr = $hours * 60 + $mins;
					list($hours,$mins) = explode(":",$accessTimeTo);
					$minsTo = $hours * 60 + $mins;
?>
<script>
$("#slider-range").slider({
	range: true,
	min: 0,
	max: 1440,
	step: 30,
	<?php print "values: [$minsFr, $minsTo],"; ?>
	slide: function (e, ui) {

		var hours1 = Math.floor(ui.values[0] / 60);
		var minutes1 = ui.values[0] - (hours1 * 60);
		if (hours1.toString().length == 1) hours1 = '0' + hours1;
		if (minutes1.length == 1) minutes1 = '0' + minutes1;
		if (minutes1 == 0) minutes1 = '00';
		$('.slider-time').val(hours1 + ':' + minutes1);
		$('.slider-time1').html(hours1 + ':' + minutes1);

		var hours2 = Math.floor(ui.values[1] / 60);
		var minutes2 = ui.values[1] - (hours2 * 60);
		if (hours2.toString().length == 1) hours2 = '0' + hours2;
		if (minutes2.length == 1) minutes2 = '0' + minutes2;
		if (minutes2 == 0) minutes2 = '00';
		$('.slider-time2').val(hours2 + ':' + minutes2);
		$('.slider-time3').html(hours2 + ':' + minutes2);
	}
});
</script>
<?php
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Login</a></p>\n";
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"staff-view.php\">Staff</a></p></p>\n";
print "</td>\n";
include('footer.php');

function validate_email($email)
{
	$regexp = '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,4})$/';
	$valid = 0;
	if (preg_match($regexp, $email)) {
		$valid = 1;
	} else {
		$valid = 0;
	}
	return $valid;
}
function titlecase($string)
{
  $string = ucwords(strtolower($string));
  foreach (array('-', '\'') as $delimiter) {
	if (strpos($string, $delimiter)!==false) {
	  $string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
	}
  }
  return $string;
}
function make_form_text($desc,$fname,$type,$maxlen,$value,$err)
{
	if ($err) { $class = "element_error"; } else { $class = "element"; }
	print "<tr>\n";
	print "	<td align=\"right\">$desc:</td>\n";
	print "	<td><input name=\"$fname\" type=\"$type\" class=\"$class\" maxlength=\"$maxlen\" value=\"$value\" /></td>";
	print "</tr>\n";
}
function generateRandomString($length = 16) {
  return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}
?>
