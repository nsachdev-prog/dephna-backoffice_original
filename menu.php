		<td class="leftcolumn" valign="top">
			<ul class="menu">
<?php
for ($n=0; $n < count($programs); $n++) {
	$thissection = $sections[$n];
	if ($accessRights[$thissection] > 0) {
		$itemdispay = str_replace("_"," ",$sections[$n]);
		if ($itemdispay == "Purchases") { $itemdispay = "Purchase Ledger"; }
		if ($itemdispay == "Internet Monitor") {
			$target = "target=\"_blank\"";
		} elseif ($itemdispay == "Cleaners Timesheets") {
			$target = "target=\"_blank\"";
		} else {
			$target = "";
		}

		if ($itemdispay == "Sales Ledger" AND $_SESSION['user'] == 1) {
			$program = "sl-ledgers-list.php";
		}
		elseif ($itemdispay == "Purchase Ledger" AND $_SESSION['user'] == 1) {
			$program = "pl-ledgers-list.php";
		} else {
			$program = $programs[$n];
		}
		
		if ($sections[$n] == $page) {
			print "			<li><a href=\"$program\" $target class=\"active\"><span>$itemdispay</span></a></li>\n";
		} else {
			print "			<li><a href=\"$program\" $target><span>$itemdispay</span></a></li>\n";
		} 
	}
}
?>
		</ul>
		</td>
