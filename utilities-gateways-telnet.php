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

		$id = $_REQUEST['id'];
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
					$ipaddress = $row['ipaddress'];
					$port      = $row['port'];

					print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
					print "<p align=\"center\" class=\"larger\">TCP Socket Test Page</p>\n";
					print "<p align=\"center\"><b>$ipaddress:$port</b></p>\n";

					if ($posted) {

						print "<blockquote>\n";

						$input = array();
						$data  = array();
						$read_timeout = 5;

						$unitid    = $_POST['unitid'];
						$reference = $_POST['reference'];
						$quantity  = $_POST['quantity'];
						$fc        = 3;

						$first = $reference; // save it for later
						
						$unitid    = dechex($unitid);
						$unitid    = str_pad($unitid, 2, "0", STR_PAD_LEFT);
						$fc        = dechex($fc);
						$fc        = str_pad($fc, 2, "0", STR_PAD_LEFT);
						$reference = dechex($reference);
						$reference = str_pad($reference, 4, "0", STR_PAD_LEFT);
						$quantity  = dechex($quantity);
						$quantity  = str_pad($quantity, 4, "0", STR_PAD_LEFT);
						$hex       = $unitid.$fc.$reference.$quantity;

						// calculate crc16 check sum
						$pak  = pack('H*', $hex);
						$res  = crc16($pak);
						$lbit = dechex($res %256);        // low eight
						$lbit = str_pad($lbit, 2, "0", STR_PAD_LEFT);
						$hbit = dechex(floor($res /256)); // high 8 bits
						$hbit = str_pad($hbit, 2, "0", STR_PAD_LEFT);
						$hex .= $lbit.$hbit;

						// for on screen reference
						$input['unitid']       = $unitid;
						$input['fc']           = $fc;
						$input['reference']    = $reference;
						$input['quantity']     = $quantity;
						$input['packed']       = bin2hex($pak);
						$input['crc16LowBit']  = $lbit;
						$input['crc16HighBit'] = $hbit;
						$input['sent']         = $hex;

						// open socket
						if ($sock = stream_socket_client("tcp://$ipaddress:$port", $errno, $errstr, 6)) {
							$resultMessage .= "Socket Opened<br />";
							stream_set_timeout($sock, $read_timeout);
							// send packet
							if (fwrite($sock, $hex)) {
								$resultMessage .= "Sent: $hex<br />";
								// get reply
								if ($res = fread($sock, 1024)) {  
									$datastr = "";
									if ($first > 0) {
										$first--;
										$data[$first] = "Ignore";
									}
									for($i=0;$i<strlen($res);$i++){
										// translate to hex
										$thisbyte = byte2hex(ord($res[$i]));
										$data[]   = $thisbyte;
										$datastr .= $thisbyte;
										$bytecount++;
									}
									$resultMessage .= "Received: $bytecount bytes<br />";
									$info = stream_get_meta_data($sock);
								} else {
									$resultMessage .= "Nothing Returned<br />";
								}
							} else {
								$resultMessage .= "Send Failed<br />";
							}
							$info = stream_get_meta_data($sock);
							if ($info['timed_out']) {
								$resultMessage .= "Timed out after $read_timeout secs waiting for data<br />";
							}
							if (fclose($sock)) {
								$resultMessage .= "Connection Closed<br />";
							}
						} else {
							$resultMessage .= "$errstr ($errno)<br />\n";
						}
						if ($resultMessage) {
							print "<p>$resultMessage</p>\n";
							$resultMessage = "";
						}

						if ($datastr) {
							$datastr = wordwrap($datastr, 80, "\n", true);
							print "<p>";
							print "<b>Received (Hex):</b><br />";
							print "<pre>$datastr</pre>";
							print "</p>";
						}

						if ($data) {
							foreach ($data AS $char) {
								$string .= chr(hexdec($char));
							}
							$string = wordwrap($string, 80, "\n", true);
							print "<p><b>As Text:</b><br />";
							print "<pre>$string</pre>";
							print "</p>";

							print "<p><b>As Array by Register:</b></p>";
							print "<pre>";
							print_r($data);
							print "</pre>";
						}

						print "<p><b>Input (Hex):</b></p>";
						print "<pre>";
						print_r($input);
						print "</pre>";

						print "<p><b>Stream Info:</b></p>";
						print "<pre>";
						print_r($info);
						print "</pre>";

						$unitid    = "";
						$reference = "";
						$quantity  = "";

						print "</blockquote>\n";
						print "<p>&nbsp;</p>";

					}

					print "<form name=\"addform\" method=\"post\" action=\"utilities-gateways-telnet.php?id=$id\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Unit ID</b></td>\n";
					print "	<td><input name=\"unitid\" type=\"text\" class=\"element_large\" value=\"$unitid\" /></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Offset (From Address)</b></td>\n";
					print "	<td><input name=\"reference\" type=\"text\" class=\"element_large\" value=\"$reference\" /></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Quantity (Registers)</b></td>\n";
					print "	<td><input name=\"quantity\" type=\"text\" class=\"element_large\" value=\"$quantity\" /></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
				}
				
				$dbcnx->close();
			} else {
				$resultMessage .= "Could Not Connect To Database!<br />" . $dbcnx->connect_error;
			}

		} else {
			$resultMessage .= "Page called without server id<br />";
		}

	} else {
		$resultMessage .= "Access Denied";
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

function crc16($string) {
	$crc = 0xFFFF;
	for ($x = 0; $x < strlen ($string); $x++) {
	  $crc = $crc ^ ord($string[$x]);
	  for ($y = 0; $y < 8; $y++) {
		if (($crc & 0x0001) == 0x0001) {
		  $crc = (($crc >> 1) ^ 0xA001);
		} else { $crc = $crc >> 1; }
	  }
	}
	return $crc;
}
function byte2hex($value){
    $h = dechex(($value >> 4) & 0x0F);
    $l = dechex($value & 0x0F);
    return "$h$l";
  }
?>
