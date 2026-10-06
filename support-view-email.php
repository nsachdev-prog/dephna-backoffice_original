<?php
$page = "Support";
$subpage = "View";
include('settings.php');
include('session.php');
if ($_SESSION['user']) {
	if ($accessRights[$page]) {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
            $eid = $_REQUEST['eid'];
            if ($eid) {
                $sql = "SELECT body FROM support_emails WHERE ID = '$eid'";
                if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row  = $result->fetch_assoc();
                    $body = $row['body'];
                    $body = html_entity_decode($body, ENT_QUOTES);
                    $body = str_replace("&#39;","'",$body);
                    print $body;
                } else {
                    $resultMessage = "Email Not Found<br>" . $dbcnx->error . "<br>";
                }
            } else {
                $resultMessage = "Email ID Not given<br>";
            }
        } else {
			$resultMessage = "Could not connect to database<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	$resultMessage = "<b>Not Logged In</b>";
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
?>
