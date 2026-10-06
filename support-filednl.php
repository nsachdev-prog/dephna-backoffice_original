<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Support";
include('settings.php');
include('session.php');
if ($_SESSION['user']) {
	if ($accessRights[$page]) {
        $aid = $_REQUEST['aid'];
        if ($aid) {
            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		    if(!$dbcnx->connect_error) {
                $path = "../../dis2_db/sup";
                $sql = "SELECT * FROM support_attachments WHERE ID = '$aid'";
                if (($aresult = $dbcnx->query($sql)) AND ($aresult->num_rows)) {
                    $arow = $aresult->fetch_assoc();
                    $attname     = $arow['attname'];
                    $attfilename = $arow['attfilename'];
                    $attmime     = $arow['attmime'];

                    header("Content-type: $attmime");
                    header("Content-Disposition: attachment; filename=$attname");
                    header('Pragma: no-cache, must-revalidate');
                    header('Expires: 0');
                    // header('Content-Length: ' . filesize("$path/$attfilename"));

                    readfile("$path/$attfilename");
                    
                }
            }
        }
    }
}
?>
