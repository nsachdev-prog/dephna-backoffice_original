<?php
$page = "Fitouts";
$subpage = "Quotes";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align = \"center\" class=\"heading\">Delete Quote</p>\n";
			
            $qid = $_REQUEST['qid'];
            $delete = $_REQUEST['delete'];
            $fr = $_REQUEST['fr'];
			if ($qid) {
				
                $sql = "SELECT q.*, u.compname
                    FROM fitouts_quotes AS q
                    LEFT JOIN users AS u ON q.custid = u.ID
                    WHERE q.ID = '$qid'
                ";
                $result = $dbcnx->query($sql);
                if ($result->num_rows) {
                    $row = $result->fetch_assoc();
                    $year      = $row['year'];
                    $month     = $row['month'];
                    $day       = $row['day'];
                    $compname  = $row['compname'];

                    print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";
					
					if ($delete) {

                        // delete images
                        $sql = "SELECT * FROM fitouts_quotes_images WHERE qid='$qid'";
                        if (($iresult = $dbcnx->query($sql)) && ($iresult->num_rows)) {
                            while ($irow = $iresult->fetch_assoc()) {
                                $image = $irow['image'];
                                $filename = "$image_path/$image";
                                if (is_file($filename)) {
                                    if (!unlink($filename)) {
                                        $resultMessage .= "Image $image could not be deleted.<br />\n";
                                    } else {
                                        $resultMessage .= "Image $image deleted.<br />\n";
                                    }
                                } else {
                                    $resultMessage .= "Image $image not found.<br />\n";
                                }
                            }
                            // delete image records
                            $sql = "DELETE FROM fitouts_quotes_images WHERE qid='$qid'";
                            $result = $dbcnx->query($sql);
                            if ($result) {
                                $resultMessage .= "Image records deleted<br>\n";
                            } else {
                                $resultMessage .= "Image records could not be deleted<br>\n";
                            }
                        } else {
                            $resultMessage .= "No Images to delete<br>\n";
                        }

                        // delete pdfs
                        $sql = "SELECT * FROM fitouts_quotes_pdfs WHERE qid='$qid'";
                        if (($iresult = $dbcnx->query($sql)) && ($iresult->num_rows)) {
                            while ($irow = $iresult->fetch_assoc()) {
                                $pdf = $irow['pdf'];
                                $filename = "/home/dephna/dis2_db/fit/$pdf";
                                if (is_file($filename)) {
                                    if (!unlink($filename)) {
                                        $resultMessage .= "Pdf $pdf could NOT be deleted.<br />\n";
                                    } else {
                                        $resultMessage .= "Pdf $pdf deleted.<br />\n";
                                    }
                                } else {
                                    $resultMessage .= "Pdf $pdf not found.<br />\n";
                                }
                            }
                            // delete pdf records
                            $sql = "DELETE FROM fitouts_quotes_pdfs WHERE qid='$qid'";
                            $result = $dbcnx->query($sql);
                            if ($result) {
                                $resultMessage .= "Pdf records deleted<br>\n";
                            } else {
                                $resultMessage .= "Pdf records could not be deleted<br>\n";
                            }
                        } else {
                            $resultMessage .= "No Pdfs to delete<br>\n";
                        }

                        $sql = "DELETE FROM fitouts_quotes_items WHERE qid='$qid'";
                        $result = $dbcnx->query($sql);
                        if ($result) {
                            $resultMessage .= "Quote Items Successfully Deleted<br>";
                            $sql = "DELETE FROM fitouts_quotes_units WHERE qid='$qid'";
                            $result = $dbcnx->query($sql);
                            if ($result) {
                                $resultMessage .= "Quote Units Successfully Deleted<br>";
                                $sql = "DELETE FROM fitouts_quotes WHERE ID='$qid'";
                                $result = $dbcnx->query($sql);
                                if ($result) {
                                    $resultMessage .= "Quote Record Successfully Deleted<br>";
                                } else {
                                    $resultMessage .= "Quote Record Could NOT Be Deleted<br>" . $dbcnx->error;
                                }
                            } else {
                                $resultMessage .= "Quote Units Could NOT Be Deleted<br>" . $dbcnx->error;
                            }
                        } else {
                            $resultMessage .= "<b>Quote Items Could NOT Be Deleted</b><br>" . $dbcnx->error;
                        }
                    } else {
                        print "<p align=\"center\"><b>Are You Sure?</b></p>";
                        print "<p align=\"center\">";
                        print "	<a href=\"fitouts-quotes-delete.php?qid=$qid&amp;fr=$fr&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"fitouts-quotes-list.php\">No DON'T Delete</a>";
                        print "</p>\n";
                    }
					

				} else {
					$resultMessage = "<span class=\"redtext\"><b>Quote Not Found!</b></span><br>" . $dbcnx->error;
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
        if ($fr == "arc") {
            print "<p align=\"center\"><a href=\"fitouts-quotes-archive-list.php\">Return</a></p>\n";
        } else {
		    print "<p align=\"center\"><a href=\"fitouts-quotes-list.php\">Return</a></p>\n";
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
