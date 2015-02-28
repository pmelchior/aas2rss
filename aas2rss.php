<?php
ini_set('display_errors', 0);
$logfile = fopen("logfile.log","a+");

function appendToLog(&$logfile,$string) {
	$timestamp = date("Y/m/d H:i:s\t");
	fwrite($logfile,$timestamp.$string."\n");
}


function appendItem($info,$cha,$xml) {
	$itm = $xml->createElement('item');
	$cha->appendChild($itm);

	$dat = $xml->createElement('title', $info['title']);
	$itm->appendChild($dat);
	 
	$dat = $xml->createElement('description', $info['desc']);
	$itm->appendChild($dat);   
	$dat = $xml->createElement('link', $info['link']);
	$itm->appendChild($dat);

	$dat = $xml->createElement('pubDate',$info['date']);
	$itm->appendChild($dat);

	$dat = $xml->createElement('guid',$info['link']);
	$itm->appendChild($dat);
}

function getDetailPage($link) {
	$data = file_get_contents($link);
	$start = stripos($data,"<fieldset class=\"fieldgroup group-announcement\">");
	$stop = stripos($data,"</fieldset>",$start);
	// don't flood the server with requests
	sleep(1);
	return substr($data,$start,$stop-$start);
}

function parseDetails($details) {
  $start = stripos($details,"<div class=\"field-item odd\">");
  $start += 29;
  $stop = stripos($details, "</div>", $start);
  return trim(substr($details,$start,$stop-$start));
}

function parseListPage($data,$cha,$xml,$starttag,$stoptag) {
	global $logfile;
	$start = strpos($data,$starttag);
	$stop = strpos($data,$stoptag,$start);
	$pos = $start;

	$info = array('link' => '','title' => '', 'desc' => '', 'date' => '');
	while (1) {
		$pos = strpos($data,"job_view?JobID=",$pos);
		if ($pos >= $stop || $pos === FALSE)
			break;
		$linkstop = strpos($data,"\">",$pos);
		$info['link'] = substr($data,$pos,$linkstop-$pos);	
		$titlestart = stripos($data,"<td",$linkstop);
		$titlestop = stripos($data,"</td>",$titlestart);
		$info['title'] = htmlspecialchars(strip_tags(substr($data,$titlestart,$titlestop-$titlestart)));
		$posequal = strpos($info['link'],'=');
		$id = substr($info['link'],$posequal+1);
		$detailpage = "cache/".$id;
		$info['link'] = "http://jobregister.aas.org/".$info['link'];
		$empty = FALSE;
		// used cached detailpage if it exists
		if (file_exists($detailpage)) {
			$info['desc'] = parseDetails(file_get_contents($detailpage));
			$info['date'] = date("D, d M Y H:i:s ",filectime($detailpage)).'GMT';
		} else {
			$details = getDetailPage($info['link']);
			appendToLog($logfile,"downloading detail page ".$info['link']);
			if (strlen($details) == 0) {
			  $empty = TRUE;
			  appendToLog($logfile,"discarding empty page for ".$id);
			}
			else {
			  file_put_contents($detailpage,$details);
			  $info['desc'] = parseDetails($details);
			  $info['date'] = date("D, d M Y H:i:s ",filectime($detailpage)).'GMT';
			}
		}
		$pos = $titlestop;
		if ($empty === FALSE)
		  appendItem($info,$cha,$xml);
	}
}

function deleteChannelXMLs($channels) {
	foreach($channels as $c) {
		$xmlpage = "xml/".$c.".xml";
		unlink($xmlpage);
	}
}


// log request to file
appendToLog($logfile,$_SERVER['REMOTE_ADDR']." -> ".$_GET['channel']);

// list of allowed channels
$channels = array('postdoc','faculty_visiting','faculty','other','graduate','engineering','management','staff');

// check if listpage is missing or is older than 24 hours
$listpage = "cache/index.html";
$timestamp = @filectime($listpage);
$now = time();
$cached = "";
if (file_exists($listpage))
	$cached = file_get_contents($listpage);

// to prevent multiple calls of the update routines:
// require an exclusive lock of an empty lock file
$lock = fopen("lock","r");
flock($lock,LOCK_EX);

