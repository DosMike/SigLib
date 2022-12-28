<?php

function makeBreakable($sig) {
    return preg_replace('/([a-z])([A-Z])/','$1<wbr>$2',str_replace('::','::<wbr>',$sig));
}

function htmlRender($data) {?>
<div class="withsidebar">
    <div class="sidebar">
        <form method="GET" action="?">
            <label>Query:<br><input type="search" name="q" value="<?=$_GET['q']??''?>"/></label>
        </form>
        <br><p><a href="?p=upload">Upload GameData</a></p>
        <br><p>Namespaces:<ul><?
        $namespacecounts=[];
        foreach ($data as $symbol) {
            if (!preg_match("/^\\w+\\(/", $symbol['name'])) {
                $ns = explode('::',$symbol['name']);
                if (count($ns) == 2) $ns = $ns[0];
                elseif (count($ns) == 1) $ns = '';
                else continue;
                $namespacecounts[$ns]=($namespacecounts[$ns]??0)+1;
            }
        }
        ksort($namespacecounts, SORT_STRING);
        foreach ($namespacecounts as $namespace=>$count) {
            ?><li><a href="?q=<?=urlencode($namespace.'::')?>"><?=htmlspecialchars(empty($namespace)?'<GLOBAL>':$namespace)?> (<?=$count?>)</a><?
        }
        ?></ul>
        <br><br><p><a href="?p=help">Help</a></p>
    </div>
    <div class="symgrid">
    <span class="head">Symbol</span><span class="head">Library</span><span class="head">Dupes</span><span class="head">Score</span><span class="head">First Seen</span>
    <?php foreach ($data as $symbol) {?>
        <a class="entry" href="?p=sym&id=<?= urlencode($symbol['id']) ?>">
        <span><?=makeBreakable($symbol['name'])?></span><span><?=$symbol['library']?></span><span><?=$symbol['dupes']?></span><span><?=$symbol['score']?></span><span><?=date('Y-m-d H:i T',$symbol['first_seen'])?></span>
        <!--<span class="symdetails"></span>--></a>
    <?}?>
    </div>
</div>
<?
if (isset($_SESSION['debug']) && !empty($_SESSION['debug']['querytiming'])) {
    ?><center><small>Query executed in <?=$_SESSION['debug']['querytiming']?>ms</small></center><?
    unset($_SESSION['debug']['querytiming']);
} 
$crumb = empty($_GET['q'])?'::':$_GET['q'];
return $crumb;
}