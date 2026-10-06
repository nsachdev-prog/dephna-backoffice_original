<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Utilities";
$subpage = "Gateways";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Gateways</p>\n";
	if ($accessRights[$page]) {

		$id     = $_REQUEST['id'];
		$posted = $_POST['posted'];

		if ($id) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				$sql = "SELECT u.*, s.title AS sitename
						FROM UtlGateways AS u
						LEFT JOIN sites AS s ON s.ID = u.siteid
						WHERE u.ID = '$id'
				";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {

					$row = $result->fetch_assoc();
					$sitename  = $row['sitename'];
					$collector = $row['collector'];
					$title     = $row['title'];
					$ipaddress = $row['ipaddress'];
					$port      = $row['port'];
					$model     = $row['model'];

                    $protocol = "TCP";

					print "<p align=\"center\" class=\"larger\">$sitename<br />$ipaddress:$port $protocol</p>\n";

					print "<p align=\"center\" class=\"larger\">Modbus Test Page</p>\n";

					$function = $_POST['function'];
					$deviceid = $_POST['deviceid'];
					$range    = $_POST['range'];
					$start    = $_POST['start'];
					$qty      = $_POST['qty'];
					$typeid   = $_POST['typeid'];

					$tryid = $deviceid;

					if (!$range) { $range = $deviceid; }

					if (strpos($start, '0x') !== FALSE) {
						$start = str_replace("0x","",$start);
						$start = hexdec($start);
					}
					
					if ($posted AND $deviceid AND $qty) {

						if ($function == 3 OR $function == 4) {

							require_once '../../Phpmodbus/Phpmodbus/ModbusMaster.php';
							require_once '../../Phpmodbus/Phpmodbus/ModbusMasterTcp.php';

							while ($tryid <= $range) {

								$modbus = new ModbusMaster($ipaddress, $port, $protocol);

								try {
									if ($function == 4) {
										$recData = $modbus->readMultipleInputRegisters($tryid, $start, $qty);
									}
									elseif ($function == 3) {
										$recData = $modbus->readMultipleRegisters($tryid, $start, $qty);
									}

									print "<blockquote>\n";
									print "<b>Device ID: $tryid - Responded</b><br>";
									$modbus = wordwrap($modbus, 80, "\n", true);
									print "<p>" . $modbus . "</p>";

									// Print read data
									print "<p>Data:<br />\n";
									print "<pre>\n";
									foreach ($recData AS $key => $value) {
										$hex = dechex($value);
										$hex = str_pad($hex, 2, "0", STR_PAD_LEFT);
										print "$key => $hex<br />";
									}
									print "</pre>\n";
									print "</p>\n";
									print "</blockquote>\n";

								}

								catch (Exception $e) {
									// Print error information if any
									print "<blockquote>\n";
									print "<b>Device ID: $tryid - NO RESPONSE</b><br>";
									print "$modbus<br>";
									print $e;
									print "</blockquote>\n";
								}

								$tryid++;

							}

						}

					}

					print "<form name=\"addform\" method=\"post\" action=\"utilities-gateways-modbus.php?id=$id\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                    // get meter types
                    $sql = "SELECT * FROM UtlTypes ORDER BY title";
                    $result = $dbcnx->query($sql);
                    if (($result) && ($result->num_rows)) {
                        print "<tr>\n";
                        print "	<td align=\"right\"><b>Meter Type</b></td>\n";
                        print "	<td>\n";
                        print "		<select name=\"typeid\" id=\"typeid\" class=\"element_medium\">\n";
                        print "			<option></option>\n";
                        while ($row = $result->fetch_assoc()) {
                            $thistypeid = $row['ID'];
                            $thistitle  = $row['title'];
                            $thisstart  = $row['start'];
                            $thisqty    = $row['qty'];
                            if ($thistypeid == 8 OR $thistypeid == 6 OR $thistypeid == 11) {
                                $func = 4;
                            } else {
                                $func = 3;
                            }
                            print "			<option value=\"$thistypeid\" data-func=\"$func\" data-start=\"$thisstart\" data-qty=\"$thisqty\""; if ($thistypeid == $typeid) { print " SELECTED"; } print ">$thistitle</option>\n";
                        }
                        print "		</select>\n";
                        print "	</td>\n";
                        print "</tr>\n";
                    }

                    print "<tr>\n";
					print "	<td align=\"right\"><b>Function (3 or 4)</b></td>\n";
					print "	<td><input name=\"function\" type=\"text\" class=\"element_medium\" id=\"func\" value=\"$function\" /></td>\n";
					print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Start Address</b></td>\n";
                    print "	<td><input name=\"start\" type=\"text\" class=\"element_medium\" id=\"start\" value=\"$start\" /></td>\n";
                    print "</tr>\n";

                    print "</tr>\n";
					print "	<td colspan=\"2\" align=\"center\">'Start Address' will accept decimal or hexadecimal in the format 0xff..</td>\n";
					print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Read Quantity</b></td>\n";
                    print "	<td><input name=\"qty\" type=\"text\" class=\"element_medium\" id=\"qty\" value=\"$qty\" /></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
					print "	<td align=\"right\"><b>From Device ID</b></td>\n";
					print "	<td><input name=\"deviceid\" type=\"text\" class=\"element_medium\" value=\"$deviceid\" /></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>To Device ID</b></td>\n";
					print "	<td><input name=\"range\" type=\"text\" class=\"element_medium\" value=\"$range\" /></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";

                    print "<script>\n";
                    print "    $(document).ready(function() {\n";
                    print "        $('#typeid').change(function() {\n";
                    print "            var func = $(this).find(':selected').data('func');\n";
                    print "            var start = $(this).find(':selected').data('start');\n";
                    print "            var qty = $(this).find(':selected').data('qty');\n";
                    print "            $('#func').val(func);\n";
                    print "            $('#start').val(start);\n";
                    print "            $('#qty').val(qty);\n";
                    print "        });\n";
                    print "    });\n";
                    print "</script>\n";

				}
				
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}

		} else {
			$resultMessage .= "Page called without server id<br />";
		}

	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"utilities-gateways-list.php\">Return</a></p>\n";
print "</td>\n";
include('footer.php');
?>
