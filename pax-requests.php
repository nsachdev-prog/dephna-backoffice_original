<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Requests";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">PAXTON</p>\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			if ($siteid) {

				// setup page number
				$rowsPerPage = 50;
				$maxPageNums = 20;
				$pageNum = 1;
				if($_GET['pa']) { $pageNum = $_GET['pa']; }
				$offset = ($pageNum - 1) * $rowsPerPage;

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$sitename = $srow['title'];
					print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
					include('pax-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
					print "<p align=\"center\" class=\"larger\">Fob Requests</p>\n";

					$sql = "SELECT r.*, c.compname
							FROM pax_user_requests AS r
							LEFT JOIN users AS c ON c.ID = r.custid
							WHERE r.siteid = '$siteid'
							ORDER BY r.xdatetime DESC
							LIMIT $offset, $rowsPerPage
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

						print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Telephone</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Processed</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>By</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";

						while ($row = $result->fetch_assoc()) {

							$name = array();
							$rid        = $row['ID'];
							$userid     = $row['userid'];
							$xdatetime  = $row['xdatetime'];
							$compname   = $row['compname'];
							$name[]     = $row['firstName'];
							$name[]     = $row['middleName'];
							$name[]     = $row['lastName'];
							$telephone  = $row['telephone'];
							$processed  = $row['processed'];
							$procby     = $row['procby'];

							if (strlen($compname) > 20) { $compname = substr($compname,0,20); }

							if ($xdatetime) {
								list($date,$time) = explode(" ",$xdatetime);
								list($y,$m,$d) = explode("-",$date);
								list($hr,$min,$sec) = explode(":",$time);
								$datetime = "$d-$m-$y $hr:$min";
							} else {
								$datetime = "&nbsp;";
							}
							$name = implode(" ",$name);

							if (strlen($name) > 20) { $name = substr($name,0, 20); }

							if ($processed) {
								list($y,$m,$d) = explode("-",$processed);
								$processed = "$d-$m-$y";
							} else {
								$processed = "&nbsp;";
							}

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print " <td valign=\"top\">$datetime</td>\n";
							print " <td valign=\"top\">$compname</td>\n";
							print " <td valign=\"top\">$name</td>\n";
							print " <td valign=\"top\">$telephone</td>\n";
							print " <td valign=\"top\">$processed</td>\n";
							print " <td valign=\"top\">$procby</td>\n";
							print " <td valign=\"top\"><a href=\"pax-users-request.php?reqid=$rid&amp;siteid=$siteid&amp;userid=$userid\">View</a></td>\n";
							print " <td valign=\"top\"><a href=\"pax-users-request.php?reqid=$rid&amp;siteid=$siteid&amp;delfq=1\">Delete</a></td>\n";
							print "</tr>\n";

						}

						print "<tr>\n";
						print "<td colspan=\"8\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";

						print "</table>\n";

						// pagination block
						$self = $_SERVER['PHP_SELF'];
						$qString = "siteid=$siteid";
						$query   = "SELECT COUNT(*) AS numrows FROM pax_user_requests WHERE siteid = '$siteid'";
						$result  = $dbcnx->query($query);
						$row     = $result->fetch_array(MYSQLI_ASSOC);
						$numrows = $row['numrows'];
						$maxPage = ceil($numrows/$rowsPerPage);
						if ($maxPage > 1) {
							$nav  = '';

							if ($maxPage > $maxPageNums+2) {
								$start = ceil($pageNum-$maxPageNums/2);
								if ($start < 3) {
									$start = 1;
								} else {
									$first = " <a href=\"$self?pa=1&amp;$qString\">1</a> ... ";
								}
								$end = $start+$maxPageNums;
								if ($end > $maxPage-2) {
									$end = $maxPage;
									$start = $end-$maxPageNums;
								} else {
									$last = " ... <a href=\"$self?pa=$maxPage&amp;$qString\">$maxPage</a> ";
								}
							} else {
								$start = 1;
								$end = $maxPage;
							}

							for($pa = $start; $pa <= $end; $pa++) {
								if ($pa == $pageNum) {
									$nav .= " <span class=\"orangeText\">$pa</span> ";
								} else {
									$nav .= " <a href=\"$self?pa=$pa&amp;$qString\">$pa</a> ";
								}
							}

							if ($pageNum > 1) {
								$pa  = $pageNum - 1;
								$prev  = " <a href=\"$self?pa=$pa&amp;$qString\">Prev</a> |";
							} else {
								$prev  = '&nbsp;';
							}
							if ($pageNum < $maxPage) {
								$pa = $pageNum + 1;
								$next = "| <a href=\"$self?pa=$pa&amp;$qString\">Next</a> ";
							} else {
								$next = '&nbsp;';
							}
							print "<p align=\"center\">$prev$first$nav$last$next</p>\n";
						}
						// pagination end
					
					} else {
						$resultMessage .= "Nothing Found<br>" . $dbcnx->error . "<br>";
					}
					

				} else {
					$resultMessage .= "Could NOT get site<br>" . $dbcnx->error . "<br>";
				}

			} else {
				$resultMessage .= "Page called without siteid.<br>";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br>" . $dbcnx->connect_error;
		}
		
	} else {
		$resultMessage .= "Access Denied<br>";
	}
	
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>";
	}
	if ($AuthResultMessage) {
		print "<p align=\"center\">$AuthResultMessage</p>";
	}
	print "<p align=\"center\"><a href=\"pax-sites-list.php\">Return To Sites</a></p>\n";
} else {
	include ('loggedout.php');
}

/* print "<p><b>Raw Data From Paxton</b></p>";
print "<pre>";
print_r($result);
print "</pre>"; */

print "</td>\n";
include('footer.php');
?>
