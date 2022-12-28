<?php
require_once("includes/authmain.php");

if (empty($fromAction)) die("Illegal direct invocation");

session_unset();
session_destroy();
$auth->LogOut();

header("Location: /${webroot}");