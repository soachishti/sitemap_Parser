<?php
$sitedbpath = "data/site.db";
$con = sqlite_open($sitedbpath, 0666, $error);
$sql = "SELECT * FROM site;";
$query = sqlite_query($con, $sql);

echo "<table border='1'><tr><th>Sites</th></tr>";
while($row = sqlite_fetch_array($query, SQLITE_ASSOC))
{
	echo "<tr>";
		echo "<td><a href='?s={$row['path']}'>".ucwords($row['site'])."</a></td>";
	echo "</tr>";
}
echo "</table>";

if(isset($_GET['s']))
{
	$_GET['s'] = str_replace('.','-',$_GET['s']);
	$con = sqlite_open('data/'.$_GET['s'].'.db', 0666, $error);
	
	////Fetching Urls From Database and it details such as loc, lastmod, frequency and prirorit of url.
	
	$sql = "SELECT * FROM urls";
	$result = sqlite_query($con,$sql);
	$count = sqlite_num_rows($result);
	$limit = 10;
	$current = (isset($_GET['p'])) ? $_GET['p'] : 0 ;
	$possible = $count/$limit;	
	echo 'Holding '.$count .' links';
	
	

	$sql = "SELECT * FROM urls LIMIT ".$current * $limit.",{$limit}";	
	echo "<table border='1'></tr>";
	
	$query = sqlite_query($con, $sql);
	echo "<td><table border='1'>
		<tr>
			<th>Loc</th>
			<th>Last Modificatin</th>
			<th>Change Frequency</th>
			<th>Prioroty</th>
		</tr>";
	while($row = sqlite_fetch_array($query, SQLITE_ASSOC))
	{
		echo "<tr>";
		echo "<td>{$row['loc']}</td>";
		echo "<td>{$row['lastmod']}</td>";
		echo "<td>{$row['changefreq']}</td>";
		echo "<td>{$row['priority']}</td>";
		echo "</tr>";
	}
	echo "</table>";
	
	echo pagination($current,$possible,'s=' . $_GET['s'],'p');
	echo "</td>";
	
	
	////Fetching Log from database
	
	$sql = "SELECT * FROM log";
	$result = sqlite_query($con,$sql);
	$count = sqlite_num_rows($result);
	$limit = 10;
	$current = (isset($_GET['p1'])) ? $_GET['p1'] : 0 ;
	$possible = $count/$limit;	
	
	$sql = "SELECT * FROM log LIMIT ".$current * $limit.",{$limit}";	
	
	$query = sqlite_query($con, $sql);
	echo "<td><table border='1'>
	<tr>
	<th>No.</th>
	<th>Log</th>
	<th>Sitemap Id</th>
	<th>Url Id</th>
	<th>Line</th>
	</tr>";
	while($row = sqlite_fetch_array($query, SQLITE_ASSOC))
	{
		echo "<tr>";
		echo "<td>{$row['id']}</td>";
		echo "<td>{$row['log']}</td>";
		echo "<td>{$row['sitemap_id']}</td>";
		echo "<td>{$row['url']}</td>";
		echo "<td>{$row['line']}</td>";
		echo "</tr>";
	}
	echo "</table>";
	
	echo pagination($current,$possible,'s=' . $_GET['s'],'p1');
	echo '</td>';
	
	////Fetching Sitemaps from database
	
	$sql = "SELECT * FROM sitemap";
	$result = sqlite_query($con,$sql);
	$count = sqlite_num_rows($result);
	$limit = 10;
	$current = (isset($_GET['p3'])) ? $_GET['p3'] : 0 ;
	$possible = $count/$limit;	
	
	$sql = "SELECT * FROM sitemap LIMIT ".$current * $limit.",{$limit}";	
	
	$query = sqlite_query($con, $sql);
	echo "<td><table border='1'>
	<tr>
	<th>No.</th>
	<th>Sitemap</th>
	</tr>";
	while($row = sqlite_fetch_array($query, SQLITE_ASSOC))
	{
		echo "<tr>";
		echo "<td>{$row['id']}</td>";
		echo "<td>{$row['sitemap']}</td>";
		echo "</tr>";
	}
	echo "</table>";
	
	echo pagination($current,$possible,'s=' . $_GET['s'],'p3');
	echo '</td>';

	echo '</tr></table>';
}

function pagination($current,$possible,$get,$page,$out = '')
{	
	if($current <= $possible)
	{
		if($current+1 <= $possible)
		{
			$next = $current+1;
			$out .= "<br /><a href='?$page=" . $next . "&$get'>Next</a>";	
		}
		if($current >= 1)
		{
			$prev = $current-1;
			$out .= "<br /><a href='?$page=". $prev . "&$get'>Previous</a>";		
		}
	}
	else
	{
		$out .= "<br /><a href='?$page=". $possible . "&$get'>Previous</a>";
	}	
	return $out;
}
?>