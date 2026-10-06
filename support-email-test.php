<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Support";
$subpage = "Email";
include('settings.php');
include('session.php');
include('header.php');

print "<td class=\"main\" valign=\"top\">\n";

if ($_POST['body']) {
	print "<pre>" . print_r($_POST,TRUE) . "</pre>";
	print "<pre>" . print_r($_FILES['atts'],TRUE) . "</pre>";
} else {

?>

    <script type="text/javascript" src="jscripts/multiple-emails/multiple-emails.js"></script>
	<link type="text/css" rel="stylesheet" href="jscripts/multiple-emails/multiple-emails.css">
	<script type="text/javascript" src="//js.nicedit.com/nicEdit-latest.js"></script> 
	<link rel="stylesheet" href="jscripts/formHelper/dist/css/jquery-formhelper.min.css" />
	<script type="text/javascript" src="jscripts/js.multifileselector.js"></script>

	<script type="text/javascript">
		bkLib.onDomLoaded(function() {
		  new nicEditor({buttonList : ['fontSize','fontFamily','fontFormat','bold','italic','underline','left','center','right','justify','ol','ul','subscript','superscript','strikethrough','removeformat','indent','outdent','hr','forecolor','bgcolor']}).panelInstance('area1');
		});
	</script>

	<script type="text/javascript">
		$(function() {
			$('#email-to').multiple_emails({theme: "Basic", position: 'bottom'});
		});
		$(function() {
			$('#email-cc').multiple_emails({theme: "Basic", position: 'bottom'});
		});
		$(function() {
			$('#email-bcc').multiple_emails({theme: "Basic", position: 'bottom'});
		});
	</script>

	<script>
		$(function() {
			$('.file_input').multifileselector({
				container: "#upload-container",
				max_file_size : 1024,
				max_files: 3,
				template: function (file) {
					var fileName = file.name;
					var fileExtension = file.name.split('.').pop();
				}
			});
		});
    </script>

	<style>
		input[type='file'] {
			color: transparent;
		}
	</style>

	<p class="heading" align="center">SUPPORT</p>
    <p class="larger" align="center">Reply All</p>
	<form enctype="multipart/form-data" method="post" action="support-email-test.php">
	<table width="98%" align="center" cellpadding="4">
		<tr>
			<td valign="top" width="30%"><b>To</b><br>
			<input type='text' id='email-to' name='email-to' class='form-control' value='["david@davidsherwood.co.uk","rajive@dephna.com"]'><br>
			</td>
			<td valign="top" width="30%"><b>Cc</b><br>
			<input type='text' id='email-cc' name='email-cc' class='form-control' value='["phooisee@dephna.com"]'><br>
			</td>
			<td valign="top" width="30%"><b>Bcc</b><br>
			<input type='text' id='email-bcc' name='email-bcc' class='form-control' value=''><br>
			</td>
		</tr>
		<tr>
			<td colspan="3" width="100%"><textarea id="area1" rows="10" name="body" style="width:100%">This can...<br>Pre-populate email addresses and html templates<br>Add and remove email addresses<br>Edit and format rich text.<br><br>Highlight text then click a formatting button.</textarea></td>
		</tr>
		<tr>
			<td><b>Attachments</b><input class="file_input" type="file" name="atts[]"><br><div id="upload-container" style="list-style-type: none"></div></td>
		</tr>
	</table>
	<p align="center"><input name="Submit" type="submit" class="button" value="Submit"></p>
	</form>

<?php

}

print "</td>\n";
include('footer.php');
?>
