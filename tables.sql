-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
--
-- Host: database-5003828670.webspace-host.com:3306
-- Erstellungszeit: 27. Dez 2022 um 23:52
-- Server-Version: 5.7.38-log
-- PHP-Version: 7.2.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `DB2637005`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `siglib_rates`
--

CREATE TABLE IF NOT EXISTS `siglib_rates` (
  `Authentication` varchar(128) NOT NULL COMMENT 'IP or API key',
  `Bucket` int(11) NOT NULL DEFAULT '60',
  `NextReset` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `LastRequest` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `Application` (`Authentication`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `siglib_symbols`
--

CREATE TABLE IF NOT EXISTS `siglib_symbols` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Symbol` varchar(128) NOT NULL COMMENT 'member or function name or expression if other',
  `Library` varchar(64) DEFAULT NULL COMMENT 'null for offsets',
  `Rating` int(11) NOT NULL DEFAULT '0',
  `Dupes` int(11) NOT NULL DEFAULT '0',
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Symbol` (`Symbol`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=ascii;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `siglib_symbol_comments`
--

CREATE TABLE IF NOT EXISTS `siglib_symbol_comments` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Symbol` int(11) NOT NULL COMMENT 'ref',
  `Message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `Created_By` int(11) NOT NULL COMMENT 'internal id',
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `symbol_comment_delete_symbol_cascade` (`Symbol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `siglib_symbol_ratings`
--

CREATE TABLE IF NOT EXISTS `siglib_symbol_ratings` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Symbol` int(11) NOT NULL COMMENT 'ref',
  `Rating` tinyint(4) NOT NULL COMMENT '+/-',
  `Created_By` int(11) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Value` (`Symbol`,`Created_By`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Trigger `siglib_symbol_ratings`
--
DELIMITER $$
CREATE TRIGGER `symbol_rating_create` AFTER INSERT ON `siglib_symbol_ratings` FOR EACH ROW UPDATE `siglib_symbols` SET `Rating`=`Rating`+NEW.`Rating` WHERE `ID`=NEW.`Symbol`
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `symbol_rating_drop` AFTER DELETE ON `siglib_symbol_ratings` FOR EACH ROW UPDATE `siglib_symbols` SET `Rating`=`Rating`-OLD.`Rating` WHERE `ID`=OLD.`Symbol`
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `symbol_rating_update` AFTER UPDATE ON `siglib_symbol_ratings` FOR EACH ROW UPDATE `siglib_symbols` SET `Rating`=`Rating`-OLD.`Rating`+NEW.`Rating` WHERE `ID`=NEW.`Symbol`
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `siglib_users`
--

CREATE TABLE IF NOT EXISTS `siglib_users` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `SteamID` bigint(20) NOT NULL,
  `DisplayName` tinytext NOT NULL,
  `AvatarURL` text NOT NULL,
  `Powerlevel` int(11) NOT NULL DEFAULT '10',
  `Anonymity` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Steam Data / Custom Name / No Avatar / Full (don''t link)',
  `API_Key` varchar(40) DEFAULT NULL,
  `First_Login` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SteamID` (`SteamID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `siglib_user_symbols`
--

CREATE TABLE IF NOT EXISTS `siglib_user_symbols` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `User` int(11) NOT NULL COMMENT 'ref',
  `Symbol` int(11) NOT NULL COMMENT 'ref',
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `User` (`User`,`Symbol`),
  KEY `user_symbols_delete_symbol_cascade` (`Symbol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Trigger `siglib_user_symbols`
--
DELIMITER $$
CREATE TRIGGER `count_symbol_dupes` AFTER INSERT ON `siglib_user_symbols` FOR EACH ROW UPDATE `siglib_symbols` SET `Dupes`=`Dupes`+1 WHERE `ID`=NEW.`Symbol`
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `drop_symbol_dupes` AFTER DELETE ON `siglib_user_symbols` FOR EACH ROW BEGIN
UPDATE `siglib_symbols` SET `Dupes`=`Dupes`-1 WHERE `ID`=OLD.`Symbol`;
DELETE FROM `siglib_symbols` WHERE `Dupes`=0 AND `ID`=OLD.`Symbol`;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `siglib_user_values`
--

CREATE TABLE IF NOT EXISTS `siglib_user_values` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `User` int(11) NOT NULL COMMENT 'ref',
  `Value` int(11) NOT NULL COMMENT 'ref',
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `User` (`User`,`Value`),
  KEY `user_values_delete_value_cascade` (`Value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Trigger `siglib_user_values`
--
DELIMITER $$
CREATE TRIGGER `count_value_dupes` AFTER INSERT ON `siglib_user_values` FOR EACH ROW UPDATE `siglib_values` SET `Dupes`=`Dupes`+1 WHERE `ID`=NEW.`Value`
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `drop_value_dupes` AFTER DELETE ON `siglib_user_values` FOR EACH ROW BEGIN
UPDATE `siglib_values` SET `Dupes`=`Dupes`-1 WHERE `ID`=OLD.`Value`;
DELETE FROM `siglib_values` WHERE `Dupes`=0 AND `ID`=OLD.`Value`;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `siglib_values`
--

CREATE TABLE IF NOT EXISTS `siglib_values` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Symbol` int(11) NOT NULL COMMENT 'ref',
  `Game` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `Version` varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'game version int',
  `Platform` tinyint(4) NOT NULL COMMENT 'windows / linux / mac',
  `Value` varchar(512) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'signature or number',
  `Rating` int(11) NOT NULL DEFAULT '0',
  `Dupes` int(11) NOT NULL DEFAULT '0',
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'first seen',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Symbol` (`Symbol`,`Game`,`Version`,`Platform`,`Value`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `siglib_value_comments`
--

CREATE TABLE IF NOT EXISTS `siglib_value_comments` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Value` int(11) NOT NULL COMMENT 'ref',
  `Message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `Created_By` int(11) NOT NULL COMMENT 'internal id',
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `value_comments_delete_value_cascade` (`Value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `siglib_value_ratings`
--

CREATE TABLE IF NOT EXISTS `siglib_value_ratings` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Value` int(11) NOT NULL COMMENT 'ref',
  `Rating` tinyint(4) NOT NULL COMMENT '+/-',
  `Created_By` int(11) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Value` (`Value`,`Created_By`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Trigger `siglib_value_ratings`
--
DELIMITER $$
CREATE TRIGGER `value_rating_create` AFTER INSERT ON `siglib_value_ratings` FOR EACH ROW UPDATE `siglib_values` SET `Rating`=`Rating`+NEW.`Rating` WHERE `ID`=NEW.`Value`
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `value_rating_drop` AFTER DELETE ON `siglib_value_ratings` FOR EACH ROW UPDATE `siglib_values` SET `Rating`=`Rating`-OLD.`Rating` WHERE `ID`=OLD.`Value`
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `value_rating_update` AFTER UPDATE ON `siglib_value_ratings` FOR EACH ROW UPDATE `siglib_values` SET `Rating`=`Rating`-OLD.`Rating`+NEW.`Rating` WHERE `ID`=NEW.`Value`
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `siglib_version`
--

CREATE TABLE IF NOT EXISTS `siglib_version` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Game` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `Version` varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'server version',
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Version` (`Game`,`Version`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Manual List for Dropdown';

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `siglib_symbol_comments`
--
ALTER TABLE `siglib_symbol_comments`
  ADD CONSTRAINT `symbol_comment_delete_symbol_cascade` FOREIGN KEY (`Symbol`) REFERENCES `siglib_symbols` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `siglib_symbol_ratings`
--
ALTER TABLE `siglib_symbol_ratings`
  ADD CONSTRAINT `symbol_ratings_delete_symbol_cascade` FOREIGN KEY (`Symbol`) REFERENCES `siglib_symbols` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `siglib_user_symbols`
--
ALTER TABLE `siglib_user_symbols`
  ADD CONSTRAINT `user_symbols_delete_symbol_cascade` FOREIGN KEY (`Symbol`) REFERENCES `siglib_symbols` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `siglib_user_values`
--
ALTER TABLE `siglib_user_values`
  ADD CONSTRAINT `user_values_delete_value_cascade` FOREIGN KEY (`Value`) REFERENCES `siglib_values` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `siglib_values`
--
ALTER TABLE `siglib_values`
  ADD CONSTRAINT `values_delete_symbol_cascade` FOREIGN KEY (`Symbol`) REFERENCES `siglib_symbols` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `siglib_value_comments`
--
ALTER TABLE `siglib_value_comments`
  ADD CONSTRAINT `value_comments_delete_value_cascade` FOREIGN KEY (`Value`) REFERENCES `siglib_values` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `siglib_value_ratings`
--
ALTER TABLE `siglib_value_ratings`
  ADD CONSTRAINT `value_ratings_delete_value_cascade` FOREIGN KEY (`Value`) REFERENCES `siglib_values` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
