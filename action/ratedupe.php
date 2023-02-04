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

if ($_POST['type'] == 'symbol' || $_POST['type'] == 'value') {
    $table = $_POST['type'];
} else {
    endWith(['Error' => 'Broken formdata'], 400);
}

$result = [];

if (isset($_POST['dupes'])) {
    
    $entry = ['User' => $Authorization['DBID'], ucwords($table) => $id];
    if ($_POST['dupes'] == 'make') {
        sqlInsert("user_{$table}s", $entry, true);
    } else if ($_POST['dupes'] == 'destroy') {
        sqlDelete("user_{$table}s", $entry);
    } else {
        endWith(['Error' => 'Invalid action'], 400);
    }

} elseif (isset($_POST['rate'])) {

    if (!is_numeric($_POST['rate'])) endWith(['Error' => 'Invalid action'], 400);
    $value = intval($_POST['rate']);
    if ($value < -1 || $value > 1) endWith(['Error' => 'Invalid action'], 400);

    if ($value == 0) {
        sqlDelete("{$table}_ratings", [ 'Created_By' => $Authorization['DBID'], ucwords($table) => $id ]);
    } else {
        sqlUpsert("{$table}_ratings", [ 'Created_By' => $Authorization['DBID'], ucwords($table) => $id, 'Rating' => $value ], ['Rating']);
    }

}

//send back if the symbol/value still exists
sqlSelect("{$table}s", ['ID'], ['ID'=>$id], null, 1);
$recode = 200;
if (sqlRows()==0) {
    $result['Deleted']='Deleted';
    $result['Error']='This entry was de-duplicated into oblivion';
    $recode = 410; //gone
}
endWith($result, $recode);