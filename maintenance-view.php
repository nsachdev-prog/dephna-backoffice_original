<?php
$page = "Maintenance";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
	print "<p align=\"center\" class=\"heading\">MAINTENANCE</p>\n";

				# filter form
				print "<form name=\"addform\" method=\"post\" action=\"maintenance-view.php?az=$az\">\n";
				print "<table class=\"filterBlock\" align=\"center\">\n";
				print "	<tr>\n";
				print "		<td><b>Type:</b></td>\n";
				print "		<td><select name=\"\" class=\"element_small\">\n";
				print "		<option value=\"\">All</option>\n";
				print "		<option value=\"\">Electrical</option>\n";
				print "		<option value=\"\">Plumbing</option>\n";
				print "		<option value=\"\">Etc</option>\n";
				print "		</select>";
				print "		</td>\n";

				print "		<td><b>Site:</b></td>\n";
				print "		<td><select name=\"\" class=\"element_small\">\n";
				print "		<option value=\"\">All</option>\n";
				print "		<option value=\"\">High Priority</option>\n";
				print "		<option value=\"\">Medium Priority</option>\n";
				print "		<option value=\"\">Low Priority</option>\n";
				print "		</select>";
				print "		</td>\n";

				print "		<td><b>Client:</b></td>\n";
				print "		<td><select name=\"\" class=\"element_small\">\n";
				print "		<option value=\"\">All</option>\n";
				print "		<option value=\"\">High Priority</option>\n";
				print "		<option value=\"\">Medium Priority</option>\n";
				print "		<option value=\"\">Low Priority</option>\n";
				print "		</select>";
				print "		</td>\n";

				print "		<td><b>Unit:</b></td>\n";
				print "		<td><input name=\"\" class=\"element_smaller\" />\n";
				print "		</td>\n";

				print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

				print "	</tr>\n";
				print "</table>\n";
				print "</form>\n";
?>
<style type="text/css">
.priority1{
	background-color:#F00 !important;
	color:#000 !important;
}
.priority2{
	background-color:#FC0 !important;
	color:#000 !important;
}
.priority3{
	background-color:#0C0 !important;
	color:#000 !important;
}
.completed{
	background-color:#ccc !important;
	color:#000 !important;
}

.colRed a, .colYellow a, .colGreen a, .colGrey a{
	font-weight:bold;
	text-decoration:underline;
	color:#000 !important;
}

.maint-viewer {
	position:absolute;
	width:20%;
}
.maint-link {
	color:#0c4173;
	cursor:pointer;
}
.maint-popup {
	position:relative;
	top:-10px;
	left:50px;
	margin:0 auto;
	text-align:left;
	width:250px;
	background:#f9f9f9;
	padding:5px;
	border:1px solid #333;
	display:none;
}
.maint-link:hover + .maint-popup{
	display:block !important;
}
</style>


