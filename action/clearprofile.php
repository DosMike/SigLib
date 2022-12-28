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

//don't break conversations by deleting comments, so only delete the author
sqlUpdate('value_comments', ['Created_By' => 0], ['Created_By' => $Authorization['DBID']]);
sqlUpdate('symbol_comments', ['Created_By' => 0], ['Created_By' => $Authorization['DBID']]);

//drop ratings
sqlDelete('value_ratings', ['Created_By' => $Authorization['DBID']]);
sqlDelete('symbol_ratings', ['Created_By' => $Authorization['DBID']]);

//de-dupe symbols
sqlDelete('user_values', ['User' => $Authorization['DBID']]);
sqlDelete('user_symbols', ['User' => $Authorization['DBID']]);