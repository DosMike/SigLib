# SigLib

A tool for sharing values for GameData files for Soruce-engine games.

This is a PHP application written for AMP stacks (It's what I know, OK).
Written for PHP 8.1 using Visual Studio Code like the cool kids.

Warning: I never got into composer for my little hobby projects, so all
dependencies are simply vendored (see [thirdparty.txt](thirdparty.txt))

# Installation

* Drop everything on your webserver
* Edit `includes/confi.serv.i.php` - Database connection, API keys, ...
* Import the tables from `tables.sql`

If you feel like changeing table prefixes, you can do so pretty easily in the
PHP part. For the database tables, I guess you could `sed` for `` `siglib_``.