<?php
$subsections = array(
	"Profit And Loss" => "acc-reports-pandl.php?id=$id&amp;fr=$fr&amp;to=$to",
	"Trial Balance" => "acc-reports-trialbal.php?id=$id&amp;fr=$fr&amp;to=$to",
	"Fixed Running Costs" => "acc-reports-fixed.php?id=$id&amp;fr=$fr&amp;to=$to",
	"Balance Sheet" => "acc-reports-balsheet.php?id=$id&amp;fr=$fr&amp;to=$to"
);
foreach ($subsections AS $key => $value) {
	if ($key == $subpage) {
		$menustr[] = "<a href=\"$value\"><span class=\"active\">$key</span></a>";
	} else {
		$menustr[] = "<a href=\"$value\">$key</a>";
	}
}
$menustr = implode("&nbsp;|&nbsp;", $menustr);
?>
