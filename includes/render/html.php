<?php
if (empty($fromIndex) && empty($fromAction)) die("Render not from main");

function loginButton() {
    if (empty($_SESSION['steamid'])) {
        ?><a href="action.php?do=login"><img src="assets/steam_login.png" /></a><?
    } else {
        ?><a href="?p=user" data-user="<?=$_SESSION['dbid']?>"><?= $_SESSION['username'] ?><img class="avatar" src="<?= $_SESSION['avatar'] ?>" /></a><?
    }
}

function output($type, $data) {
    
	//spliterating this more allows for cleaner per-page content
	require "html/".strtolower($type).".php";

    ?><!DOCTYPE html>
<html><head>
    <meta charset="utf-8">
    <title>Signature Library</title>
    <link href="css/style.css" rel="stylesheet" />
    <link href="css/alerty.min.css" rel="stylesheet" />
    <script src="script/alerty.min.js"></script>
    <script src="script/request.js"></script><?php
    
    if (function_exists('htmlHeader')) htmlHeader($data);
    
    ?>
</head><body>
    <main><?php

    $crumb = htmlRender($data);
	
    ?></main>
    <header><a href="?"><h1>SigLib</h1></a><?=$crumb?><span><?= loginButton() ?></span></header>
    <footer><a href="https://github.com/DosMike">SigLib on GitHub</a> ♥ 22w52a - BETA</footer>
    <div class="upload">
        <div class="progressbar"><div class="progress"></div></div>
    </div>
</body></html><?
}