<?php
$subsections = array(
	"By Customer"   => "telecoms-calls-bycust.php",
	"By Extension"  => "telecoms-calls-byext.php",
	"By Site"       => "telecoms-calls-bysite.php"
);
$menustr = array();
foreach ($subsections AS $key => $value) {
	if ($key == $subsubpage) {
		$menustr[] = "<a href=\"$value\"><span class=\"active\">$key</span></a>";
	} else {
		$menustr[] = "<a href=\"$value\">$key</a>";
	}
}
$menustr = implode("&nbsp;&nbsp;|&nbsp;&nbsp;", $menustr);
?>
