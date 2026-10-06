<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
include('settings.php');
include('session.php');
if ($_SESSION['user']) {
	
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    if(!$dbcnx->connect_error) {

        if (($_REQUEST['custref']) AND ( $_REQUEST['custref'] != "All")) { $custref = $_REQUEST['custref']; }
        if ($custref) {
            if ($custref == "Not Dephna") { $search[] = "custref != '20199'"; }
            else { $search[] = "custref = '$custref'"; }
        }
        if ($_REQUEST['site'] != "All") { $site = $_REQUEST['site']; }
        if ($site) { $search[] = "sitename = '$site'"; }
        if ($_REQUEST['comm'] != "All") { $comm = $_REQUEST['comm']; }
        if ($comm) { $search[] = "commodity = '$comm'"; }
        if ($search) { $search = "AND " . implode(" AND ", $search); }

        $fmonth = $_REQUEST['fmonth']; if (!$fmonth) { $fmonth = date("m"); }
        $fyear  = $_REQUEST['fyear'];  if (!$fyear)  { $fyear  = date("Y"); }
        $fmonth = sprintf("%02s",$fmonth);

        // get available
        $sql = "SELECT * FROM UtlUsedNew
                WHERE year = '$fyear'
                AND month = '$fmonth'
                $search
                ORDER BY sitename, unit, collector, counter, sub
        ";
        if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename=\"Utilities-$fyear-$fmonth.csv\"");
            header("Content-Transfer-Encoding: text");
            header('Pragma: no-cache, must-revalidate');
            header('Expires: 0');

            print "ID,Collector,Counter,Sub,SiteID,Sitename,Cust Ref,Unit,Commodity,Previous,Current,Used,Rate,Charge\n";

            while ($row = $result->fetch_assoc()) {
                $id        = $row['ID'];
                $year      = $row['year'];
                $month     = $row['month'];
                $day       = $row['day'];
                $siteid    = $row['siteid'];
                $sitename  = $row['sitename'];
                $collector = $row['collector'];
                $counter   = $row['counter'];
                $sub       = $row['sub'];
                $ccustref  = $row['custref'];
                $unit      = $row['unit'];
                $commodity = $row['commodity'];
                $reading   = $row['reading'];
                $previous  = $row['previous'];
                $meter     = $row['meter'];
                $used      = $row['used'];
                $rate      = $row['rate'];
                $charge    = $row['charge'];

                print "$id,";
                print "$collector,";
                print "$counter,";
                print "$sub,";
                print "$siteid,";
                print "$sitename,";
                print "DP$ccustref,";
                print "$unit,";
                print "$commodity,";
                print "$previous,";
                print "$meter,";
                print "$used,";
                print "$rate,";
                print "$charge\n";
            }
        } else {
            print "Nothing Found<br />";
            print $sql;
        }

        $dbcnx->close();
    }
}
?>
