<?php
$subsections = array(
	array("title" => "Revenue By Office", 
		  "prog"  => "reports-revenue-by-office.php",
		  "level" => 3),
	array("title" => "Revenue By Unittype", 
		  "prog"  => "reports-revenue.php",
		  "level" => 3),
	array("title" => "Customer by Unittype", 
		  "prog"  => "reports-revenue2.php",
		  "level" => 3),
	array("title" => "Customer by Ancillary", 
		  "prog"  => "reports-revenue3.php",
		  "level" => 3),
	array("title" => "Occupancy", 
		  "prog"  => "reports-occupancy.php",
		  "level" => 3),
		  array("title" => "Occupancy 2", 
		  "prog"  => "reports-occupancy2.php",
		  "level" => 3),
	array("title" => "Expenses By Supplier",
		  "prog"  => "reports-expenses.php",
		  "level" => 3),
	array("title" => "Expenses By Costcentre",
		  "prog"  => "reports-costcentres.php",
		  "level" => 3),
	array("title" => "Inputs/Costcentres",
		  "prog"  => "reports-inout-cc.php",
		  "level" => 3),
	array("title" => "Inputs/Suppliers",
		  "prog"  => "reports-inout-sup.php",
		  "level" => 3),
	array("title" => "Bookdebt Report",
		  "prog"  => "reports-bookdebt.php",
		  "level" => 3),
	array("title" => "Ancillaries",
		  "prog"  => "reports-anc-by-cust.php",
		  "level" => 3),
	array("title" => "Coldroom Spend",
		  "prog"  => "reports-coldroom-spend.php",
		  "level" => 3),
	array("title" => "Unused Customer Accounts",
		  "prog"  => "reports-custcheck.php",
		  "level" => 3),
	array("title" => "Converted",
		  "prog"  => "reports-converted.php",
		  "level" => 3),
	array("title" => "Deposits Wronly Assigned",
		  "prog"  => "reports-deposits1.php",
		  "level" => 3),
	array("title" => "Deposits With VAT",
		  "prog"  => "reports-deposits2.php",
		  "level" => 3),
	array("title" => "DB Integrity Check",
		  "prog"  => "reports-integrity.php",
		  "level" => 3),
	array("title" => "Check SL Allocations",
		  "prog"  => "reports-sl-allocations.php",
		  "level" => 3),
	array("title" => "Check PL Allocations",
		  "prog"  => "reports-pl-allocations.php",
		  "level" => 3),
	array("title" => "Check PL Outstanding",
		  "prog"  => "reports-pl-allocations2.php",
		  "level" => 3)
);
$n = 0;
foreach ($subsections AS $subsection) {
	if ($accessRights[$page] >= $subsection['level']) {
		$n++;
		if ($subsection['title'] == $subpage) {
			$menustr[] = "<a href=\"{$subsection['prog']}\"><span class=\"active\">{$subsection['title']}</span></a>";
		} else {
			$menustr[] = "<a href=\"{$subsection['prog']}\">{$subsection['title']}</a>";
		}
		if ($n == 6) {
			$menustr[] = "<br>";
			$n = 0;
		}
	}
}
$menustr = implode("&nbsp;&nbsp;|&nbsp;&nbsp;", $menustr);
$menustr = str_replace("&nbsp;&nbsp;|&nbsp;&nbsp;<br>&nbsp;&nbsp;|&nbsp;&nbsp;","<br>",$menustr);
?>
