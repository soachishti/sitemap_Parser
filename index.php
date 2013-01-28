<?php
include("sitemap_parser.php");

set_time_limit (60);
$url = 'example.com';
$limit = 5;
$depth = 2;
fetch($url,$depth,$limit);
$database = $dbpath;

$sql = "SELECT * FROM urls";
$con = sqlite_open($database, 0666, $error);
$result = sqlite_query($con,$sql);
while($row = sqlite_fetch_array($query, SQLITE_ASSOC))
{
	print_r($row);
}  
?>