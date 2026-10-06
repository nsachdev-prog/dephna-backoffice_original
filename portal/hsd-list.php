<?php
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
					<h2 class="page-header__subtitle">Health &amp; Safety Documents</h2>
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

			print "<div id=\"LoadingOnClick\">\n";

			// link to waste pdf
			print "<table align=\"center\" cellpadding=\"8\">\n";
			print "	<tr>\n";
			print "		<td align=\"left\">All Sites</th>\n";
			print "		<td align=\"left\">Waste Transfer Certificate</td>\n";
			print "		<td align=\"left\"><a href=\"hsd-waste-pdf.php?file=CBDU93554 Issued March 2025.pdf\" target=\"_blank\">View</a></td>\n";
			print "		<td align=\"left\">&nbsp;</td>\n";
			
			print "	</tr>\n";

			// get the current sites for this client
			$sql = "SELECT b.siteid, s.title AS sitename, s.hsdPath, s.hsdTitle
					FROM bookings AS b
					LEFT JOIN sites AS s ON s.ID = b.siteid
					WHERE b.custid = '$custref'
                    GROUP BY b.siteid
					ORDER BY sitename
			";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

                // asbestos docs array
                $asb_reports = array(
                    2  => "112-114-North-acton-road-asbestos-survey.pdf",
                    3  => "Asbestos Report Finchley.pdf",
                    4  => "Dephna House 119 Neasden Lane asbestos Management Survey.pdf",
                    5  => "no 14 Cumberland Avenue asbestos Management Survey.pdf",
                    11 => "7 Coronation road asbestos Management Survey.pdf",
                    16 => "Asbestos report Acton Lane.pdf",
                    34 => "Asbestos Report Britannia Way.pdf",
                    36 => "Whetstone-Asbestos-Survey.pdf"
                );

				while ($row = $result->fetch_assoc()) {

					$siteid   = $row['siteid'];
					$sitename = $row['sitename'];
					$hsdPath  = $row['hsdPath'];
					$hsdTitle = $row['hsdTitle'];
					
					if ($hsdPath && $hsdTitle) {
						print "	<tr>\n";
						print "		<td align=\"left\">$sitename</td>\n";
						print "		<td align=\"left\">Health &amp; Safety</td>\n";
						print "		<td align=\"left\"><a href=\"hsd-pdf.php?siteid=$siteid\" target=\"_blank\">Intro &amp; Addendum</a></td>\n";
						print "		<td align=\"left\"><a href=\"gethspdf.php?dr=$hsdPath&pdf=$hsdTitle\" target=\"_blank\">Policy Statement</a></td>\n";
						print "	</tr>\n";
					}

                    if ($asb_reports[$siteid]) {
                        print "	<tr>\n";
                        print "		<td align=\"left\">$sitename</td>\n";
                        print "		<td align=\"left\">Asbestos Report</td>\n";
                        print "		<td align=\"left\"><a href=\"hsd-asb-pdf.php?file={$asb_reports[$siteid]}\" target=\"_blank\">View</a></td>\n";
						print "		<td align=\"left\">&nbsp;</td>\n";
                        print "	</tr>\n";
                    }

				}

			} else {
				$resultMessage .= "No Coldrooms found<br />";
			}

			print "</table>\n";

			print "</div>"; // end loading div

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

	<script>

$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Connecting...</p>");
	});
});
</script>

<?php			
	include('elements/footer.php');
?>
