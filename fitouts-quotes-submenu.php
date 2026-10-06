<?php
$subsections = array(
	"Quotes"   => "fitouts-quotes-list.php",
	"Customer" => "fitouts-quotes-addedit.php?qid=$qid",
	"Units"    => "fitouts-quotes-units.php?qid=$qid",
	"Items"    => "fitouts-quotes-items.php?qid=$qid",
	"Images"   => "fitouts-quotes-images.php?qid=$qid",
	"Pdfs"     => "fitouts-quotes-pdfs.php?qid=$qid",
	"Dephna"   => "fitouts-quotes-dephna-equote.php?qid=$qid",
	"Contract" => "fitouts-quotes-contract.php?qid=$qid",
	"Summary"  => "fitouts-quotes-summary.php?qid=$qid",
	"Complete" => "fitouts-quotes-complete.php?qid=$qid"
);
foreach ($subsections AS $key => $value) {
	if ($accessRights[$page] < 3 && ($key == "Complete" OR $key == "Summary" OR $key == "Contract")) {
		continue;
	}
	if ($key == $subpage) {
		$menustr[] = "<a href=\"$value\"><span class=\"active\">$key</span></a>";
	} else {
		$menustr[] = "<a href=\"$value\">$key</a>";
	}
}
$menustr = implode("&nbsp;>&nbsp;", $menustr);
?>