<table align="center" width="95%" cellspacing="0" cellpadding="3">

      <tr>
      	<td colspan="9">&nbsp;</td>
      </tr>

      <tr>
      	<td colspan="9">BUILDING MAINTENANCE</td>
      </tr>

      <tr>
	  	<td class="colHead">Ticket</td>
	  	<td class="colHead">Date</td>
	  	<td class="colHead">Site</td>
	  	<td class="colHead">Days Open</td>
	  	<td class="colHead">Type</td>
      	<td class="colHead">Client Name</td>
      	<td class="colHead">Allocated</td>
      </tr>

      <tr>
	  	<td valign="top" class="colRed"><a href="maintenance-addedit.php">M000001</a></td>
	  	<td valign="top" class="colRed">02/04/13</td>
	  	<td valign="top" class="colRed">Cumberland</td>
	  	<td valign="top" class="colRed">3</td>
	  	<td valign="top" class="colRed">Electrical</td>
      	<td valign="top" class="colRed">ABC Limited</td>
      	<td valign="top" class="colRed"></td>
      </tr>

      <tr>
	  	<td valign="top" class="colYellow"><a href="maintenance-addedit.php">M000002</a></td>
	  	<td valign="top" class="colYellow">01/04/13</td>
	  	<td valign="top" class="colYellow">Cumberland</td>
	  	<td valign="top" class="colYellow">4</td>
	  	<td valign="top" class="colYellow">Electrical</td>
      	<td valign="top" class="colYellow">CBeebies Limited</td>
      	<td valign="top" class="colYellow">&nbsp;</td>
      </tr>

      <tr>
	  	<td valign="top" class="colGreen"><a href="maintenance-addedit.php">M000003</a></td>
	  	<td valign="top" class="colGreen">01/04/13</td>
	  	<td valign="top" class="colGreen">Cumberland</td>
	  	<td valign="top" class="colGreen">4</td>
	  	<td valign="top" class="colGreen">Plumbing</td>
      	<td valign="top" class="colGreen">CBeebies Limited</td>
      	<td valign="top" class="colGreen">&nbsp;</td>
      </tr>

      <tr>
	  	<td valign="top" class="colGreen"><a href="maintenance-addedit.php">M000004</a></td>
	  	<td valign="top" class="colGreen">01/04/13</td>
	  	<td valign="top" class="colGreen">Finchley</td>
	  	<td valign="top" class="colGreen">4</td>
	  	<td valign="top" class="colGreen">Electrical</td>
      	<td valign="top" class="colGreen">CBeebies Limited</td>
      	<td valign="top" class="colGreen">&nbsp;</td>
      </tr>

      <tr>
      	<td colspan="9">&nbsp;</td>
      </tr>

      <tr>
      	<td colspan="9">IT & TELECOMS</td>
      </tr>

      <tr>
	  	<td class="colHead">Ticket</td>
	  	<td class="colHead">Date</td>
	  	<td class="colHead">Site</td>
	  	<td class="colHead">Days Open</td>
	  	<td class="colHead">Type</td>
      	<td class="colHead">Client Name</td>
      	<td class="colHead">Allocated</td>
      </tr>

      <tr>
	  	<td valign="top" class="colRed"><a href="maintenance-addedit.php">M000001</a></td>
	  	<td valign="top" class="colRed">02/04/13</td>
	  	<td valign="top" class="colRed">Cumberland</td>
	  	<td valign="top" class="colRed">3</td>
	  	<td valign="top" class="colRed">Electrical</td>
      	<td valign="top" class="colRed">ABC Limited</td>
      	<td valign="top" class="colRed"></td>
      </tr>

      <tr>
	  	<td valign="top" class="colYellow"><a href="maintenance-addedit.php">M000002</a></td>
	  	<td valign="top" class="colYellow">01/04/13</td>
	  	<td valign="top" class="colYellow">Cumberland</td>
	  	<td valign="top" class="colYellow">4</td>
	  	<td valign="top" class="colYellow">Electrical</td>
      	<td valign="top" class="colYellow">CBeebies Limited</td>
      	<td valign="top" class="colYellow">&nbsp;</td>
      </tr>

      <tr>
	  	<td valign="top" class="colGreen"><a href="maintenance-addedit.php">M000003</a></td>
	  	<td valign="top" class="colGreen">01/04/13</td>
	  	<td valign="top" class="colGreen">Cumberland</td>
	  	<td valign="top" class="colGreen">4</td>
	  	<td valign="top" class="colGreen">Plumbing</td>
      	<td valign="top" class="colGreen">CBeebies Limited</td>
      	<td valign="top" class="colGreen">&nbsp;</td>
      </tr>

      <tr>
	  	<td valign="top" class="colGreen"><a href="maintenance-addedit.php">M000004</a></td>
	  	<td valign="top" class="colGreen">01/04/13</td>
	  	<td valign="top" class="colGreen">Finchley</td>
	  	<td valign="top" class="colGreen">4</td>
	  	<td valign="top" class="colGreen">Electrical</td>
      	<td valign="top" class="colGreen">CBeebies Limited</td>
      	<td valign="top" class="colGreen">&nbsp;</td>
      </tr>

      <tr>
      	<td colspan="9">&nbsp;</td>
      </tr>

      <tr>
      	<td colspan="9">COMPLETED JOBS AWAITING CLOSURE</td>
      </tr>

	  <tr>
	  	<td class="colHead">Ticket</td>
	  	<td class="colHead">Date</td>
	  	<td class="colHead">Site</td>
	  	<td class="colHead">Days Open</td>
	  	<td class="colHead">Type</td>
      	<td class="colHead">Client Name</td>
      	<td class="colHead">Allocated</td>
      </tr>

      <tr>
	  	<td valign="top" class="colGrey"><a href="maintenance-addedit.php">M000003</a></td>
	  	<td valign="top" class="colGrey">12/03/13</td>
	  	<td valign="top" class="colGrey">Cumberland</td>
	  	<td valign="top" class="colGrey">27</td>
	  	<td valign="top" class="colGrey">Paxton</td>
      	<td valign="top" class="colGrey">CBeebies Limited</td>
      	<td valign="top" class="colGrey">Joe Bloggs</td>
      </tr>

	</table>
<p align="center"><a href="maintenance-create.php">Add New Ticket</a>&nbsp; | &nbsp;<a href="maintenance-archive.php">Closed Tickets</a>&nbsp; | &nbsp;<a href="maintenance-groups-view.php">Groups</a></p>



    <?php
print "</td>";
include('footer.php');
?>
