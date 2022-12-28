<?php

require "includes/vdfparser.php";

if (empty($fromIndex) && empty($fromAction)) die();

function output($type, $data) {
	header ('Content-Type: application/vdf');
	write_vdf($type, $data, true);
}