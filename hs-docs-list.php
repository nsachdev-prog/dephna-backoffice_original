<?php
$page = "Health_&_Safety";
$subpage = "Documents";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align = \"center\" class=\"heading\">HEALTH &amp; SAFETY</p>\n";
	
	if ($accessRights[$page]) {
		
		include('hs-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
	
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$sql = "SELECT * FROM sites ORDER BY title";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
			
				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><strong>Site</strong></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><strong>Path</strong></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><strong>File</strong></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"70px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"70px\">&nbsp;</td>\n";
				print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$title     = $row['title'];
					$hsdPath   = $row['hsdPath'];
					$hsdTitle  = $row['hsdTitle'];
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$title</td>\n";
					print "	<td>$hsdPath</td>\n";
					print "	<td>$hsdTitle</td>\n";
					if ($hsdTitle) {
						print "	<td><a href=\"../dis/hsd-pdf.php?siteid=$id\" target=\"_blank\">Intro/Adden</a></td>\n";
						print "	<td><a href=\"gethspdf.php?dr=$hsdPath&pdf=$hsdTitle\" target=\"_blank\">H&S Doc</a></td>\n";
					} else {
						print "	<td>&nbsp;</td>\n";
						print "	<td>&nbsp;</td>\n";
					}
					
					print "</tr>\n";
				}
				print "<tr>\n";
				print "<td style=\"border-top:1px solid #999999\" colspan=\"5\" align=\"right\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p>$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
?>
<p>&nbsp;</p>
<p class="heading" align="center">ASBESTOS DOCUMENTS</p>
<table width="90%" cellspacing="0" cellpadding="4" align="center">
<tbody><tr>
	<td style="border-bottom:1px solid #999999"><strong>Site</strong></td>
	<td style="border-bottom:1px solid #999999"><strong>File</strong></td>
	<td style="border-bottom:1px solid #999999" width="40px">&nbsp;</td>
</tr>
<style>
.r0 { background-color:#fff }
.r1 { background-color:#ddd }
</style>
<tr class="r0">
	<td>1158 High Road, Whetstone</td>
	<td>1158 High road, Whetstone, London N20,Management survey, Dephna group.pdf</td>
	<td><a href="pdf/asbestos/1158 High road, Whetstone, London N20,Management survey, Dephna group.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>119 Neasden Lane</td>
	<td>Dephna House 119 Neasden Lane asbestos Management Survey.pdf</td>
	<td><a href="pdf/asbestos/Dephna House 119 Neasden Lane asbestos Management Survey.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>14 Cumberland Avenue</td>
	<td>no 14 Cumberland Avenue asbestos Management Survey.pdf</td>
	<td><a href="pdf/asbestos/no 14 Cumberland Avenue asbestos Management Survey.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>2 Chapel Street</td>
	<td>Asbestos management no 2 Chapel Street, London N1.pdf</td>
	<td><a href="pdf/asbestos/Asbestos management no 2 Chapel Street, London N1.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>24-26 Arcadia Avenue, N5</td>
	<td>24-26 Arcadia Avenue, London, N5 asbestos management.pdf</td>
	<td><a href="pdf/asbestos/24-26 Arcadia Avenue, London, N5 asbestos management.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>7 Coronation Road</td>
	<td>7 Coronation road asbestos Management Survey.pdf</td>
	<td><a href="pdf/asbestos/7 Coronation road asbestos Management Survey.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>75 Ballards Lane</td>
	<td>75 Ballards lane Finchley,Management survey, Dephna group.pdf</td>
	<td><a href="pdf/asbestos/75 Ballards lane Finchley,Management survey, Dephna group.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>77 Ballards Lane, Finchley</td>
	<td>77 Ballards lane Finchley,Management survey, Dephna group.pdf</td>
	<td><a href="pdf/asbestos/77 Ballards lane Finchley,Management survey, Dephna group.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Acton Lane</td>
	<td>Asbestos report Acton Lane.pdf</td>
	<td><a href="pdf/asbestos/Asbestos report Acton Lane.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Brittania Way</td>
	<td>Asbestos Report Britannia Way.pdf</td>
	<td><a href="pdf/asbestos/Asbestos Report Britannia Way.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Finchley</td>
	<td>Asbestos Report Finchley.pdf</td>
	<td><a href="pdf/asbestos/Asbestos Report Finchley.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>112-114 North Acton Lane</td>
	<td>112-114-North-acton-road-asbestos-survey.pdf</td>
	<td><a href="pdf/asbestos/112-114-North-acton-road-asbestos-survey.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>26 Chase Road</td>
	<td>26-Chase-Road-building-fire-risk-assessment.pdf</td>
	<td><a href="pdf/asbestos/26-Chase-Road-building-fire-risk-assessment.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>26 Chase Road</td>
	<td>26-Chase-Road-Building-Regs-Plan.pdf</td>
	<td><a href="pdf/asbestos/26-Chase-Road-Building-Regs-Plan.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Whetstone</td>
	<td>Whetstone-Asbestos-Survey.pdf</td>
	<td><a href="pdf/asbestos/Whetstone-Asbestos-Survey.pdf" target="_blank">View</a></td>
</tr>
<tr>
<td style="border-top:1px solid #999999" colspan="3" align="right">&nbsp;</td>
</tr>
</tbody></table>

<?php
print "</td>\n";
include('footer.php');
?>
