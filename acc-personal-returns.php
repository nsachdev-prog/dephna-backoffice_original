<?php
$page = "Accounts";
$subpage = "Returns";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align = \"center\" class=\"heading\">PERSONAL TAX RETURNS</p>\n";
	
	# if mat, nimesh, raj
	if (in_array( $_SESSION['user'], array(3,5,6))) { ?>
<p>&nbsp;</p>
<p class="heading" align="center">2017 / 2018</p>
<table width="90%" cellspacing="0" cellpadding="4" align="center">
<tbody><tr>
	<td style="border-bottom:1px solid #999999"><strong>Name</strong></td>
	<td style="border-bottom:1px solid #999999"><strong>File</strong></td>
	<td style="border-bottom:1px solid #999999" width="40px">&nbsp;</td>
</tr>
<style>
.r0 { background-color:#fff }
.r1 { background-color:#ddd }
</style>
<tr class="r1">
	<td>Dephna Estates</td>
	<td>Dephna Estates Full accounts 31.12.2018</td>
	<td><a href="pdf/accounts/Dephna Estates Full accounts 31.12.2018.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Dephna Estates</td>
	<td>Dephna Estates Filleted accounts 31.12.2018</td>
	<td><a href="pdf/accounts/Dephna Estates Filleted accounts 31.12.2018.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Dephna Estates</td>
	<td>Dephna Estates Letter of representation</td>
	<td><a href="pdf/accounts/Dephna Estates Letter of representation.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Dephna Estates</td>
	<td>Dephna Estates Minutes of Directors meeting 2018</td>
	<td><a href="pdf/accounts/Dephna Estates Minutes of Directors meeting 2018.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Dephna Estates</td>
	<td>Dephna Estates Dividend Voucher and Board Minutes - 30 September 2018</td>
	<td><a href="pdf/accounts/Dephna Estates Dividend Voucher and Board Minutes - 30 September 2018.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Dephna Estates</td>
	<td>Dephna Estates Corporation tax return 31.12.2018</td>
	<td><a href="pdf/accounts/Dephna Estates Corporation tax return 31.12.2018.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Dephna Estates</td>
	<td>Dephna Estates Corporation tax computation 31.12.2018</td>
	<td><a href="pdf/accounts/Dephna Estates Corporation tax computation 31.12.2018.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Dephna Estates</td>
	<td>Letter of Engagement</td>
	<td><a href="pdf/accounts/Letter of Engagement.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Dephna Estates</td>
	<td>Dephna Estates Corporation tax cover letter</td>
	<td><a href="pdf/accounts/Dephna Estates Corporation tax cover letter.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Dephna Estates</td>
	<td>Standard terms of business - updated July 2018</td>
	<td><a href="pdf/accounts/Standard terms of business - updated July 2018.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Dephna Estates</td>
	<td>Schedule of services - Preparation of Statutory financial statements</td>
	<td><a href="pdf/accounts/Schedule of services - Preparation of Statutory financial statements.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Dephna Estates</td>
	<td>Schedule of services - Corporation tax</td>
	<td><a href="pdf/accounts/Schedule of services - Corporation tax.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Dephna Estates</td>
	<td>KLSA fee note for accounts</td>
	<td><a href="pdf/accounts/KLSA fee note for accounts.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Dephna Estates</td>
	<td>KLSA fee note for tax</td>
	<td><a href="pdf/accounts/KLSA fee note for tax.pdf" target="_blank">View</a></td>
</tr>
	
<tr class="r1">
	<td>Dephna Capital</td>
	<td>Minutes of directors' meeting 2019</td>
	<td><a href="pdf/accounts/Dephna Capital Minutes of directors' meeting 2019.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Dephna Capital</td>
	<td>Corporation tax computation 31.12.2018</td>
	<td><a href="pdf/accounts/Dephna Capital Corporation tax computation 31.12.2018.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Dephna Capital</td>
	<td>Corporation tax return 31.12.2018</td>
	<td><a href="pdf/accounts/Dephna Capital Corporation tax return 31.12.2018.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Dephna Capital</td>
	<td>Filleted accounts 31.12.2018</td>
	<td><a href="pdf/accounts/Dephna Capital Filleted accounts 31.12.2018.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Dephna Capital</td>
	<td>Letter of Representation</td>
	<td><a href="pdf/accounts/Dephna Capital Letter of Representation.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Dephna Capital</td>
	<td>Minutes of directors' meeting 2018</td>
	<td><a href="pdf/accounts/Dephna Capital Minutes of directors' meeting 2018.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Nimesh Sachdev</td>
	<td>2018 CGT Tax Schedules</td>
	<td><a href="pdf/accounts/Nimesh Sachdev 2018 CGT Tax Schedules.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Nimesh Sachdev</td>
	<td>2018 Tax Computation</td>
	<td><a href="pdf/accounts/Nimesh Sachdev 2018 Tax Computation.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Nimesh Sachdev</td>
	<td>2018 Tax Cover letter</td>
	<td><a href="pdf/accounts/Nimesh Sachdev 2018 Tax Cover letter.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Nimesh Sachdev</td>
	<td>2018 Tax Return</td>
	<td><a href="pdf/accounts/Nimesh Sachdev 2018 Tax Return.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Nimesh Sachdev</td>
	<td>2018 Tax Schedules</td>
	<td><a href="pdf/accounts/Nimesh Sachdev 2018 Tax Schedules.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Rajive Sachdev</td>
	<td>2018 CGT Tax Schedules</td>
	<td><a href="pdf/accounts/Rajive Sachdev 2018 CGT Tax Schedules.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Rajive Sachdev</td>
	<td>2018 Tax Computation</td>
	<td><a href="pdf/accounts/Rajive Sachdev 2018 Tax Computation.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Rajive Sachdev</td>
	<td>2018 Tax Cover letter</td>
	<td><a href="pdf/accounts/Rajive Sachdev 2018 Tax Cover letter.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Rajive Sachdev</td>
	<td>2018 Tax Return</td>
	<td><a href="pdf/accounts/Rajive Sachdev 2018 Tax Return.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Rajive Sachdev</td>
	<td>2018 Tax Schedules</td>
	<td><a href="pdf/accounts/Rajive Sachdev 2018 Tax Schedules.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Rajive Sachdev</td>
	<td>2017 Tax Return_43077</td>
	<td><a href="pdf/accounts/Mr Rajive Sachdev 2017 Tax Return_43077.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Rajive Sachdev</td>
	<td>2016 Tax Return</td>
	<td><a href="pdf/accounts/Rajive Sachdev 2016 Tax Return.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Rajive Sachdev</td>
	<td>Signed Account 2014 Full set</td>
	<td><a href="pdf/accounts/Signed Account 2014 Full set.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Rajive Sachdev</td>
	<td>Signed Final accounts 31.12.2017</td>
	<td><a href="pdf/accounts/Signed Final accounts 31.12.2017.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Rajive Sachdev</td>
	<td>Signed Final accounts 31.12.2016</td>
	<td><a href="pdf/accounts/Signed Final accounts 31.12.2016.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Rajive Sachdev</td>
	<td>Signed Full Accounts YE 31.12.2015</td>
	<td><a href="pdf/accounts/Signed Full Accounts YE 31.12.2015.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Rajive Sachdev</td>
	<td>Final accounts 31.12.2017</td>
	<td><a href="pdf/accounts/Final accounts 31.12.2017.pdf" target="_blank">View</a></td>
</tr>
<tr class="r0">
	<td>Rajive Sachdev</td>
	<td>Signed Final accounts 31.12.2016_29930</td>
	<td><a href="pdf/accounts/Signed Final accounts 31.12.2016_29930.pdf" target="_blank">View</a></td>
</tr>
<tr class="r1">
	<td>Rajive Sachdev</td>
	<td>Signed Full Accounts YE 31.12.15</td>
	<td><a href="pdf/accounts/Signed Full Accounts YE 31.12.15.pdf" target="_blank">View</a></td>
</tr>
<tr>
<td style="border-top:1px solid #999999" colspan="3" align="right">&nbsp;</td>
</tr>
</tbody></table>

	<?php	
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}

print "</td>\n";
include('footer.php');
?>
