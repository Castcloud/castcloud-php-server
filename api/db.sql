-- phpMyAdmin SQL Dump
-- version 4.0.5
-- http://www.phpmyadmin.net
--
-- Vert: localhost
-- Generert den: 17. Feb, 2014 15:58 PM
-- Tjenerversjon: 5.1.70-log
-- PHP-Versjon: 5.5.9-pl0-gentoo

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `sjefen6`
--

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Client`
--

CREATE TABLE IF NOT EXISTS `client` (
  `ClientID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` text NOT NULL,
  `ApiKey` int(11) DEFAULT NULL,
  PRIMARY KEY (`ClientID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `ClientAuthorization`
--

CREATE TABLE IF NOT EXISTS `clientauthorization` (
  `UniqueClientID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `ClientID` int(11) NOT NULL,
  `Token` text NOT NULL,
  `ClientDescription` text NOT NULL,
  `ClientVersion` text NOT NULL,
  `UUID` text NOT NULL,
  `SeenTS` int(11) NOT NULL,
  PRIMARY KEY (`UniqueClientID`),
  KEY `ClientID` (`ClientID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Event`
--

CREATE TABLE IF NOT EXISTS `event` (
  `EventID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Type` int(11) NOT NULL,
  `ItemID` int(11) NOT NULL,
  `Event` text NOT NULL,
  `ClientTS` int(11) NOT NULL,
  `ReceivedTS` int(11) NOT NULL,
  `UniqueClientID` int(11) NOT NULL,
  PRIMARY KEY (`EventID`),
  KEY `UserID` (`UserID`),
  KEY `UniqueClientID` (`UniqueClientID`),
  KEY `ItemID` (`ItemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Feed`
--

CREATE TABLE IF NOT EXISTS `feed` (
  `FeedID` int(11) NOT NULL AUTO_INCREMENT,
  `URL` text,
  `CrawlTS` int(11) NOT NULL,
  PRIMARY KEY (`FeedID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `FeedContent`
--

CREATE TABLE IF NOT EXISTS `feedcontent` (
  `ContentID` int(11) NOT NULL AUTO_INCREMENT,
  `FeedID` int(11) NOT NULL,
  `Location` text NOT NULL,
  `ItemID` int(11) DEFAULT NULL,
  `Content` text NOT NULL,
  `CrawlTS` int(11) NOT NULL,
  PRIMARY KEY (`ContentID`),
  KEY `FeedID` (`FeedID`),
  KEY `ItemID` (`ItemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `ItemID`
--

CREATE TABLE IF NOT EXISTS `itemid` (
  `ItemID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ItemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Setting`
--

CREATE TABLE IF NOT EXISTS `setting` (
  `SettingID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Setting` text NOT NULL,
  `Value` text NOT NULL,
  `ClientID` int(11) DEFAULT NULL,
  PRIMARY KEY (`SettingID`),
  KEY `ClientID` (`ClientID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Subscription`
--

CREATE TABLE IF NOT EXISTS `subscription` (
  `FeedID` int(11) NOT NULL,
  `Tags` text NOT NULL,
  `UserID` int(11) NOT NULL,
  KEY `FeedID` (`FeedID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `Username` text NOT NULL,
  `Name` text,
  `Mail` text NOT NULL,
  `Password` text NOT NULL,
  `Salt` text NOT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Begrensninger for dumpede tabeller
--

--
-- Begrensninger for tabell `ClientAuthorization`
--
ALTER TABLE `clientauthorization`
  ADD CONSTRAINT `ClientAuthorization_ClientID` FOREIGN KEY (`ClientID`) REFERENCES `client` (`ClientID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `ClientAuthorization_UserID` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Begrensninger for tabell `Event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `Event_UserID` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Event_UniqueClientID` FOREIGN KEY (`UniqueClientID`) REFERENCES `clientauthorization` (`UniqueClientID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Event_ItemID` FOREIGN KEY (`ItemID`) REFERENCES `itemid` (`ItemID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Begrensninger for tabell `FeedContent`
--
ALTER TABLE `feedcontent`
  ADD CONSTRAINT `FeedContent_FeedID` FOREIGN KEY (`FeedID`) REFERENCES `feed` (`FeedID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `FeedContent_ItemID` FOREIGN KEY (`ItemID`) REFERENCES `itemid` (`ItemID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Begrensninger for tabell `Setting`
--
ALTER TABLE `setting`
  ADD CONSTRAINT `Setting_ClientID` FOREIGN KEY (`ClientID`) REFERENCES `client` (`ClientID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Setting_UserID` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Begrensninger for tabell `Subscription`
--
ALTER TABLE `subscription`
  ADD CONSTRAINT `Subscription_FeedID` FOREIGN KEY (`FeedID`) REFERENCES `feed` (`FeedID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Subscription_UserID` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

