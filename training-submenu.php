<?php
$subsections = array(
	array("title" => "Key Fobs", 
		  "prog"  => "training-clstaff-list.php",
		  "level" => 1),
	array("title" => "Sessions", 
		  "prog"  => "training-sessions-list.php",
		  "level" => 1),
	array("title" => "Checklist", 
		  "prog"  => "training-checklist-list.php",
		  "level" => 1),
	array("title" => "Declaration", 
		  "prog"  => "training-declaration-view.php",
		  "level" => 1)
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
if ($menustr) {
	$menustr = implode("&nbsp;&nbsp;|&nbsp;&nbsp;", $menustr);
}
?>
