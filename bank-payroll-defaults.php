<?php
$page = "Bank";
$subpage = "Defaults";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">BANK</p>\n";
		include('bank-payroll-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Payroll Defaults</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {

				$amounts     = $_POST['amounts'];
				$bnkAccNames = $_POST['bnkAccNames'];
				$bnkAccNums  = $_POST['bnkAccNums'];
				$bnkAccSorts = $_POST['bnkAccSorts'];

				$resultMessage .= "<table align=\"center\">\n";
				$resultMessage .= "<tr><td>&nbsp;</td><td><b>Name</b></td><td align=\"right\"><b>Amount</b></td><td><b>Acc Name</b></td><td><b>Acc Num</b></td><td><b>Sort Code</b></td></tr>\n";
			
				foreach ($amounts AS $item => $amount) {
					list($id,$staffname) = explode("--",$item);
					if (is_numeric($amount)) {
						$sql = "UPDATE staff SET 
								amount='$amount',
								bnkAccName='{$bnkAccNames[$id]}',
								bnkAccNum ='{$bnkAccNums[$id]}',
								bnkAccSort='{$bnkAccSorts[$id]}' 
								WHERE ID='$id'
						";
						if ($dbcnx->query($sql)) {
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							$resultMessage .= "<tr><td>Updated</td><td>$staffname</td><td align=\"right\">$amount</td><td>{$bnkAccNames[$id]}</td><td>{$bnkAccNums[$id]}</td><td>{$bnkAccSorts[$id]}</td></tr>\n";
						} else {
							$resultMessage .= "<tr><td>FAILED</td><td>$staffname</td></tr>\n";
						}
					} else {
						$resultMessage .= "<tr><td>NOT NUMERIC</td><td>$staffname</td></tr>\n";
					}
				}

				$resultMessage .= "</table>\n";
				
				if ($rdb) {
					include_once("rdb-save.php");
					include_once("rdb-mail.php");
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					
					$sql = "SELECT ID, username, amount, bnkAccName, bnkAccNum, bnkAccSort
							FROM staff
							WHERE onpayroll = 'on'
							ORDER BY username";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {

						print "<form name=\"payform\" method=\"post\" action=\"bank-payroll-defaults.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
						print "<tr>\n";
						print "<td><b>Name</b></td>\n";
						print "<td><b>Amount</b></td>\n";
						print "<td><b>Acc Name</b></td>\n";
						print "<td><b>Acc Number</b></td>\n";
						print "<td><b>Sort Code</b></td>\n";
						print "</tr>\n";

						while ($row = $result->fetch_assoc()) {
							$id         = $row['ID'];
							$staffname  = $row['username'];
							$amount     = $row['amount'];
							$bnkAccName = $row['bnkAccName'];
							$bnkAccNum  = $row['bnkAccNum'];
							$bnkAccSort = $row['bnkAccSort'];

							print "<tr>\n";
							print "<td>$staffname</td>\n";
							print "<td align=\"right\"><input name=\"amounts[$id--$staffname]\" type=\"text\" class=\"element_small\" maxlength=\"20\" value=\"$amount\" /></td>\n";
							print "<td align=\"right\"><input name=\"bnkAccNames[$id]\" type=\"text\" class=\"element_small\" maxlength=\"20\" value=\"$bnkAccName\" /></td>\n";
							print "<td align=\"right\"><input name=\"bnkAccNums[$id]\" type=\"text\" class=\"element_small\" maxlength=\"20\" value=\"$bnkAccNum\" /></td>\n";
							print "<td align=\"right\"><input name=\"bnkAccSorts[$id]\" type=\"text\" class=\"element_small\" maxlength=\"20\" value=\"$bnkAccSort\" /></td>\n";
							print "</tr>\n";

						}

						print "</table>\n";
						print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";
						print "</form>\n";
					}
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Login</a></p>\n";
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
