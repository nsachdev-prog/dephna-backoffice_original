<?php
$subsubsections = array("Daily" => "sl-daybook-daily.php",	
					"Monthly" => "sl-daybook-monthly.php"
);
foreach ($subsubsections AS $key => $value) {
	if ($key == $subsubpage) {
		$submenustr[] = "<a href=\"$value\"><span class=\"active\">$key</span></a>";
	} else {
		$submenustr[] = "<a href=\"$value\">$key</a>";
	}
}
$submenustr = implode("&nbsp;|&nbsp;", $submenustr);
?>
