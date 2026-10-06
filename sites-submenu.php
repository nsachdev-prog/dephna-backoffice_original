<?php
$subsections = array(
	array("title" => "Sites", 
		  "prog"  => "sites-list.php",
		  "level" => 1),
	array("title" => "Addendum", 
		  "prog"  => "sites-addendum-list.php",
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
$menustr = implode("&nbsp;&nbsp;|&nbsp;&nbsp;", $menustr);
?>
