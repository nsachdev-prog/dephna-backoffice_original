<?php
$page = "Fitouts";
$subpage = "Calculator";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page]) {
			
			print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
			include('fitouts-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
?>

    <script type="text/javascript" src="jscripts/formcalculations.js"></script>
    <link href="styles/cakeform.css" rel="stylesheet" type="text/css" />

    <div id="wrap">
        <form action="" id="cakeform" onsubmit="return false;">
        <div>
            <div class="cont_order">
               <fieldset>
                <legend>kW to Amps</legend>
				   
				   <table width="100%" border="0" cellspacing="10" cellpadding="0">
					  <tbody>
						<tr>
						  <th scope="col"><label for="filling">Type</label></th>
						  <th scope="col"><label for="quantity">Kw</label></th>
						</tr>
						<tr>
						  <td><select id="filling" name='filling' onchange="calculateTotal()">
										  <option value="singlephase">Single Phase</option>
										  <option value="threephase">Three Phase</option>
								   </select></td>
						  <td><input type="text" id="quantity" name="quantity" oninput="calculateTotal()" /></td>
						</tr>
						<tr>
						  <td colspan="2"><div id="totalPrice"></div></td>
						</tr>
						<tr>
						  <td colspan="2"><div id="totalPrice4"></div></td>
						</tr>
					  </tbody>
					</table>
                
                </fieldset>
            </div>
        </div>  
       </form>

       <form action="" id="cakeform2" onsubmit="return false;">
        <div>
            <div class="cont_order">
               <fieldset>
                <legend>kWh to BTU</legend>
				   
				   <table width="100%" border="0" cellspacing="10" cellpadding="0"selectedFilling2
						<tr>
						  <th scope="col"><label for="filling2">Type</label></th>
						  <th scope="col"><label for="quantity2">Qty</label></th>
						</tr>
						<tr>
						  <td><select id="filling2" name='filling2' onchange="calculateTotal2()">
										  <option value="BTU">BTU</option>
										  <option value="kWh">kWh</option>
								   </select></td>
						  <td><input type="text" id="quantity2" name="quantity2" oninput="calculateTotal2()" /></td>
						</tr>
						<tr>
						  <td colspan="2"><div id="totalPrice2"></div></td>
						</tr>
						<tr>
						  <td colspan="2"><div id="totalPrice3"></div></td>
						</tr>
					  </tbody>
					</table>
                
                </fieldset>
            </div>
        </div>  
       </form>
	</div><!--End of wrap-->

<?php		
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
