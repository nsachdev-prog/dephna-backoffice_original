<?php
$submenu = array();
if (!$siteid) {
	$submenu[] = "<span class=\"active\">Summary</span>";
} else {
	$submenu[] = "<a href=\"coldroom-reports-summary.php?date=$date\">Summary</a>";
}
// get sites
$sql = "SELECT ID, title FROM sites
		WHERE ID != '2'
          AND ID != '4'
		  AND ID != '26'
		  AND ID != '27'
		ORDER BY title
";
$result = $dbcnx->query($sql);
if (($result) && ($result->num_rows)) {
	while ($row = $result->fetch_assoc()) {
		$thisid = $row['ID'];
		$thistitle = $row['title'];
		if ($thisid == $siteid) {
			$submenu[] = "<span class=\"active\">$thistitle</span>";
			$sitetitle = $thistitle;
		} else {
			$submenu[] = "<a href=\"coldroom-reports-sites.php?siteid=$thisid\">$thistitle</a>";
		}
	}
}
// make menu string from array
$menustr = implode("&nbsp;|&nbsp;", $submenu);

// submenu for Reports and Parts
$submenu = array();
if ($homePageSections['ColdRoomReports'] == "on") {
	if ($subpage == "Parts") {
		$submenu[] = "<a href=\"coldroom-reports-summary.php\">Reports</a>";
		$submenu[] = "<a href=\"coldroom-reports-unapproved.php\">Unapproved</a>";
		$submenu[] = "<span class=\"active\">Parts</span>";
		$submenu[] = "<a href=\"coldroom-reports-parts-used.php\">Used</a>";
	} elseif ($subpage == "Unapproved") {
		$submenu[] = "<a href=\"coldroom-reports-summary.php\">Reports</a>";
		$submenu[] = "<span class=\"active\">Unapproved</span>";
		$submenu[] = "<a href=\"coldroom-reports-parts-list.php\">Parts</a>";
		$submenu[] = "<a href=\"coldroom-reports-parts-used.php\">Used</a>";
	} elseif ($subpage == "Used") {
		$submenu[] = "<a href=\"coldroom-reports-summary.php\">Reports</a>";
		$submenu[] = "<a href=\"coldroom-reports-unapproved.php\">Unapproved</a>";
		$submenu[] = "<a href=\"coldroom-reports-parts-list.php\">Parts</a>";
		$submenu[] = "<span class=\"active\">Used</span>";
	} else {
		$submenu[] = "<span class=\"active\">Reports</span>";
		$submenu[] = "<a href=\"coldroom-reports-unapproved.php\">Unapproved</a>";
		$submenu[] = "<a href=\"coldroom-reports-parts-list.php\">Parts</a>";
		$submenu[] = "<a href=\"coldroom-reports-parts-used.php\">Used</a>";
	}
} else {
	if ($subpage == "Parts") {
		$submenu[] = "<a href=\"coldroom-reports-summary.php\">Reports</a>";
		$submenu[] = "<span class=\"active\">Parts</span>";
		$submenu[] = "<a href=\"coldroom-reports-parts-used.php\">Used</a>";
	} elseif ($subpage == "Used") {
		$submenu[] = "<a href=\"coldroom-reports-summary.php\">Reports</a>";
		$submenu[] = "<a href=\"coldroom-reports-parts-list.php\">Parts</a>";
		$submenu[] = "<span class=\"active\">Used</span>";
	} else {
		$submenu[] = "<span class=\"active\">Reports</span>";
		$submenu[] = "<a href=\"coldroom-reports-parts-list.php\">Parts</a>";
		$submenu[] = "<a href=\"coldroom-reports-parts-used.php\">Used</a>";
	}
}
// make menu string from array
$submenustr = implode("&nbsp;|&nbsp;", $submenu);
?>
