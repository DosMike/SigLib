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

if (empty($_POST['type']) || empty($_POST['id']) || empty($_POST['message'])) {
    endWith(['Error' => 'Broken formdata'], 400);
}
$id = intval($_POST['id']);
$message = trim($_POST['message']);
if ($id <= 0 || empty($message)) {
    endWith(['Error' => 'Empty message'], 400);
}
$messageHash = md5($id.$message);

if (isset($_SESSION['lastCommentMD5']) && $_SESSION['lastCommentMD5'] === $messageHash) {
    endWith(['Error' => 'This message was alread posted'], 400);
}
$_SESSION['lastCommentMD5'] = $messageHash;

include "includes/Parsedown/Parsedown.php";
$Parsedown = new Parsedown();
$Parsedown->setSafeMode(true);

if ($_POST['type'] == 'symbol') {

    $id = sqlInsert('symbol_comments', ['Symbol' => $id, 'Message' => sqlEscape($message), 'Created_By' => $Authorization['DBID']]);
    endWith([ 'ID' => $id, 'HTML' => $Parsedown->text($message) ]);

} elseif ($_POST['type'] == 'value') {

    $id = sqlInsert('value_comments', ['Value' => $id, 'Message' => sqlEscape($message), 'Created_By' => $Authorization['DBID']]);
    endWith([ 'ID' => $id, 'HTML' => $Parsedown->text($message) ]);

} else {
    endWith(['Error' => 'Broken formdata'], 400);
}