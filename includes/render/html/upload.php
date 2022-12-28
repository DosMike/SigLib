<?php

function htmlHeader($data) {
    if (isset($_SESSION['steamid'])) { ?>
    <script src="script/vdfparser.js"></script>
    <script src="script/upload.js"></script><?
    }
}

function htmlRender($data) {
    global $games;
?><content class="comment">
    <h1>Upload a GameData file:</h1>
    <p>Here you can upload GameData files to batch import symbols and values. Please follow this naming scheme for offsets and signatures when uploading files:</p>
    <ul>
        <li><code>member</code> for global objects
        <li><code>function()</code> for global functions. UTIL_* function for example
        <li><code>namespace::member</code> for class members and alike, mostly offsets
        <li><code>namespace::function()</code> for member function signatures and vtable offsets
        <li><code>fun(name)</code> and <code>fun(namespace::name)</code> are allowed as well, for example if you store a type size in Offsets
    </ul>
    <p>Each part in the list above allows word characters (a-zA-Z0-9_).<br>
    This is not an enforced rule outside of pattern matching, but it will help others find the symbols they are looking for.</p>
    <p>For every game that is detected in the gamedata file, you will be prompted to give the game version. This should be the value of <code>ServerVersion</code> in the game's
    <code>steam.inf</code> file as positive integer. If there was no recent update the latest versions should get suggested as well (double click on a
    version input box on Windows, activate/tap on android, ...).</p>
    <? if (isset($_SESSION['steamid'])) { ?>
    <p><form action="javascript:" method="POST" style="margin: auto 5rem;min-width: 30rem;" id="uploadForm">
    <input type="hidden" name="MAX_FILE_SIZE" value="5242880" />
    <label>GameData File: <small>(5 MiB max)</small><br><p><input name="gamedata" type="file" accept="*.games.txt,*.txt,text/plain"/></p></label><br>

    <span>Game Versions:</span><br><p><?
    foreach ($games as $key=>$name) {?>
        <label class="gamever <?= trim($key,'$#') ?>" style="display:none;"><?=$name?> <?
        if ($key != '#default') { //default version uses first seen data as version
            ?><input type="text" name="<?=$key?>_version" autocomplete="off" list="<?=$key?>_knownVersions"/><datalist id="<?=$key?>_knownVersions"><?
            sqlSelect('version', ['Version','Created_At'], ['Game'=>sqlEscape($key)], 'Created_At DESC');
            while (($row=sqlGetRow())!==null) {
                ?><option value="<?= $row['Version'] ?>"><?= $row['Version'].' ('.date("Y-m-d", strtotime($row['Created_At'])).')' ?></option><?
            }
            sqlFreeResult();
            ?></datalist><? 
        }?></label><?
    }
    ?></p><br>
    <input type="submit" value="Upload" />
    </form></p>
    <? } else { ?>
    <h1>You need to be logged in to do this</h1>
    <? } ?>
</content><?
return "GameData Upload";
}