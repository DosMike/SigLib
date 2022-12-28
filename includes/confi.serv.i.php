<?php
// This file is required for dbcon.i.php in order to run properly.
// By extracting this information configuration can be done without
// having to edit the functional part, and updates can ba applied
// without removing database configurations.

// These informations will be provided by your hoster, just fill them in
// corresponding to the variable names
$sqlcon_host = 'localhost'; 		// Host Name
$sqlcon_user = 'root';				// SQL User Account Name
$sqlcon_pass = '';	// SQL User Account Password
$sqlcon_dBas = '';				// Database name (most providers give you one or two fixed ones)

// This prefix will be automatically added to $Table parameters in the library.
// Let's say you have use the tables Students and Grades in your code.
// If you only run one application you can leave this empty, so the table names
// used by the library will be extactly those names:
// database.Students and database.Grades.
// If you're running multiple applications on the same database you can simply
// add a prefix (e.g. 'myApp_') without changing any code but the tables used
// will change to e.g. the following (remember to include the prefix to table
// names when creating your tables using phpMyAdmin or similar):
// database.myApp_Students and databse.myApp_Grades
$sqltp = 'siglib_';

// This will prefix all redirects the application makes. This is most useful 
// if you host this application in a subdirectory on your web-server.
// If you put this application in your web root, use '' as value.
$webroot = 'siglib';

// This is the name of the session to use. You need to change this if you plan
// on running multiple instances of this application on the same web server.
// If not you can leave it as is.
$session_name = 'siglib';

// Since write access is only granted to logged in users with positive power level,
// the steam API Key associated with your steam id and the website domain has to be
// generated and assigned here.
// You can get/generate API keys here: https://steamcommunity.com/dev/apikey
$steam_api_key = '';

// this is a list to name all games we know. This could be a table as well but is
// unlike to change a lot. The keys on the left are the internal names used for
// GameData files by SourceMod, the values on the right are human readable names.
$games = [
    '#default'=>'Generic',
    '$INSURGENCY'=>'Insurgency (2014)',
    '$Insurgency'=>'Insurgency (Mod)',
    'FortressForever'=>'Fortress Forever',
    'NeotokyoSource'=>'Neotokyo Source',
    'RnLBeta'=>'Resistance & Liberation Beta',
    // 'ag2'=>'Adrenalin Gamer 2', // dead?
    'ageofchivalry'=>'Age of Chivalry',
    //'berimbau'=>'Berimbau aka Blade Symphony aka Versus: Blind Edge', //dead?
    'bg2'=>'Battle Grounds 2',
    'bms'=>'Black Mesa: Source',
    'csgo'=>'Counter-Strike: Global Offensive',
    'cspromod'=>'CS Promod',
    'cstrike'=>'Counter-Strike: Source',
    'dinodday'=>'Dino D-Day',
    'diprip'=>'D.I.P.R.I.P.',
    'dod'=>'Day of Defeat: Source',
    'doi'=>'Day of Infamy',
    'empires'=>'Empires Mod',
    'esmod'=>'Eternal Silence Mod',
    // 'eye'=>'', //no idea
    'fas'=>'Firearms: Source',
    'fof'=>'Fistful of Frags',
    'gesource'=>'GoldenEye: Source',
    'hl2ctf'=>'Half-Life 2 Capture the Flag',
    'hl2mp'=>'Half-Life 2 Deathmatch',
    // 'ios'=>'IOSoccer', //old?
    'iosoccer'=>'IOSoccer',
    'kz'=>'Kreedz Climb',
    'left4dead'=>'Left 4 Dead',
    'left4dead2'=>'Left 4 Dead 2',
    'nmrih'=>'No More Room in Hell',
    'nucleardawn'=>'Nuclear Dawn',
    'obsidian'=>'Obsidian Conflict',
    'pvkii'=>'Pirates, Vikings and Knights II',
    'reactivedrop'=>'Alien Swarm: Reactive Drop',
    'sourceforts'=>'SourceForts',
    'swarm'=>'Alien Swarm',
    'synergy'=>'Synergy',
    'tf'=>'Team Fortress 2',
    // 'zombie_master'=>'Zombie Master', //dead?
    'zps'=>'Zombie Panic! Source',
];