<html>
<head>
<title>$CVSHeader: www/eclipse/platform-ui/scripts/jdt_contributions_csv.php,v 1.2 2008/06/02 18:29:07 khorne Exp $</title>
</head>
<body>
<?php
require_once "/home/data/httpd/eclipse-php-classes/system/dbconnection_bugs_ro.class.php";
ini_set("display_errors", "true");
error_reporting (E_ALL);
$committerList = array("Olivier Thomann" => array("IBM", ""), "Martin Aeschlimann" => array("IBM", ""), "Frederic Fusier" => array("IBM", ""), "David Audel" => array("IBM", ""), "Jerome Lanneluc" => array("IBM", ""), "Philippe Mulet" => array("IBM", ""), "Darin Wright" => array("IBM", ""), "Markus Keller" => array("IBM", ""), "Benno Baumgartner" => array("IBM", ""), "Daniel Megert" => array("IBM", ""));
// the following bugs have been examined by hand and found to not be viable contributions
$exclusions = array();
$committerOverrides = array();
$contributorOverrides = array();
$locationOverrides = array();
$includedMilestones = array("3.4", "3.4 M1", "3.4 M2", "3.4 M4", "3.4 M5", "3.4 M6", "3.4 M7", "3.4 RC1", "3.4 RC2", "3.4 RC3", "3.4 RC4");
$uniqueNames = array();
$uniqueCount = array();
$uniqueBugs = array();

$dbc 	= new DBConnectionBugs();
$dbh 	= $dbc->connect();
$rs = null;

//
// David M Williams
// Tim deBoer
// Raghu Srinivasan
// Phil Berkland
// kiril mitov

function count_addedlines( $data ) {
//$count = substr_count( $data, "\n+" );
$count = preg_match_all ('/\n\+/', $data, $matches );
return $count;
}
function count_newlines( $data ) {
$count = substr_count( $data, "\n" );
//$count = preg_match_all ('/\n\+/', $data, $matches );
return $count;
}
function countNewLines($myrow) {

$result = 0;
$count1 = preg_match( '/patch/', $myrow['filename'] );
if( $count1 > 0 ) {
$result = count_newlines($myrow['thedata']);
}
else
{
$count2 = preg_match( '/RCS file:/', $myrow['thedata'] );
if( $count2 > 0 ) {
$result = count_newlines($myrow['thedata']);
}
}
return $result;
}
function countAddedLines($myrow) {

$result = 0;
$count1 = preg_match( '/patch/', $myrow['filename'] );
if( $count1 > 0 ) {
$result = count_addedlines($myrow['thedata']);
}
else
{
$count2 = preg_match( '/RCS file:/', $myrow['thedata'] );
if( $count2 > 0 ) {
$result = count_addedlines($myrow['thedata']);
}
}
return $result;
}

function findPatchProjects($myrow) {

preg_match_all( '/RCS file: \/(?:(?:cvsroot){0,1}(?:home){0,1}){1}\/eclipse\/([A-Za-z0-9\-\.]+)\/[A-Za-z0-9\/]*/', $myrow['thedata'], $matches);
return $matches[1];
}

function checkProject($componenetLabel, $projectNumber, $component, $includes) {

global $dbc;
global $dbh;
global $rs;
global $committerList;
global $exclusions;
global $uniqueCount;
global $uniqueNames;
global $uniqueBugs;
global $committerOverrides;
global $contributorOverrides;
global $locationOverrides;

$buglist = array ();

$sql_info = "SELECT  bugs.bug_id as bug_id,
		bugs.bug_status as bug_status,
		bugs.resolution as bug_resolution,
		bugs.target_milestone as bug_target_milestone,
		bugs.status_whiteboard as status,
		bugs.short_desc as description,
		attachments.filename as filename,
		attachments.ispatch as ispatch,
        attachments.creation_ts as timestamp,
		attachmentProfiles.login_name as attachment_login_name,
        attachmentProfiles.realname as attachment_real_name,
        committerProfiles.realname as committer_real_name,
        attach_data.thedata as thedata,
		bugs.keywords as bug_keywords,
		bugs.component_id as component_id
    FROM bugs,attachments,profiles as attachmentProfiles, profiles as committerProfiles,attach_data
      WHERE attachments.bug_id = bugs.bug_id
      	  AND resolution = \"FIXED\"
          AND attachments.ispatch = 1
          AND attachments.isobsolete = 0
		  AND char_length(bugs.keywords) > 0
          AND bugs.product_id = $projectNumber
		  AND attachments.submitter_id = attachmentProfiles.userid
		  AND bugs.assigned_to = committerProfiles.userid
          AND attach_data.id = attachments.attach_id 
          AND component_id = $component
          ORDER BY bugs.bug_id, attachments.creation_ts DESC";

$rs = mysql_query($sql_info, $dbh);

while( $myrow  = mysql_fetch_assoc($rs) ) {
if( !array_key_exists($myrow['attachment_real_name'], $committerList) && !in_array($myrow['bug_id'], $exclusions) && !in_array($myrow['bug_id'], $uniqueBugs)) {
if (in_array($myrow['bug_target_milestone'],$includes)) {

array_push($uniqueBugs, $myrow['bug_id']);
$contributor =  $myrow['attachment_real_name'];
$contributorEmail =  $myrow['attachment_login_name'];
if (array_key_exists($myrow['bug_id'], $contributorOverrides)) {
$pair = $contributorOverrides[$myrow['bug_id']];
$contributorEmail = $pair[1];
$contributor = $pair[0];
}
if ($contributor == null) {
$contributor = $contributorEmail;
}
$committer = $myrow['committer_real_name'];
if (array_key_exists($myrow['bug_id'], $committerOverrides)) {
$committer = $committerOverrides[$myrow['bug_id']];
}


echo $componenetLabel . ",";
echo "https://bugs.eclipse.org/bugs/show_bug.cgi?id=" . $myrow['bug_id'] . ",";

echo str_replace("@","{at}", $contributor) . ",";

$count = countAddedLines($myrow);
echo ($count < 100 ? "small" :  $count) . ",";

echo $committer . ",";
echo str_replace(",", " ", $myrow['description']);
echo "\n";
}
}


}

flush();
}

checkProject("APT", 3, 268, $includedMilestones);
checkProject("Core", 3, 10, $includedMilestones);
checkProject("Debug", 3, 11, $includedMilestones);
checkProject("Doc", 3, 24, $includedMilestones);
checkProject("Text", 3, 43, $includedMilestones);
checkProject("UI", 3, 12, $includedMilestones);

$dbc->disconnect();

$rs 		= null;
$dbh 		= null;
$dbc 		= null;


exit();
?>