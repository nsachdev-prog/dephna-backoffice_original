<?php
$page = "Payroll";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
	print "<p align=\"center\" class=\"heading\">STAFF PAYROLL</p>\n";
	
				# filter form
				print "<form name=\"addform\" method=\"post\" action=\"payroll-view.php?az=$az\">\n";
				print "<table class=\"filterBlock\" align=\"center\">\n";
				print "	<tr>\n";
				print "		<td><b>Filter List</b></td>\n";
				print "		<td><select name=\"\" class=\"element_small\">\n";
				print "		<option value=\"\">Current Month</option>\n";
				print "		<option value=\"\">Current Year</option>\n";
				print "		<option value=\"\">January</option>\n";
				print "		<option value=\"\">February</option>\n";
				print "		<option value=\"\">Etc...</option>\n";
				print "		</select>";
				print "		</td>\n";

				print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

				print "	</tr>\n";
				print "</table>\n";
				print "</form>\n";
?>
	<table align="center" width="90%" cellspacing="0" cellpadding="3">
	  <tr>
	  	<td class="colHead">Name</td>
	  	<td class="colHead">WP</td>
      	<td class="colHead">WU</td>
      	<td class="colHead">OT</td>
      	<td class="colHead">SP</td>
      	<td class="colHead">SU</td>
      	<td class="colHead">HP (year)</td>
      	<td class="colHead">HU</td>
      	<td class="colHead" width="40px">&nbsp;</td>
      	<td class="colHead" width="30px">&nbsp;</td>
      	<td class="colHead" width="50px">&nbsp;</td>
      	<td class="colHeadEnd" width="40px">&nbsp;</td>
      </tr>
      
      <tr>
	  	<td class="col"><a href="payroll-mos.php?id=$id">Rajive Sachdev</a></td>
	  	<td class="col">22/22</td>
      	<td class="col">0</td>
      	<td class="col">0</td>
      	<td class="col">0</td>
      	<td class="col">0</td>
      	<td class="col">0 (5/20)</td>
      	<td class="col">0</td>
        <td class="col" align="center"><a href="payroll-report.php?id=$id">Report</a></td>
        <td class="col" align="center"><a href="payroll-addedit.php?id=$id">Edit</a></td>
        <td class="col" align="center"><a href="payroll-calendar.php?id=$id">Calendar</a></td>
        <td class="colEnd" align="center"><a href="payroll-delete.php?id=$id">Delete</a></td>
      </tr>
      
      <tr>
	  	<td class="col"><a href="payroll-mos.php?id=$id">Mat Blackmore</a></td>
	  	<td class="col">15/22</td>
      	<td class="col">0</td>
      	<td class="col">2</td>
      	<td class="col">1</td>
      	<td class="col">2</td>
      	<td class="col">2 (3/20)</td>
      	<td class="col">0</td>
        <td class="col" align="center"><a href="payroll-report.php?id=$id">Report</a></td>
        <td class="col" align="center"><a href="payroll-addedit.php?id=$id">Edit</a></td>
        <td class="col" align="center"><a href="payroll-calendar.php?id=$id">Calendar</a></td>
        <td class="colEnd" align="center"><a href="payroll-delete.php?id=$id">Delete</a></td>
      </tr>
      
	</table>
      <p align="center"><a href="payroll-addedit.php?action=addedit">Add New Staff Member</a> &nbsp;|&nbsp; <a href="payroll-global.php">Global Edit</a></p>

<?php
      print "</td>";
include('footer.php');
?>
