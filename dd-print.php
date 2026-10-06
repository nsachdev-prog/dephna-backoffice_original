<?php
$page = "Customers";
include('settings.php');
include('session.php');
include('header-print.php');
$id = $_REQUEST['id'];
if ($_SESSION['user']) {
	if ($id) {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# Some fields come from the settings file
			# $compName
			# $compAddr
			# $compDDid

			# get customer fields
			$sql = "SELECT * FROM users WHERE ID = '$id'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$bnkAccName = $row['bnkAccName'];
				$bnkSort    = $row['bnkSort'];
				$bnkAccNum  = $row['bnkAccNum'];
				$custref    = $row['custref'];

				# check we have all fields
				if (!$compName)   { $errst .= "Your company name is missing<br />"; $e++; }
				if (!$compAddr)   { $errst .= "Your company address is missing<br />"; $e++; }
				if (!$compDDid)   { $errst .= "Your company Originator ID is missing<br />"; $e++; }
				if (!$bnkAccName) { $errst .= "Customer bank account name is missing<br />"; $e++; }
				if (!$bnkSort)    { $errst .= "Customer bank sort code is missing<br />"; $e++; }
				if (!$bnkAccNum)  { $errst .= "Customer bank account number is missing<br />"; $e++; }

				if ($e) {
					# if any fields are missing report error
					$resultMessage = "<b>Please fix the following errors</b><br /><br />" . $errst;
				} else {
					# print the DD form
					?>

<div id="container-dd">
  <div id="midBlock-dd">
        	<div id="header-left-dd"><br />
        	  <img src="images/dephna-logo.jpg" width="150" height="73" />
</div>
            <div id="header-right-dd"><img src="images/DirectDebit-Form-DD-Logo.gif" width="122" height="47" /><br />
Instruction to your<br />
Bank or Building Society<br />
to pay by Direct Debit
</div>
            <div id="left-block-dd">
            	<div class="title-text-dd">Please fill in the whole form using a ball point pen and send it to:</div><br />
				<div id="dephna-address-dd"><div class="dd-form-inner">Dephna Estates Ltd<br />
2 Portal Way<br />
London<br />
W3 6RT</div>
                </div>
				<div class="title-text-dd">Name(s) of Account Holder(s)</div><br />
				<div id="account-holder-name1-dd"><div class="dd-form-inner-small"><?php print $bnkAccName ?></div>
                </div>
				<div id="account-holder-name2-dd"><div class="dd-form-inner-small"></div>
                </div>
				<div class="title-text-dd">Bank/Building Society account number</div><br />
				<div id="account-number-dd"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td><?php print $bnkAccNum[0]; ?></td>
    <td><?php print $bnkAccNum[1]; ?></td>
    <td><?php print $bnkAccNum[2]; ?></td>
    <td><?php print $bnkAccNum[3]; ?></td>
    <td><?php print $bnkAccNum[4]; ?></td>
    <td><?php print $bnkAccNum[5]; ?></td>
    <td><?php print $bnkAccNum[6]; ?></td>
    <td><?php print $bnkAccNum[7]; ?></td>
  </tr>
</table></div>
				<div class="title-text-dd">Branch Sort Code</div><br />
				<div id="sort-code-dd"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td><?php print $bnkSort[0]; ?></td>
    <td><?php print $bnkSort[1]; ?></td>
    <td><?php print $bnkSort[2]; ?></td>
    <td><?php print $bnkSort[3]; ?></td>
    <td><?php print $bnkSort[4]; ?></td>
    <td><?php print $bnkSort[5]; ?></td>
  </tr>
</table></div>
				<div class="title-text-dd">Name and full postal address of your Bank or Building Society</div><br />
				<div id="account-holder-name1a-dd"><div class="dd-form-inner-small"></div>
                </div>
				<div id="account-holder-name1-dd"><div class="dd-form-inner-small"></div>
                </div>
				<div id="account-holder-name1-dd"><div class="dd-form-inner-small"></div>
                </div>
				<div id="account-holder-name2-dd"><div class="dd-form-inner-small"></div>
                </div>
            </div>
            <div id="right-block-dd">
				<div class="title-text-dd">Originators Identification Number</div><br />
				<div id="sort-code-dd"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>4</td>
    <td>2</td>
    <td>0</td>
    <td>8</td>
    <td>6</td>
    <td>8</td>
  </tr>
</table></div>
				<div class="title-text-dd">Reference</div><br />
				<div id="account-number-dd"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>D</td>
    <td>P</td>
    <td><?php print $custref[0]; ?></td>
    <td><?php print $custref[1]; ?></td>
    <td><?php print $custref[2]; ?></td>
    <td><?php print $custref[3]; ?></td>
    <td><?php print $custref[4]; ?></td>
    <td><?php print $custref[5]; ?></td>
    <td><?php print $custref[6]; ?></td>
    <td><?php print $custref[7]; ?></td>
    <td><?php print $custref[8]; ?></td>
    <td><?php print $custref[9]; ?></td>
    <td><?php print $custref[10]; ?></td>
    <td><?php print $custref[11]; ?></td>
    <td><?php print $custref[12]; ?></td>
  </tr>
</table></div><div class="small-print"><strong>Instruction to your Bank or Building Society</strong><br />
Please pay Dephna Estates Ltd Direct Debits from the account detailed in
this Instruction subject to the safeguards assured by the Direct Debit
Guarantee. I understand that this instruction may remain with
Dephna Estates Ltd and, if so, details will be passed electronically to my
Bank/Building Society.</div>
				<div class="title-text-dd">Signature(s)</div><br />
				<div id="account-holder-name1-dd">
                </div>
				<div id="account-holder-name1-dd">
                </div>
				<div id="account-holder-name2-dd"><span class="mini-font">Date</span>
                </div>
            </div>
	</div>
        </div>
      <div id="bottom-text-dd">Banks and Building Societies may not accept Direct Debit Instructions for some types of account
    </div>
        <div id="footerBlock-dd"><img src="images/Dephna-DirectDebit-bottom.gif" width="720" height="353" />
        </div>
        </div>
					<?php
				}
			} else {
				$resultMessage = "<b>Could not find customer</b><br />" . $dbcnx->error;
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Page called without customer id</b>";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
include('footer-print.php');
?>
