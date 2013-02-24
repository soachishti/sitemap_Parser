<?php
include("sitemap_parser.php");
set_time_limit (60);

$url = 'google.com'; #Website Host
$limit = 5; # No of url to index from a sitemap
$depth = 2; # Depth of sitemap-index
fetch($url,$depth,$limit); # Fetch links and put in the database

echo "View result: <a href='view.php?s=$url'>$url</a><br /><br />"; # View result and log in view.php

$con = sqlite_open($dbpath, 0666, $error); # Open Database (dbpath is generated by function fetch in global variable)
$sql = "SELECT * FROM urls"; # Query for link
//$sql = "SELECT * FROM log"; # Query for log details
//$sql = "SELECT * FROM sitemap"; # Query for sitemaps
$query = sqlite_query($con,$sql); # Execute and return result

echo "<pre>";
while($row = sqlite_fetch_array($query, SQLITE_ASSOC))
{
	print_r($row);	# print results
}  
echo "</pre>";

?>