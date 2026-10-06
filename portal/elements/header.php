<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Area - Dephna</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap-4.3.1.css" rel="stylesheet">
	  
	  
    <link href="css/screen.css?v=?v=<?php echo md5(time()); ?>" rel="stylesheet">
	  
	  
<?php
if ($anytime) {
	print "
	<link rel=\"stylesheet\" type=\"text/css\" href=\"anytime.css\" />
	";
}
if ($jquery) {
	?>
	<link href="//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" rel="stylesheet">
	<link href="css/jquery.datetimepicker.css" rel="stylesheet">
	<script type="text/javascript" src="//code.jquery.com/jquery-latest.min.js"></script>
	<script type="text/javascript" src="//code.jquery.com/ui/1.10.3/jquery-ui.min.js"></script>
	<script type="text/javascript" src="jscripts/jquery.datetimepicker.full.min.js"></script>
	<?php
}
if ($charts) {
	print "		<script type=\"text/javascript\" src=\"//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js\"></script>\n";
}
if ($anytime) {
	print "
	<script type=\"text/javascript\" src=\"jscripts/jquery-1.4.2.min.js\"></script>
	<script type=\"text/javascript\" src=\"jscripts/anytime.js\"></script>
	<script type=\"text/javascript\">
	$(document).ready(function() {
	  $(\"#dateinput\").AnyTime_picker(
	      { format: \"%M %D %Y\",
	        hideInput: false,
	      } );
	});
	</script>
	";
}
if ($wysiwyg) { print "
<!-- Load TinyMCE -->
<script type=\"text/javascript\" src=\"jscripts/tiny_mce/jquery.tinymce.js\"></script>
<script type=\"text/javascript\">
	$().ready(function() {
		$('textarea.tinymce').tinymce({
			// Location of TinyMCE script
			script_url : 'jscripts/tiny_mce/tiny_mce.js',

			// General options
			theme : \"advanced\",
			plugins : \"pagebreak,table,advhr,advlink,emotions,inlinepopups,insertdatetime,preview,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist\",

			// Theme options
			theme_advanced_buttons1 : \"bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontselect,fontsizeselect\",
			theme_advanced_buttons2 : \"cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor\",
			theme_advanced_buttons3 : \"tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,advhr,|,print,|,ltr,rtl,|,fullscreen\",
			theme_advanced_buttons4 : \"styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak\",
			theme_advanced_toolbar_location : \"top\",
			theme_advanced_toolbar_align : \"left\",
			theme_advanced_statusbar_location : \"bottom\",
			theme_advanced_resizing : true
		});
	});
</script>\n"; }
?>
<script type="text/javascript">

function toggleNext2(el,tname,first) {
	var next=el.nextSibling;
	var tags=el.parentNode.getElementsByTagName(tname);
	while(next.nodeType != 1) next = next.nextSibling;
	next.style.display=((next.style.display=="none") ? "block" : "none");
	if (first!=1){
		for (i=0; i<tags.length; i++) {
			var tohide=tags[i].nextSibling;
			while(tohide.nodeType != 1) tohide = tohide.nextSibling;
			if (tohide!=next){tohide.style.display="none";}
		}
	}
}

function toggleNextByIdAndTag2(el,tname) {
	var ccn="clicker";
	clickers=document.getElementById(el).getElementsByTagName(tname);
	for (i=0; i<clickers.length; i++) {
		clickers[i].className+=" "+ccn;
		clickers[i].onclick=function() {toggleNext2(this,tname)}
		toggleNext2(clickers[i],tname,1);
	}
}

window.onload=function(){toggleNextByIdAndTag2('dlist','dt')}
</script>

<SCRIPT LANGUAGE="JavaScript">
<!-- Hide
function listredirect(newlocation) {
	if (newlocation.indexOf("no") != "0") {
			window.location.href="" + newlocation + ".php";
	}
}
// Hide, end -->
</SCRIPT>

  </head>
  <body>
	  <div  class="grey-circle-bg">
		<div class="container">
		   <div class="row text-center">
			   <div class="col-lg-1">
				  	<a href="//www.dephna.com" target"_blank"><svg class="logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 68">
						<title>Dephna logo</title>
						<g fill="none" fill-rule="evenodd">
							<g class="logo__mark">
								<path d="M61.4 11.6h-5.1a6.2 6.2 0 00-1.9-4.4A6.3 6.3 0 0047.5 6a6.3 6.3 0 00-3.9 5.7h-5a11.1 11.1 0 013.3-8 11.4 11.4 0 018-3.3 11.5 11.5 0 0110.6 7c.6 1.3.9 2.8.9 4.3"></path>
								<path d="M50 33a21.8 21.8 0 01-20-13 21 21 0 01-1.7-8.4h5.1a16 16 0 004.9 11.6 16.5 16.5 0 0018.1 3.5 16.5 16.5 0 0010.1-15h5.1a21 21 0 01-6.3 15A21.6 21.6 0 0150 33"></path>
							</g>
							<path class="logo__text" d="M37.6 51h-4.9v16.2h2.7V53.5h2.1c2 0 3.1 1 3.1 2.7 0 1.5-1 2.5-2.7 2.7v2.4c3.2 0 5.5-2.2 5.5-5.1 0-3-2.4-5.2-5.8-5.2M18.5 51.1v16.3h9.6v-2.6h-6.9v-4.4h5.6v-2.5h-5.6v-4.2h6.9v-2.6zM5.3 51.1H0v16.3h2.7V53.6h2.6c3.6 0 6 2.4 6 5.7 0 3.2-2.4 5.6-6 5.6v2.5c5 0 8.8-3.5 8.8-8.1 0-4.7-3.8-8.2-8.8-8.2M65.9 67.2h2.7V57.1L80 67.4V51.3h-2.7v10.2L65.9 51.1zM83.7 67.4h2.9l1.3-2.7.2-.4.3-.5 1.1-2.4 2.4-4.8 2.3 4.8h-1.9l-1.2 2.4h4.2l1.8 3.6h2.9l-8.1-16.3zM58.1 51.1v6.8h-8v-6.8h-2.7v16.3h2.7v-7h8v7h2.6V51.1z"></path>
						</g>
					</svg></a>
			   </div>
			   <div class="col-lg-8 offset-lg-3">
					<ul class="navbar-nav ml-auto">
						<li class="nav-item">
						<a class="nav-link" href="index.php">Main Menu</a>
						</li>
						<li class="nav-item not-mob">
						<a class="nav-link" href="//www.dephna.com/">Main Site</a>
						</li>
						<li class="nav-item">
						<a class="nav-link" href="#020 3820 0848">0208 896 7575</a>
						</li>
						<li class="nav-item">
						   <?php if ($_SESSION['level']) {print "<a href=\"index.php?logout=1\" class=\"nav-link\">Logout</a>\n"; } ?>
						</li>		
					</ul>
			   </div>
			</div>