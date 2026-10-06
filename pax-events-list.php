<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
// increase memory limit for large queries
ini_set('memory_limit', '512M');
$page = "Paxton";
$subpage = "Events";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">PAXTON</p>\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			if ($siteid) {

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$sitename = $srow['title'];
					print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
					include('pax-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
					print "<p align=\"center\" class=\"larger\">Events</p>\n";

					include_once('PaxGetAccessToken.php');

					if ($access_token) {

						$find = $_REQUEST['find'];
						$date = $_REQUEST['date'];
						$today = date("Y-m-d");
						if (!$date) { $date = $today; }
						$ad = $_REQUEST['ad'];
						$dr = $_REQUEST['dr'];
						
						if ($find) {
							$search = " AND (e.firstName LIKE '%$find%' OR e.surname LIKE '%$find%' OR u.departmentName LIKE '%$find%' OR e.peripheralName LIKE '%$find%' OR e.cardNumber LIKE '%$find%')";
						}
						if ($ad) {
							$search .= " AND (e.eventTypeDescription LIKE '%Access denied%' AND (e.firstName > '' OR e.surname > ''))";
						}
						if ($dr) {
							$search .= " AND (e.eventTypeDescription LIKE '%Door%')";
						}
	
						$fr = $date . "T00:00:00";
						$to = $date . "T23:59:59";
						$nextdate = date("Y-m-d",strtotime("$date +1 Day"));
						$backdate = date("Y-m-d",strtotime("$date -1 Day"));
						if ($date == $today) {
							$displayText = "Today";
						} else {
							$displayText = date("jS M Y",strtotime($date));
						}

						print "<form name=\"addform\" method=\"post\" action=\"pax-events-list.php?siteid=$siteid\">";
						print "<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2px\">\n";
						print "<tr>\n";
						print "<td style=\"font-size:1.8em\"><a href=\"pax-events-list.php?siteid=$siteid&amp;find=$find&amp;ad=$ad&amp;&amp;dr=$dr&amp;date=$backdate\"><b>&lt;&lt;</b></a></td>\n";
						print "<td style=\"font-size:1.2em\"><b>Find</b></td>\n";
						print "<td><input type=\"text\" name=\"find\" value=\"$find\" class=\"element_small\" /></td>\n";
						print "<td style=\"font-size:1.2em\"><b>&nbsp;Access Denied</b></td>\n";
						print "<td><input type=\"checkbox\" name=\"ad\""; if ($ad) { print " CHECKED"; } print " /></td>\n";
						print "<td style=\"font-size:1.2em\"><b>&nbsp;Doors</b></td>\n";
						print "<td><input type=\"checkbox\" name=\"dr\""; if ($dr) { print " CHECKED"; } print " /></td>\n";
						print "<td style=\"font-size:1.2em\"><b>Date</b></td>\n";
						print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
						print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "<td style=\"font-size:1.2em\"><a href=\"pax-events-list.php?siteid=$siteid\"><b>All</b></a></td>\n";
						if ($date < $today) { 
							print "<td style=\"font-size:3.6em;line-height:.4em\"><a href=\"pax-events-list.php?siteid=$siteid&amp;find=$find&amp;ad=$ad&amp;date=$today\"><b>&bull;</b></a></td>\n"; 
							print "<td style=\"font-size:1.8em\"><a href=\"pax-events-list.php?siteid=$siteid&amp;find=$find&amp;ad=$ad&amp;date=$nextdate\"><b>&gt;&gt;</b></a></td>\n";
						} else {
							print "<td style=\"font-size:3.6em;color:#999;line-height:.4em\"><b>&bull;</b></td>\n"; 
							print "<td style=\"font-size:1.8em;color:#999\"><b>&gt;&gt;</b></td>\n";
						}
						print "</tr>\n";
						print "</table>";
						print "</form>\n";

						print "<p class=\"larger\" align=\"center\">$displayText</p>\n";

						$headers = array(
							'Authorization: bearer '.$access_token,
							'Accept: application/json'
						);

						$postdata = http_build_query(
							array(
								'query' => "SELECT e.*, u.departmentName
										FROM EventsEx AS e
										LEFT JOIN UsersEx AS u ON e.userID = u.userID
										WHERE e.eventTime >= '$fr' AND e.eventTime <= '$to' $search
										ORDER BY e.eventTime DESC"
							)
						);

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/customquery/querydb/?$postdata");
						curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
						curl_setopt($ch, CURLINFO_HEADER_OUT, true);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
						$result = curl_exec($ch);
						curl_close($ch);
				
						if ($result !== FALSE) {

							if ($result = json_decode($result, TRUE)) {

								if ($result) {

									$departments = array();

									print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
									print "<tr>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Department</b></td>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Location</b></td>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Token</b></td>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Description</b></td>\n";
									print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
									print "</tr>\n";

									// row background colours
									print "<style>\n";
									print ".r0 { background-color:#fff }\n";
									print ".r1 { background-color:#ddd }\n";
									print "</style>\n";

									foreach ($result AS $id => $event) {
										list($date,$time) = explode("T",$event['eventTime']);
										list($time,$junk) = explode("+",$time);
										list($time,$junk) = explode(".",$time);

										$cardNumber = $event['cardNumber'];
										$peripheralName = $event['peripheralName'];
										$department = $event['departmentName'];
										if (strlen($department) > 15) { $department = substr($department, 0, 15); }
										$name = "{$event['firstName']} {$event['surname']}";
										if (strlen($name) > 15) { $name = substr($name, 0, 15); }
										$description = $event['eventTypeDescription'];
										if (strlen($description) > 16) { $description = substr($description, 0, 16); }
										$subDesc = $event['eventSubTypeDescription'];
										if (strlen($subDesc) > 16) { $subDesc = substr($subDesc, 0, 16); }

										if (strpos($peripheralName, "Main Entrance") !== FALSE) {
											if ($description == "Access permitted") {
												$ins++;
											}
											elseif ($subDesc == "With exit button") {
												$outs++;
											}
										}

										print "<tr class=\"r" . ($i++ % 2) . "\">\n";
										print " <td valign=\"top\">$date $time</td>\n";
										print " <td valign=\"top\">$name</td>\n";
										print " <td valign=\"top\">$department</td>\n";
										print " <td valign=\"top\">$peripheralName</td>\n";
										print " <td valign=\"top\">$cardNumber</td>\n";
										print " <td valign=\"top\">$description</td>\n";
										print " <td valign=\"top\">$subDesc</td>\n";
										print "</tr>\n";

										if ($department) {
											$departments[] = $department;
										}

									}

									print "<tr>\n";
									print "<td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
									print "</tr>\n";

									$bal = $ins - $outs;

									print "<tr>\n";
									print "<td colspan=\"5\">&nbsp;</td>\n";
									print "<td>Entered</td>\n";
									print "<td align=\"right\">$ins</td>\n";
									print "</tr>\n";
									print "<tr>\n";
									print "<td colspan=\"5\">&nbsp;</td>\n";
									print "<td>Exited</td>\n";
									print "<td align=\"right\">$outs</td>\n";
									print "</tr>\n";
									print "<tr>\n";
									print "<td colspan=\"5\">&nbsp;</td>\n";
									print "<td>In Building</td>\n";
									print "<td align=\"right\">$bal</td>\n";
									print "</tr>\n";

									print "</table>\n";

									$departments = array_unique($departments);

									print "<p align=\"center\" class=\"larger\">Unique Departments</p>\n";

									if ($departments) {

										print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
										print "<tr>\n";
										print "	<td style=\"border-bottom:1px solid #999999\"><b>Department</b></td>\n";
										print "</tr>\n";

										foreach($departments AS $dep) {
											// chamge & to %26 for URL
											$depx = str_replace("&","%26",$dep);
											print "<tr>\n";
											print "<td><a href=\"pax-events-list.php?siteid=$siteid&amp;find=$depx&amp;date=$date\">$dep</a></td>\n";
											print "</tr>\n";
										}

										print "</table>\n";

									} else {
										print "<p align=\"center\">None Found</p>\n";
									}

								} else {
									$resultMessage .= "Nothing was returned from Paxton.<br />";
								}

							} else {
								$resultMessage .= "Nothing Found<br />";
							}
				
						} else {
							$resultMessage .= "Error: <br />Nothing was returned from Paxton.<br />";
						}

						// jquery date picker script
						print "<script type=\"text/javascript\">\n";
						print "	$( document ).ready(function() {\n";
						print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
						print "	});\n";
						print "</script>\n";
						
					} else {
						$resultMessage .= "Could NOT get access token<br />" . $AuthResultMessage . "<br />";
					}

				} else {
					$resultMessage .= "Could NOT get site<br />" . $dbcnx->error . "<br />";
				}

			} else {
				$resultMessage .= "Page called without siteid.<br />";
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
	if ($AuthResultMessage) {
		print "<p align=\"center\">$AuthResultMessage</p>";
	}
	print "<p align=\"center\"><a href=\"pax-sites-list.php\">Return To Sites</a></p>\n";
} else {
	include ('loggedout.php');
}

/* print "<p><b>Raw Data From Paxton</b></p>";
print "<pre>";
print_r($result);
print "</pre>"; */

print "</td>\n";
include('footer.php');
?>
