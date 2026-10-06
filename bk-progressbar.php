<?php
if ($bkid) {
	if ($bktype == "Units") {
		$modules = array(
			"BOOKINGS" => "",
			"SITE"     => "module=site&amp;action=select&amp;bkid=$bkid",
			"UNITS"    => "module=units&amp;action=select&amp;bkid=$bkid",
			"CUSTOMER" => "module=customer&amp;action=select&amp;bkid=$bkid",
			"CONTRACT" => "module=contract&amp;bkid=$bkid",
			"SUMMARY"  => "module=summary&amp;bkid=$bkid"
		);
	} elseif ($bktype == "Virtuals") {
		$modules = array(
			"BOOKINGS" => "",
			"SITE"     => "module=site&amp;action=select&amp;bkid=$bkid",
			"VIRTUALS" => "module=virtuals&amp;action=select&amp;bkid=$bkid",
			"CUSTOMER" => "module=customer&amp;action=select&amp;bkid=$bkid",
			"VCONTRACT" => "module=vcontract&amp;bkid=$bkid",
			"SUMMARY"  => "module=summary&amp;bkid=$bkid"
		);
	}
	print "<div class=\"booking-breadcrumbs\">\n";
	if ($modules) {
		foreach($modules AS $key => $value) {
			print "	<div class=\"crumb\"><div class=\"inner\">";
			if ($subpage == $key) {
				print $key;
			} else {
				print "<a href=\"bk-manager.php?$value\">$key</a>";
			}
			print "</div></div>\n";
		}
	}
	print "</div>\n";
} else {
	print"<div class=\"booking-breadcrumbs\">\n";
	print"<div class=\"crumb\"><div class=\"inner\"><a href=\"bk-manager.php\">BOOKINGS</a></div></div>\n";
	print"<div class=\"crumb\"><div class=\"inner\"><a href=\"bk-manager.php?module=site&amp;action=select\">SITE</a></div></div>\n";
	if ($bktype == "Units") {
		print"<div class=\"crumb\"><div class=\"inner\"><a href=\"bk-manager.php?module=units&amp;action=select&amp;bkid=$bkid\">UNITS</a></div></div>\n";
	} elseif ($bktype == "Virtuals") {
		print"<div class=\"crumb\"><div class=\"inner\"><a href=\"bk-manager.php?module=virtuals&amp;action=select&amp;bkid=$bkid\">VIRTUALS</a></div></div>\n";
	}
	print"</div>\n";
}
?>
