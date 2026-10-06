<?php
$subsections = array(
	array("title" => "Transactions", 
		  "prog"  => "invoicing-ledger.php",
		  "level" => 3),
	array("title" => "Add Transaction", 
		  "prog"  => "invoicing-add-invcred.php",
		  "level" => 3),
	array("title" => "Auto Invoicing",
		  "prog"  => "invoicing-auto.php",
		  "level" => 3),
	array("title" => "Uninvoiced",
		  "prog"  => "invoicing-uninvoiced.php",
		  "level" => 3)
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
$menustr = implode("&nbsp;&nbsp;|&nbsp;&nbsp;", $menustr);
?>