// now do update check
if ($timestamp+24*60*60 < $now || !file_exists($listpage)) {
	// if so check if it has changed online
	appendToLog($logfile,"checking for listpage update");
	$online = file_get_contents("http://jobregister.aas.org");
	// save changed online version to cache
	file_put_contents($listpage,$online);
	$cached = $online;
	$online = "";
	// since ads pages could be empty even though the ads are listed
	// on the index page, we need to regenerate xml files, too
	deleteChannelXMLs($channels);
}
// release lock by closing lock file
// see below for a saver but slower alternative
fclose($lock);

// only do something for a declared channel
if (in_array($_GET['channel'],$channels)) {
	$channel = $_GET['channel'];
	$xmlpage = "xml/".$channel.".xml";
	// file has been deleted to force an update
	if (!file_exists($xmlpage)) {
		// set search tags and title/description for the channel
		$title = "";
		$description = "";
		$key = "";
		$starttag = "";
		$stoptag = "";
		if ($channel == "postdoc") {
			$key = "Post-doctoral Positions";
			$starttag = "id=\"PostVFellow\">";
			$stoptag  = "id=\"PreVGrad\">";
		} else if ($channel == "faculty_visiting") {
			$key = "Faculty Positions (visiting/non-tenure)";
			$starttag = "id=\"FacPosNonTen\">";
			$stoptag  = "id=\"FacPosTen\">";
		} else if ($channel == "faculty") {
			$key = "Faculty Positions (tenure/tenure-track)";
			$starttag = "id=\"FacPosTen\">";
			$stoptag  = "id=\"Other\">";
			//$stoptag  = "id=\"PostVFellow\">";
		} else if ($channel == "other") {
			$key = "Other Positions";
			$starttag = "id=\"Other\">";
			$stoptag = "id=\"PostVFellow\">";
		} else if ($channel == "graduate") {
			$key = "Pre-doctoral/Graduate Positions";
			$starttag = "id=\"PreVGrad\">";
			$stoptag  = "id=\"SciEng\">";
		} else if ($channel == "engineering") {
			$key = "Science Engineering Positions";
			$starttag = "id=\"SciEng\">";
			$stoptag  = "id=\"SciMgmt\">";
		} else if ($channel == "management") {
			$key = "Science Management Positions";
			$starttag = "id=\"SciMgmt\">";
			$stoptag  = "id=\"SciTechStaff\">";
		} else if ($channel == "staff") {
			$key = "Scientific/Technical Staff";
			$starttag = "id=\"SciTechStaff\">";
			$stoptag  = "<div id=\"footer\">";
		}
		$title = "AAS Job Register: $key";
		$description = "RSS feed for ".strtolower($key)." offered at the AAS Job Register";	

		// set up XML DOM
		$xml = new DOMDocument('1.0','utf-8');
		$xml->formatOutput = true;
		$roo = $xml->createElement('rss');
		$roo->setAttribute('version', '2.0');
		$xml->appendChild($roo);
		$cha = $xml->createElement('channel');
		$roo->appendChild($cha); 
		$hea = $xml->createElement('title',$title);
		$cha->appendChild($hea);
		$hea = $xml->createElement('description',$description);
		$cha->appendChild($hea);
		$hea = $xml->createElement('language','en');
		$cha->appendChild($hea);
		$hea = $xml->createElement('link','http://www.ita.uni-heidelberg.de/~pmelchior/aas2rss/aas2rss.php?channel='.$channel);
		$cha->appendChild($hea);
		$hea = $xml->createElement('lastBuildDate',date("D, d M Y H:i:s ").'GMT');
		$cha->appendChild($hea);
		// parse the new list page to get infos
		appendToLog($logfile,"creating new XML page $xmlpage");
		parseListPage($cached,$cha,$xml,$starttag,$stoptag);
		$xml->save($xmlpage);
	}
	// if one want to exclude concurrent calls to
	// parseListPage() for the same channel:
	// release the lock not before here...
 	// flock($lock,LOCK_UN);

	readfile($xmlpage);
}

fclose($logfile);
?>
