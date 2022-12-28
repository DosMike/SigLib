<?php

function process() {
    if (!empty($_GET['id'])) {
        $forid = intval($_GET['id']);
        if ($forid != 0) $byidtype = 'ID';
    } elseif (!empty($_GET['steamid'])) {
        $forid = intval($_GET['steamid']);
        if ($forid != 0) $byidtype = 'SteamID';
    } elseif (!empty($_SESSION['steamid'])) {
        $forid = intval($_SESSION['steamid']);
        if ($forid != 0) $byidtype = 'SteamID';
    }
    if (!isset($byidtype)) {
        return ['Error'=>'Missing ID for user', 'HttpCode'=>400];
    }

    $result = ['steamid'=>0,'username'=>'','avatarurl'=>'','first_seen'=>'NEVER','powerlevel'=>'0','symbols'=>[],'values'=>[],'symbol_comments'=>[],'value_comments'=>[]];
    $dbid = -1;
    sqlSelect('users', '*', "`$byidtype`=$forid");
    if (($row=sqlGetRow())!=null) {
        anonUserFilter($row);
        $dbid = $row['ID'];
        $result['id']=$row['ID'];
        $result['steamid']=$row['SteamID'];
        $result['username']=$row['DisplayName'];
        $result['avatarurl']=$row['AvatarURL'];
        $result['first_seen']=$row['First_Login'];
        $result['powerlevel']=$row['Powerlevel'];
        if (isset($_SESSION['steamid']) && $forid == $_SESSION['steamid']) {
            $result['apikey']=(empty($row['API_Key'])?0:1);
        }
    }
    sqlFreeResult();
    if ($dbid < 0) {
        return ['Error'=>'Invalid user ID', 'HttpCode'=>400];
    }

    global $sqltp;
    sqlQuery("SELECT s.ID, s.Symbol, s.Library, s.Created_At AS First_Seen, us.Created_At AS Duped_At FROM ${sqltp}user_symbols AS us
    LEFT JOIN ${sqltp}symbols as s ON us.Symbol = s.ID
    WHERE us.User = ${dbid}
    ORDER BY Duped_At DESC");
    while (($row=sqlGetRow())!=null) {
        $result['symbols'][]=[
            'id'       => $row['ID'],
            'name'       => $row['Symbol'],
            'library'    => ($row['Library']??'')?:'<Offset>',
            'first_seen' => strtotime($row['First_Seen']),
            'duped_at'   => strtotime($row['Duped_At']),
        ];
    }
    sqlFreeResult();

    sqlQuery("SELECT s.ID as SymbolID, s.Symbol, s.Library, v.ID as ValueID, v.Game, v.Version, v.Platform, v.Value, v.Created_At AS First_Seen, uv.Created_At AS Duped_At FROM ${sqltp}user_values AS uv
    LEFT JOIN `${sqltp}values` AS v ON uv.Value = v.ID
    LEFT JOIN ${sqltp}symbols AS s ON s.ID = v.Symbol
    WHERE uv.User = ${dbid}
    ORDER BY Duped_At DESC");
    $platNames=['Windows','Linux','Mac'];
    while (($row=sqlGetRow())!=null) {
        $result['values'][]=[
            'id'         => $row['ValueID'],
            'symbol'     => [
                'id'         => $row['SymbolID'],
                'name'       => $row['Symbol'],
                'library'    => ($row['Library']??'')?:'<Offset>',
            ],
            'game'      => $row['Game'],
            'build'      => $row['Version'],
            'platform'   => $platNames[$row['Platform']],
            'value'      => $row['Value'],
            'first_seen' => strtotime($row['First_Seen']),
            'duped_at'   => strtotime($row['Duped_At']),
        ];
    }
    sqlFreeResult();

    sqlQuery("SELECT s.ID as SymbolID, s.Symbol, s.Library, s.Created_At AS First_Seen, sc.ID as CommentID, sc.Created_At AS Posted_At, sc.Message FROM ${sqltp}symbol_comments as sc
    LEFT JOIN ${sqltp}symbols AS s ON s.ID = sc.Symbol
    WHERE sc.Created_By = ${dbid}
    ORDER BY sc.Created_At DESC");
    while (($row=sqlGetRow())!=null) {
        $result['symbol_comments'][]=[
            'id'         => $row['CommentID'],
            'symbol'     => [
                'id'         => $row['SymbolID'],
                'name'       => $row['Symbol'],
                'library'    => ($row['Library']??'')?:'<Offset>',
            ],
            'message'    => $row['Message'],
            'posted_at'  => strtotime($row['Posted_At']),
        ];
    }
    sqlFreeResult();

    sqlQuery("SELECT s.ID as SymbolID, s.Symbol, s.Library, v.ID as ValueID, v.Game, v.Version, v.Platform, v.Value, v.Created_At AS First_Seen, vc.ID as CommentID, vc.Created_At AS Posted_At, vc.Message FROM ${sqltp}value_comments as vc
    LEFT JOIN `${sqltp}values` AS v ON vc.Value = v.ID
    LEFT JOIN ${sqltp}symbols AS s ON s.ID = v.Symbol
    WHERE vc.Created_By = ${dbid}
    ORDER BY vc.Created_At DESC");
    while (($row=sqlGetRow())!=null) {
        $result['value_comments'][]=[
            'id'         => $row['CommentID'],
            'value'      => [
                'id'         => $row['ValueID'],
                'game'      => $row['Game'],
                'build'      => $row['Version'],
                'platform'   => $platNames[$row['Platform']],
                'value'      => $row['Value'],
                'symbol'     => [
                    'id'     => $row['SymbolID'],
                    'name'       => $row['Symbol'],
                    'library'    => ($row['Library']??'')?:'<Offset>',
                ],
            ],
            'message'    => $row['Message'],
            'posted_at'  => strtotime($row['Posted_At']),
        ];
    }
    sqlFreeResult();


    return $result;
}