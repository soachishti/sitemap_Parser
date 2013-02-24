Sitemap_Parser
==============

Sitemap_Parser is a set of function
with the help of which fetch urls and
data from Sitemap or Sitemap-Index file.

Requirements:
------------
* PHP > 5
* Sqlite
* SimpleXML

Features:
------------
* Fetch Sitemap and Sitemap-Index.
* Store data in different database file.
* Work with large amount of links.
* Excellent Logging system.

**Example**

	<?php
		$url = 'google.com';
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
	
License:
------------
Sitemap_Parser uses BSD 2-Clause License.