<?php
include("sitemap_parser.php");

set_time_limit (60);
$url = 'infocorridor.tk';
$limit = 5;
$depth = 2;
fetch($url,$depth,$limit);
echo $dbpath."";
?>