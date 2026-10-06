<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Dephna || Training Session Sheet</title>

<style>
	@media print 
{
  @page { margin: 0; }
  body  { margin: 1.2cm; }
}
	
	html, body {
		font-family:Arial, Helvetica, "sans-serif";
		font-size:11pt;
	}
	table.name-table, table.name-table td{
		border:1px solid black;
	}
h1, h2 {
	font-size:16pt;
	font-weight:bold;
	margin-top:20px;
}
.page-break {
  page-break-before: always;
}
.page-break img {
	margin-top:15px;
}
h3{
	font-size:12pt;
	font-weight:bold;
	margin:20px 0 10px 0;
	padding:0;
}
h3.dephnastaff{
	margin:20px 0 10px 0;
}
		
h1, h2, h3, h4, h5, .nobreak {
  page-break-after: avoid;
}

table, figure, .nobreakup {
  page-break-inside: avoid;
}
  div.divFooter {
    position: fixed;
    bottom: 0;
  }
	
	.tprint-company, .tprint-date{
		font-size:14pt;
		font-weight:bold;
		margin:5px 10px 10px 10px;
		
	}
	
	.tprint-date{
		
	}
	
	.tprint-name-title{
		font-weight:bold;
		
	}
	
	.tprint-name-title.width1{
		width:25%;
		
	}
	
	.tprint-name-title.width2{
		width:10%;
		
	}
	
	.tprint-name-title.width3{
		width:20%;
		
	}
	
	.tprint-name{
		
	}
	
	.tickboxes{
		border:2px solid black;
		padding:1px;
		text-align: center;
	}
	
	.tickbox-block{
		display:inline-block;
		width:108px;
		/* border:2pt solid black;
		padding:5px;
		margin:0.8%; */
		text-align: center;
		font-size:9pt;
		margin:6px 0;
		padding:0;
		white-space: nowrap;
		background:#fff;
	}
	
	.tickbox{
		display:block;
		border:2px solid black;
		padding:5px;
		width:11px;
		height:11px;
		margin:4px auto;
		clear:both;
	}
	
	.tprint-commentsbox{
		margin:10px 0 0 0;
		border:2px solid black;
		height:200px;
	}
	
</style>
</head>

<body>

<?php
$page = "Training";
$subpage = "Sessions";
include('settings.php');
include('session.php');

