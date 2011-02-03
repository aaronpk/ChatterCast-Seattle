<?php
ini_set('error_reporting', E_ALL);
date_default_timezone_set('America/Los_Angeles');
define('DEBUG_MODE', TRUE);

set_include_path(dirname(__FILE__) . PATH_SEPARATOR . get_include_path());

include('config.php');
include('twitter-async/EpiCurl.php');
include('twitter-async/EpiOAuth.php');
include('twitter-async/EpiTwitter.php');
include('Twitter.php');
include('GeoloqiAPI.php');

function irc($msg)
{
	irc_debug($msg);
}

function irc_debug($msg)
{
	static $sock = FALSE;
	if($sock == FALSE)
		$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	socket_sendto($sock, $msg, strlen($msg), 0, MW_IRC_HOST, MW_IRC_PORT);
}

function force_login()
{
	if(!array_key_exists('username', $_SESSION))
	{
		header('Location: /index.php');
	}
}

function pa($a)
{
	echo '<pre>';
	print_r($a);
	echo '</pre>';
}

$geoloqi = new GeoloqiAPI(GEOLOQI_API_KEY, GEOLOQI_API_SECRET);

if(!array_key_exists('SHELL', $_SERVER))
	session_start();

$db = new PDO(PDO_DSN, PDO_USER, PDO_PASS);


?>
