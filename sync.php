#!/usr/bin/php
<?php

// ------------- [  config ] ---------------

// URL to transmission RPC service
$transmission_rpc = 'http://10.10.0.10:9091/transmission/rpc';

// Credentials to access transmission
$transmission_user = 'bt';
$transmission_pass = 'bt';

// How many days old episodes to download
$days_limit = 7;

// Series to download
//  More details about RSS parameters are on http://www.dailytvtorrents.org/blog/rss-feed-parameters-for-hardcore-geeks--3

$series = array();
$series[] = array("url" => "http://www.dailytvtorrents.org/rss/show/how-i-met-your-mother?only=hd&norar=yes&minage=4");

// ------------- [ /config ] ---------------

set_time_limit(0);

require_once( dirname( __FILE__ ) . '/class/TransmissionRPC.class.php' );

$rpc = new TransmissionRPC($transmission_rpc);
$rpc->username = $transmission_user;
$rpc->password = $transmission_pass;

$fields = array(
	"id", "name", "status", "doneDate", "haveValid", "totalSize",
	'isFinished',
	'torrentFile',
	);

$x = $rpc->get(array(), $fields);

if($x->result === "success")
{
	foreach($x->arguments->torrents as $t)
	{
		if(isset($t->doneDate) && isset($t->isFinished) && $t->isFinished === true)
		{
			$stari = (time() - $t->doneDate) / 3600 / 24;
			if($stari > $days_limit)
			{
				$q = $rpc->remove($t->id);
				sleep(1);
			}
		}
	}
}

foreach($series as $s)
{
	$xml = simplexml_load_file($s["url"], "SimpleXMLElement", LIBXML_NOCDATA);
	foreach($xml->channel->item as $i)
	{
		$old = ((time() - strtotime($i->pubDate)) / 3600 / 24);
		if($old < $days_limit)
		{
			$url = (string)$i->enclosure["url"];	
			$file = substr($i->link, strrpos($i->link, "/") +1 );
			$base = substr($file, 0, strrpos($file, "."));
			$title = (string)$i->title;
			try 
			{
			    $r = $rpc->add_file($url);
			    echo ($title . ": " . $r->result) . "\n";
			} catch(Exception $e) 
			{
				echo $title . ": HTTP ERROR" . "\n";
			}
		}
	}
}
