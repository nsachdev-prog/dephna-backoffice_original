<?php
$subsections = array(
	array("title" => "Sites", 
		  "prog"  => "pax-sites-list.php",
		  "level" => 3),
	array("title" => "Users", 
		  "prog"  => "pax-users-list.php?siteid=$siteid",
		  "level" => 3),
	array("title" => "Doors", 
		  "prog"  => "pax-doors-list.php?siteid=$siteid",
		  "level" => 3),
	array("title" => "Departments", 
		  "prog"  => "pax-departments-list.php?siteid=$siteid",
		  "level" => 3),
	array("title" => "Levels", 
		  "prog"  => "pax-levels-list.php?siteid=$siteid",
		  "level" => 3),
	array("title" => "Events", 
		  "prog"  => "pax-events-list.php?siteid=$siteid",
		  "level" => 3),
	array("title" => "Fob Search", 
		  "prog"  => "pax-fobsearch.php?siteid=$siteid",
		  "level" => 3),
	array("title" => "Requests", 
		  "prog"  => "pax-requests.php?siteid=$siteid",
		  "level" => 3)
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
