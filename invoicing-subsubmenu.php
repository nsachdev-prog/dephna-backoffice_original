<?php
$subsections = array(
	"All"  => "telecoms-importfailures-view.php",
	"Batch" => "telecoms-importfailures-batch-cust.php",
	"Notes" => "telecoms-importfailures-notes.php"
);
foreach ($subsections AS $key => $value) {
	if ($key == $subsubpage) {
		$submenustr[] = "<span class=\"active\">$key</span>";
	} else {
		$submenustr[] = "<a href=\"$value\">$key</a>";
	}
}
$submenustr = implode("&nbsp;&nbsp;|&nbsp;&nbsp;", $submenustr);
?>
