<?php
	include('settings.php');
	include('session.php');
	include('elements/header.php');
?>
		   <div class="row text-center main-title">
			  <div class="col-lg-6 offset-lg-3 mt-1">
				   <h1 class="page-header__title">Client Area</h1>
				   <h2 class="page-header__subtitle">Contact Dephna</h2>
			   </div>
		   </div>
		   <div class="row text-center">
			  <div class="col-lg-10 offset-lg-1">
				  
<?php
	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		?>
			<section class="voffice-blurb text-blurb container">
				<article class="page type-page">
					
	
<style>
	.contactsItem{
		display:inline-block;width:40%;margin:1%; text-align:left;font-size:1.3em;
	}
	.contactsItem.title{
		font-weight:bold; text-align:right;
	}
	
	@media screen and (max-width:425px) {
		.contactsItem{
			display:block;width:90%;margin:0 5%; text-align:center;
		}
		.contactsItem.title{
			text-align:center;padding-top:7%;
		}
	}
	
</style>
	
	<?php	
	print "			<div style=\"text-align:center;width:100%;\">";
	print "				<div class=\"contactsItem title\">Support:</div>\n";
	print "				<div class=\"contactsItem\"><a href=\"mailto:support@dephna.com?subject=Support Query from {$_SESSION['compname']}\">support@dephna.com</a></div>\n";
	print "				<div class=\"contactsItem title\">Sales:</div>\n";
	print "				<div class=\"contactsItem\"><a href=\"mailto:sales@dephna.com?subject=Sales Query from {$_SESSION['compname']}\">sales@dephna.com</a></div>\n";
	print "				<div class=\"contactsItem title\">Accounts:</div>\n";
	print "				<div class=\"contactsItem\"><a href=\"mailto:accounts@dephna.com?subject=Accounts Query from {$_SESSION['compname']}\">accounts@dephna.com</a></div>\n";
	print "				<div class=\"contactsItem title\">IT:</div>\n";
	print "				<div class=\"contactsItem\"><a href=\"mailto:it@dephna.com?subject=IT Query from {$_SESSION['compname']}\">it@dephna.com</a></div>\n";
	print "			</div>\n";
			
	print "		</article>\n";
	print "	</section>\n";
		
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";
?>
			</div>
	</div>
<?php			
	include('elements/footer.php');
?>

