<?php
$subsections = array(
	array("title" => "Active Requests", 
		  "prog"  => "deposits-active.php",
		  "level" => 3),
	array("title" => "Add Request", 
		  "prog"  => "deposits-addedit.php",
		  "level" => 3),
	array("title" => "Payment Run", 
		  "prog"  => "deposits-paymentrun.php",
		  "level" => 3),
	array("title" => "Bank Files", 
		  "prog"  => "deposits-bankandtally.php",
		  "level" => 3),
	array("title" => "Deposit History", 
		  "prog"  => "deposits-history.php",
		  "level" => 3),
	array("title" => "Deposit Totals", 
		  "prog"  => "deposits-total.php",
		  "level" => 3),
);
foreach ($subsections AS $subsection) {
	if ($accessRights[$page] >= $subsection['level']) {
		if ($subsection['title'] == $subpage) {
			$menustr[] = "<span class=\"active\">{$subsection['title']}</span>";
		} else {
			$menustr[] = "<a href=\"{$subsection['prog']}\">{$subsection['title']}</a>";
		}
	}
}
$menustr = implode("&nbsp;|&nbsp;", $menustr);
?>
