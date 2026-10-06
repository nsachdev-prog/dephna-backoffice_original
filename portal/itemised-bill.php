<?php
include('settings.php');
include('session.php');
include('header.php');
?>
	<p>&nbsp;</p>
	<div class="heading">itemised bill for [Company Name]</div>
	<table align="center">
	<tr>
		<td width="100%" align="center" valign="top">
        	<table width="560" border="0" cellspacing="0" cellpadding="6">
  <tr>
    <td class="phonebill-top">Date &amp; Time</td>
    <td class="phonebill-top">Destination</td>
    <td class="phonebill-top">Number Dialled</td>
    <td class="phonebill-top">Duration</td>
    <td class="phonebill-top">Cost</td>
  </tr>
  <tr>
    <td colspan="5" style="height:2px;" height="2">&nbsp;</td>
    </tr>
  <tr>
    <td class="phonebill-date"> 01/03/2012 08:33:01 </td>
    <td class="phonebill-dest"> Vodafone </td>
    <td class="phonebill-number"> 07825159757 </td>
    <td class="phonebill-duration"> 00:00:17 </td>
    <td class="phonebill-cost"> £ 0.047 </td>
  </tr>
  <tr>
    <td class="phonebill-date"> 01/03/2012 10:14:39 </td>
    <td class="phonebill-dest"> London </td>
    <td class="phonebill-number"> 02079285620 </td>
    <td class="phonebill-duration"> 00:02:23 </td>
    <td class="phonebill-cost"> £ 0.072 </td>
  </tr>
  <tr>
    <td class="phonebill-date"> 01/03/2012 12:04:48 </td>
    <td class="phonebill-dest"> Brentwood </td>
    <td class="phonebill-number"> 01277634555 </td>
    <td class="phonebill-duration"> 00:04:20 </td>
    <td class="phonebill-cost"> £ 0.130 </td>
  </tr>
  <tr>
    <td class="phonebill-date"> 01/03/2012 12:28:08 </td>
    <td class="phonebill-dest"> London </td>
    <td class="phonebill-number"> 02084523964 </td>
    <td class="phonebill-duration"> 00:00:21 </td>
    <td class="phonebill-cost"> £ 0.050 </td>
  </tr>
  <tr>
    <td colspan="5" class="row-spacer">&nbsp;</td>
    </tr>
  <tr>
    <td>&nbsp;</td>
    <td class="phonebill-total">4 Calls</td>
    <td class="phonebill-total">&nbsp;</td>
    <td class="phonebill-total">Total for Extension</td>
    <td class="phonebill-total">£21.37</td>
  </tr>
  <tr>
    <td colspan="5" class="row-spacer">&nbsp;</td>
    </tr>
  <tr>
    <td>&nbsp;</td>
    <td class="phonebill-total">&nbsp;</td>
    <td class="phonebill-total">SUBTOTAL</td>
    <td class="phonebill-total"></td>
    <td class="phonebill-total">£21.37</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td class="phonebill-total">&nbsp;</td>
    <td class="phonebill-total">VAT</td>
    <td class="phonebill-total">20%</td>
    <td class="phonebill-total">£4.28</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td class="phonebill-total">&nbsp;</td>
    <td class="phonebill-total">TOTAL</td>
    <td class="phonebill-total"></td>
    <td class="phonebill-total">£25.65</td>
  </tr>
</table>

		</td>
	</tr>
    </table>
    <p>&nbsp;</p>

<?php
include('footer.php');
?>
