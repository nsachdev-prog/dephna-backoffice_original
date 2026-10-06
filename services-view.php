<?php
$page = "Services";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SERVICES</p>\n";
	if ($accessRights[$page]) {
		
		$id = $_REQUEST['id'];
		$lo = $_REQUEST['lo'];
		$y = $_REQUEST['y'];
		$m = $_REQUEST['m'];
		if (!$y) {
			$y = date("Y");
			$m = date("m");
		}
		$ym = "$y/$m";
			
		if ($id) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				$sql = "SELECT * FROM services WHERE ID='$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$daynames = array("na","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday");
					$row = $result->fetch_assoc();
					$suppref       = $row['suppref'];
					$contractno	   = $row['contractno'];
					$contractdate  = $row['contractdate'];
					$servname      = $row['servname'];
					$servtype	   = $row['servtype'];
					$sitename      = $row['sitename'];
					$frequency     = $row['frequency'];
					$dayofweek     = $row['dayofweek'];
					$dayofmonth    = $row['dayofmonth'];
					$qty           = $row['qty'];
					$fixedrate     = $row['fixedrate'];
					$eachrate      = $row['eachrate'];
					$notes         = $row['notes'];
					if ($fixedrate == 0.00) { $fixedrate = ""; }
					if ($eachrate == 0.00) { $eachrate = ""; }
					
					print "<p align=\"center\" class=\"larger\">$sitename - $servname</p>\n";
					print "<p align=\"center\" class=\"larger\">$servtype</p>\n";
					
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					
					// get supplier
					print "<tr>\n";
					print "	<td align=\"right\"><b>Supplier</b></td>\n";
					$sql = "SELECT suppname FROM suppliers WHERE suppref='$suppref'";
					$suppresult = $dbcnx->query($sql);
					if ($suppresult->num_rows) {
						$supprow = $suppresult->fetch_assoc();
						$suppname = $supprow['suppname'];
						print "	<td>$suppname</td>\n";
					} else {
						print "	<td>Can't get supplier</td>\n";
					}
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Supplier's Ref</b></td>\n";
					print "	<td>$contractno</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Contract Date</b></td>\n";
					print "	<td>$contractdate</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Frequency</b></td>\n";
					print "	<td>$frequency</td>\n";
					print "</tr>\n";
					
					if ($fequency == "Weekly") {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Day of Week</b></td>\n";
						print "	<td>{$daynames[$dayofweek]}</td>\n";
						print "</tr>\n";
					} elseif ($fequency == "Monthly") {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Day of Month</b></td>\n";
						print "	<td>$dayofmonth</td>\n";
						print "</tr>\n";
					}
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Qty</b></td>\n";
					print "	<td>$qty</td>\n";
					print "</tr>\n";
					
					if ($fixedrate) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Fixed Price</b></td>\n";
						print "	<td>$fixedrate</td>\n";
						print "</tr>\n";
					} elseif ($eachrate) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Price Each</b></td>\n";
						print "	<td>$eachrate</td>\n";
						print "</tr>\n";
					}
					
					if ($notes) {
						print "<tr>\n";
						print "	<td colspan=\"2\" width=\"300px\"><b>Notes...</b><br>\n";
						print "	$notes</td>\n";
						print "</tr>\n";
					}
					
					print "</table>\n";
					
					print "<p align=\"center\">";
					print "<a href=\"services-addedit.php?id=$id&amp;lo=$lo\">Edit</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
					print "<a href=\"services-delete.php?id=$id&amp;lo=$lo\">Delete</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
					print "<a href=\"services-receive.php?id=$id&amp;lo=$lo\">Receive</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
					print "<a href=\"services-list.php?lo=$lo\">Return</a>";
					print "</p>\n";
					
					
					# Service History
					print "<p align=\"center\" class=\"heading\">History</p>\n";

					print "<form name=\"addform\" method=\"post\" action=\"services-view.php?id=$id&amp;lo=$lo\">\n";
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
			
					print "<tr>\n";
					print "	<td>\n";
					print "	<select name=\"m\" class=\"element_date\">\n";
					$options = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
					for ($o=1; $o <= 12; $o++) {
						print "		<option ";
						if ($o < 10) { $check = "0".$o; } else { $check = $o; }
						if ($m == $check) { print "SELECTED "; }
						$op = $o-1;
						print "value=\"$check\">$options[$op]</option>\n";
					}
					print "	</select>\n";
					print "	<select name=\"y\" class=\"element_date\">\n";
					$options[0] = date("Y");
					$options[1] = date("Y")-1;
					for ($o=0; $o < 2; $o++) {
						print "		<option ";
						if ($y == $options[$o]) { print "SELECTED "; }
						print "value=\"$options[$o]\">$options[$o]</option>\n";
					}
					print "	</select>\n";
					print "	</td>\n";
					print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
					print "</table>\n";
					print "</form>\n";

					# row background colours
					print "<style>\n";
					print ".r0 { background-color:#fff }\n";
					print ".r1 { background-color:#ddd }\n";
					print "</style>\n";
					
					$sql = "SELECT * FROM services_done
							WHERE sid='$id' AND received LIKE '%$ym%'
							ORDER BY received
					";
					$servresult = $dbcnx->query($sql);
					if ($servresult->num_rows) {
						print "<table align=\"center\" cellspacing=\"0px\" cellpadding=\"4px\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Qty</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Price</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Total</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Notes</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";
						while ($servrow = $servresult->fetch_assoc()) {
							$rid       = $servrow['ID'];
							$received  = $servrow['received'];
							$qty       = $servrow['qty'];
							$fixedrate = $servrow['fixedrate'];
							$eachrate  = $servrow['eachrate'];
							$nettotal  = $servrow['nettotal'];
							$notes     = $servrow['notes'];
							if ($fixedrate == 0.00) { $fixedrate = ""; }
							if ($eachrate == 0.00) { $eachrate = ""; }
							
							if ($fixedrate) {
								$rate = $fixedrate;
							} else {
								$rate = $eachrate;
							}
							if ($notes) {
								$notes = stripslashes($notes);
								if (strlen($notes) > 30) { $notes = substr($notes, 0, 30) . "..."; }
							} else {
								$notes = "";
							}
							$total += $nettotal;
							$totalqty += $qty;
							
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	<td>$received</td>\n";
							print "	<td align=\"right\">$qty</b></td>\n";
							print "	<td align=\"right\">$rate</td>\n";
							print "	<td align=\"right\">$nettotal</td>\n";
							print "	<td>$notes</td>\n";
							print "	<td><a href=\"services-receive.php?id=$id&amp;rid=$rid&amp;lo=$lo&amp;y=$y&amp;m=$m\">Edit</a></td>\n";
							print "	<td><a href=\"services-receive-delete.php?id=$id&amp;rid=$rid&amp;lo=$lo&amp;y=$y&amp;m=$m\">Delete</a></td>\n";
							print "</tr>\n";
						}
						$total = number_format($total,'2');
						print "<tr>\n";
						print "	<td style=\"border-top:1px solid #999999\"><b>Month Totals</b></td>\n";
						print "	<td style=\"border-top:1px solid #999999\"align=\"right\"><b>$totalqty</b></td>\n";
						print "	<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$total</b></td>\n";
						print "	<td style=\"border-top:1px solid #999999\" colspan=\"3\">&nbsp;</td>\n";
						print "</tr>\n";

						print "</table>\n";
					} else {
						print "<p align=\"center\">No Service History</p>\n";
					}

				} else {
					print "<p align=\"center\">Services Not Found!</p>\n";
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page Called Without Service ID</b>";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
