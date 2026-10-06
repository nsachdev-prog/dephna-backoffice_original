<?php
session_start();

$uid = isset($_POST['login']) ? $_POST['login'] : $_SESSION['uid'];
$pwd = isset($_POST['password']) ? $_POST['password'] : $_SESSION['pwd'];
$now = time();

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {
	if ($_GET['logout']) {
		$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Logged Out','','Session','$now')");
		if (isset($_COOKIE[session_name()])) {
	    	setcookie(session_name(), '', time()-42000, '/');
		}
		$_SESSION = array();
		session_destroy();
		header('Location: index.php');
	}

	elseif (isset($uid)) {
		$login    = $dbcnx->real_escape_string($uid);
		$password = $dbcnx->real_escape_string($pwd);
		$accessDays = '';
		$accessTime = '';
		$passwordExpired = 0;
		$passwordNotValidYet = 0;
		$access = '';
		$homepage = '';
		$sql = "SELECT ID, username, password, telephone2, expires, levels, allowAccess, accessDays, accessTime, homepage FROM staff WHERE email=?";
		if ($stmt = $dbcnx->prepare($sql)) {
			$stmt->bind_param("s", $login);
			$stmt->execute();
			$stmt->bind_result($userid, $username, $dbpassword, $telephone2, $passwordExpires, $access, $allowAccess, $accessDays, $accessTime, $homepage);
			if ($stmt->fetch()) {
				$crypted = crypt($password, $dbpassword);
				if ($crypted == $dbpassword) {
					if ($_POST['login']) {
						if ($allowAccess) {
							$currentDay = date("N");
							$accessDays = explode("-",$accessDays);
							if ($accessDays[$currentDay]) {
								$currentTime = date("H:i");
								list($fromTime,$toTime) = explode("-",$accessTime);
								if (($fromTime <= $currentTime) AND ($toTime >= $currentTime)) {
									$now = time();
									$_SESSION['uid']       = $uid;
									$_SESSION['pwd']       = $pwd;
									$_SESSION['user']      = $userid;
									$_SESSION['username']  = $username;
									$_SESSION['workphone'] = $telephone2;
									$stmt->close();
									$dbcnx->query("UPDATE staff SET lastlogin='$now' WHERE ID = '{$_SESSION['user']}'");
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Logged In','','Session','$now')");
									if ($passwordExpires < $now) { $passwordExpired = 1; }
								} else {
									$resultMessage .= "<b>Out Of Hours</b><br>You cannot log in at this time.<br />Please check your valid access hours with an administrator.<br />";
								}
							} else {
								$resultMessage .= "<b>Out Of Hours</b><br>You cannot log in at this time.<br />Please check your valid access hours with an administrator.<br />";
							}
						} else  {
							$resultMessage .= "<b>Access Disabled</b><br />Please contact an administrator.<br />";
						}
					} else {
						$stmt->close();
					}
					if ($passwordExpires < $now) { $passwordExpired = 1; }
					$thisaccess = explode(",",$access);
					$accessRights = array();
					foreach($thisaccess AS $key => $value) {
						$temp = explode("-",$value);
						$k = $temp[0];
						$v = $temp[1];
						$accessRights[$k] = $v;
					}
					$allHomePageSections = explode(",",$homepage);
					if ($allHomePageSections) {
						$homePageSections = array();
						foreach($allHomePageSections AS $key => $value) {
							$temp = explode("-",$value);
							$k = $temp[0];
							$v = $temp[1];
							$homePageSections[$k] = $v;
						}
					}
					if ($_GET['switchdb']) {
						$_SESSION['database'] = $_GET['switchdb'];
					}
					if ($_SESSION['database']) {
						$dbname = $_SESSION['database'];
						include_once("companies/$dbname.php");
					} else {
						include_once("companies/dephna.php");
					}
					// get vat period lock
					$sql = "SELECT period_end, paid_on FROM vat_periods ORDER BY paid_on DESC LIMIT 0,1";
					if ($vresult = $dbcnx->query($sql) AND $vresult->num_rows) {
						$vrow = $vresult->fetch_assoc();
						$yearmonth = $vrow['period_end'];
						$VAT_Period_Lock = date("Y-m-t",strtotime("$yearmonth-15"));
					} else {
						$VAT_Period_Lock = $dbcnx->error;
					}
				} else {
					$resultMessage .= "<b>Email or Password Incorrect!<br /></b>";
					$_SESSION = array();
				}
			} else {
				$resultMessage .= "<b>Email or Password Incorrect!<br /></b>";
				$_SESSION = array();
			}
		} else {
			$resultMessage .= "<b>Could Not Prepare Statement!<br /></b>";
			$_SESSION = array();
		}
	}
	$dbcnx->close();
} else {
	$resultMessage .= "<b>Could not connect to database!</b><br />";
}
?>
