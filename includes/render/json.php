<?php
if (empty($fromIndex) && empty($fromAction)) die();

function output($type, $data) {
	header ('Content-Type: application/json');
	echo json_encode($data);
}