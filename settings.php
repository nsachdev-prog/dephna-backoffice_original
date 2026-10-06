<?php
	date_default_timezone_set('Europe/London');
	if (!$cronjob) {
		error_reporting(E_ALL ^ E_NOTICE);
		ini_set('session.gc_maxlifetime', '1800');
		ini_set('session.gc_divisor', '1');
		ini_set('session.gc_probability', '1');
		ini_set('session.cookie_lifetime', '0');
		session_name('dephna');
		include_once('../../cron/DB.php');
		include_once('../../cron/WA.php');
	} else {
		include_once('DB.php');
		include_once('WA.php');
	}
	$SYScompanies = array("dephna" => "Dephna Estates","dephna_capital" => "Dephna Capital");
	$dbhost = "localhost";
	$dbname = "dephna";
	$dbuser = "dephna";
	$dbpass = "REDACTED";
	$supplierEmail = "accounts@dephna.com";
	$accountsEmail = "accounts@dephna.com";
	$accountsPayable = "david@davidsherwood.co.uk";
	$db_dir = "../../dis2_db";
	$sql_dir = "../../dis2_db/sql";
	$backup_dir = "../../dis2_backups";
	$image_path = "../db_images";
	$compName = "Dephna Group Ltd.";
	$compAddr = "2 Portal Way<br />London W3 6RT";
	$compDDid = "420868";
	$vatRate  = 20;
	$HKrate   = "30.00";
	$ColdRoomWeekDayRate = "35.00";
	$ColdRoomWeekEndRate = "35.00";
	$ColdRoomVAT         = "Yes";
	$StorageWeekDayRate  = "25.00";
	$StorageWeekEndRate  = "25.00";
	$StorageVAT          = "Yes";
	$nonBakTables = array("TelFeatures","TelInternational","TelNational","xweb_history"); # DB Tables not to backup
	$changePasswordMonths = 3;	# The months till password expires.
	$tk_stat_threshold = 5;		# Change tickets to High status when > this value.
	$diaryperiod = 14;			# holiday period printed on index page. Must be > 0 and < 29.
	$unitPrefixes = array(
		"Acton Lane" => "Act",
		"190 Acton Lane" => "190",
		"Coronation" => "Cor",
		"Cumberland" => "Cmb",
		"Finchley" => "Fin",
		"Neasden" => "Ndn",
		"North Acton" => "Nac",
		"Brittania Way" => "Brit",
		"Portal Way" => "Ptl",
		"26 Chase Road" => "CH",
		"Whetstone" => "Whet"
	);
	$unit_amps = 63;	# maximum ampage per unit
	$unit_kws  = 55;	# maximum kw per unit
	$icesites = array(
		28 => "190 Acton Lane",
		16 => "214 Acton Lane",
		 3 => "Arcadia Avenue",
		35 => "Chase Road",
		11 => "Coronation Road",
		34 => "Britannia Way",
		 5 => "Cumberland",
		17 => "Portal Way",
		36 => "Whetstone"
	);
?>
