<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
$page = "Bookings";
$subpage = "Vlans";
$internet = 1;
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			include('bookings-submenu.php');
			print "<p align=\"center\" class=\"heading\">Bookings - Internet</p>\n";
			print "<p align=\"center\">$menustr</p>\n";

			$sql = "SELECT * FROM IceAuth WHERE ID = '1'";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$api_access_point = $row['api_access_point'];
				$access_token     = $row['access_token'];

				$postdata = http_build_query(
					array(
						'apikey' => $access_token,
						'action' => 'listall'
					)
				);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $api_access_point."/?".$postdata);
				// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLINFO_HEADER_OUT, true);
				$result = curl_exec($ch);
				$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);

				if ($httpcode == 200) {

					// print "<p align=\"center\">$result</p>";
					if ($result = json_decode($result, true)) {

						$vlans = array();
						
						foreach ($result AS $id => $item) {
							if ($item['Data']['ID']) {
								$vlans[$item['Data']['ID']]['VLAN']     = $item['VLAN'];
								$vlans[$item['Data']['ID']]['Site']     = $item['Data']['Site'];
								$vlans[$item['Data']['ID']]['Tenant']   = $item['Data']['Tenant'];
								$vlans[$item['Data']['ID']]['Username'] = $item['Data']['Username'];
								$vlans[$item['Data']['ID']]['Status']   = $item['Data']['Status'];
							}
						}

					} else {
						$resultMessage .= "Json Decode Failed.<br />";
						print "<p align=\"center\">Sent: $api_access_point/?$postdata</p>";
						print "<p align=\"center\">Received: $result</p>";
					}
				} elseif ($httpcode == 400) {
					$resultMessage = "Error<br />";
					print "<p align=\"center\">Sent: $api_access_point/?$postdata</p>";
					print "<p align=\"center\">Received: $result</p>";
				} elseif ($httpcode == 401) {
					$resultMessage = "Unauthorised<br />";
				} else {
					$resultMessage = "Unknown error code $httpcode<br />";
					print "<p align=\"center\">Sent: $api_access_point/?$postdata</p>";
					print "<p align=\"center\">Received: $result</p>";
				}
				
			} else {
				$resultMessage .= "Could not get access token<br />";
			}

			$sql = "SELECT a.*, u.compname, s.title AS sitename, u.ID AS cid
					FROM bookings_an AS a
					LEFT JOIN users AS u ON u.custref = a.custref
					LEFT JOIN bookings AS b ON b.ID = a.bkid
					LEFT JOIN sites AS s ON s.ID = b.siteid
					WHERE a.ancid = '5'
					ORDER BY u.compname
			";
			if (($aresult = $dbcnx->query($sql)) AND ($aresult->num_rows)) {
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>BKID</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Custref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Rate</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>VLAN</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";

				print "</tr>\n";


				while($arow = $aresult->fetch_assoc()) {
					$bkid     = $arow['bkid'];
					$custref  = $arow['custref'];
					$cid      = $arow['cid'];
					$compname = $arow['compname'];
					$rate     = $arow['rate'];
					$sitename = $arow['sitename'];

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print " <td>$bkid</td>\n";
					print " <td>$sitename</td>\n";
					print " <td><a href=\"customer-view.php?id=$cid\">$compname</a></td>\n";
					print " <td>DP$custref</td>\n";
					print " <td align=\"right\">".number_format($rate,2)."</td>\n";
					if ($vlans[$custref]) {
						print " <td>{$vlans[$custref]['VLAN']}</td>\n";
						print " <td><a href=\"bookings-vlans-password.php?vlan={$vlans[$custref]['VLAN']}&amp;custref=$custref\">Password</a></td>\n";
						if ($vlans[$custref]['Status'] == "Active") {
							print " <td><a href=\"bookings-vlans-status.php?vlan={$vlans[$custref]['VLAN']}&amp;custref=$custref&amp;status=suspend\"><span style=\"color:green\">Restrict</span></a></td>\n";
						} else {
							print " <td><a href=\"bookings-vlans-status.php?vlan={$vlans[$custref]['VLAN']}&amp;custref=$custref&amp;status=active\"><span style=\"color:red\">Unrestrict</span></a></td>\n";
						}
						if ($accessRights[$page] > 2) {
							print " <td><a href=\"bookings-vlans-delete.php?vlan={$vlans[$custref]['VLAN']}&amp;custref=$custref\">Delete</a></td>\n";
						} else {
							print "	<td><span class=\"noAccess\">Delete</span></td>\n";
						}
					} else {
						print " <td>Not Found</td>\n";
						print " <td>&nbsp;</td>\n";
						print " <td>&nbsp;</td>\n";
						print " <td>&nbsp;</td>\n";
					}
					print "</tr>\n";

				}

				print "<tr>\n";
				print "<td colspan=\"8\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

			} else {
				$resultMessage .= "No Customers found with internet access";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}
		
	} else {
		$resultMessage .= "Access Denied<br />";
	}
	
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>";
	}

	print "<p align=\"center\"><a href=\"bookings-vlans-create.php\">Create New VLAN</p>";

	/* print "<p><b>Cust Refs on Ice</b></p>";
	print "<pre>";
	print_r($vlans);
	print "</pre>";

	print "<p><b>Raw Data From Ice</b></p>";
	print "<pre>";
	print_r($result);
	print "</pre>"; */

} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
