<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
	include('settings.php');
	include('session.php');
	include('elements/header.php');
?>
		   <div class="row text-center main-title">
			  <div class="col-lg-6 offset-lg-3 mt-1">
				   <h1 class="page-header__title">Client Area</h1>
				   <h2 class="page-header__subtitle">View Invoices</h2>
			   </div>
		   </div>
		   <div class="row text-center">
			  <div class="col-lg-10 offset-lg-1">
				  
<?php

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		$postedmonth = $_POST['postedmonth'];
		if (!$postedmonth) {
			$postedmonth = date("Y-m");
		}
		list($wyear,$wmonth) = explode("-",$postedmonth);
		$monthnames	= array("01" =>	"Jan","02" => "Feb","03" =>	"Mar","04" => "Apr","05" =>	"May","06" => "Jun","07" =>	"Jul","08" => "Aug","09" =>	"Sep","10" => "Oct","11" =>	"Nov","12" => "Dec");

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			$sql = "
				SELECT * FROM inv_headers
				WHERE custref = '$custref' AND (txtype = 'I' OR txtype = 'C')
				GROUP BY year, month
				ORDER BY year DESC, month DESC
			";
			$result	= $dbcnx->query($sql);
			if (($result) && ($result->num_rows))	{
				print "<section class=\"voffice-blurb text-blurb\">
						<article class=\"page type-page\">\n";
				print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";
				print "<form name=\"monthform\"	method=\"post\"	action=\"invoices-view.php\">\n";
				print "Month: <select name=\"postedmonth\" class=\"element\">\n";
				while ($row = $result->fetch_assoc()) {
					$year   = $row['year'];
					$month  = $row['month'];
					$thismonth = "$year-$month";
					print "	<option	value=\"$year-$month\"";
					if (($year == $wyear) && ($month ==	$wmonth)) {
						print "	SELECTED";
					}
					print ">$monthnames[$month]	$year</option>\n";
				}
				print "</select>\n";
				print "<input class=\"button\" type=\"submit\" name=\"submit\" value=\"Submit\">\n";
				print "</form>\n";
				if ($postedmonth) {
					print "<p align=\"center\"><b>$monthnames[$thisinvmonth] $thisinvyear</b></p>\n";

					$sql = "SELECT * FROM inv_headers WHERE	custref='$custref' AND year='$wyear' AND month='$wmonth' ORDER BY year,	month";
					$result	= $dbcnx->query($sql);
					if (($result) && ($result->num_rows))	{
						print "<table align=\"center\" cellspacing=\"15\" style=\"margin:0 auto;\">\n";
						print "	<tr>\n";
						print "		<td>DATE</td>\n";
						print "		<td>INVOICE</td>\n";
						print "		<td	align=\"right\">AMOUNT</td>\n";
						print "	</tr>\n";
						while ($row = $result->fetch_assoc()) {
							$id	       = $row['ID'];
							$invnumber = $row['invnumber'];
							$year      = $row['year'];
							$month     = $row['month'];
							$day       = $row['day'];
							$invtotal  = $row['invtotal'];
							print "	<tr>\n";
							print "		<td>$year/$month/$day</td>\n";
							print "		<td><a href=\"invoices-html.php?id=$id\" target=\"_blank\">#$invnumber</a></td>\n";
							print "		<td	align=\"right\">£" . number_format($invtotal,2) . "</td>\n";
							print "	</tr>\n";
						}
						print "</table>\n";
						print "</div></article></section>\n";
					}
				}
			} else {
				print "<p align=\"center\">No invoices found</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	if ($resultMessage)	{
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";
?>
			</div>
	</div>
<?php			
	include('elements/footer.php');
?>
