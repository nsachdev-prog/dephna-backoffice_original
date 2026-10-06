<?php
session_start();

$uid = isset($_POST['login']) ? $_POST['login'] : $_SESSION['uid'];
$pwd = isset($_POST['password']) ? $_POST['password'] : $_SESSION['pwd'];
$now = time();
$resultMessage = '';

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {
	if ($_GET['logout']) {
		$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Logged Out','','Session','$now')");
		$_SESSION = array();
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}
		session_destroy();
	}

	elseif (isset($uid)) {
		$dbpassword = '';
		$passwordExpired = 0;
		$passwordNotValidYet = 0;
		$login    = $dbcnx->real_escape_string($uid);
		$password = $dbcnx->real_escape_string($pwd);
		$sql = "SELECT ID, username, compname, password, level, location, custref, validfrom, expires FROM users WHERE email=?";
		if ($stmt = $dbcnx->prepare($sql)) {
			$stmt->bind_param("s", $login);
			$stmt->execute();
			$stmt->bind_result(
				$_SESSION['user'],
				$_SESSION['username'],
				$_SESSION['compname'],
				$dbpassword,
				$_SESSION['level'],
				$_SESSION['cmip'],
				$_SESSION['custref'],
				$passwordValidFrom,
				$passwordExpires
			);
			if ($stmt->fetch()) {
				$now = time();
				if ($now > $passwordValidFrom) {
					$stmt->free_result();
					$crypted = crypt($password, $dbpassword);
					if ($crypted == $dbpassword) {
						if ($_POST['login']) {
							$_SESSION['uid'] = $uid;
							$_SESSION['pwd'] = $pwd;
							$dbcnx->query("UPDATE users SET lastlogin='$now' WHERE ID = '{$_SESSION['user']}'");
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Logged In','','Session','$now')");
						}

						if ($passwordExpires < $now) { $passwordExpired = 1; }

						// get a site id from bookings for this customer, if they have one, to use as a default for the session

						$sql = "SELECT siteid FROM bookings WHERE custid = ? LIMIT 0,1";
						if ($stmt = $dbcnx->prepare($sql)) {

							$stmt->bind_param("i", $_SESSION['custref']);
							$stmt->execute();
							$stmt->bind_result($siteid);
							if ($stmt->fetch()) {
								$_SESSION['siteid'] = $siteid;
							}
							$stmt->free_result();
						}

						// is this a kitchen or office customer?
						$sql = "SELECT ID
								FROM bookings
								WHERE custid = ?
								AND (
									(unittype = 'Office') OR
									(unittype = 'Office V') OR
									(unittype = 'Kitchen') OR
									(unittype = 'Kitchen V')
								)
								LIMIT 0,1
						";
						if ($stmt = $dbcnx->prepare($sql)) {
							$stmt->bind_param("i", $_SESSION['custref']);
							$stmt->execute();
							$stmt->store_result();
							if ($stmt->num_rows) {
								$_SESSION['hsriskApplies'] = 1;
							} else {
								$_SESSION['hsriskApplies'] = 0;
							}
							$stmt->free_result();
						}	
					} else {
						$resultMessage = "<b>Email or Password Incorrect! (3)</b>";
						$_SESSION = array();
					}
				} else {
					$passwordNotValidYet = 1;
					$validAt = date("H:i",$passwordValidFrom);
					$_SESSION = array();
				}
			} else {
				$resultMessage = "<b>Email or Password Incorrect! (2)</b>";
				$_SESSION = array();
			}
		} else {
			$resultMessage = "<b>Could Not Prepare Statement! (1)</b>";
			$_SESSION = array();
		}
	}
	$dbcnx->close();
}  else {
	$resultMessage = "<b>Could not connect to database!</b>";
}
?>
