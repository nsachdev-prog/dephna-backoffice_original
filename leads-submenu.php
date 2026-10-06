<?php
$subsections = array(
	"Leads"     => "leads-list.php?ty=Leads",
	"Offices"   => "leads-list.php?ty=Offices",
	"Archived"  => "leads-archive-list.php",
	"Meetings"  => "leads-meetings-list.php",
	"Snapshot"  => "leads-snapshot-detail.php",
	"Brokers"   => "leads-brokers-list.php",
	"Templates" => "leads-templates-list.php",
	"Settings"  => "leads-settings.php"
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
