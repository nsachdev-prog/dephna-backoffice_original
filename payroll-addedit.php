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
	if ($_REQUEST['id']) {
		$id = $_REQUEST['id'];
		$heading = "UPDATE MEMBER OF STAFF";
	} else {
		$heading = "NEW MEMBER OF STAFF";
	}
	print "<p class=\"heading\" align=\"center\">$heading</p>\n";
				print "<form name=\"addform\" method=\"post\" action=\"payroll-addedit.php\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
				
				print "<tr>\n";
				print "	<td align=\"right\">Name:</td>\n";
				print "	<td><input name=\"mosname\" type=\"text\" class=\"element\" maxlength=\"75\" value=\"$mosname\" />";
				print "</td>\n";
				print "</tr>\n";
				
				print "<tr>\n";
				print "	<td align=\"right\">Email:</td>\n";
				print "	<td><input name=\"mosemail\" type=\"text\" class=\"element\" maxlength=\"75\" value=\"$mosemail\" />";
				print "</td>\n";
				print "</tr>\n";
				
				print "<tr>\n";
				print "	<td align=\"right\">Telephone:</td>\n";
				print "	<td><input name=\"mosphone\" type=\"text\" class=\"element\" maxlength=\"75\" value=\"$mosphone\" />";
				print "</td>\n";
				print "</tr>\n";
			  	
				print "<tr>\n";
				print "	<td align=\"right\">Site Location:</td>\n";
				print "	<td><select name=\"site\" class=\"element\">\n";
				print "		<option value=\"Select\">Select</option>\n";
				print "		<option value=\"\">All Sites</option>\n";
				print "		<option value=\"\">Acton Lane</option>\n";
				print "		<option value=\"\">Coronation</option>\n";
				print "		<option value=\"\">Cumberland</option>\n";
				print "		<option value=\"\">Finchley</option>\n";
				print "		<option value=\"\">Neasden</option>\n";
				print "		<option value=\"\">North Acton</option>\n";
				print "	</select>";
				print "</td>\n";
				print "</tr>\n";
				
				print "<tr>\n";
				print "	<td align=\"right\" valign=\"top\">Default Working Week:</td>\n";
				print "	<td><table width=\"90%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\" style=\"margin-top:-2px;\">
  							<tr>
    							<td>Mon</td>
    							<td>Tue</td>
    							<td>Wed</td>
    							<td>Thu</td>
    							<td>Fri</td>
    							<td>Sat</td>
    							<td>Sun</td>
  							</tr>
  							<tr>
    							<td><input type=\"checkbox\" name=\"mosmonday\" /></td>
    							<td><input type=\"checkbox\" name=\"mostuesday\" /></td>
    							<td><input type=\"checkbox\" name=\"moswednesday\" /></td>
    							<td><input type=\"checkbox\" name=\"mosthursday\" /></td>
    							<td><input type=\"checkbox\" name=\"mosfriday\" /></td>
    							<td><input type=\"checkbox\" name=\"mossaturday\" /></td>
    							<td><input type=\"checkbox\" name=\"mossunday\" /></td>
  							</tr>
							</table>";
				print "</td>\n";
				print "</tr>\n";
				
				print "<tr>\n";
				print "	<td align=\"right\">Default Hours Per Day:</td>\n";
				print "	<td><input name=\"moshours\" type=\"text\" class=\"element\" maxlength=\"75\" value=\"$moshours\" />";
				print "</td>\n";
				print "</tr>\n";
				
			  	print "<tr>\n";
				print "	<td align=\"right\">Contract Start Date:</td>\n";
				print "	<td>\n";
						$iday = date("d");
						$imonth = date("m");
						$iyear = date("Y");
				print "	<select name=\"iday\" class=\"element_date\">\n";
				for ($o=1; $o <= 31; $o++) {
					print "		<option ";
					if ($o < 10) { $check = "0".$o; } else { $check = $o; }
					if ($iday == $check) { print "SELECTED "; }
					print "value=\"$check\">$check</option>\n";
				}
				print "	</select>\n";
				print "	<select name=\"imonth\" class=\"element_date\">\n";
				$options = array("January","February","March","April","May","June","July","August","September","October","November","December");
				for ($o=1; $o <= 12; $o++) {
					print "		<option ";
					if ($o < 10) { $check = "0".$o; } else { $check = $o; }
					if ($imonth == $check) { print "SELECTED "; }
					$op = $o-1;
					print "value=\"$check\">$options[$op]</option>\n";
				}
				print "	</select>\n";
				print "	<select name=\"iyear\" class=\"element_date\">\n";
				$options[0] = date("Y")-3;
				$options[1] = date("Y")-2;
				$options[2] = date("Y")-1;
				$options[3] = date("Y");
				$options[4] = date("Y")+1;
				for ($o=0; $o < 5; $o++) {
					print "		<option ";
					if ($iyear == $options[$o]) { print "SELECTED "; }
					print "value=\"$options[$o]\">$options[$o]</option>\n";
				}
				print "	</select>\n";
				if ($eridate) { print "&nbsp;<span class=\"redtext\">$eridate</span>"; }
				print "	</td>\n";
				print "</tr>\n";
			  			
			  	print "<tr>\n";
				print "<td>&nbsp;</td>\n";
				print "</tr>\n";
				
				if ($iyear == date("Y")) {
					print "<tr>\n";
					print "	<td align=\"right\">Recommended Holidays for first year:</td>\n";
						$fullmonths = (12 - $imonth+1);
						$recdays = ($fullmonths*1.67);
					print "	<td>$fullmonths full months x 1.67 = $recdays days";
					print "</td>\n";
					print "</tr>\n";
				
					$rounddays = round($recdays, 0, PHP_ROUND_HALF_UP);
					print "<tr>\n";
					print "	<td align=\"right\">Confirmed Holidays for first year:</td>\n";
				$allholsone = ($rounddays + $bankholsone);
					print "	<td><input name=\"mosholsone\" type=\"text\" class=\"element\" style=\"width:20px !important;\" maxlength=\"3\" value=\"$rounddays\" /> days + <input name=\"bankholsone\" type=\"text\" class=\"element\" style=\"width:20px !important;\" maxlength=\"3\" value=\"$bankholsone\" /> bank holidays = <b>$allholsone Days</b>";
					print "</td>\n";
					print "</tr>\n";
				}
				
				print "<tr>\n";
				print "	<td align=\"right\">Holiday Entitlement:</td>\n";
				$allhols = ($moshols + $bankhols);
				print "	<td><input name=\"moshols\" type=\"text\" class=\"element\" style=\"width:20px !important;\" maxlength=\"3\" value=\"$moshols\" /> days + $bankhols bank holidays = <b>$allhols Days</b>";
				if ($iyear == date("Y")) {
					print " (after first year)";
				}
				print "</td>\n";
				print "</tr>\n";
							  			
			  	print "<tr>\n";
				print "<td>&nbsp;</td>\n";
				print "</tr>\n";
				
	  			print "</table>\n";

  	 		print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"8\">\n";
				print "<tr>\n";
				print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"buttonSmall\" value=\"Submit\" /></td>\n";
				print "</tr>\n";

				print "</table>\n";
				print "</form>\n";
print "<p align=\"center\"><a href=\"payroll-view.php\">Payroll</a> &nbsp;|&nbsp; <a href=\"payroll-addedit.php?action=addedit\">Add New Staff Member</a></p>\n";
print "</td>\n";
include('footer.php');
?>
