<?php
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dephna Group Limited');
$pdf->SetTitle('Direct Debit Form');
$pdf->SetSubject('Direct Debit Form');
$pdf->SetKeywords('Dephna, Direct Debit');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetLeftMargin(6);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->setFontSubsetting(true);
$pdf->AddPage();
$pdf->setJPEGQuality(75);
$pdf->SetFont('Times', '', 9);
$tbl .= "<body>\n";
$tbl .= <<<EOD
<table cellspacing="0" cellpadding="10" border="0">
    <tr>
		<td align="center" valign="center" rowspan="2"><img src="images/dephna-logo.jpg" width="150" height="73" border="0" />
		</td>
		<td align="center"><img src="images/DirectDebit-Form-DD-Logo.gif" width="122" height="47" border="0" />
		</td>
	</tr>
    <tr>
		<td align="left" style="font-family:serif;">Instruction to your<br />
Bank or Building Society<br />
to pay by Direct Debit</td>
	</tr>
</table>
EOD;
$pdf->writeHTML($tbl, true, false, true, false, '');

$tbl = <<<EOD
<table cellspacing="0" cellpadding="3" border="0" align="center" width="720">
    <tr>
		<td align="left">

			<table cellspacing="0" cellpadding="4" border="0" align="center" width="320">
				<tr>
					<td align="left">Please fill in the whole form using a ball point pen and send it to:</td>
				</tr>
				<tr>
        			<td align="left">
        				<table cellspacing="0" cellpadding="3" border="0" align="center" width="280">
							<tr>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="50">Dephna Group Ltd<br />
									2 Portal Way<br />
									London<br />
									W3 6RT
								</td>
							</tr>
						</table><br />
					</td>
    			</tr>
				<tr>
					<td align="left">Name(s) of Account Holder(s)</td>
				</tr>
				<tr>
        			<td align="left">
        				<table cellspacing="0" cellpadding="3" border="0" align="center" width="280">
							<tr>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="20">$bnkAccName</td>
							</tr>
							<tr>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="20">&nbsp;</td>
							</tr>
						</table><br />
					</td>
    			</tr>
				<tr>
					<td align="left">Bank/Building Society account number</td>
				</tr>
				<tr>
        			<td align="left">
        				<table cellspacing="0" cellpadding="3" border="0" align="center" width="280">
							<tr>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="20">$bnkAccNum[0]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$bnkAccNum[1]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$bnkAccNum[2]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$bnkAccNum[3]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$bnkAccNum[4]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$bnkAccNum[5]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$bnkAccNum[6]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$bnkAccNum[7]</td>
							</tr>
						</table><br />
					</td>
    			</tr>
				<tr>
					<td align="left">Branch Sort Code</td>
				</tr>
				<tr>
        			<td align="left">
        				<table cellspacing="0" cellpadding="3" border="0" align="center" width="280">
							<tr>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="20">$bnkSort[0]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$bnkSort[1]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$bnkSort[2]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$bnkSort[3]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$bnkSort[4]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$bnkSort[5]</td>
								<td style="background-color:#fff;font-family:Helvetica;" align="left">&nbsp;</td>
								<td style="background-color:#fff;font-family:Helvetica;" align="left">&nbsp;</td>
							</tr>
						</table><br />
					</td>
    			</tr>
				<tr>
					<td align="left">Name and full postal address of your Bank or Building Society</td>
				</tr>
				<tr>
        			<td align="left">
        				<table cellspacing="0" cellpadding="3" border="0" align="center" width="280">
							<tr><td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="20">&nbsp;</td>
							</tr>
							<tr><td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="20">&nbsp;</td>
							</tr>
							<tr><td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="20">&nbsp;</td>
							</tr>
							<tr><td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="20">&nbsp;</td>
							</tr>
						</table><br />
					</td>
    			</tr>
			</table>
		</td>
		<td>

			<table cellspacing="0" cellpadding="4" border="0" align="center" width="360">
				<tr>
					<td align="left"><br />Originators Identification Number</td>
				</tr>
				<tr>
        			<td align="left">
        				<table cellspacing="0" cellpadding="3" border="0" align="center" width="280">
							<tr>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="20">4</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">2</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">0</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">8</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">6</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">8</td>
								<td style="background-color:#fff;font-family:Helvetica;" align="left">&nbsp;</td>
								<td style="background-color:#fff;font-family:Helvetica;" align="left">&nbsp;</td>
							</tr>
						</table><br />
					</td>
    			</tr>
				<tr>
					<td align="left">Reference</td>
				</tr>
				<tr>
        			<td align="left">
        				<table cellspacing="0" cellpadding="3" border="0" align="center" width="320">
							<tr>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="20">D</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">P</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$custref[0]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$custref[1]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$custref[2]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$custref[3]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$custref[4]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$custref[5]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$custref[6]</td>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left">$custref[7]</td>
							</tr>
						</table><br />
					</td>
    			</tr>
				<tr>
					<td align="left" style="font-size:7pt;"><strong>Instruction to your Bank or Building Society</strong><br />
						Please pay Dephna Group Ltd Direct Debits from the account detailed<br />
						in this Instruction subject to the safeguards assured by the Direct<br />
						Debit Guarantee. I understand that this instruction may remain with<br />
						Dephna Group Ltd and, if so, details will be passed electronically to<br />
						my Bank/Building Society.<br />
					</td>
				</tr>
				<tr>
					<td align="left">Signature(s)</td>
				</tr>
				<tr>
        			<td align="left">
        				<table cellspacing="0" cellpadding="3" border="0" align="center" width="280">
							<tr>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="70">&nbsp;</td>
							</tr>
							<tr>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;" align="left" height="70">&nbsp;</td>
							</tr>
							<tr>
								<td style="border:1px solid #000;background-color:#fff;font-family:Helvetica;font-size:7pt;" align="left" height="20">Date</td>
							</tr>
						</table><br />
					</td>
    			</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align="center" colspan="2">Banks and Building Societies may not accept Direct Debit Instructions for some types of account</td>
	</tr>
	<tr>
		<td colspan="2" style="padding-left:-20px;"><img src="images/DirectDebit-Form-Guarantee-pdf.gif" width="700" height="359" /></td>
	</tr>
</table>
EOD;
$tbl .= "</body>\n";

$pdf->writeHTML($tbl, true, false, true, false, '');
$fileatt = $pdf->Output("Dephna-Direct-Debit-Form.pdf", 'S');
$fileatttype = "application/pdf";
$fileattname = "Dephna-Direct-Debit-Form.pdf";
?>
