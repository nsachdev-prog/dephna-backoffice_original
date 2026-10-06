<?php
$page = "Accounts";
$subpage = "Download";
include('settings.php');
include('session.php');

if ($_SESSION['user']) {

	if ($accessRights[$page] > 1) {

        $id = $_REQUEST['id'];

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            // get period dates and manual figures
            $sql = "SELECT * FROM acc_periods WHERE ID = '$id'";
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                $row = $result->fetch_assoc();
                $period_start  = $row['period_start'];
                $period_end    = $row['period_end'];

                $zip = new ZipArchive();
                $filename = "../../dis2_db/accounts.zip";

                if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
                    $resultMessage .= "Cannot open zip file<br>";
                } else {
                
                    $accfiles['acc-dnl-wages']          = $_POST['wages'];
                    $accfiles['acc-dnl-dividends']      = $_POST['dividends'];
                    $accfiles['acc-dnl-paye']           = $_POST['paye'];
                    $accfiles['acc-dnl-vat']            = $_POST['vat'];
                    $accfiles['acc-dnl-intercompany']   = $_POST['intercompany'];
                    $accfiles['acc-dnl-pledgers']       = $_POST['pledgers'];        // List of supplier ledgers from Jan – Dec in date order done as a yearly  - with the balance outstanding
                    $accfiles['acc-dnl-bankcharges']    = $_POST['bankcharges'];
                    $accfiles['acc-dnl-personalincome'] = $_POST['personalincome'];  // Personal income – this will include any ledgers that have gone in to the business for personal  - e.g. David Lloyd if applicable
                    $accfiles['acc-dnl-personaltax']    = $_POST['personaltax']; 
                    $accfiles['acc-dnl-donations']      = $_POST['donations'];
                    $accfiles['acc-dnl-deposits']       = $_POST['deposits'];        // deposits
                    $accfiles['acc-dnl-costcentres']    = $_POST['costcentres'];     // Supplier Ledger downloaded as per cost centres – eg maintenance, utilities, legal fees
                    
                    foreach ($accfiles AS $report => $ticked) {
                        if ($ticked) {
                            include_once ($report.".php");
                        }
                    }

                    $zip->close();

                    if (file_exists($filename)) {

                        header('Content-Type: application/zip');
                        header('Content-Disposition: attachment; filename=accounts.zip');
                        header('Content-Length: ' . filesize($filename));
                        flush();
                        readfile($filename);
                        
                    }

                }
                
            }
 
			$dbcnx->close();

		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
    }
} else {
	include ('loggedout.php');
}

?>
