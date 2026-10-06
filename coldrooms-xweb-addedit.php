<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
$page = "Coldroom_Monitors";
$subpage = "Monitors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">XWEB ACCESS CONTROL</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
				
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id']; 
				$heading = "Update";
			} else {
				$heading = "New";
			}

			print "<p align=\"center\" class=\"larger\">$heading</p>\n";
		
			if ($_POST['posted']) {

				$siteid = strip_tags($_POST['siteid']);
				$xsub   = strip_tags($_POST['xsub']);
				$prefix = strip_tags($_POST['prefix']);
				$api_access_point = strip_tags($_POST['api_access_point']);
				$loginaddress = strip_tags($_POST['loginaddress']);
				$xlogin       = strip_tags($_POST['xlogin']);
				$xpassword    = strip_tags($_POST['xpassword']);
				$temperature  = strip_tags($_POST['temperature']);
				$evaporator   = strip_tags($_POST['evaporator']);
				$product      = strip_tags($_POST['product']);
				$setpoint     = strip_tags($_POST['setpoint']);
				$onoff        = strip_tags($_POST['onoff']);
				$doorswitch   = strip_tags($_POST['doorswitch']);
				$doorheater   = strip_tags($_POST['doorheater']);
				$dooropen     = strip_tags($_POST['dooropen']);
				$defrost      = strip_tags($_POST['defrost']);
				$keyboard     = strip_tags($_POST['keyboard']);
				$light        = strip_tags($_POST['light']);

				if (!$siteid) { $ersiteid = "Select Site"; $e++; }
				if (!$xsub)   { $erxsub   = "Select Xsub"; $e++; }
				if (!$api_access_point) {
					$erapi_access_point = "Enter Access Address"; $e++;
				} else {
					if (strpos(strtolower($api_access_point), "?") !== FALSE) {
						$erapi_access_point = "Complete Access Address"; $e++;
					}
				}
				// if (!$prefix) { $erprefix = "Enter unit prefix"; $e++; } 
				if (!$loginaddress) {
					$erloginaddress = "Enter loginaddress"; $e++;
				} else {
					if (strpos(strtolower($loginaddress), "?") !== FALSE) {
						$erloginaddress = "Complete the login address"; $e++;
					}
				}
				if (!$xlogin)       { $erxlogin = "Enter login"; $e++; }
				if (!$xpassword)    { $erxpassword = "Enter password"; $e++; }
				if (!$temperature)  { $ertemperature = "Enter value for temperature"; $e++; }
				if (!$evaporator)   { $erevaporator = "Enter value for evaporator"; $e++; }
				if (!$product)   { $erproduct = "Enter value for product"; $e++; }
				if (!$setpoint)     { $ersetpoint = "Enter value for setpoint"; $e++; }
				if (!$onoff)        { $eronoff = "Enter value for onoff"; $e++; }
				if (!$doorswitch)   { $erdoorswitch = "Enter value for doorswitch"; $e++; }
				if (!$doorheater)   { $erdoorheater = "Enter value for doorheater"; $e++; }
				if (!$dooropen)     { $erdooropen = "Enter value for dooropen"; $e++; }
				if (!$defrost)      { $erdefrost = "Enter value for defrost"; $e++; }
				if (!$keyboard)     { $erkeyboard = "Enter value for keyboard"; $e++; }
				if (!$light)        { $erlight = "Enter value for light"; $e++; }

				if (!$e) {

					$now = time();
								
					if ($id) {
						$sql = "UPDATE XWebAuth SET 
							siteid='$siteid',
							xsub='$xsub',
							prefix='$prefix',
							api_access_point='$api_access_point',
							loginaddress='$loginaddress',
							xlogin='$xlogin',
							xpassword='$xpassword',
							temperature='$temperature',
							evaporator='$evaporator',
							product='$product',
							setpoint='$setpoint',
							onoff='$onoff',
							doorswitch='$doorswitch',
							doorheater='$doorheater',
							dooropen='$dooropen',
							defrost='$defrost',
							keyboard='$keyboard',
							light='$light'
							WHERE ID = '$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "Xweb Access Point Updated<br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Xweb Access Point $siteid/$xsub','$page','$now')");
						} else {
							$resultMessage .= "Could NOT Update Details<br>" . $dbcnx->error . "<br>";
						}
					} else {
						$sql = "INSERT INTO XWebAuth VALUES (
							null,
							'$siteid',
							'$xsub',
							'$prefix',
							'$api_access_point',
							'$loginaddress',
							'$xlogin',
							'$xpassword',
							'$temperature',
							'$evaporator',
							'$product',
							'$setpoint',
							'$onoff',
							'$doorswitch',
							'$doorheater',
							'$dooropen',
							'$defrost',
							'$keyboard',
							'$light',
							'',
							'',
							'',
							'',
							'',
							''
						)";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "Xweb Access Point Created<br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Xweb Access Point $siteid/$xsub','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
				}
			}

			elseif ($id) {
				$sql = "SELECT * FROM XWebAuth WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$siteid  = $row['siteid'];
					$xsub    = $row['xsub'];
					$prefix  = $row['prefix'];
					$api_access_point = $row['api_access_point'];
					$loginaddress = $row['loginaddress'];
					$xlogin       = $row['xlogin']; 
					$xpassword    = $row['xpassword']; 
					$temperature  = $row['temperature']; 
					$evaporator   = $row['evaporator']; 
					$product   = $row['product']; 
					$setpoint     = $row['setpoint']; 
					$onoff        = $row['onoff']; 
					$doorswitch   = $row['doorswitch']; 
					$doorheater   = $row['doorheater']; 
					$dooropen     = $row['dooropen']; 
					$defrost      = $row['defrost']; 
					$keyboard     = $row['keyboard']; 
					$light        = $row['light']; 
				} else {
					$resultMessage .= "<span class=\"redtext\">Record Not Found!</span><br />" . $dbcnx->error . "<br />";
				}
			} else {
				// defaults
				$api_access_point = "http://xweb.?????.dephna.com:8?/api";
				$loginaddress = "http://xweb.?????.dephna.com:8?/";
				$xlogin      = "Admin";
				$xpassword   = "REDACTED";
				$temperature = 1;
				$evaporator  = 2;
				$product  = 3;
				$setpoint    = 4;
				$onoff       = 29;
				$doorswitch  = 10;
				$doorheater  = 22;
				$dooropen    = 42;
				$defrost     = 19;
				$keyboard    = 31;
				$light       = 20;
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
						
					print "<form name=\"addform\" method=\"post\" action=\"coldrooms-xweb-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "	<tr>\n";
					print "		<td align=\"right\"><b>Site</b></td>\n";
					print "		<td><select name=\"siteid\" class=\"element\">\n";
					$sql = "SELECT s.ID, s.title
							FROM sites AS s
							LEFT JOIN units AS u ON u.siteid = s.ID
							WHERE u.unittype = 'Cold Room'
							GROUP BY s.ID
							ORDER BY s.title
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						while ($row = $result->fetch_assoc()) {
							$thissiteid   = $row['ID'];
							$thissitename = $row['title'];
							print "		<option value=\"$thissiteid\""; if ($siteid == $thissiteid) { print " SELECTED"; } print ">$thissitename</option>\n";
						}
					}
					print "		</select>";
					print "		</td>\n";
					print "	</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Xsub</b> (Sub site number 1,2,3...)</td>\n";
					print "	<td><input type=\"text\" name=\"xsub\" value=\"$xsub\" class=\"element_tiny\" />\n";
					if ($erxsub) { print "<br><span class=\"redtext\">$erxsub</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Unit Prefix</b></td>\n";
					print "	<td><input type=\"text\" name=\"prefix\" value=\"$prefix\" class=\"element_small\" />\n";
					if ($erprefix) { print "<br><span class=\"redtext\">$erprefix</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>API Access Address</b></td>\n";
					print "	<td><input type=\"text\" name=\"api_access_point\" value=\"$api_access_point\" class=\"element\" />\n";
					if ($erapi_access_point) { print "<br><span class=\"redtext\">$erapi_access_point</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Login Address</b></td>\n";
					print "	<td><input type=\"text\" name=\"loginaddress\" value=\"$loginaddress\" class=\"element\" />\n";
					if ($erloginaddress) { print "<br><span class=\"redtext\">$erloginaddress</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Login Username</b></td>\n";
					print "	<td><input type=\"text\" name=\"xlogin\" value=\"$xlogin\" class=\"element\" />\n";
					if ($erxlogin) { print "<br><span class=\"redtext\">$erxlogin</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Password</b></td>\n";
					print "	<td><input type=\"text\" name=\"xpassword\" value=\"$xpassword\" class=\"element\" />\n";
					if ($erxpassword) { print "<br><span class=\"redtext\">$erxpassword</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" class=\"larger\" align=\"center\"><br>Sensor IDs<br>&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Temperature</b></td>\n";
					print "	<td><input type=\"text\" name=\"temperature\" value=\"$temperature\" class=\"element_tiny\" />\n";
					if ($ertemperature) { print "<br><span class=\"redtext\">$ertemperature</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Evaporator</b></td>\n";
					print "	<td><input type=\"text\" name=\"evaporator\" value=\"$evaporator\" class=\"element_tiny\" />\n";
					if ($erevaporator) { print "<br><span class=\"redtext\">$erevaporator</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Product</b></td>\n";
					print "	<td><input type=\"text\" name=\"product\" value=\"$product\" class=\"element_tiny\" />\n";
					if ($erproduct) { print "<br><span class=\"redtext\">$erproduct</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Setpoint</b></td>\n";
					print "	<td><input type=\"text\" name=\"setpoint\" value=\"$setpoint\" class=\"element_tiny\" />\n";
					if ($ersetpoint) { print "<br><span class=\"redtext\">$ersetpoint</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Power</b></td>\n";
					print "	<td><input type=\"text\" name=\"onoff\" value=\"$onoff\" class=\"element_tiny\" />\n";
					if ($eronoff) { print "<br><span class=\"redtext\">$eronoff</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Door Switch</b></td>\n";
					print "	<td><input type=\"text\" name=\"doorswitch\" value=\"$doorswitch\" class=\"element_tiny\" />\n";
					if ($erdoorswitch) { print "<br><span class=\"redtext\">$erdoorswitch</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Door Heater</b></td>\n";
					print "	<td><input type=\"text\" name=\"doorheater\" value=\"$doorheater\" class=\"element_tiny\" />\n";
					if ($erdoorheater) { print "<br><span class=\"redtext\">$erdoorheater</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Door Open</b></td>\n";
					print "	<td><input type=\"text\" name=\"dooropen\" value=\"$dooropen\" class=\"element_tiny\" />\n";
					if ($erdooropen) { print "<br><span class=\"redtext\">$erdooropen</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Defrost</b></td>\n";
					print "	<td><input type=\"text\" name=\"defrost\" value=\"$defrost\" class=\"element_tiny\" />\n";
					if ($erdefrost) { print "<br><span class=\"redtext\">$erdefrost</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Keyboard</b></td>\n";
					print "	<td><input type=\"text\" name=\"keyboard\" value=\"$keyboard\" class=\"element_tiny\" />\n";
					if ($erkeyboard) { print "<br><span class=\"redtext\">$erkeyboard</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Light</b></td>\n";
					print "	<td><input type=\"text\" name=\"light\" value=\"$light\" class=\"element_tiny\" />\n";
					if ($erlight) { print "<br><span class=\"redtext\">$erlight</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";

					print "</form>\n";
					
				}
			}

			print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

			print "<tr>\n";
			print "	<td>A new access point must also have a new cron job to collect the data.</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td>If you edit the Site or Xsub the cron job will also need to be changed.</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td>The sensor ids suggested for a new access point are the ones being used at existing sites.</td>\n";
			print "</tr>\n";

			print "</table>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"coldrooms-xweb.php\">Return</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
 