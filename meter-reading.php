<?php
$page = "Payroll";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
	print "<p class=\"heading\" align=\"center\">METER READER</p>\n";
	print "<p align=\"center\"><b>Cumberland Avenue</b></p>\n";
	?>
<style type="text/css">

.meter-unit{
	width:100%;
	border:1px solid #666666;
	background-color:#ffffff;
	font-family:arial,verdana,helvetica,sans-serif;
	font-size:24px !important;
}

.meter-unit-bt{
	width:120px;
	border:1px solid #666666;
	background-color:#ccc;
	font-family:arial,verdana,helvetica,sans-serif;
	font-size:24px !important;
	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;
	border-radius:5px;
	cursor:pointer;
}

.meter-read{
	width:100%;
	border:1px solid #666666;
	background-color:#ffffff;
	font-family:arial,verdana,helvetica,sans-serif;
	font-size:50px !important;
	line-height:50px;
	padding:10px 0 !important;
}

.meter-go-bt{
	width:160px;
	border:1px solid #666666;
	background-color:#ccc;
	font-family:arial,verdana,helvetica,sans-serif;
	font-size:35px !important;
	font-weight:bold;
	padding:10px 0 !important;
	line-height:40px;
	-webkit-border-radius: 10px;
	-moz-border-radius: 10px;
	border-radius:10px;
	cursor:pointer;
}

.meter-unit-bt:hover, .meter-go-bt:hover{
	background-color:#333;
	color:#fff;
}


</style>

    
    
    <table width="500" border="0" align="center" cellpadding="0" cellspacing="30">
  <tr>
    <td colspan="3"><select name="unitname" class="meter-unit">
      <option value="Cmb101">Cmb 101</option>
      <option value="Cmb102">Cmb 102</option>
      <option value="Cmb103">Cmb 103</option>
    </select></td>
    <td width="0" align="right"><input name="" type="button" value="UNIT" class="meter-unit-bt" /></td>
  </tr>
  <tr>
    <td colspan="4"><input name="" type="text" class="meter-read" /></td>
  </tr>
  <tr>
    <td colspan="2" align="left"><input name="back" type="button" value="BACK" class="meter-go-bt" /></td>
    <td colspan="2" align="right"><input name="back" type="button" value="NEXT" class="meter-go-bt" /></td>
  </tr>
</table>

    
    
    <?php
print "</td>";
include('footer.php');
?>
