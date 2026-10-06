<?php
print "<form name=\"addform\" method=\"post\" action=\"receive.php\">\n";
print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

print "<tr>\n";
print "	<td align=\"right\">Name</td>\n";
print "	<td><input name=\"name\" type=\"text\" maxlength=\"30\" /></td>\n";
print "</tr>\n";

print "<tr>\n";
print "	<td align=\"right\">Address 1</td>\n";
print "	<td><input name=\"addr[line1]\" type=\"text\" maxlength=\"30\" /></td>\n";
print "</tr>\n";

print "<tr>\n";
print "	<td align=\"right\">Address 2</td>\n";
print "	<td><input name=\"addr[line2]\" type=\"text\" maxlength=\"30\" /></td>\n";
print "</tr>\n";

print "<tr>\n";
print "	<td align=\"right\">Address 3</td>\n";
print "	<td><input name=\"addr[line3]\" type=\"text\" maxlength=\"30\" /></td>\n";
print "</tr>\n";

print "<tr>\n";
print "	<td align=\"right\">Male/Female:</td>\n";
print "	<td><select name=\"gender\">";
print "		<option value=\"male\">Male</option>";
print "		<option value=\"female\">Female</option>";
print "	</select>";
print "	</td>\n";
print "</tr>\n";

print "<tr>\n";
print "	<td colspan=\"2\">Notes:<br />";
print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" >$notes</textarea></td>\n";
print "</tr>\n";

print "<tr>\n";
print "	<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Submit\" /></td>\n";
print "</tr>\n";

print "</table>\n";
print "</form>\n";
?>