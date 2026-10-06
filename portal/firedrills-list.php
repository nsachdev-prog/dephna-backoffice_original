<?php
	// ini_set('display_errors', 1);
	// ini_set('display_startup_errors', 1);
	// error_reporting(E_ALL);
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
					<h2 class="page-header__subtitle">Fire Evacuation Drills</h2>
				</div>
			</div>
			<div class="row text-center">
				<div class="col-lg-10 offset-lg-1">
				  
<?php

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		/* if ($custref == "20199") {
			$custref = "21315";
		} */
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
			print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";

			// get client sites
			$sql = "SELECT b.siteid, s.title AS sitename
					FROM bookings AS b
					LEFT JOIN sites AS s ON s.ID = b.siteid
					WHERE b.custid = '$custref'
					GROUP BY b.siteid
					ORDER BY sitename
			";
			$sitesresult = $dbcnx->query($sql);
			if (($sitesresult) AND ($sitesresult->num_rows)) {
				while ($srow = $sitesresult->fetch_assoc()) {
					$siteid   = $srow['siteid'];
					$sitename = $srow['sitename'];

					// get drills for this site
					$sql = "SELECT *
                            FROM fam_drills
                            WHERE siteid = '$siteid' AND `visible` = '1'
                            ORDER BY `date` DESC, `time` DESC
                    ";
					$drillsresult = $dbcnx->query($sql);
					if (($drillsresult) AND ($drillsresult->num_rows)) {
						print "<p align=\"center\"><h3>$sitename</h3></p>\n";
						print "<table align=\"center\" cellpadding=\"8\">\n";
						print "	<tr>\n";
						print "		<td align=\"left\"><h5>Date</h5></td>\n";
						print "		<td align=\"left\"><h5>Time</h5></td>\n";
						print "		<td align=\"left\"><h5>&nbsp;</h5></td>\n";
						print "	</tr>\n";
						while ($trow = $drillsresult->fetch_assoc()) {
                            $id   = $trow['ID'];
							$date = $trow['date'];
							$time = $trow['time'];
							print "	<tr>\n";
							print "		<td align=\"left\">$date</td>\n";
							print "		<td align=\"left\">$time</td>\n";
							print "     <td><a href=\"../backoffice/fam-drills-pdf.php?id=$id\" target=\"_blank\">View</a></td>\n";
							print "	</tr>\n";
						}
						print "	</table>\n";
					}
				}
			}
			print "</div></article></section>\n";
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
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
