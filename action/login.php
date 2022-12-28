<?php
require_once("includes/authmain.php");

if (empty($fromAction)) die("Illegal direct invocation");

function jcurl($at) {
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, $at);
	$result = curl_exec($ch);
	curl_close($ch);
	return json_decode($result, true);
}

if(!$auth->IsUserLoggedIn())
{
    header("Location: ".$auth->GetLoginURL());
}
else
{   
    $newacc = false;
    sqlSelect('users', ['ID','DisplayName','Anonymity','Powerlevel'], ['SteamID'=>$auth->SteamID]);
    if (($row=sqlGetRow())!=null) {
        $anonymity = $row['Anonymity'];
        $_SESSION["username"] = $row['DisplayName'];
        $_SESSION["dbid"] = $row['ID'];
    } else {
        $newacc = true;
        $anonymity = 0;
    }

    $json = jcurl("https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=".$steam_api_key."&steamids=".$auth->SteamID);

    $_SESSION["username"] = htmlspecialchars($json['response']['players'][0]['personaname']);
    $_SESSION["avatar"] = $json['response']['players'][0]['avatarmedium'];

    if ($anonymity <= 1) { // full or "custom nick"
        
        if ($anonymity==0) {
            $update = ['DisplayName', 'AvatarURL'];
        } else {
            $update = ['AvatarURL'];
        }
    
        if ($newacc) {
            $_SESSION["dbid"] = sqlInsert('users', ['SteamID'=>$auth->SteamID,'DisplayName'=>$_SESSION["username"],'AvatarURL'=>$_SESSION["avatar"]]);
        } else {
            sqlUpsert('users', ['SteamID'=>$auth->SteamID,'DisplayName'=>$_SESSION["username"],'AvatarURL'=>$_SESSION["avatar"]], $update);
        }

    }
    
    header("Location: /${webroot}");
}