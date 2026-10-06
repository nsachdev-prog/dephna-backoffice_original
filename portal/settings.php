<?php
	date_default_timezone_set('Europe/London');
	$dbhost = "localhost";
	$dbname = "dephna";
	$dbuser = "dephna";
	$dbpass = "REDACTED";
	$supplierEmail = "accounts@dephna.com";
	$image_path = "../db_images";
	$vatRate = 20;
	$compName = "Dephna Estates Ltd";
	$compAddr = "24 - 26 Arcadia Avenue<br />London<br />N3 2JU";
	$compDDid = "420868";
	$changePasswordMonths = 3; # The months till password expires.
	$TallyIPAddrs = array("http://195.27.19.211:9001","http://81.143.156.185:9001","http://81.143.156.177:9001");
	$unitPrefixes = array("Acton Lane" => "Act",
						"190 Acton Lane" => "190",
						"Coronation" => "Cor",
						"Cumberland" => "Cmb",
						"Finchley" => "Fin",
						"Neasden" => "Ndn",
						"North Acton" => "Nac",
						"Brittania Way" => "Brit",
						"Portal Way" => "Ptl",
						"26 Chase Road" => "26");
?>
