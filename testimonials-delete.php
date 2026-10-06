<?php
$page = "Testimonials";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p class=\"heading\" align=\"center\">DELETE TESTIMONIAL</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_GET['id'];
			$delete = $_GET['delete'];

			$sql = "SELECT title, details FROM testimonials WHERE ID='$id'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$title   = $row['title'];
				$details = $row['details'];

				print "<p align=\"center\"><b>$title</b></p>\n";
				print "<p align=\"center\"><blockquote>$details</blockquote></p>\n";

				if ($delete) {

					$sql = "SELECT image FROM testimonials WHERE ID='$id'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$image   = $row['image'];
						if ($image) {
							if (is_file($image_path."/".$image)) {
								unlink($image_path."/".$image);
							}
						}
					}
					$sql = "DELETE FROM testimonials WHERE ID='$id'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "Testimonial Successfully Deleted";
						$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$title','Testimonials','$now')");
					} else {
						$resultMessage = "Could not delete testimonial<br />" . $dbcnx->error;
					}

				} else {
					print "<p align=\"center\"><a href=\"testimonials-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"testimonials-view.php\">No DON'T Delete</a></p>\n";
				}
			} else {
				$resultMessage = "Could not get record from database<br />" . $dbcnx->error;
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"testimonials-view.php\">Testimonials</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
