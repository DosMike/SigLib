<?php

function process() {
    global $sqltp;
    global $Authorization;

    if (empty($_GET['name']) && empty($_GET['id'])) {
        return ['Error'=>'Invalid Symbol', 'HttpCode'=>400];
    } else if (empty($_GET['id'])) {
        $symselect = ['Symbol'=>sqlEscape($_GET['name'])];
    } else {
        $symselect = ['ID'=>intval($_GET['id'])];
    }

    if (sqlSelect('symbols', ['ID','Symbol','Library','Rating','Dupes','Created_At'], $symselect)===false) {
        return ['Error'=>'Could not fetch Symbol', 'HttpCode'=>404];
    }
    if (($row=sqlGetRow()) != null) {
        $symbol = [
            'id' => $row['ID'],
            'symbol' => $row['Symbol'],
            'library' => $row['Library'],
            'score' => intval($row['Rating']),
            'dupes' => intval($row['Dupes']),
            'first_seen' => strtotime($row['Created_At']),
        ];
    } else {
        return ['Error'=>'Could not find Symbol', 'HttpCode'=>404];
    }
    sqlFreeResult();
    $symID = $symbol['id'];

    sqlQuery("SELECT u.`ID`, u.`SteamID`, u.`DisplayName`, u.`AvatarURL`, u.`Anonymity` FROM {$sqltp}users AS u
    LEFT JOIN `{$sqltp}user_symbols` as us ON u.`ID` = us.`User`
    WHERE us.`Symbol` = $symID
    ORDER BY u.`DisplayName` ASC");
    $symbol['dupers']=[];
    while (($row = sqlGetRow()) !== null) {
        anonUserFilter($row);
        $symbol['dupers'][] = [
            'id'=>$row['ID'],
            'steamid'=>$row['SteamID'],
            'name'=>$row['DisplayName'],
            'avatarurl'=>$row['AvatarURL'],
        ];
    }

    sqlQuery("SELECT sc.`ID`, sc.`Message`, sc.`Created_At`, u.`ID` as UserID, u.`DisplayName`, u.`SteamID`, u.`AvatarURL`, u.`Anonymity` FROM {$sqltp}symbol_comments AS sc
    LEFT JOIN `{$sqltp}users` AS u ON u.`ID` = sc.`Created_By`
    WHERE sc.`Symbol` = $symID
    ORDER BY sc.`Created_At` DESC");
    $symbol['comments']=[];
    while (($row = sqlGetRow()) !== null) {
        anonUserFilter($row, $kID='UserID');
        $symbol['comments'][] = [
            'id'=>$row['ID'],
            'created_at'=>strtotime($row['Created_At']),
            'message'=>$row['Message'],
            'author'=>[
                'id'=>$row['UserID'],
                'name'=>$row['DisplayName'],
                'steamid'=>$row['SteamID'],
                'avatarurl'=>$row['AvatarURL'],
            ]
        ];
    }
    if (isset($Authorization) && !empty($Authorization['DBID'])) {
        sqlQuery("SELECT `Rating` FROM {$sqltp}symbol_ratings
        WHERE `Symbol` = $symID AND `Created_By` = ".$Authorization['DBID']);
        $symbol['user_rating']=0;
        if (($row = sqlGetRow()) !== null) {
            $symbol['user_rating'] = $row['Rating'];
        }
    } else {
        $symbol['user_rating']='-';
    }

    sqlSelect('values', ['ID', 'Game', 'Version', 'Platform', 'Value', 'Rating', 'Dupes', 'Created_At'], ['Symbol' => $symID], '`Version` DESC, `Created_At` DESC');
    $symbol['values']=[];
    while (($row = sqlGetRow()) !== null) {
        $symbol['values'][]=[
            'id'=>$row['ID'],
            'game'=>$row['Game'],
            'version'=>$row['Version'],
            'platform'=>['Windows','Linux','MAC'][intval($row['Platform'])],
            'value'=>$row['Value'],
            'score'=>$row['Rating'],
            'dupes'=>$row['Dupes'],
            'first_seen'=>strtotime($row['Created_At']),
        ];
    }
    sqlFreeResult();

    foreach($symbol['values'] as &$value) {
        $valID = $value['id'];

        sqlQuery("SELECT u.`ID`, u.`SteamID`, u.`DisplayName`, u.`AvatarURL`, u.`Anonymity` FROM {$sqltp}users AS u
        LEFT JOIN `{$sqltp}user_values` as uv ON u.`ID` = uv.`User`
        WHERE uv.`Value` = $valID
        ORDER BY u.`DisplayName` ASC");
        $value['dupers']=[];
        while (($row = sqlGetRow()) !== null) {
            anonUserFilter($row);
            $value['dupers'][] = [
                'id'=>$row['ID'],
                'steamid'=>$row['SteamID'],
                'name'=>$row['DisplayName'],
                'avatarurl'=>$row['AvatarURL'],
            ];
        }

        sqlQuery("SELECT vc.`ID`, vc.`Message`, vc.`Created_At`, u.`ID`as UserID, u.`DisplayName`, u.`SteamID`, u.`AvatarURL`, u.`Anonymity` FROM {$sqltp}value_comments AS vc
        LEFT JOIN `{$sqltp}users` AS u ON u.`ID` = vc.`Created_By`
        WHERE vc.`Value` = $valID
        ORDER BY vc.`Created_At` DESC");
        $value['comments']=[];
        while(($row = sqlGetRow()) !== null) {
            anonUserFilter($row, $kID='UserID');
            $value['comments'][]=[
                'id'=>$row['ID'],
                'created_at'=>strtotime($row['Created_At']),
                'message'=>$row['Message'],
                'author'=>[
                    'id'=>$row['UserID'],
                    'name'=>$row['DisplayName'],
                    'steamid'=>$row['SteamID'],
                    'avatarurl'=>$row['AvatarURL'],
                ]
            ];
        }

        if (isset($Authorization) && !empty($Authorization['DBID'])) {
            sqlQuery("SELECT `Rating` FROM {$sqltp}value_ratings
            WHERE `Value` = $valID AND `Created_By` = ".$Authorization['DBID']);
            $value['user_rating']=0;
            if (($row=sqlGetRow())!==null) {
                $value['user_rating'] = $row['Rating'];
            }
        } else {
            $value['user_rating']='-';
        }
    }

    return $symbol;
}