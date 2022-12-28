<?php

if (empty($fromAction)) {
    http_response_code(400);
    die("Illegal direct invocation");
}

function endWith($data, $code=200) {
    http_response_code($code);
    output('Comments', $data);
    die;
}

if (empty($Authorization) || intval($Authorization['Powerlevel']) <= 0 || intval($Authorization['DBID']) <= 0) { 
    endWith(['Error' => 'You do not have permission to do this. Please log in or use your API Key. If you already are, your write access might have been revoked.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    endWith(['Error' => 'Ay this is a PHP application, I can only cleanly get request body with POST requests'], 405);
}

if (empty($_POST['type']) || empty($_POST['id'])) {
    endWith(['Error' => 'Broken formdata'], 400);
}
$id = intval($_POST['id']);

if ($_POST['type'] == 'symbol') {
    $table = 'symbol_comments';
} elseif ($_POST['type'] == 'value') {
    $table = 'value_comments';
} else {
    endWith(['Error' => 'Broken formdata'], 400);
}

if ($Authorization['Powerlevel'] < 50) {
    sqlSelect($table, ['Created_By'], ['ID'=>$id]);
    if (($row = sqlGetRow()) == null || intval($row['Created_By']) != $Authorization['DBID']) {
        endWith(['Error' => 'You do not have permission to do this.'], 403);
    }
}
sqlQuery("DELETE FROM `$sqltp$table` WHERE `ID`=$id");
endWith([]);