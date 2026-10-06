<?php
$subsections = array(
	"Calls"            => "telecoms-calls-bycust.php",
	"Rentals"          => "telecoms-rentals.php",
	"Extensions"       => "telecoms-extensions.php",
	"Import Calls"     => "telecoms-importcalldata.php",
	"Failed Calls"     => "telecoms-importfailures-view.php"
);
foreach ($subsections AS $key => $value) {
	if ($key == $subpage) {
		$menustr[] = "<a href=\"$value\"><span class=\"active\">$key</span></a>";
	} else {
		$menustr[] = "<a href=\"$value\">$key</a>";
	}
}
$menustr = implode("&nbsp;&nbsp;|&nbsp;&nbsp;", $menustr);
?>
