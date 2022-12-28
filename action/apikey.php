<?php

if (empty($fromAction)) die("Illegal direct invocation");

function endWith($data, $code=200) {
    http_response_code($code);
    output('GameDataImport', $data);
    die;
}

if (empty($_POST['action'])) {
    endWith(['Error'=>'Nothing to do'],400);
}

if (empty($_SESSION['steamid']) || empty($_SESSION['dbid'])) {
    endWith(['Error'=>'You are not logged in'],401);
}

function makeUUIDv4() {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = random_bytes(16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

if ($_POST['action']=='generate') {
    $key = makeUUIDv4();
    sqlUpdate('users', ['API_Key'=>$key], ['ID'=>$_SESSION['dbid']]);
    endWith(['apiKey' => base64_encode($_SESSION['steamid'].':'.$key)],201);
} else if ($_POST['action']=='destroy') {
    sqlUpdate('users', ['API_Key'=>null], ['ID'=>$_SESSION['dbid']]);
    endWith(['apiKey' => '']);
} else {
    endWith(['Error' => 'Invalid Action'], 400);
}