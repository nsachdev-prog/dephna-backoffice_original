<?php
$page = "Payroll";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
	print "<p align=\"center\" class=\"heading\">UPDATE MAT BLACKMORE'S PAYROLL</p>\n";
	?>

<table width="100%" border="0" cellspacing="10" cellpadding="0">
  <tr>
    <td width="50%">
    <table width="100%" border="0" cellspacing="1" cellpadding="0" class="payroll-calendar">
    <tr>
	<td colspan="3" class="calendar-head-left"><a href="#"><img src="images/calMonthBack.gif" alt="" border="0" height="20" width="20"></a><a href="#"><img src="images/calToday.gif" alt="" border="0" height="20" width="20"></a><a href="#"><img src="images/calMonthNext.gif" alt="" border="0" height="20" width="20"></a>
    </td>
    <td colspan="4" class="calendar-head-right">March 2013</td>
    </tr>
    <tr>
    	<td class="calendar-day-head">Sun</td>
        <td class="calendar-day-head">Mon</td>
        <td class="calendar-day-head">Tue</td>
        <td class="calendar-day-head">Wed</td>
        <td class="calendar-day-head">Thu</td>
        <td class="calendar-day-head">Fri</td>
        <td class="calendar-day-head">Sat</td>
	</tr>
    <tr>
    	<td class="calendar-day-np">&nbsp;</td>
        <td class="calendar-day-np">&nbsp;</td>
        <td class="calendar-day-np">&nbsp;</td>
        <td class="calendar-day-np">&nbsp;</td>
        <td class="calendar-day-np">&nbsp;</td>
        <td class="calendar-day-wp"><a href="#"><div class="payroll-day">1</div></a></td>
        <td class="calendar-day">2</td>
	</tr>
    <tr>
    	<td class="calendar-day">3</td>
        <td class="calendar-day-sp">4</td>
        <td class="calendar-day-su">5</td>
        <td class="calendar-day-wp">6</td>
        <td class="calendar-day-wp">7</td>
        <td class="calendar-day-wp">8</td>
        <td class="calendar-day-ot">9</td>
	</tr>
    <tr>
    	<td class="calendar-day-ot">10</td>
        <td class="calendar-day-wp">11</td>
        <td class="calendar-day-wp">12</td>
        <td class="calendar-day-wp">13</td>
        <td class="calendar-day-wp">14</td>
        <td class="calendar-day-wp">15</td>
        <td class="calendar-day">16</td>
	</tr>
    <tr>
    	<td class="calendar-day">17</td>
        <td class="calendar-day-wp">18</td>
        <td class="calendar-day-su">19</td>
        <td class="calendar-day-wp">20</td>
        <td class="calendar-day-wp">21</td>
        <td class="calendar-day-wp">22</td>
        <td class="calendar-day">23</td>
	</tr>
    <tr>
    	<td class="calendar-day">24</td>
        <td class="calendar-day-hp calendar-day-selected">25</td>
        <td class="calendar-day-hp">26</td>
        <td class="calendar-day-wp">27</td>
        <td class="calendar-day-wpot">28</td>
        <td class="calendar-day-wp">29</td>
        <td class="calendar-day">30</td>
	</tr>
    <tr>
    	<td class="calendar-day">31</td>
        <td class="calendar-day-np">&nbsp;</td>
        <td class="calendar-day-np">&nbsp;</td>
        <td class="calendar-day-np">&nbsp;</td>
        <td class="calendar-day-np">&nbsp;</td>
        <td class="calendar-day-np">&nbsp;</td>
        <td class="calendar-day-np">&nbsp;</td>
	</tr>
</table></td>
    <td width="50%"><table width="90%" border="0" align="center" cellpadding="0" cellspacing="10">
      <tr>
        <td colspan="2" align="center"><b>25th March 2013</b></td>
        </tr>
      <tr>
        <td align="right" valign="top" width="100">Edit Day:</td>
        <td><select name="select" id="select" class="element" style="width:180px;">
            <option value="wp">Work (Paid)</option>
            <option value="wu">Work (Unpaid)</option>
            <option value="wu">Work (Paid + Overtime)</option>
            <option value="ot">Overtime</option>
            <option value="sp">Sick (Paid)</option>
            <option value="su">Sick (Unpaid)</option>
            <option value="hp" selected="selected">Holiday (Paid)</option>
            <option value="hu">Holiday (Unpaid)</option>
          </select></td>
      </tr>
      <tr>
        <td align="right" valign="top">Overtime:</td>
        <td><input name="" type="text" value="0" class="element_box" style="width:20px;" /> Hours</td>
      </tr>
      <tr>
        <td align="right" valign="top">Notes:</td>
        <td><textarea name="" cols="" rows="3" class="element_box" style="width:176px;">Booked Holiday</textarea></td>
      </tr>
      <tr>
        <td align="right" valign="top">Adjustment:</td>
        <td><input name="" type="checkbox" value="" /><br /><span style="font-size:0.8em">Tick box to add this day to<br />next month's report.</span></td>
      </tr>
      <tr>
        <td align="right">&nbsp;</td>
        <td><input type="submit" name="button" id="button" value="Submit" class="button" /></td>
      </tr>
      <tr>
        <td align="right">&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </table></td>
  </tr>
</table>
<p align="center"><a href="payroll-view.php">Payroll</a> &nbsp;|&nbsp; <a href="payroll-report.php">View Report</a></p>
   


<?php
print "</td>\n";
include('footer.php');
?>
