<?php
$page = "CCTV";
$subpage = "Logins";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align = \"center\" class=\"heading\">CCTV LOGINS</p>\n";
	
	if ($accessRights[$page]) { ?>
	
<table width="90%" cellspacing="0" cellpadding="4" align="center">
<tbody><tr>
	<td style="border-bottom:1px solid #999999"><strong>Site</strong></td>
	<td style="border-bottom:1px solid #999999"><strong>Login details</strong></td>
	<td style="border-bottom:1px solid #999999" width="40px">&nbsp;</td>
</tr>
<style>
.r0 { background-color:#fff }
.r1 { background-color:#ddd }
</style>
<tr class="r0">
	<td>PORTAL WAY</td>
	<td>Username: <strong>admin</strong> - Password: <strong>DSec1373</strong></td>
	<td><a href="https://cctv.portal.dephna.com:8007" target="_blank">Login</a></td>
</tr>
<tr class="r1">
	<td>CUMBERLAND AVENUE</td>
	<td>Username: <strong>admin</strong> - Password: <strong>DSec1373</strong></td>
	<td><a href="https://cctv.cumberland.dephna.com:8008/" target="_blank">Login</a></td>
</tr>
<tr class="r0">
	<td>CORONATION ROAD</td>
	<td>Username: <strong>admin</strong> - Password: <strong>DSec1373</strong></td>
	<td><a href="https://cctv.coronation.dephna.com:8006/" target="_blank">Login</a></td>
</tr>
<tr class="r1">
	<td>FINCHLEY</td>
	<td>Username: <strong>admin</strong> - Password: <strong>DSec1373</strong></td>
	<td><a href="https://cctv.finchley.dephna.com:8008/" target="_blank">Login</a></td>
</tr>
<tr class="r0">
	<td>214 ACTON LANE</td>
	<td>Username: <strong>admin</strong> - Password: <strong>DSec1373</strong></td>
	<td><a href="http://cctv.acton.dephna.com:8007/" target="_blank">Login</a></td>
</tr>
<tr class="r1">
	<td>190 ACTON LANE</td>
	<td>Username: <strong>admin</strong> - Password: <strong>DSec1373</strong></td>
	<td><a href="https://cctv.190.dephna.com:8005/" target="_blank">Login</a></td>
</tr>
<tr class="r0">
	<td>26 CHASE ROAD</td>
	<td>Username: <strong>admin</strong> - Password: <strong>DSec1373</strong></td>
	<td><a href="https://cctv.chase.dephna.com:8008/" target="_blank">Login</a></td>
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
