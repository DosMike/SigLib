<?php

function htmlHeader($data) {?>
    <script src="script/user.js"></script><?
}

function makeBreakable($sig) {
    return preg_replace('/([a-z])([A-Z])/','$1<wbr>$2',str_replace(['::','\\'],['::<wbr>','\\<wbr>'],htmlspecialchars($sig)));
}
function optDate($date) {
    if (empty($date) || !is_numeric($date)) return "-?-";
    return date('Y-m-d', $date);
}

function htmlRender($data) {
    global $Authorization;
    global $games;
    ?><content class="comment profile">
    
    <p class="profileBanner">
        <a class="userHead"<? if (!empty($data['steamid'])) { echo ' href="https://steamcommunity.com/profiles/'.$data['steamid'].'"';}?>>
            <?=$data['username']?><img class="avatar" src="<?=$data['avatarurl']?>" />
        </a>
        <span>Fist seen: <?=$data['first_seen']?><br>Power Level: <?=$data['powerlevel']?></span>
<? if (!empty($Authorization['DBID']) && intval($data['id']) == intval($Authorization['DBID'])) { ?>
        <a href="action.php?do=logout"><button>Log out</button></a>
<? } ?>
    </p>

<? if (!empty($Authorization['DBID']) && intval($data['id']) == intval($Authorization['DBID'])) {
    $hasKey = intval($data['apikey']??"0")!=0;
    $styleDestroy = $hasKey ? '' : 'display:none;' ;
    ?>
    <h2>Profile</h2>
    <form id="privacy" action="action.php?do=settings" method="POST">
    <p><label><input type="radio" name="privacy" value="0"<?=$Authorization['Anonymity']==0?' checked':''?>/> Use and display Steam inforamtion for your profile.</label><br>This includes: SteamID, Steam Display Name and Steam Avatar URL.</p>
    <p><label><input type="radio" name="privacy" value="1"<?=$Authorization['Anonymity']==1||$Authorization['Anonymity']==2?' checked':''?>/> Custom Profile.</label><br>
        <label>Display name: <input type="text" name="nick" value="<?=$_SESSION["username"]?>"/></label><label><input type="checkbox" name="usesteam" value="yes"<?=$Authorization['Anonymity']==1?' checked':''?>/> Show Steam data?</label>
    </p>
    <p><label><input type="radio" name="privacy" value="3"<?=$Authorization['Anonymity']==3?' checked':''?>/> Anonymous profile.</label><br>You will apear as &quot;Anonymous&quot;.</p>
    <p>If you don't show Steam data or pick anonymous, your Steam profile will not be linked and your Steam avatar will not be loaded. For the purpose of logging in, your SteamID has to be saved anyways.</p>
    <p>Changeing this setting might only fully apply after loggin out and back in.</p>
    <p><input type="submit" value="Save Settings"/></p>
    </form>
    <h2>APIKey</h2>
    <p><button id="bnApiKeyGenerate">Generate</button><button id="bnApiKeyDestroy"style="<?=$styleDestroy?>color:red;">Destroy</button></p>
    <h2 style="color:#c80000;">Danger Zone</h2>
    <p><button class="critical" id="bnClearProfile">Delete All Conributions</button></p>
<? } ?>
    
    <h2>Duped Symbols</h2>
    <p><table><thead><tr><th>Symbol</th><th>Library</th><th>First Seen</th><th>Duped At</th></tr></thead><tbody><?
    foreach ($data['symbols'] as $symbol) { ?>
        <tr><td><a href="?p=sym&id=<?=$symbol['id']?>"><?=makeBreakable($symbol['name'])?></a></td><td><?=htmlspecialchars($symbol['library'])?></td><td><?=optDate($symbol['first_seen'])?></td><td><?=optDate($symbol['duped_at'])?></td></tr><?
    }
    ?></tbody></table></p>
    
    <h2>Duped Values</h2>
    <p><table><thead><tr><th>Value</th><th>Symbol</th><th>Version</th><th>First Seen</th><th>Duped At</th></tr></thead><tbody><?
    foreach ($data['values'] as $value) { ?>
        <tr><td><?=makeBreakable($value['value'])?></td><td><a href="?p=sym&id=<?=$value['symbol']['id']?>"><?=makeBreakable($value['symbol']['name'])?></a> <?=htmlspecialchars($value['symbol']['library'])?></td><td><?=(array_key_exists($value['game'],$games) ? $games[$value['game']] : $value['game']).' '.$value['build'].' on '.$value['platform']?></td><td><?=optDate($symbol['first_seen'])?></td><td><?=optDate($symbol['duped_at'])?></td></tr><?
    }
    ?></tbody></table></p>
    
    <h2>Comments on Symbols</h2>
    <p><table><thead><tr><th></th><th>Symbol</th><th>Posted At</th></tr></thead><tbody><?
    foreach ($data['symbol_comments'] as $comment) { ?>
        <tr><td>#<?=$comment['id']?></td><td><a href="?p=sym&id=<?=$comment['symbol']['id']?>"><?=makeBreakable($comment['symbol']['name'])?></a> <?=htmlspecialchars($comment['symbol']['library'])?></td><td><?=optDate($comment['posted_at'])?></td></tr><tr><td colspan="3"><?=htmlspecialchars($comment['message'])?></td></tr><?
    }
    ?></tbody></table></p>

    <h2>Comments on Values</h2>
    <p><table><thead><tr><th></th><th>Symbol / Value</th><th>Posted At</th></tr></thead><tbody><?
    foreach ($data['value_comments'] as $comment) { ?>
        <tr><td>#<?=$comment['id']?></td><td><?=makeBreakable($comment['value']['value'])?> in <a href="?p=sym&id=<?=$comment['value']['symbol']['id']?>"><?=makeBreakable($comment['value']['symbol']['name'])?></a> <?=htmlspecialchars($comment['value']['symbol']['library'].' for '.$comment['value']['game'].' '.$comment['value']['build'].' '.$comment['value']['platform'])?></td><td><?=optDate($comment['posted_at'])?></td></tr><tr><td colspan="3"><?=htmlspecialchars($comment['message'])?></td></tr><?
    }
    ?></tbody></table></p>

</content><?
return "User ".$data['username'];
}