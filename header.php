<!DOCTYPE html>
<!--
***********************************************
******** Backoffice CM3.1 Framework ***********
***********************************************
*  Written By: David Sherwood                 *
*              david@davidsherwood.co.uk      *
* Licensed To: Dephna Estates Ltd.            *
*        Term: Lifetime                       *
***********************************************
**** Do not remove this license statement  ****
***********************************************
-->
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>Dephna - Backoffice<?php if ($page) { print " - $page"; } if ($subpage) { print " - $subpage"; } ?></title>
		<link href="style.css" rel="stylesheet" type="text/css" />
		<link href="styles/jquery.datetimepicker.css" rel="stylesheet">
		<link href="styles/jquery.timepicker.min.css" rel="stylesheet">
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
		<script type="text/javascript" src="//code.jquery.com/jquery-1.10.2.min.js"></script>
		<script type="text/javascript" src="//code.jquery.com/ui/1.10.3/jquery-ui.min.js"></script>
		<!-- <script type="text/javascript" src="jscripts/jquery-ui-timepicker-addon.js"></script> -->
		<script type="text/javascript" src="jscripts/jquery-ui-sliderAccess.js"></script>
		<script type="text/javascript" src="jscripts/jquery.datetimepicker.full.min.js"></script>
		<script type="text/javascript" src="jscripts/jquery.timepicker.min.js"></script>


		<?php
		if ($charts) {
			print "		<script type=\"text/javascript\" src=\"//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js\"></script>\n";
		}
		
		if ($_SESSION['user']) {
		print "
		<script>
			$(function () {
				$('body').on('click keypress', function () {
					ResetThisSession();
				});
			});
			var timeout = 600;
			var secondTick = 0;
			function ResetThisSession() {
				secondTick = 0;
			}
			function StartThisSessionTimer() {
				secondTick++;
				var secsLeft = timeout - secondTick;
				var secs = Math.floor(secsLeft % 60);
        		var mins = Math.floor(secsLeft / 60 % 60);
				if (secondTick > timeout) {
					clearTimeout(tick);
					window.location = 'index.php?logout=1';
					return;
				}
				$('#spanTimeLeft').html(('00' + mins).slice(-2)+':'+('00' + secs).slice(-2));
				tick = setTimeout(\"StartThisSessionTimer()\", 1000);
			}
			StartThisSessionTimer();
		</script>
	";
		}

		if ($clockpicker) {
			print "
			<link href=\"clockpicker/jquery-clockpicker.min.css\" rel=\"stylesheet\" type=\"text/css\" />
			<script type=\"text/javascript\" src=\"clockpicker/jquery-clockpicker.min.js\"></script>
			";
		}

		if ($loadcal) {
			print "
			<link href=\"kcal-style.css\" rel=\"stylesheet\" type=\"text/css\" />
			<script type=\"text/javascript\">
				function loadContent(s,p) {
					$('.cal-inner').replaceWith(\"<div class='cal-inner'></div>\");
					$(s).load(p);
				}
			</script>
			";
		}

		if ($stlloadcal) {
			print "
			<link href=\"stl-cal-style.css\" rel=\"stylesheet\" type=\"text/css\" />
			<script type=\"text/javascript\">
				function loadContent(s,p) {
					$('.cal-inner').replaceWith(\"<div class='cal-inner'></div>\");
					$(s).load(p);
				}
			</script>
			";
	}

		if ($colourpicker) {
			print "
			<script src='../Scripts/spectrum.js'></script>
			<link rel='stylesheet' href='../css/spectrum.css' />
			";
		}

		if ($_SESSION['return_url']) { $return_url = $_SESSION['return_url']; }
		$_SESSION['return_url'] = str_replace("/backoffice/","",$_SERVER['REQUEST_URI']);
		?>

<script type="text/javascript">
function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}
</script>

<!-- Digi Clock -->
<script>
function startTime()
{
var today=new Date();
var h=today.getHours();
var m=today.getMinutes();
var s=today.getSeconds();
// add a zero in front of numbers<10
h=checkTime(h);
m=checkTime(m);
s=checkTime(s);
document.getElementById('digiclock').innerHTML=h+":"+m;
t=setTimeout(function(){startTime()},500);
}

function checkTime(i)
{
if (i<10)
  {
  i="0" + i;
  }
return i;
}
</script>

<!-- tooltip -->
<link rel="stylesheet" href="//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script>
$(function() {
$( document ).tooltip();
});
</script>

<style>
label {
display: inline-block;
width: 5em;
}
</style>
	</head>
	<body onload="startTime()">

		<table align="center" border="0" cellspacing="0" cellpadding="0" class="table-style">
			<tr>
				<td><div id="head-image"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left" background="images/logo-header-mid.jpg" width="225"><img src="images/logo-header-left.png" width="175" height="116" alt="Dephna Group" /></td>
    <td align="center" background="images/logo-header-mid.jpg"><div id="digiclock"></div>
		<?php $tyear  = date("Y");
		$tmonth = date("F");
		$tdate   = date("d");
		$tday   = date("l");
		$todayDate = "<div id=\"t-date\">$tday $tdate  $tmonth  $tyear</div>";
	print "	<div align=\"center\">$todayDate</div>"; ?></td>
    <td align="right" background="images/logo-header-mid.jpg" width="225"><img src="images/logo-header-right.jpg" width="198" height="120" alt="Back Office (Camilla)" /></td>
  </tr>
</table></div><div id="head-image-PRINT"><img src="images/logo-header-PRINT.jpg" width="350" height="120" alt="Dephna Group" /></div></td>
			</tr>
		</table>

		<table align="center" border="0" cellspacing="0" cellpadding="0" class="table-style">
			<tr>
				<td class="spacer1"></td>
			</tr>
			<tr>
			<?php
			if ($_SESSION['user']) {
				if ($_SESSION['testmode']) { $gradstyle = "grad-test"; } else { $gradstyle = "grad"; }
				print "			<td class=\"$gradstyle\">Logged in as {$_SESSION['username']} <span id=\"spanTimeLeft\"></span>";
				if ($_SESSION['user'] == 1 OR
					$_SESSION['user'] == 5 OR
					$_SESSION['user'] == 6 OR
					$_SESSION['user'] == 123 OR
					$_SESSION['user'] == 166 OR
					$_SESSION['user'] == 25) {
					foreach ($SYScompanies AS $key => $value) {
						if ($_SESSION['database'] == $key) {
							print "&nbsp;|&nbsp;<span style=\"color:#fff\">$value</span>";
						} else {
							print "&nbsp;|&nbsp;<span class=\"gradlink\"><a href=\"index.php?switchdb=$key\">$value</a></span>";
						}
					}
				}

				print "</td>\n";
			}
			else {
				print "			<td class=\"grad\">Not logged in</td>\n";
			}
			?>
			</tr>
		</table>

		<table height="400px" align="center" border="0" cellspacing="0" cellpadding="0" class="table-style">
			<tr>
			<?php
			if (($_SESSION['user']) AND (!$passwordExpired)) {
				include("menu.php");
			}

