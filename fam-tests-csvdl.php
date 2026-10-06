<?php
$page = "Fire_Alarms";
$subpage = "Tests";
include('settings.php');
include('session.php');
if ($accessRights[$page] > 2) {
	
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {
		
		# get filters
		if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; $search[] = "p.sitename='$lo'"; }
		if ($_REQUEST['fr']) { $fr = $_REQUEST['fr']; list($d,$m,$y) = explode("-",$fr); $search[] = "t.testdatetime >= '$y-$m-$d 00:00'"; }
		if ($_REQUEST['to']) { $to = $_REQUEST['to']; list($d,$m,$y) = explode("-",$to); $search[] = "t.testdatetime <= '$y-$m-$d 23:59'"; }
		if ($search) { $search = "AND " . implode(" AND ", $search); }
		
		# get some test records
		$sql = "SELECT t.testdatetime, t.ferclear, t.notes, p.siteid, p.sitename, p.title, s.username
				FROM fam_tests AS t
				LEFT JOIN fam_points AS p ON t.pointid = p.ID
				LEFT JOIN staff AS s ON t.staffid = s.ID
				WHERE t.testdatetime > '0' $search
				ORDER BY p.sitename, p.title";
		$result = $dbcnx->query($sql);
		if ($result->num_rows) {
			
			
			$list = array(
				array("Site","Location","Date/Time","Tested By","FER Clear","Comments")
			);
			
			while ($row = $result->fetch_assoc()) {
				$sitename = $row['sitename'];
				$title    = $row['title'];
				$username = $row['username'];
				$datetime = $row['testdatetime'];
				$ferclear = $row['ferclear'];
				$notes    = $row['notes'];
				$notes = str_replace('\n',"",$notes);
				$notes = str_replace('\r','',$notes);
				$notes = str_replace("<br>"," ",$notes);
				list($testdate,$testtime) = explode(" ",$datetime);
				list($y,$m,$d) = explode("-",$testdate);
				$testdate = "$d-$m-$y";
				if ($ferclear == "on") {
					$ferclear = "Yes";
				} else {
					$ferclear = "No";
				}
				$list[] = array("$sitename","$title","$testdate $testtime","$username","$ferclear","$notes");
			}
	
			header("Content-type: application/x-tar");
			header("Content-Disposition: attachment; filename=\"FireAlarmTests.csv\"");
			header("Content-Transfer-Encoding: binary");
			header('Pragma: no-cache, must-revalidate');
			header('Expires: 0');
			$fp = fopen('php://output', 'w');
			foreach ($list as $fields) {
    			fputcsv($fp, $fields);
			}
			fclose($fp);
			
		} else {
			die("No Records Found");
		}
	} else {
		die("Could Not Open Database");
	}
} else {
	die("Access Denied");
}
?>