if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$id = $_REQUEST['id'];
			
			if ($id) {
				# get the training session
				$sql = "SELECT t.ID, t.sid, t.cid, t.started, t.notes, s.title AS sitename, u.compname AS compname
					FROM training_sessions AS t
					LEFT JOIN sites AS s ON t.sid = s.ID
					LEFT JOIN users AS u ON t.cid = u.ID
					WHERE t.ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$id       = $row['ID'];
					$sitename = $row['sitename'];
					$compname = $row['compname'];
					$started  = $row['started'];
					$notes    = $row['notes'];
					list($y,$m,$d) = explode("-",$started);
					$started = "$d-$m-$y";
					$tsref = "TS" . sprintf("%'.04d", $id);
					
					# get staff included in this session
					$sql = "SELECT i.ID, i.clsid, c.name, c.fobnum, c.phone
									FROM training_sessions_included AS i
									LEFT JOIN training_clstaff AS c ON i.clsid = c.ID
									WHERE tsid='$id'
									ORDER BY c.name
					";
					$cresult = $dbcnx->query($sql);
					
					# get the checklist
					$sql = "SELECT * FROM training_checklist ORDER BY pos";
					$clresult = $dbcnx->query($sql);
					
					# get the declaration
					$sql = "SELECT details FROM training_declaration WHERE ID = '1'";
					$dresult = $dbcnx->query($sql);
					if ($dresult->num_rows) {
						$drow = $dresult->fetch_assoc();
						$declaration = $drow['details'];
					}
					
					
					# printout starts here
					print "<p align=\"center\"><img src=\"images/DephnaEstates_logo-sm.jpg\"></p>\n";
					
					print "<h1 align=\"center\" class=\"tprint-title\">TRAINING SESSION: $tsref</h1>\n";
					
					print "<table border=\"0\" width=\"100%\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td align=\"left\" class=\"tprint-company\"><b>$compname</b></td>\n";
					print "	<td align=\"right\" class=\"tprint-date\">" . date("H:i jS F Y") . "</td>\n";
					print "</tr>\n";
					print "</table>\n";
					
					if ($cresult->num_rows) {
						print "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"6\" class=\"name-table\">\n";
						print "<tr>\n";
						print "	<td class=\"tprint-name-title width1\">Name</td>\n";
						print "	<td class=\"tprint-name-title width2\">Key Fob</td>\n";
						print "	<td class=\"tprint-name-title width3\">Telephone</td>\n";
						print "	<td class=\"tprint-name-title width2\">Checklist?</td>\n";
						print "	<td class=\"tprint-name-title width1\">Signature</td>\n";
						print "</tr>\n";
						while ($crow  = $cresult->fetch_assoc()) {
							$name   = $crow['name'];
							$fobnum = $crow['fobnum'];
							$phone  = $crow['phone'];
							print "<tr>\n";
							print "	<td class=\"tprint-name\">$name</td>\n";
							print "	<td class=\"tprint-name\">$fobnum</td>\n";
							print "	<td class=\"tprint-name\">$phone</td>\n";
							print "	<td class=\"tprint-name\">Yes/No</td>\n";
							print "	<td class=\"tprint-name\">&nbsp;</td>\n";
							print "</tr>\n";
							
						}
						print "</table>\n";
					} else {
						print "<p align=\"center\"><b>No Client Staff In List</b></p>\n";
					}
					
					if ($clresult->num_rows) {
						print "<h3 align=\"center\" class=\"tprint-title nobreak\">CHECK LIST</h3>\n";
						print "<div class=\"tickboxes  nobreakup\">";
						$checklistarray = array();
						while ($clrow  = $clresult->fetch_assoc()) {
							$title = $clrow['title'];
							$details = $clrow['details'];
							$checklistarray[] = array('title' => $title, 'details' => $details); # save for later use
							print "<div class=\"tickbox-block\"><div class=\"tickbox\">&nbsp;</div>$title</div>\n";
						}
						print "</div>\n";
					} else {
						$resultMessage = "<span class=\"redtext\"><b>No Checklist Found</b></span>";
					}
					
					# comments section
					print "<h3 align=\"center\" class=\"tprint-title nobreak\">COMMENTS</h3>\n";
					print "<div align=\"center\" class=\"tprint-commentsbox nobreakup\">&nbsp;</div>\n";
					
					# declaration section
					print "<h3 align=\"center\" class=\"tprint-title nobreak\">DECLARATION</h3>\n";
					print "<div class=\"tickboxes  nobreakup\">";
					print $declaration;
					print "</div>\n";
					
					# Office Use Only section
					print "<h3 align=\"center\" class=\"tprint-title dephnastaff nobreak\">DEPHNA STAFF - TO BE SIGNED UPON COMPLETION</h3>\n";
					print "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"6\" class=\"name-table\">\n";
					print "<tr>\n";
					print "	<td class=\"tprint-name-title\">Staff Name</td>\n";
					print "	<td class=\"tprint-name-title\">Signature</td>\n";
					print "	<td class=\"tprint-name-title\">Date and Time</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td class=\"tprint-name\">&nbsp;</td>\n";
					print "	<td class=\"tprint-name\">&nbsp;</td>\n";
					print "	<td class=\"tprint-name\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";
					
					
					// print "<p align=\"center\" class=\"page-break\"><img src=\"images/DephnaEstates_logo-sm.jpg\"></p>\n";
					print "<h2 align=\"center\" class=\"tprint-title page-break\">DEPHNA INDUCTION CHECKLIST</h2>\n";
					foreach ($checklistarray AS $key => $item) {
						$num = $key+1;
						print "<p class=\"nobreakup\">\n";
						print "<b>$num. {$item['title']}</b><br>\n";
						print "{$item['details']}\n";	
						print "</p>\n";
					}
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Training Session Not Found!</b></span>";
				}
				
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Page called without id</b></span>";
			}
					
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

?>

</body>
</html>
