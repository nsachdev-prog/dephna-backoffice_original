<?php
// Include the main TCPDF library
require_once('../../tcpdf6.8/tcpdf.php');
include('settings.php');

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if (!$dbcnx->connect_error) {

    $fullPathToLogo = "images/dephna-logo-fresh.png";

    // create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Dephna Estates');
    $pdf->SetSubject('Floorplan');
    $pdf->SetKeywords('Dephna, Group, Floorplan');

    // set margins
    $pdf->SetMargins('15', '40', '15');

    // set page break
    $pdf->SetAutoPageBreak(true, 1);

    // set font size
    $pdf->SetFont('helvetica', '', 11);

    // remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $unitid = $_REQUEST['unitid'];

    // get the floorplan details
    $sql = "SELECT u.*, s.title AS sitename, s.siteaddress
            FROM units AS u
            JOIN sites AS s ON u.siteid = s.ID
            WHERE u.ID = '$unitid'
            LIMIT 1
    ";
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
        $fpcanopydepth = $row['fpcanopydepth'];
        $fpcanopyheight = $row['fpcanopyheight'];
        $fpleftofcanopy = $row['fpleftofcanopy'];
        $fprightofcanopy = $row['fprightofcanopy'];
        $fpdrainleft = $row['fpdrainleft'];
		$fpdrainright = $row['fpdrainright'];
        $fpacodrains = $row['fpacodrains'];
        $fpacodrainswidth = $row['fpacodrainswidth'];
        $fpacofromdoor = $row['fpacofromdoor'];
        $fpevap     = $row['fpevap'];
        $fpevappos  = $row['fpevappos'];
        $flspecs    = $row['flspecs'];
        $fpnotes    = $row['fpnotes'];
        $flspecs    = stripslashes($flspecs);
        $fpnotes    = stripslashes($fpnotes);
        $siteaddress = $row['siteaddress'];
        $sitename    = $row['sitename'];

        // calculate the square meters
        $sqmeters = $fpwidth * $fpdepth;
        // to 2 decimal places
        $sqmeters = number_format($sqmeters, 2);

        $title = "Floorplan $unitname";
        $pdf->SetTitle($title);

        list($prefix, $unitname) = explode(" ", $unitname);

        // Add a page ---------------------------------------------------------
        $pdf->AddPage();

        // Header ---------------------------------------------------------
        // Add filled blue box rgb 12, 65, 115 at the top of the page, full width and 40 high
        $pdf->SetFillColor(12, 65, 115);
        $pdf->Rect(0, 0, 210, 35, 'F');
        // add logo actual size at the top left of the page
        $pdf->Image($fullPathToLogo, 9, 5, 0, 10, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        // Site Name and address
        // use cell to add text in white
        $pdf->SetTextColor(255, 255, 255);
        // set font size to 18
        $pdf->SetFont('helvetica', '', 18);
        // set cell top under image and left
        $pdf->SetXY(10, 15);
        $pdf->Cell(190, 12, "$sitename - Unit $unitname", 0, 1, 'L', 0, '', 0, false, 'T', 'B');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(10, 27);
        $pdf->Cell(190, 6, $siteaddress, 0, 1, 'L', 0, '', 0, false, 'T', 'M');

        // Footer ---------------------------------------------------------
        // add filled blue box rgb 12, 65, 115 at the bottom of the page, full width and 10 high
        $pdf->Rect(0, 279, 210, 18, 'F');
        // put text inside the blue box at the bottom of the page and centered
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Text(10, 283, 'Dephna Group Ltd');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Text(10, 290, '2 Portal Way, Gypsy Corner, Acton, London W3 6RT');
        $pdf->Text(180, 286, '020 8896 7575');
        $pdf->Text(176, 290, 'www.dephna.com');

        // Content ---------------------------------------------------------
        // draw an outline box 160 x 160 in the middle of the page 50 down
        $pdf->SetLineWidth(2.0);
        $pdf->Rect(25, 55, 160, 160);

        $pdf->SetLineWidth(0.5);
        // add text dimentions on the left and bottom of the box
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(90, 90, 90);
        // rotate text by 90 degrees
        $pdf->StartTransform();
        $pdf->Rotate(90, 25, 55 + 160 / 2);
        $pdf->Text(12, 50 + 160 / 2, "Room Width $fpwidth");
        $pdf->StopTransform();
        $pdf->Text(8 + 160 / 2, 55 + 160 + 2, "Room Depth $fpdepth Height $fpheight");

        // add square meters in the middle of the box in small light grey text
        $pdf->SetTextColor(90, 90, 90);
        $pdf->Text(25 + 160 / 2 - 10, 55 + 160 / 2, "Area $sqmeters m2");

        // add the door openning in the middle of the box on the right side
        // set line color to white and draw the door openning at 40
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetLineWidth(2.0);
        $pdf->Line(25 + 160, 55 + 160 / 2 - 40 / 2, 25 + 160, 55 + 160 / 2 + 40 / 2);
        // reset line color to black and add short horizontal door stop lines to top and bottom of door openning
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(25 + 160 - 2, 55 + 160 / 2 - 40 / 2, 25 + 160 + 2, 55 + 160 / 2 - 40 / 2);
        $pdf->Line(25 + 160 - 2, 55 + 160 / 2 + 40 / 2, 25 + 160 + 2, 55 + 160 / 2 + 40 / 2);

        // add door width in the middle of the door in small light grey text
        $pdf->SetTextColor(90, 90, 90);
        // rotate text by 90 degrees
        $pdf->StartTransform();
        $pdf->Rotate(90, 187, 154);
        $pdf->Text(187, 154, "Door Width $fpdoorwidth Height $fpdoorheight");
        $pdf->StopTransform();

        // add fpdoorleft and fpdoorright text
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(90, 90, 90);
        // place text in the middle of the space between the door and the wall, rotated by 90 degrees
        $pdf->StartTransform();
        $pdf->Rotate(90, 25 + 160 + 2, 55 + 34);
        $pdf->Text(25 + 160 + 2, 55 + 34, $fpdoorleft);
        $pdf->StopTransform();
        $pdf->StartTransform();
        $pdf->Rotate(90, 25 + 160 + 2, 55 + 130);
        $pdf->Text(25 + 160 + 2, 55 + 130, $fpdoorright);
        $pdf->StopTransform();

        if ($fpcanopy == "on") {
            // draw the canopy on the center left side of the page 90 wide and 15 high
            // reset line color to black
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Rect(25, 55 + 160 / 2 - 90 / 2, 25, 90);
            // add canopy text rotated by 90 degrees in the middle of the canopy in small light grey text
            $pdf->SetTextColor(90, 90, 90);
            $pdf->StartTransform();
            $pdf->Rotate(90, 45, 164);
            $pdf->Text(45, 164, "Canopy Width $fpcanopywidth, Depth $fpcanopydepth, Height $fpcanopyheight");
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

        if ($fpacodrains == 'on') {
            // add aco drain 90 high and 5 wide virtically centered and 40 from the right side
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Rect(25 + 160 - 40, 55 + 160 / 2 - 90 / 2, 5, 90);
            // add text in center and middle of the drain
            $pdf->SetTextColor(90, 90, 90);
            // rotate text by 90 degrees
            $pdf->StartTransform();
            $pdf->Rotate(90, 146, 151);
            $pdf->Text(146, 151, "ACO Drains Width $fpacodrainswidth");
            $pdf->StopTransform();
            // add aco drain from door text
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(90, 90, 90);
            // place text in the middle of the space between the drain and the door not rotated
            $pdf->Text(165, 100, $fpacofromdoor);
            // faint horizontal lines either side of fpacofromdoor to wall and drain
            $pdf->SetDrawColor(200, 200, 200);
            $pdf->SetLineWidth(0.5);
            $pdf->Line(152,102,165,102);
            $pdf->Line(173,102,182,102);

        }

        // Drains left and right
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(90, 90, 90);
        if ($fpdrainleft == "on") {
            $pdf->Circle(25 + 160 - 15, 55 + 15, 5, 0, 360, 'F', array(), array(90,90 , 90));
            $pdf->Text(25 + 160 - 14 - 15, 55 + 14, 'Drain');
        }
        if ($fpdrainright == "on") {
            $pdf->Circle(25 + 160 - 15, 55 + 160 - 15, 5, 0, 360, 'F', array(), array(90, 90, 90));
            $pdf->Text(25 + 160 - 14 - 15, 55 + 160 - 16, 'Drain');
        }

        // Evaporator
        if ($fpevap == 'on') {
            // draw small evaporator as filled box 6 x 3
            // reset line color to black
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetFillColor(0, 0, 0);
            if ($fpevappos == 1) {
                // draw evaporator on inside of the wall below the door
                $pdf->Rect(176, 168, 8, 30, 'F');
                $pdf->Text(166, 179, 'Evap');
            } else {
                // Draw evaporator on the opposite wall
                $pdf->Rect(25, 121, 8, 30, 'F');
                $pdf->Text(34, 134, 'Evap');
            }
        }

        // use html to add the disclaimer floorplan specs and notes
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(90, 90, 90);
        $pdf->SetXY(10, 230);
        $html = "<p align=\"center\"><i>This floorplan is for illustrative purposes only. The dimensions given are correct but the layout is not drawn to scale.</i></p>";
        if ($flspecs) {
            $html .= "<p><b>Specifications</b><br>$flspecs</p>";
        }
        if ($fpnotes) {
            $html .= "<p><b>Notes</b><br>$fpnotes</p>";
        }
        $pdf->writeHTML($html, true, false, true, false, '');

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
