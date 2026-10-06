<?php
$page = "Fitouts";
$subpage = "Contract";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		$qid = $_REQUEST['qid'];
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		print "<p align=\"center\" class=\"larger\">Quote</p>\n";

		if ($qid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if (!$dbcnx->connect_error) {

				if ($_REQUEST['reset_cletter']) {
					$sql = "SELECT body AS cletter FROM fitouts_templates WHERE tref = 'Cletter'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$ncletter = $row['cletter'];
						$sql = "UPDATE fitouts_quotes SET cletter = '$ncletter' WHERE ID = '$qid'";
						$dbcnx->query($sql);
					}
				}

				if ($_REQUEST['reset_terms']) {
					$sql = "SELECT body AS TsAndCs FROM fitouts_templates WHERE tref = 'Terms'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$nTsAndCs = $row['TsAndCs'];
						$sql = "UPDATE fitouts_quotes SET TsAndCs = '$nTsAndCs' WHERE ID = '$qid'";
						$dbcnx->query($sql);
					}
				}

				$sql = "SELECT q.*, u.compname
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					WHERE q.ID = '$qid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$compname  = $row['compname'];

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";
				}
			
				if ($_POST['posted']) {

					$notes     = $_POST['notes'];
					$cletter   = $_POST['cletter'];
					$TsAndCs   = $_POST['TsAndCs'];
					$ymd       = strip_tags($_POST['ymd']);
					$dcamount  = strip_tags($_POST['dcamount']);
					$dcpercent = strip_tags($_POST['dcpercent']);
					$ufpercent = strip_tags($_POST['ufpercent']);
					$leadtime  = strip_tags($_POST['leadtime']);
					$email     = strip_tags($_POST['email']);
					$contact   = strip_tags($_POST['contact']);
					$prov_sum  = strip_tags($_POST['prov_sum']);

					if (!$ymd) {
						$erymd = "Select a transaction date"; $e++;
					} elseif (!validateDate($ymd)) {
						$erymd = "Invalid Date"; $e++;
					} else {
						list($year,$month,$day) = explode("-", $ymd);
					}
					
					if ($prov_sum)  { if (!is_numeric($prov_sum))  { $erprov_sum  = "Not Numeric"; $e++; } }
					if ($dcamount)  { if (!is_numeric($dcamount))  { $erdcamount  = "Not Numeric"; $e++; } }
					if ($dcpercent) { if (!is_numeric($dcpercent)) { $erdcpercent = "Not Numeric"; $e++; } }
					if ($ufpercent) { if (!is_numeric($ufpercent)) { $erufpercent = "Not Numeric"; $e++; } }
					if ($leadtime)  { if (!is_numeric($leadtime))  { $erleadtime  = "Not Numeric"; $e++; } }
					else { $erleadtime  = "Please enter lead time"; $e++; }
					// if (!$title) { $ertitle = "Please enter title"; $e++; }
					if (!$contact) { $ercontact = "Please enter contact"; $e++; }
					if ($email)  { if (!validate_email($email))  { $eremail = "Not Valid"; $e++; } }
					else { $eremail = "Please enter email"; $e++; }

					if (!$e) {
						
						// $title   = htmlentities($title, ENT_QUOTES);
						$contact = htmlentities($contact, ENT_QUOTES);
						$cletter = str_replace("<div>","",$cletter);
						$cletter = str_replace("</div>","<br>",$cletter);
						$TsAndCs = str_replace("<div>","",$TsAndCs);
						$TsAndCs = str_replace("</div>","<br>",$TsAndCs);
						$notes = str_replace("<div>","",$notes);
						$notes = str_replace("</div>","<br>",$notes);

						$now = time();

						$sql = "UPDATE fitouts_quotes SET
							year      = '$year',
							month     = '$month',
							day       = '$day',
							notes     = '$notes',
							cletter   = '$cletter',
							TsAndCs   = '$TsAndCs',
							dcamount  = '$dcamount',
							dcpercent = '$dcpercent',
							ufpercent = '$ufpercent',
							leadtime  = '$leadtime',
							prov_sum  = '$prov_sum',
							email     = '$email',
							contact   = '$contact',
							lastupd   = '$now',
							updby     = '{$_SESSION['username']}'
							WHERE ID = '$qid'
						";
						if ($result = $dbcnx->query($sql)) {
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$title','$page','$now')");
							print "<script type=\"text/javascript\">\n";
							print "	window.location=\"fitouts-quotes-summary.php?qid=$qid\"\n";
							print "</script>\n";
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
						}
					}

				} else { 
			
					$sql = "SELECT * FROM fitouts_quotes WHERE ID = '$qid'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();

						$year      = $row['year'];
						$month     = $row['month'];
						$day       = $row['day'];
						$dcamount  = $row['dcamount'];
						$dcpercent = $row['dcpercent'];
						$ufpercent = $row['ufpercent'];
						$notes     = $row['notes'];
						$cletter   = $row['cletter'];
						$TsAndCs   = $row['TsAndCs'];
						$leadtime  = $row['leadtime'];
						$email     = $row['email'];
						$contact   = $row['contact'];
						$prov_sum  = $row['prov_sum'];

						if ($notes) {
							$notes = str_replace("<br><br>","<div><br></div>",$notes);
						}
						$cletter = str_replace("<br><br>","<div><br></div>",$cletter);
						$TsAndCs = str_replace("<br><br>","<div><br></div>",$TsAndCs);

						$ymd = "$year-$month-$day";

						if (!$ufpercent) { $ufpercent = 50; }

					} else {
						$resultMessage .= "<span class=\"redtext\"><b>Fitout Quote Not Found!</b></span><br>" . $dbcnx->error;
					}

				}

				if (!$resultMessage) {
					if ((!$posted) || ($e)) {

						// add script from wysiwyg cdn
						print "<script type=\"text/javascript\" src=\"//js.nicedit.com/nicEdit-latest.js\"></script>\n";
						// config wysiwyg
						print "<script type=\"text/javascript\">\n";
						print "	bkLib.onDomLoaded(function() {\n";
						print "	new nicEditor({buttonList : ['bold','italic','underline','subscript','superscript','strikethrough','removeformat','indent','outdent','hr','forecolor','bgcolor']}).panelInstance('area1');\n";
						print "	new nicEditor({buttonList : ['bold','italic','underline','subscript','superscript','strikethrough','removeformat','indent','outdent','hr','forecolor','bgcolor']}).panelInstance('area2');\n";
						print "	new nicEditor({buttonList : ['bold','italic','underline','subscript','superscript','strikethrough','removeformat','indent','outdent','hr','forecolor','bgcolor']}).panelInstance('area3');\n";
						print "	});\n";
						print "</script>\n";
						
						print "<form name=\"addform\" method=\"post\" action=\"fitouts-quotes-contract.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"qid\" value=\"$qid\" />\n";
						print "<table align=\"center\" width=\"95%\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Quotation Date:</b></td>\n";
						print "	<td><input type=\"text\" id=\"datepicker\" name=\"ymd\" value=\"$ymd\" class=\"element\" />\n";
						if ($erymd) { print "&nbsp;<span class=\"redtext\">$erymd</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Contact Name:</b></td>\n";
						print "	<td><input name=\"contact\" type=\"text\" class=\"element\" maxlength=\"80\" value=\"$contact\" />\n";
						if ($ercontact) { print "&nbsp;<span class=\"redtext\">$ercontact</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Email:</b></td>\n";
						print "	<td><input name=\"email\" type=\"text\" class=\"element\" maxlength=\"80\" value=\"$email\" />\n";
						if ($eremail) { print "&nbsp;<span class=\"redtext\">$eremail</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Lead Time:</b></td>\n";
						print "	<td><input name=\"leadtime\" type=\"text\" class=\"element_smaller\" maxlength=\"10\" value=\"$leadtime\" />\n";
						if ($erleadtime) { print "&nbsp;<span class=\"redtext\">$erleadtime</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Discount Amount:</b></td>\n";
						print "	<td><input name=\"dcamount\" type=\"text\" class=\"element_smaller\" maxlength=\"20\" value=\"$dcamount\" /> (overrides percent)\n";
						if ($erdcamount) { print "&nbsp;<span class=\"redtext\">$erdcamount</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Discount Percent:</b></td>\n";
						print "	<td><input name=\"dcpercent\" type=\"text\" class=\"element_smaller\" maxlength=\"20\" value=\"$dcpercent\" /> %\n";
						if ($erdcpercent) { print "&nbsp;<span class=\"redtext\">$erdcpercent</span>"; }
						print "</td>\n";
						print "</tr>\n";
						
						print "<tr>\n";
						print "	<td align=\"right\"><b>Up Front Percent:</b></td>\n";
						print "	<td><input name=\"ufpercent\" type=\"text\" class=\"element_smaller\" maxlength=\"20\" value=\"$ufpercent\" /> %\n";
						if ($erufpercent) { print "&nbsp;<span class=\"redtext\">$erufpercent</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Provisional Sum:</b></td>\n";
						print "	<td><input name=\"prov_sum\" type=\"text\" class=\"element_smaller\" maxlength=\"20\" value=\"$prov_sum\" />\n";
						if ($erprov_sum) { print "&nbsp;<span class=\"redtext\">$erprov_sum</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\"><span class=\"larger\">Covering Letter:</span>&nbsp;&nbsp;<a href=\"fitouts-quotes-contract.php?qid=$qid&amp;reset_cletter=1\">Reset</a> (Only use this if you have updated the template)<br>";
						print "<textarea id=\"area1\" name=\"cletter\"rows=\"24\" style=\"width:100%\">$cletter</textarea></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\"><span class=\"larger\">Quotation Notes:</span><br>";
						print "<textarea id=\"area2\" name=\"notes\"rows=\"24\" style=\"width:100%\">$notes</textarea></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\"><span class=\"larger\">Ts &amp; Cs:</span>&nbsp;&nbsp;<a href=\"fitouts-quotes-contract.php?qid=$qid&amp;reset_terms=1\">Reset</a> (Only use this if you have updated the template)<br>";
						print "<textarea id=\"area3\" name=\"TsAndCs\"rows=\"24\" style=\"width:100%\">$TsAndCs</textarea></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";

						print "<p align=\"center\">";
						print "<a href=\"fitouts-quotes-images.php?qid=$qid\">Back</a> | ";
						print "<a href=\"fitouts-quotes-summary.php?qid=$qid\">Next</a>";
						print "</p>\n";

						# print jquery date picker
						print "<script type=\"text/javascript\">\n";
						print "	$( document ).ready(function() {\n";
						print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
						print "	});\n";
						print "</script>\n";
						
					}
				}		
				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called witout id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-quotes-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
function validate_email($email)
{
	$regexp = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,9})$/';
	$valid = 0;
	if (preg_match($regexp, $email)) {
		$valid = 1;
	} else {
		$valid = 0;
	}
	return $valid;
}
?>
