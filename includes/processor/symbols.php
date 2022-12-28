<?php

function process() {
    global $sqltp;
    $filters = [];
    $order = [];
    // filter parsing, some key=values, namespaces::symbols and normal keywords
    if (!empty($_GET['q'])) {
        $parts = explode(" ",str_replace("  "," ",trim($_GET['q'])));
        foreach ($parts as $filter) {
            $matches=[];
            if (preg_match('/^(\\w+)([=<>])([a-zA-Z0-9_.-]+)?(?:,(a|de)sc)?$/',$filter,$matches)) {
                $oname = $matches[1];
                $oop = $matches[2];
                $ovalue = $matches[3];
                $oflag = (count($matches)>4?$matches[4]:'');
                if ($oname == 'user') {
                    if ($oop == '=' && !empty($ovalue)) {
                        $filters[] = "`ID` IN ("
                            .getSqlQuerySelect('user_symbols','Symbol',"`User` IN ("
                                .getSqlQuerySelect('users','ID',"`DisplayName` LIKE '%".sqlEscape(str_replace(["%","_"],["|%","|_"],$ovalue))."%' ESCAPE '|' OR `SteamID` LIKE '%".sqlEscape(str_replace(["%","_"],["|%","|_"],$ovalue))."%' ESCAPE '|'")
                            .")")
                        .")";
                    }
                }
                elseif ($oname == 'dupes') {
                    if (!empty($ovalue)) {
                        $filters[] = "s.`Dupes` $oop ".intval($ovalue);
                    }
                    if (!empty($oflag)) {
                        $order[] = "s.`Dupes` ".$oflag;
                    }
                }
                elseif ($oname == 'score') {
                    if (!empty($ovalue)) {
                        $filters[] = "s.`Rating` $oop ".intval($ovalue);
                    }
                    if (!empty($oflag)) {
                        $order[] = "s.`Rating` ".$oflag;
                    }
                }
                elseif ($oname == 'game' && $oop == '=') {
                    if (!empty($ovalue)) {
                        $filters[] = "v.`Game`='".sqlEscape($ovalue)."'";
                    }
                }
                elseif ($oname == 'version') {
                    if (!empty($ovalue)) {
                        // versions are not quite numeric
                        $filters[] = "v.`Version` $oop '".sqlEscape(trim($ovalue))."'";
                    }
                }
                elseif ($oname == 'library' && $oop == '=') {
                    if (!empty($ovalue)) {
                        $filters[] = "s.`Library`='".sqlEscape($ovalue)."'";
                    }
                }
                elseif ($oname == 'created_at' && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$ovalue)) {
                    if (!empty($ovalue)) {
                        $filters[] = "s.`Created_At` $oop '".sqlEscape($ovalue)."'";
                    }
                    if (!empty($oflag)) {
                        $order[] = "s.`Created_At` ".$oflag;
                    }
                }
                elseif ($oname == 'touched_at' && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$ovalue)) {
                    if (!empty($ovalue)) {
                        $filters[] = "s.`Duped_At` $oop '".sqlEscape($ovalue)."'";
                    }
                    if (!empty($oflag)) {
                        $order[] = "s.`Duped_At` ".$oflag;
                    }
                }
                continue;
            }
            if (preg_match('/^(?:[*]?::\\w+|\\w+::\\w*)$/',$filter)) {
                $parts = explode("::", $filter);
                $leftwild = '%';
                if (empty($parts[0])) {
                    $leftwild = '';
                } else if ($parts[0]=='*') {
                    $leftwild = '%';
                    $filter = substr($filter, 1);
                }
                if (empty($parts[0]) && empty($parts[1])) continue; //wut
                $filters[] = "s.Symbol LIKE '".$leftwild.sqlEscape(str_replace(["%","_"],["|%","|_"],$filter))."%' ESCAPE '|'";
                continue;
            }
            $filters[] = "s.Symbol LIKE '%".sqlEscape(str_replace(["%","_"],["|%","|_"],$filter))."%' ESCAPE '|' OR "
                ."s.ID IN (".getSqlQuerySelect('values','Symbol',"`Value` LIKE '%".sqlEscape(str_replace(["%","_"],["|%","|_"],$filter))."%' ESCAPE '|'").")";
        }
    }
    if (count($order)==0) 
        $order[] = "s.Created_At DESC";

    // query database
    global $sqltp;
    $query = "SELECT distinct s.`ID`, s.`Symbol`, s.`Library`, s.`Created_At` AS First_Seen, s.`Rating`, s.`Dupes` FROM ${sqltp}symbols AS s
    LEFT JOIN `${sqltp}values` AS v ON s.`ID` = v.`Symbol`";
    if (count($filters)!=0) $query.=" WHERE ".implode(' AND ',$filters);
    if (count($order)!=0) $query.=" ORDER BY ".implode(', ',$order);
    $query.=" LIMIT 1000";
    
    $result=[];
    $timeme = hrtime(true);
    sqlQuery($query);
    $_SESSION['debug']['querytiming'] = (hrtime(true) - $timeme)/1e6; //nano -> millis
    while (($row=sqlGetRow())!=null) {
        $result[]=[
            'id'         => $row['ID'],
            'name'       => $row['Symbol'],
            'library'    => $row['Library']??'<Offset>',
            'score'      => $row['Rating'],
            'dupes'      => $row['Dupes'],
            'first_seen' => strtotime($row['First_Seen']),
        ];
    }
    sqlFreeResult();

    return $result;
}