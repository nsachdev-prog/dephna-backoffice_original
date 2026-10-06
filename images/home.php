<?php
$page = "Maintenance";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
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

#home .heading{
	font-family:arial,verdana,helvetica,sans-serif;
	font-size:16px;
	letter-spacing:.1em;
	color:#666;
}

#home .cal-title{
	font-family:arial,verdana,helvetica,sans-serif;
	font-size:11px;
	color:#666;
}

#home .cal-subtitle{
	font-family:arial,verdana,helvetica,sans-serif;
	font-size:10px;
	color:#999;
}

#home .cal-style{
	border-top:1px dashed #999;
	padding-top:8px;
	padding-bottom:8px;
}
	
.board-title{
	font-weight:bold;
	margin-bottom:4px;
}

.board-text{
	font-size:11px;
}

</style>


	<form name="addform" method="post" action="maintenance-addedit.php">
	<table width="740" border="0" align="center" cellpadding="0" cellspacing="5" id="home">
  <tr>
    <td width="36%" valign="top"><div class="heading"><span class="date-number">16th</span> April 2013</div></td>
    <td rowspan="2"><img src="images/spacer.gif" width="25" height="1" /></td>
    <td width="28%" valign="top"><div class="heading">MAINTENANCE</div></td>
    <td rowspan="2"><img src="images/spacer.gif" width="25" height="1" /></td>
    <td width="36%" valign="top"><div class="heading">NOTICEBOARD</div></td>
  </tr>
  <tr>
    <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="4">
      <tr>
        <td width="25%" valign="top" class="cal-style">09.30</td>
        <td width="75%" valign="top" class="cal-style"><div class="cal-title">Meeting Joe Bloggs about drainage</div><div class="cal-subtitle">Rajive Sachdev</div></td>
        </tr>
      <tr>
        <td width="25%" valign="top" class="cal-style">10.00</td>
        <td width="75%" valign="top" class="cal-style"><div class="cal-title">Hourly Kitchen (4hrs)</div>
          <div class="cal-subtitle">ABC Limited</div></td>
        </tr>
      <tr>
        <td width="25%" valign="top" class="cal-style">12.30</td>
        <td width="75%" valign="top" class="cal-style"><div class="cal-title">New client: XYZ Media Limited</div>
          <div class="cal-subtitle">Alka Jotangia</div></td>
        </tr>
      <tr>
        <td width="25%" valign="top" class="cal-style">12.30</td>
        <td width="75%" valign="top" class="cal-style"><div class="cal-title">On Holiday</div>
          <div class="cal-subtitle">Lara Croft</div></td>
        </tr>
      <tr>
        <td width="25%" valign="top" class="cal-style">All Day</td>
        <td width="75%" valign="top" class="cal-style"><div class="cal-title">On Holiday</div>
          <div class="cal-subtitle">Jim Beam, Billy Piper, Jeff Daniels</div></td>
        </tr>
      <tr>
        <td valign="top" class="cal-style" colspan="2">&nbsp;</td>
        </tr>
      </table></td>
    <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="4">
      <tr>
        <td valign="top" class="cal-style">&nbsp;</td>
        <td align="center" valign="middle" class="cal-style"><span class="cal-title">High</span></td>
        <td align="center" valign="middle" class="cal-style"><span class="cal-title">Med</span></td>
        <td align="center" valign="middle" class="cal-style"><span class="cal-title">Low</span></td>
        </tr>
      <tr>
        <td width="40%" valign="top" class="cal-style"><span class="cal-title">Acton Lane</span></td>
        <td width="15%" align="center" valign="top" class="cal-style"><span class="cal-title">5</span></td>
        <td width="15%" align="center" valign="top" class="cal-style"><span class="cal-title">2</span></td>
        <td width="15%" align="center" valign="top" class="cal-style"><span class="cal-title">4</span></td>
        </tr>
      <tr>
        <td valign="top" class="cal-style"><span class="cal-title">Coronation</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">2</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">1</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">5</span></td>
        </tr>
      <tr>
        <td valign="top" class="cal-style"><span class="cal-title">Cumberland</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">6</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">2</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">4</span></td>
        </tr>
      <tr>
        <td valign="top" class="cal-style"><span class="cal-title">Finchley</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">0</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">2</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">2</span></td>
        </tr>
      <tr>
        <td valign="top" class="cal-style"><span class="cal-title">Neasden</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">7</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">0</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">6</span></td>
        </tr>
      <tr>
        <td valign="top" class="cal-style"><span class="cal-title">North Acton</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">4</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">0</span></td>
        <td align="center" valign="top" class="cal-style"><span class="cal-title">3</span></td>
        </tr>
      <tr>
        <td valign="top" class="cal-style" colspan="4">&nbsp;</td>
        </tr>
      </table></td>
    <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="4">
      <tr>
        <td valign="top" class="cal-style"><div class="cal-title">TAPS LEFT RUNNING</div><div class="cal-subtitle">Dear all, please make sure that all taps are turned off properly before you leave the kitchen.</div></td>
        </tr>
      <tr>
        <td width="40%" valign="top" class="cal-style"><div class="cal-title">HAPPY BIRTHDAY, JOE BLOGGS</div>
          <div class="cal-subtitle">Joe's birthday today so be sure to give him a great big hug!.</div></td>
        </tr>
      <tr>
        <td valign="top" class="cal-style"><div class="cal-title">LET'S GET BUSY!!!</div>
          <div class="cal-subtitle">Dear all, you're slacking. Get back to work!.</div></td>
        </tr>
      <tr>
        <td valign="top" class="cal-style">&nbsp;</td>
        </tr>
      </table></td>
  </tr>
  </table>
    </form>

    
    
    <?php
print "</td>";
include('footer.php');
?>
