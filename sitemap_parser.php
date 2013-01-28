<?php
function fetch($url,$depth,$limit)
{
	$GLOBALS['dbname'] = $dbname = str_replace('.','-',$url);
	$GLOBALS['limit'] = $limit;
	$GLOBALS['dbpath'] = $dbpath = "data/{$dbname}.db";	#Location of Database Where site's sitemap stores
	
	# If sitemap is not fetched before so create database with name of its host.
	if (!file_exists($dbpath)) {
		$con = sqlite_open($dbpath, 0666, $error);
		if(!$con){die ($error);}
		$sql = "
		CREATE TABLE 'log' (id INTEGER PRIMARY KEY NOT NULL,'log' TEXT, 'url'  INTEGER , 'sitemap_id'  INTEGER , 'line'  INTEGER );
		CREATE TABLE sitemap(id INTEGER PRIMARY KEY NOT NULL, sitemap TEXT);
		CREATE TABLE urls(id INTEGER PRIMARY KEY NOT NULL,
		sitemap INTEGAR NOT NULL,
		loc TEXT NOT NULL,
		lastmod TEXT,
		changefreq TEXT,
		priority TEXT);
		";
		$query = sqlite_exec($con, $sql);
		$query = ($query == true) ? progress_log($con,'Database Created',__LINE__) : progress_log($con,'Error Creating Database',__LINE__);
	}
	else
	{
		#if database already exist so connect to it. 
		$con = sqlite_open($dbpath, 0666, $error);
	}
	
	$sitedbpath = "data/site.db";	#Location of Site Database Where url host is stored.
		
	# If it is first time so create site database.
	if (!file_exists($sitedbpath)) {
		$con_site = sqlite_open($sitedbpath, 0666, $error);
		$sql = "CREATE TABLE site(id INTEGER PRIMARY KEY NOT NULL, site TEXT, path TEXT);";
		$query = sqlite_exec($con_site, $sql);
		$sql = "INSERT INTO site(site,path) VALUES('".str_replace('-','.',$dbname)."','{$dbname}')";
		$query = sqlite_exec($con_site, $sql);
		if($query) {
			progress_log($con,"Database Created successfully",__LINE__);
		}
		else{
			progress_log($con,"Error Encountered in creating database",__LINE__);
		}
	}
	else
	{
		# If site database exists so add sitemap host to it, if doesnt exist.
		$con_site = sqlite_open($sitedbpath, 0666, $error);		
		$sql = "SELECT site FROM site WHERE path = '".$dbname."';";
		$query = sqlite_query($con_site, $sql);
		if(sqlite_num_rows($query) == 0){
			$sql = "INSERT INTO site(site,path) VALUES('".str_replace('-','.',$dbname)."','{$dbname}')";
		$query = sqlite_exec($con_site, $sql);
		}
	}
	
	progress_log($con,"Downloading $url/sitemap.xml",__LINE__);
	$data = download_curl("http://$url/sitemap.xml");
	if(is_numeric($data) && $data < 10485760)
	{
		progress_log($con,"Large Sitemap $data",__LINE__);
		exit;
	}
	$GLOBALS['con'] = $con;
	return sitemap($data,0,$depth);
}
function sitemap($data,$sitemapid = '0',$depth = 2)
{
	global $limit,$con,$dbname; #Get database name from global vairable.
	
	if ($depth == 0) {
        return 0;
    }
	
	$sitemap = simplexml_load_string($data, null, false); #Convert sitemap XML to object using SimpleXML
	$sitemap = object2array($sitemap); #convert object sitemap to sitemap xml
	#if sitemap is a index-sitemap, so fetch sitemap url from it.
	if(isset($sitemap['sitemap']))
	{
		#Go through all sitemap recursively
		foreach($sitemap['sitemap'] as $value)
		{
			$loc = isset($value['loc']) ? $value['loc'] : null; # Sitemap url
			$lastmod = isset($value['lastmod']) ? $value['lastmod'] : null; # Sitemap last modification date
			progress_log($con,"Downloading $loc",__LINE__);			
			$data = download_curl($loc); #get content from Sitemap url
			
			# if sitemap is larger than 10 mb so skip this sitemap and log to database.
			if(is_numeric($data) && $data < 10485760)
			{
				progress_log($con,"Sitemap Size Exceeded $data",__LINE__);
				continue;
			}
			#check if sitemap already exist or not.
			$sql = "SELECT id FROM sitemap WHERE sitemap = '".$loc."';";
			$query = sqlite_query($con, $sql);
			$row = sqlite_fetch_array($query, SQLITE_ASSOC);

			#if sitemap not exists so insert it into database and fetch it using it new id.
			if(sqlite_num_rows($query) == 0){
				progress_log($con,"Fetching",__LINE__,$loc);
				$sql = "INSERT INTO sitemap(sitemap) VALUES('$loc')";
				$query = sqlite_exec($con, $sql);
				$sitemapid = sqlite_last_insert_rowid($con);
				progress_log($con,"Depth".$depth - 1,__LINE__);
				sitemap($data,$sitemapid,$depth - 1);
			}
			else
			{
				#if sitemap exists so fetch sitemap using its previous id 
				progress_log($con,"Exist",__LINE__,$sitemapid);
				$sitemapid = $row['id'];
				$query = sqlite_exec($con, $sql);
				progress_log($con,"Depth".$depth - 1,__LINE__);
				sitemap($data,$sitemapid,$depth - 1);
			}
		}
	}
	
	#if sitemap is not a sitemap-index so fetch its uurl and add it to database.
	if(isset($sitemap['url']))
	{
		#if there is bulk of link so use array sitemap[url] else if there is on link so use sitemap var	
		$sitemap_url = (!isset($sitemap['url']['loc'])) ? $sitemap['url'] : $sitemap;
		$count = 1;	
		foreach($sitemap_url as $value)
		{
			if($count != $limit)
			{
				$count++;
			}
			else
			{
				break;
			}
			$loc = isset($value['loc']) ? parse_url($value['loc'], PHP_URL_PATH) : null; # Link
			$lastmod = isset($value['lastmod']) ? $value['lastmod'] : null;	# Last modifcation date of link
			$changefreq = isset($value['changefreq']) ? $value['changefreq'] : null; # Change Frequency of link
			$priority = isset($value['priority']) ? $value['priority'] : null; # Priority of link
				
			# check if link already added	
			$sql = "SELECT id FROM urls WHERE loc = '".$loc."';";
			$query = sqlite_query($con, $sql);
			$row = sqlite_fetch_array($query, SQLITE_ASSOC);	
			#if link already added in database
			if(sqlite_num_rows($query) == 1)
			{
				# check modifcation date is same or not to last previous result
				$sql = "SELECT id,loc 
					FROM urls
					WHERE loc = '$loc' AND
					sitemap = '$sitemapid' AND
					lastmod = '$lastmod'
				";
				$query = sqlite_query($con, $sql);
				$row = sqlite_fetch_array($query, SQLITE_ASSOC);
				# Link Modifacation date is changed so update the entry
				if(sqlite_num_rows($query) == 0)
				{
					$sql = "
					UPDATE urls
					SET loc = '$loc', sitemap = '$sitemapid', lastmod = '$lastmod', changefreq = '$changefreq', priority = '$priority'
					WHERE id = '{$row['id']}'					
					";
					$query = sqlite_exec($con, $sql);
					$lastid = sqlite_last_insert_rowid($con);
					if ($query) {
						progress_log($con,"Updated",__LINE__,$sitemapid,$lastid);
					}
					else
					{
						progress_log($con,"Error",__LINE__,$sitemapid,$lastid);
					} 
				}
				else
				{
					progress_log($con,"Exist",__LINE__,$sitemapid,$row['id']);
				}
				
			}
			else
			{
				#if link is not exist is database so add it to db
				$sql = "
				INSERT 
				INTO urls(loc,sitemap,lastmod,changefreq,priority) 
				VALUES('".parse_url($loc, PHP_URL_PATH)."','$sitemapid','$lastmod','$changefreq','$priority')
				";
				$query = sqlite_exec($con, $sql);
				$lastid = sqlite_last_insert_rowid($con);
				if ($query) {
					progress_log($con,"Added",__LINE__,$sitemapid,$lastid);
				}
				else
				{
					progress_log($con,"Error",__LINE__,$sitemapid,$lastid);
				} 
			}
		}
	}
	sqlite_close($con);
}

/*======================================================================*\
	Function:	download_curl
	Purpose:	It will get the html from the url
	Input:		url
	Output:		HTML
\*======================================================================*/

function download_curl($url)
{
	$header = get_headers($url, 1);
	if($header['Content-Length'] < 10485760)
	{	
		$ch = curl_init();
	
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	else
	{
		return $header['Content-Length'];
	}
}

/*======================================================================*\
	END OF Function download_curl
\*======================================================================*/


function object2array($object) { 
	return json_decode(json_encode($object),true); 
} 


function progress_log($con,$log,$line,$sitemap_id = NULL,$url = NULL)
{
	$sql = "INSERT INTO log (log,url,sitemap_id,line)
	VALUES ('$log','$url','$sitemap_id','$line');";
	$query = sqlite_exec($con, $sql);
	return 0;
}

?>