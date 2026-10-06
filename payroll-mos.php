<?php
$page = "Payroll";
include('settings.php');
$bankhols = "8";

// calculate how many bank holidays are left in year one
// $bankhols minus $bankholsused equals $bankholsone
$bankholsone = "6";

$moshols = "20";
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
	print "<p class=\"heading\" align=\"center\">MEMBER OF STAFF DETAILS</p>\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
				
				print "<tr>\n";
				print "	<td align=\"right\"><b>Name:</b></td>\n";
				print "	<td>Mat Blackmore";
				print "</td>\n";
				print "</tr>\n";
				
				print "<tr>\n";
				print "	<td align=\"right\"><b>Email:</b></td>\n";
				print "	<td><a href=\"mailto:mat@dephna.com\">mat@dephna.com</a>";
				print "</td>\n";
				print "</tr>\n";
				
				print "<tr>\n";
				print "	<td align=\"right\"><b>Telephone:</b></td>\n";
				print "	<td>07590 532 170";
				print "</td>\n";
				print "</tr>\n";
			  	
				print "<tr>\n";
				print "	<td align=\"right\"><b>Site Location:</b></td>\n";
				print "	<td>Finchley";
				print "</td>\n";
				print "</tr>\n";
				
				print "<tr>\n";
				print "	<td align=\"right\" valign=\"top\"><b>Default Working Week:</b></td>\n";
				print "	<td>Mon, Tue, Wed, Thu, Fri";
				print "</td>\n";
				print "</tr>\n";
				
				print "<tr>\n";
				print "	<td align=\"right\"><b>Default Hours Per Day:</b></td>\n";
				print "	<td>8";
				print "</td>\n";
				print "</tr>\n";
				
			  	print "<tr>\n";
				print "	<td align=\"right\"><b>Contract Start Date:</b></td>\n";
				print "	<td>12th March 2013\n";
				print "	</td>\n";
				print "</tr>\n";
			  			
			  	print "<tr>\n";
				print "<td>&nbsp;</td>\n";
				print "</tr>\n";
				
				if (date("Y") == "2013") {
					print "<tr>\n";
					print "	<td align=\"right\"><b>Holiday Entitlement for first year:</b></td>\n";
				$allholsone = (17 + $bankholsone);
					print "	<td>17 days + $bankholsone bank holidays = <b>$allholsone Days</b>";
					print "</td>\n";
					print "</tr>\n";
				}
				
				print "<tr>\n";
				print "	<td align=\"right\"><b>Default Holiday Entitlement:</b></td>\n";
				$allhols = ($moshols + $bankhols);
				print "	<td>$moshols days + $bankhols bank holidays = <b>$allhols Days</b>";
				if ($iyear == date("Y")) {
					print " (after first year)";
				}
				print "</td>\n";
				print "</tr>\n";
				
			  	print "<tr>\n";
				print "	<td align=\"right\"><b>Holidays Remaining:</b></td>\n";
				print "	<td>17 days left.\n";
				print "	</td>\n";
				print "</tr>\n";
							  			
			  	print "<tr>\n";
				print "<td>&nbsp;</td>\n";
				print "</tr>\n";
				
	  			print "</table>\n";
				print "<p align=\"center\"><a href=\"payroll-view.php\">Payroll</a> &nbsp;|&nbsp; <a href=\"payroll-addedit.php?action=addedit\">Add New Staff Member</a> &nbsp;|&nbsp; <a href=\"payroll-addedit.php\">Edit</a> &nbsp;|&nbsp; <a href=\"payroll-report.php\">View Report</a></p></p>\n";
				print "</td>\n";
include('footer.php');
?>
