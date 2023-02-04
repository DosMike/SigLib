<?php

function makeBreakable($value) {
    return preg_replace('/([a-z])([A-Z])/','$1<wbr>$2',str_replace('\\','\\<wbr>',$value));
}
function renderUserInline($userData) {
    if (empty($userData['steamid']) && empty($userData['id'])) {
    ?><a class="inlineuser deleted">Deleted <img /></a><?
    } else {
    ?><a class="inlineuser" href="?p=user&<?= !empty($userData['id']) ? "id=".$userData['id'] : "steamid=".$userData['steamid'] ?>" data-user="<?=$userData['id']?>"><?=$userData['name']?> <img src="<?=$userData['avatarurl']?>"/></a><?
    }
}
function didIDupe($dupers) {
    global $Authorization;
    if (empty($dupers)) return false;
    foreach ($dupers as $duper) {
        if ($duper['id'] == $Authorization['DBID']) return true;
    }
    return false;
}

function htmlHeader($data) {?>
    <script src="script/symbol.js"></script><?
    if (!empty($data['Error'])) {?>
    <script>back2front = <?= json_encode([ 'toast'=>$data['Error'] ]) ?></script><?
    }
}

function htmlRender($data) {
    global $Authorization;

    include "includes/Parsedown/Parsedown.php";
    $Parsedown = new Parsedown();
    $Parsedown->setSafeMode(true);

    // content start : symbol
    ?><content class="comment">

    <h2><?=$data['symbol']?> <small><? if (empty($data['library'])) { echo (preg_match('/^.+::.+\\(\\).*$/',$data['symbol']) ? "[vtable]" : "[offset]"); } else echo 'lib:'.$data['library']; ?></small></h2>
    <p><?
    if (isset($Authorization['Powerlevel']) && $Authorization['Powerlevel'] > 0) {
        $duped = didIDupe($data['dupers']);
        //disable dupe button by default, so you have to use gamedata. duping by button is still useful/doable to undo de-duping, until you refresh the page.
        ?><button id="bnDupe"<?= $duped ? ' class="in"' : ' disabled' ?> data-symbol="<?=$data['id']?>" title="<?=$duped?'De-duplicate':'Duplicate'?> Symbol">&#10697; Dupes | <?= $data['dupes']; ?></button><?
        ?><button id="bnRateUp"<? if ($data['user_rating']>0) echo ' class="in"'; ?> data-symbol="<?=$data['id']?>" title="Rate Up">&#x1F44D; Score | <?= $data['score']; ?></button><?
        ?><button id="bnRateDown"<? if ($data['user_rating']<0) echo ' class="in"'; ?> data-symbol="<?=$data['id']?>" title="Rate Down">&#x1F44E;</button><?
    } else {
        ?><button id="bnDupe" disabled>&#10697; Dupe | <?= $data['dupes']; ?></button><?
        ?><button id="bnRateUp" disabled>&#x1F44D; Score | <?= $data['score']; ?></button><?
        ?><button id="bnRateDown" disabled>&#x1F44E;</button><?
    }
    ?> First Seen: <span data-time="<?=$data['first_seen']?>"><?=date("Y-m-d H:i T", $data['first_seen'])?></span></p>
    <p class="dupeslist" data-target="s<?=$data['id']?>">Duped by: <? foreach ($data['dupers'] as $duper) { renderUserInline($duper); } ?></p>

    <? 
    if (!empty($_SESSION['steamid'])) {
        ?><form action="action.php?do=comment" method="POST" class="comment">
            <input type="hidden" name="type" value="symbol" />
            <input type="hidden" name="id" value="<?=$data['id']?>" />
            <textarea name="message"></textarea>
            <input type="submit" value="Post" />
        </form><?
    }
    ?><div class="commentgroup"><?
    if (empty($data['comments'])) {
        ?><p>No Comments Yet</p><?
    } else foreach ($data['comments'] as $comment) {
        ?><div class="comment"><span><?
        renderUserInline($comment['author']); 
        echo ' - '.date('Y-m-d H:i T', $comment['created_at']).' <small>#'.$comment['id'].'</small>';
        if (isset($Authorization['DBID']) && $comment['author']['id'] == $Authorization['DBID'] || $Authorization['Powerlevel']>=50) {
            ?><button class="critical" data-type="symbol" data-id="<?=$comment['id']?>">Delete</button><?
        }
        ?></span><span><?= $Parsedown->text($comment['message']); ?></span></div><?
    }
    //content end : symbol (after div)
    ?></div>
    <div class="valgrid">
    <span class="head">Value</span><span class="head">Platform</span><span class="head">Game</span><span class="head">Version</span><span class="head">Dupes</span><span class="head">Score</span><span class="head">First Seen</span>
    <?php foreach ($data['values'] as $value) {
        // content start : value
        ?>
        <span class="entry">
        <span><?=makeBreakable($value['value'])?></span><span><?=$value['platform']?></span><span><?=$value['game']?></span><span><?=$value['version']?></span><span name="dupes"><?=$value['dupes']?></span><span name="score"><?=$value['score']?></span><span><?= date('Y-m-d H:i T', $value['first_seen']) ?></span>
        <span class="valdetails"><?
            ?><p><?
            if (isset($Authorization['Powerlevel']) && $Authorization['Powerlevel'] > 0) {
                $duped = didIDupe($value['dupers']);
                ?><button id="bnDupe"<? if ($duped) echo ' class="in"'; ?> data-value="<?=$value['id']?>" title="<?=$duped?'De-duplicate':'Duplicate'?> Symbol">&#10697; Dupes | <?= $value['dupes']; ?></button><?
                ?><button id="bnRateUp"<? if ($value['user_rating']>0) echo ' class="in"'; ?> data-value="<?=$value['id']?>" title="Rate Up">&#x1F44D; Score | <?= $value['score']; ?></button><?
                ?><button id="bnRateDown"<? if ($value['user_rating']<0) echo ' class="in"'; ?> data-value="<?=$value['id']?>" title="Rate Down">&#x1F44E;</button><?
            } else {
                ?><button id="bnDupe" disabled>&#10697; Dupe | <?= $value['dupes']; ?></button><?
                ?><button id="bnRateUp" disabled>&#x1F44D; Score | <?= $value['score']; ?></button><?
                ?><button id="bnRateDown" disabled>&#x1F44E;</button><?
            }
            ?></p>
            <p class="dupeslist" data-target="v<?=$value['id']?>">Duped by: <? foreach ($value['dupers'] as $duper) { renderUserInline($duper); } ?></p>

            <? 
            if (!empty($_SESSION['steamid'])) {
                ?><form action="action.php?do=comment" method="POST" class="comment">
                    <input type="hidden" name="type" value="value" />
                    <input type="hidden" name="id" value="<?=$value['id']?>" />
                    <textarea name="message"></textarea>
                    <input type="submit" value="Post" />
                </form><?
            }
            ?><div class="commentgroup"><?
            if (empty($value['comments'])) {
                ?><p>No Comments Yet</p><?
            } else foreach ($value['comments'] as $comment) {
                ?><div class="comment"><span><?
                renderUserInline($comment['author']); 
                echo ' - '.date('Y-m-d H:i T', $comment['created_at']).' <small>#'.$comment['id'].'</small>';
                if (isset($Authorization['DBID']) && $comment['author']['id'] == $Authorization['DBID'] || $Authorization['Powerlevel']>=50) {
                    ?><button class="critical" data-type="value" data-id="<?=$comment['id']?>">Delete</button><?
                }
                ?></span><span><?= $Parsedown->text($comment['message']); ?></span></div><?
            }
            //close comment group, val details, entry
            ?></div></span></span>
    <?}
    //content end : value
    ?>
    </div>

</content><?
if (isset($data['Error'])) return 'Symbol';
else return $data['symbol'];
}