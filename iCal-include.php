<?php

// create email links and attachment in iCalendar format (ics)

// Requires...
// $iCal_start "YYY-MM-DD HH:MM"
// $iCal_duration (int Hours)
// $iCal_title
// $iCal_description
// $iCal_location

$search  = array("&","\r","\n");
$replace = array(" "," "," ",);
$iCal_title       = str_replace($search,$replace,$iCal_title);
$iCal_location    = str_replace($search,$replace,$iCal_location);
$iCal_description = str_replace($search,$replace,$iCal_description);

$iCal_start .= ":00";
$iCal_dst   = date("P",strtotime($iCal_start)); // UTC (GMT) offset
$iCal_end   = date("Y-m-d H:i:00",strtotime("$iCal_start +$iCal_duration Hours"));
$iCal_end   = str_replace(" ","T",$iCal_end);
$iCal_start = str_replace(" ","T",$iCal_start);

$iCal_links = array();

// Outlook Link
$url = "https://outlook.office.com/calendar/0/deeplink/compose";
$postdata = http_build_query(array(
    'body' => $iCal_description,
    'enddt' => $iCal_end.$iCal_dst,
    'location' => $iCal_location,
    'path' => '/calendar/action/compose',
    'rru' => 'addevent',
    'startdt' => $iCal_start.$iCal_dst,
    'subject' => $iCal_title
));
$iCal_links[] = "<a href=\"$url?$postdata\">Outlook</a>";

// Office365 Link
$url = "https://outlook.office.com/calendar/0/deeplink/compose";
$postdata = http_build_query(array(
    'body' => $iCal_description,
    'enddt' => $iCal_end.$iCal_dst,
    'location' => $iCal_location,
    'path' => '/calendar/action/compose',
    'rru' => 'addevent',
    'startdt' => $iCal_start.$iCal_dst,
    'subject' => $iCal_title
));
$iCal_links[] = "<a href=\"$url?$postdata\">Office365</a>";

// Adjust format for google and yahoo
$iCal_start = str_replace("-","",$iCal_start);
$iCal_start = str_replace(":","",$iCal_start);
$iCal_end   = str_replace("-","",$iCal_end);
$iCal_end   = str_replace(":","",$iCal_end);

// Google Link
$url = "https://calendar.google.com/calendar/u/0/r/eventedit";
$postdata = http_build_query(array(
    'action' => 'TEMPLATE',
    'dates' => $iCal_start.$iCal_dst."/".$iCal_end.$iCal_dst,
    'details' => $iCal_description,
    'location' => $iCal_location,
    'text' => $iCal_title
));
$iCal_links[] = "<a href=\"$url?$postdata\">Google</a>";

// Yahoo Link
$url = "https://calendar.yahoo.com";
$postdata = http_build_query(array(
    'v'      => '60',
    'view'   => 'd',
    'type'   => '20',
    'title'  => $iCal_title,
    'st'     => $iCal_start.$iCal_dst,
    'et'     => $iCal_end.$iCal_dst,
    'desc'   => $iCal_description,
    'in_loc' => $iCal_location,
    'uid'    => ''
));
$iCal_links[] = "<a href=\"$url?$postdata\">Yahoo</a>";

// put them together
$iCal_links = implode(" | ",$iCal_links);

// create ics file
$iCal_uid = date('Ymd').'T'.date('His')."-".rand()."-dephna.com";
$iCal_timestamp = date('Ymd').'T'.date('His');

$iCal_ics  = "";
$iCal_ics .= "BEGIN:VCALENDAR\n";
$iCal_ics .= "VERSION:2.0\n";
$iCal_ics .= "PRODID:Dephna Estates\n";
$iCal_ics .= "METHOD:REQUEST\n";
$iCal_ics .= "BEGIN:VEVENT\n";
$iCal_ics .= "UID:{$iCal_uid}\n";
$iCal_ics .= "DTSTAMP:{$iCal_timestamp}\n";
$iCal_ics .= "DTSTART:{$iCal_start}\n"; 
$iCal_ics .= "DTEND:{$iCal_end}\n";
$iCal_ics .= "LOCATION:{$iCal_location}\n";
$iCal_ics .= "SUMMARY:{$iCal_title}\n";
$iCal_ics .= "DESCRIPTION:{$iCal_description}\n";
$iCal_ics .= "CLASS:PUBLIC\n";
$iCal_ics .= "END:VEVENT\n";
$iCal_ics .= "END:VCALENDAR\n";

?>
