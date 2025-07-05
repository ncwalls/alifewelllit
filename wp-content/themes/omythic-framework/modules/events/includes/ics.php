<?php 

$event_title = $_GET['title'];
$event_dtstart = $_GET['dtstart'];
$event_dtend = $_GET['dtend'];
$event_dtstamp = $_GET['dtstamp'];
$event_location = null;
if(isset($_GET['location'])){
  $event_location = $_GET['location'];
}
$event_geo = null;
if(isset($_GET['geo'])){
  $event_geo = $_GET['geo'];
}
$event_description = $_GET['description'];
$event_url = $_GET['url'];

$ics_content = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//MSW//MSW Events 1.5//EN
BEGIN:VEVENT
SUMMARY:' . htmlspecialchars(urldecode($event_title)) . '
UID:'.bin2hex(random_bytes(18)) . '
DTSTART:TZID='.htmlspecialchars($event_dtstart) . '
DTEND:TZID='.htmlspecialchars($event_dtend) . '
DTSTAMP:' . htmlspecialchars($event_dtstamp);
if($event_location){
  $ics_content .= '
LOCATION:'.htmlspecialchars(urldecode($event_location));
}
if($event_geo){
  $ics_content .= '
GEO:'.htmlspecialchars($event_geo);
}
$ics_content .= '
DESCRIPTION:' . htmlspecialchars(urldecode($event_description)) . '
URL:' . htmlspecialchars($event_url) . '
END:VEVENT
END:VCALENDAR';

header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename='.htmlspecialchars($event_title).'.ics');

echo $ics_content;