SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `prefix_client` (
  `ClientID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` text NOT NULL,
  `ApiKey` int(11) DEFAULT NULL,
  PRIMARY KEY (`ClientID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_clientauthorization` (
  `UniqueClientID` int(11) NOT NULL AUTO_INCREMENT,
  `StatusID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ClientID` int(11) NOT NULL,
  `Token` text NOT NULL,
  `ClientDescription` text NOT NULL,
  `ClientVersion` text NULL,
  `UUID` text NOT NULL,
  `SeenTS` int(11) NOT NULL,
  PRIMARY KEY (`UniqueClientID`),
  KEY `ClientID` (`ClientID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_event` (
  `EventID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Type` int(11) NOT NULL,
  `EpisodeID` int(11) NOT NULL,
  `PositionTS` int(11) NOT NULL,
  `ConcurrentOrder` int(11) NULL,
  `ClientTS` int(11) NOT NULL,
  `ReceivedTS` int(11) NOT NULL,
  `UniqueClientID` int(11) NOT NULL,
  PRIMARY KEY (`EventID`),
  KEY `UserID` (`UserID`),
  KEY `UniqueClientID` (`UniqueClientID`),
  KEY `EpisodeID` (`EpisodeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_cast` (
  `CastID` int(11) NOT NULL AUTO_INCREMENT,
  `URL` text,
  `Content` text NOT NULL,
  `CrawlTS` int(11) NOT NULL,
  `XML` mediumtext NULL,
  PRIMARY KEY (`CastID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_episode` (
  `EpisodeID` int(11) NOT NULL AUTO_INCREMENT,
  `CastID` int(11) NOT NULL,
  `Content` text NOT NULL,
  `GUID` text,
  `CrawlTS` int(11) NOT NULL,
  PRIMARY KEY (`EpisodeID`),
  KEY `CastID` (`CastID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_setting` (
  `SettingID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Setting` text NOT NULL,
  `Value` text NOT NULL,
  `ClientID` int(11) DEFAULT NULL,
  PRIMARY KEY (`SettingID`),
  KEY `ClientID` (`ClientID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_subscription` (
  `SubscriptionID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` text NOT NULL,
  `CastID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `SubscriptionTS` int(11) NULL,
  PRIMARY KEY (`SubscriptionID`),
  KEY `CastID` (`CastID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_label` (
  `LabelID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Name` text NOT NULL,
  `Content` text NOT NULL,
  `Expanded` int(11) NOT NULL,
  PRIMARY KEY (`LabelID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `UserLevel` int(11) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `Name` text,
  `Mail` text NOT NULL,
  `Password` text NOT NULL,
  PRIMARY KEY (`UserID`),
  CONSTRAINT prefix_users UNIQUE (Username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `prefix_clientauthorization`
  ADD CONSTRAINT `ClientAuthorization_ClientID` FOREIGN KEY (`ClientID`) REFERENCES `prefix_client` (`ClientID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `ClientAuthorization_UserID` FOREIGN KEY (`UserID`) REFERENCES `prefix_users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `prefix_event`
  ADD CONSTRAINT `Event_UserID` FOREIGN KEY (`UserID`) REFERENCES `prefix_users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Event_UniqueClientID` FOREIGN KEY (`UniqueClientID`) REFERENCES `prefix_clientauthorization` (`UniqueClientID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Event_EpisodeID` FOREIGN KEY (`EpisodeID`) REFERENCES `prefix_episodeid` (`EpisodeID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `prefix_feedcontent`
  ADD CONSTRAINT `FeedContent_CastID` FOREIGN KEY (`CastID`) REFERENCES `prefix_cast` (`CastID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `FeedContent_EpisodeID` FOREIGN KEY (`EpisodeID`) REFERENCES `prefix_episodeid` (`EpisodeID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `prefix_setting`
  ADD CONSTRAINT `Setting_ClientID` FOREIGN KEY (`ClientID`) REFERENCES `prefix_client` (`ClientID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Setting_UserID` FOREIGN KEY (`UserID`) REFERENCES `prefix_users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `prefix_subscription`
  ADD CONSTRAINT `Subscription_CastID` FOREIGN KEY (`CastID`) REFERENCES `prefix_cast` (`CastID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Subscription_UserID` FOREIGN KEY (`UserID`) REFERENCES `prefix_users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION;
