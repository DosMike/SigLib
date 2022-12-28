<?php

function ratelimit_auto($authHeader='') {
    $key = ratelimit_getkey($authHeader);
    if ($key === false) {
        http_response_code(401);
        die("Unauthorized\nCould not authenticate you\n");
    } elseif (count($key)==0) {
        http_response_code(403);
        die("Forbidden\nYou do not have permission to do this\n");
    }
    if (!ratelimit_checkandtake($key)) {
        http_response_code(429);
        die("Too Many Requests\nBucket Info: Remaining=".$key['Remaining'].", Next Reset=".date("Y-m-d H:i:s", $key['NextReset']).", Next Request=".date("Y-m-d H:i:s", $key['LastRequest']+1));
    }
    return $key;
}

function ratelimit_getkey($authHeader='') {
    global $sqltp;
    sqlQuery("DELETE FROM `${sqltp}rates` WHERE `NextReset` <= NOW()");
    $key = '';
    $type = '';
    if (!empty($authHeader)) {
        if (str_starts_with($authHeader, 'Basic ') && strlen($authHeader)>6) {
            $key = substr($authHeader,6);
            $type = 'api';
        } else return false; //otherwise gives the wrong impression of arbitrary values being accepted
    }
    if (empty($type) && isset($_SESSION['steamid'])) {
        $key = $_SESSION['steamid'];
        $type = 'login';
    }
    if (empty($type)) {
        $key = $_SERVER['REMOTE_ADDR'];
        $type = 'ip';
    }
    if (empty($type)) return false;

    $power = -10;
    $userid = 0;
    $steamid = null;
    $anonymity = 0; //loading this in auth is only important for the settings page
    if ($type == 'api') {
        $dec = base64_decode($key,true);
        if ($dec === false) return false; // not base64
        $up = explode(':',$dec);
        if (count($up)!=2 && !empty($up[0]) && !empty($up[1])) return false; // not user:passwd
        $steamid = $up[0];
        sqlSelect('users', ['ID','Powerlevel','Anonymity'], ['SteamID'=>sqlEscape($up[0]), 'API_KEY'=>sqlEscape($up[1])]);
        if (($row=sqlGetRow())!==null) {
            $power = $row['Powerlevel'];
            $userid = $row['ID'];
            $anonymity = $row['Anonymity'];
        }
        else return false; //signal: who dis?
        sqlFreeResult();
    } else if ($type == 'login') {
        $steamid = $key;
        sqlSelect('users', ['ID','Powerlevel','Anonymity'], ['SteamID'=>sqlEscape($key)]);
        if (($row=sqlGetRow())!==null) {
            $power = $row['Powerlevel'];
            $userid = $row['ID'];
            $anonymity = $row['Anonymity'];
        }
        else return false; //signal: no such user
        sqlFreeResult();
    } else if ($type == 'ip') {
        $power = 0;
    }
    if ($power < 0) return []; //signal: yes but actually no

    sqlSelect('rates', ['Bucket', 'NextReset', 'LastRequest'], ['Authentication'=>sqlEscape($key)]);
    if (($row = sqlGetRow())!==null) {
        $result=[
            'Remaining' => intval($row['Bucket']),
            'NextReset' => strtotime($row['NextReset']),
            'LastRequest' => strtotime($row['LastRequest']),
        ];
    } elseif ($type == 'api') {
        $result=[
            'Remaining' => 120,
            'NextReset' => time()+60,
            'LastRequest' => 0,
        ];
    } elseif ($type == 'ip') {
        $result=[
            'Remaining' => 60,
            'NextReset' => time()+60,
            'LastRequest' => 0,
        ];
    } elseif ($type == 'login') {
        $result=[
            'Remaining' => 180,
            'NextReset' => time()+60,
            'LastRequest' => 0,
        ];
    } else return false;
    sqlFreeResult();
    $result['DBID'] = $userid;
    $result['SteamID'] = $steamid;
    $result['Type'] = $type;
    $result['Powerlevel'] = $power;
    $result['Anonymity'] = $anonymity;
    $result['Authentication'] = sqlEscape($key);
    return $result;
}

function ratelimit_checkandtake(&$key) {
    $limited = ($key['Powerlevel']<0) || ($key['Type'] == 'ip' && time()-$key['LastRequest']<1) || ($key['Remaining']<1);
    if ($limited) return false;
    $key['Remaining']-=1;
    sqlUpsert('rates', ['Authentication'=>$key['Authentication'],'Bucket'=>$key['Remaining'],'NextReset'=>date("Y-m-d H:i:s", $key['NextReset'])], ['Bucket']);
    return true;
}