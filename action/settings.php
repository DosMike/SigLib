<?php

if (empty($fromAction)) {
    http_response_code(400);
    die("Illegal direct invocation");
}

function endWith($data, $code=200) {
    http_response_code($code);
    output('Settings', $data);
    die;
}

if (empty($Authorization) || intval($Authorization['DBID']) <= 0) { 
    endWith(['Error' => 'You do not have permission to do this. Please log in or use your API Key. If you already are, your write access might have been revoked.'], 403);
}

$response = [];

if (isset($_POST['privacy'])) {
    $pval = intval($_POST['privacy']);
    if ($pval == 1 && empty($_POST['usesteam'])) $pval = 2;
    if ($pval < 0 || $pval > 3) {
        endWith(['Error', 'Invalid data'], 400);
    }

    $data = ['Anonymity' => $pval];
    if ($pval == 1 || $pval == 2) {
        $name = trim($_POST['nick']);
        if (empty($name)) $name='Anonymous';
        $data['DisplayName'] = $name;
        $response['Displayname']='OK';
    }

    if (sqlUpdate('users', $data, ['ID' => $Authorization['DBID']]) === false) endWith([],500);
    $response['Privacy']='OK';
}

endWith($response);