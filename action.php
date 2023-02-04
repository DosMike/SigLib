<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
require_once "includes/dbcon.i.php";

session_name($session_name);
session_start();

$fromAction = true;

// special actions, these bypass our system

if ($_GET['do']=='login') {
    include("action/login.php");
    die;
} elseif ($_GET['do']=='logout') {
    include("action/logout.php");
    die;
}

// pre-processing similar to index.php

if (function_exists('getallheaders')) {
	$_HEADER = getallheaders();
} else {
	$_HEADER = array (); 
	foreach ($_SERVER as $name => $value) { 
		if (substr($name, 0, 5) == 'HTTP_') {
			$_HEADER[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
		}
	}
}

$render = "json";
if (isset($_HEADER['Accept'])) {
	$types = explode(', ', $_HEADER['Accept']);
	array_map(function($type){return explode(';', $type)[0];},$types); //throw weight
	
	// includes a renderer for the output data
	// output types so far are: 
	if (in_array('application/xml', $types)) {
		$render = "xml";
	} else if (in_array('application/json', $types)) {
		$render = "json";
	} else if (in_array('application/vdf', $types)) {
		$render = "keyvalue";
	}
}

if (empty($_HEADER['User-Agent'])) {
	http_response_code(400);
	die("Bad Request");
}

require "includes/ratelimit.php";
// when PHP is like: Let's arbitrarily move this value around "for safety"
if (isset($_HEADER['Authorization'])) $Authorization = ratelimit_auto( $_HEADER['Authorization'] );
elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) $Authorization = ratelimit_auto( $_SERVER['HTTP_AUTHORIZATION'] );
elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $Authorization = ratelimit_auto( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] );
else $Authorization = ratelimit_auto();

require "includes/render/".strtolower($render).".php";

//process requests

if ($_GET['do']=='import') {
    include("action/import.php");
} elseif ($_GET['do']=='apikey') {
    include("action/apikey.php");
} elseif ($_GET['do']=='comment') {
	include("action/addcomment.php");
} elseif ($_GET['do']=='delcomment') {
	include("action/delcomment.php");
} elseif ($_GET['do']=='ratemod') {
    include("action/ratedupe.php");
} elseif ($_GET['do']=='settings') {
    include("action/settings.php");
} elseif ($_GET['do']=='clearprofile') {
	include("action/clearprofile.php");
} else {
    header("location: /{$webroot}", true, 302);
}