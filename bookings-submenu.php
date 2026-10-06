<?php
$submenu = array();
if ($available) {
	$submenu[] = "<span class=\"active\">Available</span>";
} else {
	$submenu[] = "<a href=\"bookings-available.php\">Available</a>";
}
if ($onnotice) {
	$submenu[] = "<span class=\"active\">On Notice</span>";
} else {
	$submenu[] = "<a href=\"bookings-onnotice.php\">On Notice</a>";
}
# get sites
$sql = "SELECT ID, title FROM sites
		WHERE ID != '26'
		  AND ID != '27'
		ORDER BY title
";
$result = $dbcnx->query($sql);
if (($result) && ($result->num_rows)) {
	$half = ceil($result->num_rows/2);
	while ($row = $result->fetch_assoc()) {
		$thisid = $row['ID'];
		$thistitle = $row['title'];
		if ($thisid == $siteid) {
			$submenu[] = "<span class=\"active\">$thistitle</span>";
			$sitetitle = $thistitle;
		} else {
			$submenu[] = "<a href=\"bookings-units-view.php?siteid=$thisid\">$thistitle</a>";
		}
	}
}
if ($parking) {
	$submenu[] = "<span class=\"active\">Parking</span>";
} else {
	$submenu[] = "<a href=\"bookings-parking-view.php\">Parking</span></a>";
}
if ($virtuals) {
	$submenu[] = "<span class=\"active\">Virtuals";
} else {
	$submenu[] = "<a href=\"bookings-virtuals-view.php\">Virtuals</span></a>";
}
if ($internet) {
	$submenu[] = "<span class=\"active\">Internet</span>";
} else {
	$submenu[] = "<a href=\"bookings-vlans-list.php\">Internet</span></a>";
}
if ($uanotes) {
	$submenu[] = "<span class=\"active\">Unactioned Notes</span>";
} else {
	$submenu[] = "<a href=\"bookings-units-uanotes.php\">Unactioned Notes</a>";
}
if ($current) {
	$submenu[] = "<span class=\"active\">Current</span>";
} else {
	$submenu[] = "<a href=\"bookings-current.php\">Current</a>";
}
$half++;
$menustr = implode("&nbsp;|&nbsp;", array_slice($submenu,0,$half)) . "<br>" . implode("&nbsp;|&nbsp;", array_slice($submenu,$half));
?>
