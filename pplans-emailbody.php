<?php
$page = "Debtors";
$subpage = "Payment Plans";
include('settings.php');
include('session.php');
?>
<html>
<head>
<title>Payment Plan - Email Message</title>
<link rel="stylesheet" type="text/css" href="style.css">
<style type="text/css">
<!--
body {
	background-color: #fff;
}
td {
	font-size:11px;
}
-->
</style>
</head>
<body>
<?php
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// print heading
			print "<p align=\"center\" class=\"heading\">Email Message</p>\n";

			$nid = $_REQUEST['nid'];

			if ($nid) {
				// get message
				$sql = "SELECT emailbody, crdate FROM pplans_notes WHERE ID = '$nid'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$emailbody = $row['emailbody'];
					$thisdate = $row['crdate'];
					$thisdate = date("d-m-Y H:i",$thisdate);	
					print "<p align=\"center\">Sent $thisdate</p>\n";
					print "<blockquote>$emailbody</blockquote>\n";	
				}

			} else {
				$resultMessage = "<b>Page called without nid!</b>";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p style=\"text-align:center;color:red;\">$resultMessage</p>\n";
	}
} else {
	print "<p align=\"center\"><b>You Are Not Logged In</b></p>\n";
}
?>
</body>
</html>
