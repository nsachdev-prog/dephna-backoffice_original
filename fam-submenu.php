<?php
$subsections = array(
	array("title" => "Points", 
		  "prog"  => "fam-points-list.php",
		  "level" => 1),
	array("title" => "Tests", 
		  "prog"  => "fam-tests-list.php",
		  "level" => 1),
	array("title" => "Drills",
		  "prog"  => "fam-drills-list.php",
		  "level" => 1),
	array("title" => "Templates",
		  "prog"  => "fam-templates-list.php",
		  "level" => 1),
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
