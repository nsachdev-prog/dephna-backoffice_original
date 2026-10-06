<?php
$page = "Payroll";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
	print "<p align=\"center\" class=\"heading\">PAYROLL REPORT</p>\n";
	?>

<table width="600px" border="0" align="center" cellpadding="0" cellspacing="10">
  <tr>
    <td colspan="3" style="padding-left:10px;"><b>Mat Blackmore</b></td>
  </tr>
  <tr>
    <td width="45%"><table width="100%" border="0" cellspacing="10" cellpadding="0">
  <tr>
    <td colspan="2" style="border-bottom:1px dashed #666;padding-bottom:4px;"><b>Current Month (March)</b></td>
  </tr>
  <tr>
    <td>Days worked (Paid)</td>
    <td>15 days</td>
  </tr>
  <tr>
    <td>Days worked (Unpaid)</td>
    <td>0 days</td>
  </tr>
  <tr>
    <td>Days worked (Overtime)</td>
    <td>2 days</td>
  </tr>
  <tr>
    <td>Days Sick (Paid)</td>
    <td>1 day</td>
  </tr>
  <tr>
    <td>Days Sick (Unpaid)</td>
    <td>2 days</td>
  </tr>
  <tr>
    <td>Days Holiday (Paid)</td>
    <td>2 days</td>
  </tr>
  <tr>
    <td>Days Holiday (Unpaid)</td>
    <td>0 days</td>
  </tr>
</table>
</td>
    <td width="10%">&nbsp;</td>
    <td width="45%"><table width="100%" border="0" cellspacing="10" cellpadding="0">
  <tr>
    <td colspan="2" style="border-bottom:1px dashed #666;padding-bottom:4px;"><b>Current Year (2013)</b></td>
  </tr>
  <tr>
    <td>Days worked (Paid)</td>
    <td>63 days</td>
  </tr>
  <tr>
    <td>Days worked (Unpaid)</td>
    <td>2 days</td>
  </tr>
  <tr>
    <td>Days worked (Overtime)</td>
    <td>6 days</td>
  </tr>
  <tr>
    <td>Days Sick (Paid)</td>
    <td>3 day</td>
  </tr>
  <tr>
    <td>Days Sick (Unpaid)</td>
    <td>4 days</td>
  </tr>
  <tr>
    <td>Days Holiday (Paid)</td>
    <td>3 days of 20</td>
  </tr>
  <tr>
    <td>Days Holiday (Unpaid)</td>
    <td>2 days</td>
  </tr>
</table></td>
  </tr>
  <tr>
  	<td><table width="100%" border="0" cellspacing="10" cellpadding="0">
  <tr>
    <td colspan="3" style="border-bottom:1px dashed #666;padding-bottom:4px;"><strong>Details of Absences</strong></td>
  </tr>
  <tr>
    <td><strong>Date</strong></td>
    <td><strong>Type</strong></td>
    <td><strong>Detail</strong></td>
  </tr>
  <tr>
    <td>05/01/13</td>
    <td>SP</td>
    <td>Flu</td>
  </tr>
  <tr>
    <td>19/01/13</td>
    <td>HP</td>
    <td>Booked holiday</td>
  </tr>
  <tr>
    <td>20/01/13</td>
    <td>HP</td>
    <td>Booked holiday</td>
  </tr>
  <tr>
    <td>21/01/13</td>
    <td>HP</td>
    <td>Booked holiday</td>
  </tr>
  <tr>
    <td>04/03/13</td>
    <td>SP</td>
    <td>Ear infection</td>
  </tr>
  <tr>
    <td>05/03/13</td>
    <td>SU</td>
    <td>Unknown</td>
  </tr>
  <tr>
    <td>19/03/13</td>
    <td>SU</td>
    <td>Unknown</td>
  </tr>
  <tr>
    <td>25/03/13</td>
    <td>HP</td>
    <td>Family Emergency</td>
  </tr>
  <tr>
    <td>26/03/13</td>
    <td>HP</td>
    <td>Family Emergency</td>
  </tr>
</table>
</td>
    <td width="10%">&nbsp;</td>
  	<td valign="top"><table width="100%" border="0" cellspacing="10" cellpadding="0">
  <tr>
    <td colspan="2" style="border-bottom:1px dashed #666;padding-bottom:4px;"><strong>Details of Overtime</strong></td>
  </tr>
  <tr>
    <td><strong>Date</strong></td>
    <td><strong>Detail</strong></td>
  </tr>
  <tr>
    <td>09/01/13</td>
    <td>2 hours</td>
  </tr>
  <tr>
    <td>29/01/13</td>
    <td>1 hour</td>
  </tr>
  <tr>
    <td>20/02/13</td>
    <td>1 hour</td>
  </tr>
</table>
</td>
  </tr>  <tr>
  	<td><table width="100%" border="0" cellspacing="10" cellpadding="0">
  <tr>
    <td colspan="3" style="border-bottom:1px dashed #666;padding-bottom:4px;"><strong>Pay Adjustments from February</strong></td>
  </tr>
  <tr>
    <td><strong>Date</strong></td>
    <td><strong>Type</strong></td>
    <td><strong>Detail</strong></td>
  </tr>
  <tr>
    <td class="payroll-minus">25/02/13</td>
    <td class="payroll-minus">SU</td>
    <td class="payroll-minus">Flu</td>
  </tr>
  <tr>
    <td class="payroll-minus">26/02/13</td>
    <td class="payroll-minus">HU</td>
    <td class="payroll-minus">Unauthorised holiday</td>
  </tr>
  <tr>
    <td class="payroll-plus">27/02/13</td>
    <td class="payroll-plus">OT</td>
    <td class="payroll-plus">2 hours</td>
  </tr>
</table>
</td>
    <td width="10%">&nbsp;</td>
  	<td valign="top">&nbsp;</td>
  </tr>
</table>
<p align="center"><a href="#">Download</a> &nbsp;|&nbsp; <a href="#">Print</a> &nbsp;|&nbsp; <a href="payroll-view.php">Payroll</a> &nbsp;|&nbsp; <a href="payroll-addedit.php">Edit</a> &nbsp;|&nbsp; <a href="payroll-calendar.php">Calendar</a></p>
   


<?php
print "</td>\n";
include('footer.php');
?>
