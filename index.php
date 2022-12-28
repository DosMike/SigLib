<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);

require_once "includes/dbcon.i.php";

session_name($session_name);
session_start();

$fromIndex = true;

if (function_exists('getallheaders')) {
	$_HEADER = getallheaders();
} else {
	$_HEADER = array (); 
	foreach ($_SERVER as $name => $value) { 
		if (substr($name, 0, 5) == 'HTTP_') {
			$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
		}
	}
	return $headers; 
}

$render = "html";

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
	} else {
		$render = "html";
	}
}

if (empty($_GET['p']) || $_GET['p']=='list') {
	$contenttype = 'Symbols';
} else if ($_GET['p']=='sym') {
	$contenttype = 'Values';
} else if ($_GET['p']=='user') {
	$contenttype = 'User';
} else if ($_GET['p']=='upload') {
	$contenttype = 'Upload';
} else if ($_GET['p']=='help') {
	$render = "html";
	$contenttype = 'Help';
} else {
	http_response_code(404);
	$contenttype = 'Unknown';
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

require "includes/sharedutil.php";

require "includes/render/".strtolower($render).".php";

include "includes/processor/".strtolower($contenttype).".php";

$result=process($contenttype);
if ($result !== false) {
	if (isset($result['Error'])) {
		//assume bad request unless we have an actual error code
		http_response_code(isset($restult['HttpCode']) ? $restult['HttpCode'] : 400);
	}
	output($contenttype, $result);
}