<?php
	include('settings.php');
	include('session.php');
	$jquery = 1;
	include('elements/header.php');
?>

<style>
	#parking h5 {font-size:1.125rem;}
	#parking .parking-site,#parking .parking-bay{font-weight:bold;font-size:1.25em;}
	#parking .parking-space{
		padding:2px;
	}
	#parking .parking-space span{background:#ffcd20;color:black;padding:5px 8px;border:2px solid black;border-radius:3px;font-weight:bold;font-family: Arial, sans-serif;letter-spacing: 0.05em;}
	#parking select.dis-select{width:100%;}
	#parking input.dis-field{width:100%;border:2px solid black;border-radius:3px; padding:2px;margin-bottom:10px;margin-top:10px;}
</style>
		   <div class="row text-center main-title">
			  <div class="col-lg-6 offset-lg-3 mt-1">
				   <h1 class="page-header__title">Client Area</h1>
				   <h2 class="page-header__subtitle">Key Fob Events</h2>
			   </div>
		   </div>
		   <div class="row text-center">
			  <div class="col-lg-10 offset-lg-1">
			  
<?php

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		$custid  = $_SESSION['user'];
		$today   = date("Y-m-d");
		$siteid  = $_REQUEST['siteid'];
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";

			if ($siteid AND $custref) {

				// get site
                $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
                if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
                    $srow = $sresult->fetch_assoc();
                    $sitename = $srow['title'];
					print "<h4 align=\"center\">$sitename</h4>\n";
				}

				// row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				include('../backoffice/PaxGetAccessToken.php');

				if ($access_token) {
					
					$find = $_REQUEST['find'];
					$date = $_REQUEST['date'];
					$today = date("Y-m-d");
					if (!$date) { $date = $today; }
					
					if ($find) {
						$search = "AND (e.firstName LIKE '%$find%' OR e.surname LIKE '%$find%' OR e.peripheralName LIKE '%$find%' OR e.eventTypeDescription LIKE '%$find%' OR e.eventSubTypeDescription LIKE '%$find%')";
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

					print "<form name=\"addform\" method=\"post\" action=\"keyfobs-events.php?siteid=$siteid\">\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "<td style=\"font-size:2em\"><a href=\"keyfobs-events.php?siteid=$siteid&amp;find=$find&amp;date=$backdate\"><b>&lt;&lt;</b></a></td>\n";
					print "<td style=\"font-size:1.2em\"><b>Find</b></td>\n";
					print "<td><input type=\"text\" name=\"find\" value=\"$find\" class=\"element_small\" /></td>\n";
					print "<td style=\"font-size:1.2em\"><b>Date</b></td>\n";
					print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
					print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "<td style=\"font-size:1.2em\"><a href=\"keyfobs-events.php?siteid=$siteid\"><b>All</b></a></td>\n";
					if ($date < $today) {
						print "<td style=\"font-size:1.2em\"><a href=\"keyfobs-events.php?siteid=$siteid&amp;find=$find\"><b> TD </b></a></td>\n"; 
						print "<td style=\"font-size:2em\"><a href=\"keyfobs-events.php?siteid=$siteid&amp;find=$find&amp;date=$nextdate\"><b>&gt;&gt;</b></a></td>\n";
					} else {
						print "<td style=\"font-size:1.2em;color:#999\"><b> TD </b></td>\n"; 
						print "<td style=\"font-size:2em;color:#999\"><b>&gt;&gt;</b></td>\n";
					}
					print "</tr>\n";
					print "</table>\n";
					print "</form>\n";

					print "<h4 align=\"center\">$displayText</h4>\n";

					$headers = array(
						'Authorization: bearer '.$access_token,
						'Accept: application/json'
					);

					$postdata = http_build_query(
						array(
							'query' => "SELECT e.*, u.departmentName
									FROM EventsEx AS e
									LEFT JOIN UsersEx AS u ON e.userID = u.userID
									WHERE e.eventTime >= '$fr'
										AND e.eventTime <= '$to'
										AND u.field4_50 = '$custref'
										$search
									ORDER BY e.eventTime DESC"
						)
					);

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/customquery/querydb/?$postdata");
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLINFO_HEADER_OUT, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
					$result = curl_exec($ch);
			
					if ($result !== FALSE) {

						if ($result = json_decode($result, TRUE)) {

							if ($result) {

								print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
								print "<tr>\n";
								print "	<td align=\"left\" style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
								print "	<td align=\"left\" style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
								print "	<td align=\"left\" style=\"border-bottom:1px solid #999999\"><b>Location</b></td>\n";
								print "	<td align=\"left\" style=\"border-bottom:1px solid #999999\"><b>Description</b></td>\n";
								print "	<td align=\"left\" style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
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

									if (strlen($department) > 15) { $department = substr($department, 0, 15); }
									$name = "{$event['firstName']} {$event['surname']}";
									if (strlen($name) > 15) { $name = substr($name, 0, 15); }

									print "<tr class=\"r" . ($i++ % 2) . "\">\n";
									print " <td align=\"left\" valign=\"top\">$date $time</td>\n";
									print " <td align=\"left\" valign=\"top\">$name</td>\n";
									print " <td align=\"left\" valign=\"top\">{$event['peripheralName']}</td>\n";
									print " <td align=\"left\" valign=\"top\">{$event['eventTypeDescription']}</td>\n";
									print " <td align=\"left\" valign=\"top\">{$event['eventSubTypeDescription']}</td>\n";
									print "</tr>\n";

								}

								print "<tr>\n";
								print "<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
								print "</tr>\n";

								print "</table>\n";

							} else {
								$resultMessage .= "Nothing was returned from Paxton.<br />";
							}

						} else {
							$resultMessage .= "Nothing Found";
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
					$resultMessage .= "Could not get paxton access token for $sitename<br />";
				}

			} else {
				$resultMessage .= "Page called without SiteID or CustRef<br />";
			}

			print "</article></section>\n";

			print "<p align=\"center\" style=\"padding:10px 0 10px 0;\"><a href=\"keyfobs.php\">Return</a></p>";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	if ($resultMessage)	{
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\" style=\"padding:10px 0 10px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";

	/* print "<p align=\"left\"><b>Raw Data</b></p>";
	print "<div style=\"text-align:left\"><pre>";
	print_r($keyfobs);
	print "</pre></div>"; */

?>
			</div>
	</div>
<?php			
	include('elements/footer.php');
?>
