<?php
$page = "Training";
$subpage = "Sessions";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            print "<p align=\"center\" class=\"larger\"><b>Request New User &amp; Key Fob</b></p>\n";
            print "<p align=\"center\">You must give at least a first name and a telephone number.</p>";

            print "<form name=\"addform\" enctype=\"multipart/form-data\" method=\"post\" action=\"training-sessions-form.php\">\n";
            print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

            print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
            print "<tr>\n";
            print " <td align=\"right\"><b>First Name</b></td>\n";
            print "	<td><input name=\"firstName\" type=\"text\" class=\"element dis-field\" maxlength=\"20\" value=\"$firstName\" />";
            if ($erfirstName) { print "<br /><span style=\"color:red\">$erfirstName</span>"; }
            print "</td>\n";
            print "</tr>\n";

            print "<tr>\n";
            print " <td align=\"right\"><b>Middle Name</b></td>\n";
            print "	<td><input name=\"middleName\" type=\"text\" class=\"element dis-field\" maxlength=\"20\" value=\"$middleName\" />";
            if ($ermiddleName) { print "<br /><span style=\"color:red\">$ermiddleName</span>"; }
            print "</td>\n";
            print "</tr>\n";

            print "<tr>\n";
            print " <td align=\"right\"><b>Last Name</b></td>\n";
            print "	<td><input name=\"lastName\" type=\"text\" class=\"element dis-field\" maxlength=\"20\" value=\"$lastName\" />";
            if ($erlastName) { print "<br /><span style=\"color:red\">$erlastName</span>"; }
            print "</td>\n";
            print "</tr>\n";

            print "<tr>\n";
            print " <td><b>Telephone</b></td>\n";
            print "	<td><input name=\"telephone\" type=\"text\" class=\"element dis-field\" maxlength=\"50\" value=\"$telephone\" />";
            if ($ertelephone) { print "<br /><span  style=\"color:red\">$ertelephone</span>"; }
            print "</tr>\n";

            print "<tr>\n";
            print "	<td><b>Photograph (jpg)</b></td>\n";
            print "	<td><input type=\"file\" name=\"image\" class=\"element dis-field\" />";
            if ($erimage) { print "<br /><span  style=\"color:red\">$erimage</span>"; }
            print "</td>\n";
            print "</tr>\n";

            print "</table>\n";
			
			print "<p align=\"center\" class=\"larger\">Training</p>\n";
            print "<p align=\"center\">You must give the new key fob holder the following training.<br>Please click each checkbox to confirm that the relevent training has been given and understood.</p>\n";

			$sql = "SELECT * FROM training_checklist ORDER BY pos";
			if ($clresult = $dbcnx->query($sql) AND $clresult->num_rows) {
                print "<table width=\"60%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";
                while ($clrow  = $clresult->fetch_assoc()) {
                    $clid    = $clrow['ID'];
                    $title   = $clrow['title'];
                    $details = $clrow['details'];
                    print "<tr>\n";
                    print "<td style=\"border-top:1px solid grey;\" valign=\"top\"><b>$title</b><br>$details</td>\n";
                    print "<td style=\"border-top:1px solid grey;\" valign=\"top\"><input type=\"checkbox\" name=\"cl[$clid]\""; if ($cl[$clid]) { print " CHECKED"; } print " /></td>\n";
                    print "</tr>\n";
                }
                print "<tr>\n";
                print "<td colspan=\"3\" style=\"border-top:1px solid grey;\">&nbsp;</td>\n";
                print "</tr>\n";
                print "</table>\n";
			}

            print "<p align=\"center\" class=\"larger\">Charge</p>\n";
            print "<table width=\"60%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
            print "<tr>";
            print "<td style=\"border-top:1px solid grey;\" valign=\"top\"><b>Accept The Charge</b><br>I understand that there is a charge for key fobs and that the charge will be included on my next invoice.";
            if ($eraccept) { print "<br><span style=\"color:red\">$eraccept</span>"; }
            print "</td>";
            print "<td style=\"border-top:1px solid grey;\" valign=\"top\"><input type=\"checkbox\" name=\"accept\""; if ($accept) { print " CHECKED"; } print " /></td>";
            print "</tr>\n";
            print "<tr>\n";
            print "<td colspan=\"3\" style=\"border-top:1px solid grey;\">&nbsp;</td>\n";
            print "</tr>\n";
            print "</table>\n";

            print "	<span class=\"notclicked\">\n";
            print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button btn btn-primary\" value=\"Submit\"></p>\n";
            print "	</span>\n";

            print "	<span class=\"uploading\">\n";
            print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
            print "	</span>\n";

            print "</form>\n";

            print "<script>\n";
            print "  $('.uploading').hide();\n";
            print "  	$('form').submit(function () {\n";
            print " 	$('.uploading').show();\n";
            print "  	$('.notclicked').hide();\n";
            print "  });\n";
            print "</script>\n";
	
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}	
	
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
