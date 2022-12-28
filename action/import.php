<?php
// This file will try to import a gamedata file into the database.
// Requirements: 
//   Active database connection <- from action.php
//   User session or API authorization with Power > 0 <- prepped by action.php
//   Valid gamedata file in user uploads
//   Server version in args

if (empty($fromAction)) {
    http_response_code(400);
    die("Illegal direct invocation");
}

function endWith($data, $code=200) {
    http_response_code($code);
    output('GameDataImport', $data);
    die;
}

if (empty($Authorization) || intval($Authorization['Powerlevel']) <= 0 || intval($Authorization['DBID']) <= 0) { 
    endWith(['Error'=>'You do not have permission to do this. Please log in or use your API Key. If you already are, your write access might have been revoked.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    endWith(['Error'=>'Ay this is a PHP application, I can only cleanly get uploads with POST requests'], 405);
}

if (empty($_FILES)) {
    endWith(['Error'=>'Your file got lost on it\'s way'],404);
}

if (!empty($_FILES['gamedata']['error'])) {
    endWith(['Error'=>$_FILEs['gamedata']['error']],400);
}

if (intval($_FILES['gamedata']['size']) > 5242880 || intval($_FILES['gamedata']['size']) <= 0) {
    endWith(['Error'=>'File is too large'],413);
}

require "includes/vdfparser.php";
$data = parse_vdfkv(fopen($_FILES['gamedata']['tmp_name'],"r"));
if ($data != false && isset($data['Games'])) $data=$data['Games'];
else endWith(['Error'=>'Unable to read GameData file'], 400);

function pushSymbol($symbol, $library, $user) {
    global $sqltp;
    global $sqlcon;

    //validate input - signature formats:
    // asd - globals
    // asd() - util funcs
    // asd::asd - class members
    // asd::asd() - functions
    // asd::asd()::asd - function members
    // asd(asd) - value of global type, e.g. sizeof(something)
    // asd(asd::asd) - value of scoped type
    // function brackets can contain any mix of symbols, spaces, template and grouping characters, in case someone writes a full sig
    if (!preg_match("/^\\w+(?:(?:::\\w+[(][\\w :,<>()*&]*[)])?(?:::\\w+)?|\\((?:\\w+(?:::\\w+)?)?\\))$/",$symbol)) {
        // echo "Failed Syntax Check on $symbol";
        return false;
    }

    if ($user < 1) return false;

    $sym = "'".sqlEscape($symbol)."'";
    $lib = 'NULL';
    if ($library != null) $lib = "'".sqlEscape($library)."'";
    
    //insert the symbol or get the current id
    $result = sqlQuery("INSERT INTO ${sqltp}symbols (`Symbol`,`Library`) VALUES ($sym, $lib) ON DUPLICATE KEY UPDATE `ID`=LAST_INSERT_ID(`ID`);");
    if ($result === false) {
        // echo "Could not insert $symbol";
        return false; //error
    }
    $symID = mysqli_insert_id($sqlcon);
    if ($symID == 0) {
        // echo "Failed to get SYM ID";
        return false; //error
    }

    //register the ownership
    sqlQuery("INSERT IGNORE INTO ${sqltp}user_symbols (`User`,`Symbol`) VALUES ($user, $symID);");
    //if the insert worked we have a trigger that counts up the duplicates in the symbols table

    return $symID; //give back the symbol ID
}

function pushValue($symID, $game, $version, $platform, $value, $user) {
    global $sqltp;
    global $sqlcon;
    global $games;

    //validate input - value formats:
    // 123 - offsets
    // @asdasd - mangled names form gcc? (@prefix)
    // \xHH* - bytes / raw signature
    if (!preg_match("/^([0-9]+|@\\w+|(\\\\x[0-9a-fA-F]{2}|[*])+)$/", $value)) {
        // echo "Failed Syntax Check on $value";
        return false;
    }
    
    if ($symID < 1 || $user < 1) return false;
    if (!array_key_exists($game, $games)) return false;
    if ($game == '#default') $version = '';
    elseif (empty(trim($version))) return false;
    $version = "'".sqlEscape($version)."'";
    $game = "'".sqlEscape($game)."'";
    $platform = strtolower($platform);
    if ($platform == 'windows') $plat = 0;
    elseif ($platform == 'linux') $plat = 1;
    elseif ($platform == 'mac') $plat = 2;
    else return false; //not supported
    $value = "'".sqlEscape($value)."'";

    //insert new value for the symbol or get the current id
    $result = sqlQuery("INSERT INTO `${sqltp}values` (`Symbol`,`Game`,`Version`,`Platform`,`Value`) VALUES ($symID, $game, $version, $plat, $value) ON DUPLICATE KEY UPDATE `ID`=LAST_INSERT_ID(`ID`);");
    if ($result === false) {
        // echo "Could not insert $symID : $value";
        return false; //error
    }
    $valID = mysqli_insert_id($sqlcon);
    if ($valID == 0) {
        // echo "Failed to get VAL ID";
        return false; //error
    }

    //register the ownership
    sqlQuery("INSERT IGNORE INTO ${sqltp}user_values (`User`,`Value`) VALUES ($user, $valID);");
    //if the insert worked we have a trigger that counts up the duplicates in the values table

    return $valID; //give back the value ID
}

//collect #default offsets and signatures
$insert=[];

$dbid = $Authorization['DBID'];

foreach (array_keys($games) as $game) {
    if ($game == '#default') {
        $gameversion = '';
    } else {
        if (empty($data[$game])) continue;
        $gameversion = $_POST[$game.'_version'];
        if (empty(trim($gameversion))) continue;
    }
    $insert[$game] = [
        'offsets'=>[
            'windows'=>['imported'=>[],'rejected'=>[]],
            'linux'=>['imported'=>[],'rejected'=>[]],
            'mac'=>['imported'=>[],'rejected'=>[]],
            'rejected'=>[],
        ],
        'signatures'=>[
            'windows'=>['imported'=>[],'rejected'=>[]],
            'linux'=>['imported'=>[],'rejected'=>[]],
            'mac'=>['imported'=>[],'rejected'=>[]],
            'rejected'=>[],
        ],
    ];

    if (isset($data[$game])) {
        $defaults = $data[$game];
        foreach(['Offsets'=>false,'Signatures'=>true] as $group=>$hasLib) {
            if (isset($defaults[$group])) {
                $offsets = $defaults[$group];
                $group = strtolower($group); //used lowercase for output

                foreach ($offsets as $symbol=>$symvalue) {
                    if ($hasLib) {
                        $lib = $symvalue['library']??'';
                        if (empty($lib)) { $insert[$game][$group]['rejected'][]=$symbol; continue; }
                    } else {
                        $lib = null;
                    }

                    $sid = pushSymbol($symbol, $lib, $dbid);
                    if ($sid === false) { $insert[$game][$group]['rejected'][]=$symbol; continue; }

                    foreach (['windows','linux','mac'] as $platform) {
                        if (empty($symvalue[$platform])) continue;
                        if (pushValue($sid, $game, $gameversion, $platform, $symvalue[$platform], $dbid)) {
                            $insert[$game][$group][$platform]['imported'][]=$symbol;
                        } else {
                            $insert[$game][$group][$platform]['rejected'][]=$symbol;
                        }
                    }
                }
            }
        }
    }
}

endWith($insert);