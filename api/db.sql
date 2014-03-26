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
  `ClientVersion` text NOT NULL,
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
  `ItemID` int(11) NOT NULL,
  `PositionTS` int(11) NOT NULL,
  `ConcurrentOrder` int(11) NULL,
  `ClientTS` int(11) NOT NULL,
  `ReceivedTS` int(11) NOT NULL,
  `UniqueClientID` int(11) NOT NULL,
  PRIMARY KEY (`EventID`),
  KEY `UserID` (`UserID`),
  KEY `UniqueClientID` (`UniqueClientID`),
  KEY `ItemID` (`ItemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_feed` (
  `FeedID` int(11) NOT NULL AUTO_INCREMENT,
  `URL` text,
  `CrawlTS` int(11) NOT NULL,
  PRIMARY KEY (`FeedID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_feedcontent` (
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

CREATE TABLE IF NOT EXISTS `prefix_itemid` (
  `ItemID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ItemID`)
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
  `FeedID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Tags` text NULL,
  `Arrangement` int(11) NULL,
  PRIMARY KEY (`SubscriptionID`),
  KEY `FeedID` (`FeedID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_tag` (
  `TagID` int(11) NOT NULL AUTO_INCREMENT,
  `Tag` text NULL,
  PRIMARY KEY (`TagID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_subscriptiontag` (
  `SubTagID` int(11) NOT NULL AUTO_INCREMENT,
  `SubscriptionID` int(11) NOT NULL,
  `Tag` int(11) NULL,
  `Arrangement` int(11) NULL,
  PRIMARY KEY (`SubTagID`),
  KEY (`TagID`),
  KEY (`SubscriptionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `prefix_users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `UserLevel` int(11) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `Name` text,
  `Mail` text NOT NULL,
  `Password` text NOT NULL,
  `Salt` text NOT NULL,
  PRIMARY KEY (`UserID`),
  CONSTRAINT prefix_users UNIQUE (Username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `prefix_clientauthorization`
  ADD CONSTRAINT `ClientAuthorization_ClientID` FOREIGN KEY (`ClientID`) REFERENCES `prefix_client` (`ClientID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `ClientAuthorization_UserID` FOREIGN KEY (`UserID`) REFERENCES `prefix_users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `prefix_event`
  ADD CONSTRAINT `Event_UserID` FOREIGN KEY (`UserID`) REFERENCES `prefix_users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Event_UniqueClientID` FOREIGN KEY (`UniqueClientID`) REFERENCES `prefix_clientauthorization` (`UniqueClientID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Event_ItemID` FOREIGN KEY (`ItemID`) REFERENCES `prefix_itemid` (`ItemID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `prefix_feedcontent`
  ADD CONSTRAINT `FeedContent_FeedID` FOREIGN KEY (`FeedID`) REFERENCES `prefix_feed` (`FeedID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `FeedContent_ItemID` FOREIGN KEY (`ItemID`) REFERENCES `prefix_itemid` (`ItemID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `prefix_setting`
  ADD CONSTRAINT `Setting_ClientID` FOREIGN KEY (`ClientID`) REFERENCES `prefix_client` (`ClientID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Setting_UserID` FOREIGN KEY (`UserID`) REFERENCES `prefix_users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `prefix_subscription`
  ADD CONSTRAINT `Subscription_FeedID` FOREIGN KEY (`FeedID`) REFERENCES `prefix_feed` (`FeedID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Subscription_UserID` FOREIGN KEY (`UserID`) REFERENCES `prefix_users` (`UserID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `prefix_subscriptiontag`
  ADD CONSTRAINT `SubscriptionTag_TagID` FOREIGN KEY (`TagID`) REFERENCES `prefix_tag` (`TagID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `SubscriptionTag_SubscriptionID` FOREIGN KEY (`CastID`) REFERENCES `prefix_subscription` (`SubscriptionID`) ON DELETE NO ACTION ON UPDATE NO ACTION;