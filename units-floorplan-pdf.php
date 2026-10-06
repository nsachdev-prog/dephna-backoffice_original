<?php
// Include the main TCPDF library
require_once('/home/tcpdf6.3/tcpdf.php');
require_once('/home/fpdi/fpdi.php');
include('settings.php');

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if (!$dbcnx->connect_error) {

    $fullPathToLogo = "../dis/images/invoice-header-logo-3.gif";

    // Extend the TCPDF class to create custom Header and Footer
    class MYPDF extends FPDI {
        public function Header() {
            global $fullPathToLogo;
            $this->Image($fullPathToLogo, 14, 5, '', '', 'GIF', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        /* public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        } */
    }

    // create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Dephna Estates');
    $pdf->SetSubject('Floorplan');
    $pdf->SetKeywords('Dephna, Group, Floorplan');

    // set default header data
    // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

    // set header and footer fonts
    // $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
    $pdf->SetMargins('15', '40', '15');
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    // $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // set font size
    $pdf->SetFont('helvetica', '', 11);

    $uid = $_REQUEST['uid'];

    // get the floorplan details
    $sql = "SELECT * FROM units WHERE ID = '$uid'";
    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
        $row = $result->fetch_assoc();
        $unitname    = $row['title'];
        $fpwidth     = $row['fpwidth'];
        $fpdepth     = $row['fpdepth'];
        $fpheight    = $row['fpheight'];
        $fpdoorwidth = $row['fpdoorwidth'];
        $fpdoorheight = $row['fpdoorheight'];
        $fpdoorleft  = $row['fpdoorleft'];
        $fpdoorright = $row['fpdoorright'];
        $fpcanopy    = $row['fpcanopy'];
        $fpcanopywidth = $row['fpcanopywidth'];
        $fpleftofcanopy = $row['fpleftofcanopy'];
        $fprightofcanopy = $row['fprightofcanopy'];
        $fpdrainleft = $row['fpdrainleft'];
		$fpdrainright = $row['fpdrainright'];
        $fpnotes     = $row['fpnotes'];
        $fpnotes     = stripslashes($fpnotes);

        $title = "Floorplan $unitname";
        $pdf->SetTitle($title);

        // Add a page ---------------------------------------------------------
        $pdf->AddPage();

        // Unit Name
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 0, $unitname, 0, 1, 'C');

        /* // draw outlined box fpwidth x fpdepth starting 50 down the page with longest side as virticle and the shortest side as horizontal using the full width of the page
        // draw door as an openning fpdoorwidth on the right side of the page
        // if the there is a canopy, draw canopy as an outline box fpcanopywidth x 15% of page deep the left side and centered

        // find the longest side and scale it to 90% of the page width
        $scale = 0.9 * 190 / max($fpwidth, $fpdepth);

        $pdf->SetLineWidth(0.5);
        $pdf->Rect(25, 55, $fpwidth * $scale, $fpdepth * $scale);

        // Door openning
        // set line color to white and draw the door openning
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->Line(25 + $fpwidth * $scale, 55 + $fpdepth * $scale / 2 - $fpdoorwidth * $scale / 2, 25 + $fpwidth * $scale, 55 + $fpdepth * $scale / 2 + $fpdoorwidth * $scale / 2);
        // reset line color to black and add short horizontal door stop lines to top and bottom of door openning
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(25 + $fpwidth * $scale - 2, 55 + $fpdepth * $scale / 2 - $fpdoorwidth * $scale / 2, 25 + $fpwidth * $scale + 2, 55 + $fpdepth * $scale / 2 - $fpdoorwidth * $scale / 2);
        $pdf->Line(25 + $fpwidth * $scale - 2, 55 + $fpdepth * $scale / 2 + $fpdoorwidth * $scale / 2, 25 + $fpwidth * $scale + 2, 55 + $fpdepth * $scale / 2 + $fpdoorwidth * $scale / 2);

        // Add width and dppth text. Width is virticle and depth is horizontal. Width is on the left and depth is on the bottom
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(90, 90, 90);
        $pdf->StartTransform();
        // text is rotated 90 degrees
        $pdf->Rotate(90, 25, 55 + $fpdepth * $scale / 2);
        $pdf->Text(12, 50 + $fpdepth * $scale / 2, "Room Width $fpwidth");
        $pdf->StopTransform();
        $pdf->Text(8 + $fpwidth * $scale / 2, 55 + $fpdepth * $scale + 2, "Room Depth $fpdepth Height $fpheight");

        // add door width in the middle of the door in small light grey text
        $pdf->SetTextColor(90, 90, 90);
        // rotate text by 90 degrees
        $pdf->StartTransform();
        $pdf->Rotate(90, 25 + $fpwidth * $scale, 55 + $fpdepth * $scale / 2);
        $pdf->Text(6 + $fpwidth * $scale, 50 + $fpdepth * $scale / 2, "Door Width $fpdoorwidth Height $fpdoorheight");
        $pdf->StopTransform();

        if ($fpcanopy == "on") {
            // draw the canopy on the left side of the page use the width as height and 15 wide and centered
            // reset line color to black
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Rect(25, 55 + $fpdepth * $scale / 2 - $fpcanopywidth * $scale / 2, 25, $fpcanopywidth * $scale);
            // add canopy text rotated by 90 degrees in the middle of the canopy in small light grey text
            $pdf->SetTextColor(90, 90, 90);
            $pdf->StartTransform();
            $pdf->Rotate(90, 25, 55 + $fpdepth * $scale / 2);
            $pdf->Text(10, 75 + $fpdepth * $scale / 2, "Canopy Width $fpcanopywidth");
            $pdf->StopTransform();
        } */

        // draw an outline box 160 x 160 in the middle of the page 50 down
        $pdf->SetLineWidth(0.5);
        $pdf->Rect(25, 55, 160, 160);

        // add text dimentions on the left and bottom of the box
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(90, 90, 90);
        // rotate text by 90 degrees
        $pdf->StartTransform();
        $pdf->Rotate(90, 25, 55 + 160 / 2);
        $pdf->Text(12, 50 + 160 / 2, "Room Width $fpwidth");
        $pdf->StopTransform();
        $pdf->Text(8 + 160 / 2, 55 + 160 + 2, "Room Depth $fpdepth Height $fpheight");

        // add the door openning in the middle of the box on the right side
        // set line color to white and draw the door openning at 40
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->Line(25 + 160, 55 + 160 / 2 - 40 / 2, 25 + 160, 55 + 160 / 2 + 40 / 2);
        // reset line color to black and add short horizontal door stop lines to top and bottom of door openning
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(25 + 160 - 2, 55 + 160 / 2 - 40 / 2, 25 + 160 + 2, 55 + 160 / 2 - 40 / 2);
        $pdf->Line(25 + 160 - 2, 55 + 160 / 2 + 40 / 2, 25 + 160 + 2, 55 + 160 / 2 + 40 / 2);

        // add door width in the middle of the door in small light grey text
        $pdf->SetTextColor(90, 90, 90);
        // rotate text by 90 degrees
        $pdf->StartTransform();
        $pdf->Rotate(90, 25 + 160, 55 + 160 / 2);
        $pdf->Text(6 + 160, 50 + 160 / 2, "Door Width $fpdoorwidth Height $fpdoorheight");
        $pdf->StopTransform();

        // add fpdoorleft and fpdoorright text
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(90, 90, 90);
        // place text in the middle of the space between the door and the wall, rotated by 90 degrees
        $pdf->StartTransform();
        $pdf->Rotate(90, 25 + 160 - 5, 55 + 38);
        $pdf->Text(25 + 160 - 5, 55 + 38, $fpdoorleft);
        $pdf->StopTransform();
        $pdf->StartTransform();
        $pdf->Rotate(90, 25 + 160 - 5, 55 + 126);
        $pdf->Text(25 + 160 - 5, 55 + 126, $fpdoorright);
        $pdf->StopTransform();

        if ($fpcanopy == "on") {
            // draw the canopy on the center left side of the page 90 wide and 15 high
            // reset line color to black
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Rect(25, 55 + 160 / 2 - 90 / 2, 25, 90);
            // add canopy text rotated by 90 degrees in the middle of the canopy in small light grey text
            $pdf->SetTextColor(90, 90, 90);
            $pdf->StartTransform();
            $pdf->Rotate(90, 25, 55 + 160 / 2);
            $pdf->Text(10, 75 + 160 / 2, "Canopy Width $fpcanopywidth");
            $pdf->StopTransform();
            // add canopy left and right text
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(90, 90, 90);
            // place text in the middle of the space between the canopy and the wall, rotated by 90 degrees
            $pdf->StartTransform();
            $pdf->Rotate(90, 25 + 2, 76);
            $pdf->Text(25 + 2, 76, $fprightofcanopy);
            $pdf->StopTransform();
            $pdf->StartTransform();
            $pdf->Rotate(90, 25 + 2, 201);
            $pdf->Text(25 + 2, 201, $fpleftofcanopy);
            $pdf->StopTransform();
        }

        // add aco drain 90 high and 5 wide virtically centered and 40 from the right side
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Rect(25 + 160 - 40, 55 + 160 / 2 - 90 / 2, 5, 90);
        // add text in center and middle of the drain
        $pdf->SetTextColor(90, 90, 90);
        // rotate text by 90 degrees
        $pdf->StartTransform();
        $pdf->Rotate(90, 25 + 160 - 40, 55 + 160 / 2);
        $pdf->Text(139, 136, "ACO Drain");
        $pdf->StopTransform();

        // Drains
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(90, 90, 90);
        if ($fpdrainleft == "on") {
            $pdf->Circle(25 + 160 - 15, 55 + 15, 5, 0, 360, 'DF', array(), array(255, 255, 0));
            $pdf->Text(25 + 160 - 14 - 15, 55 + 14, 'Drain');
        }
        if ($fpdrainright == "on") {
            $pdf->Circle(25 + 160 - 15, 55 + 160 - 15, 5, 0, 360, 'DF', array(), array(255, 255, 0));
            $pdf->Text(25 + 160 - 14 - 15, 55 + 160 - 16, 'Drain');
        }

        // add disclaimer text
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(90, 90, 90);
        $pdf->Text(31, 230, 'This floorplan is for illustrative purposes only. The dimensions given are correct but the layout is not drawn to scale.');

        // add notes
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(90, 90, 90);
        $pdf->Text(31, 235, $fpnotes);

    } else {
        $pdf->AddPage();
        $pdf->Write(0, "No Floorplan Details Found");
    }
} else {
    $pdf->AddPage();
    $pdf->Write(0, "Database Connection Error");
}

// End Content ---------------------------------------------------------

// Close and output PDF document
if ($output == "Email") {
    $fileattname = "Dephna-$title.pdf";
    $fileatttype = "application/pdf";
    $fileatt = $pdf->Output($fileattname, 'S');
} else {
    $pdf->Output($title.'.pdf', 'I');
}
