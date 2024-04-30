-- MySQL dump 10.13  Distrib 5.5.62, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: event
-- ------------------------------------------------------
-- Server version	5.5.62-0ubuntu0.12.04.1-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Device`
--

DROP TABLE IF EXISTS `Device`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hostname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'undefined.hostname.domain' COMMENT 'FQDN',
  `address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'IP address should match DNS',
  `firstSeen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date entered into Database',
  `productionState` tinyint(1) DEFAULT '0' COMMENT 'Defines monitoring levels',
  `isAlive` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dead',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hostname_Address` (`hostname`,`address`),
  UNIQUE KEY `hostname_2` (`hostname`,`address`),
  KEY `hostname` (`hostname`(128))
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Start of the tree is the host itself';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DeviceFolder`
--

DROP TABLE IF EXISTS `DeviceFolder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DeviceFolder` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `DeviceFolder` varchar(255) NOT NULL,
  `Devices` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `DeviceFolder` (`DeviceFolder`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DeviceGroup`
--

DROP TABLE IF EXISTS `DeviceGroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DeviceGroup` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `devicegroupName` varchar(90) NOT NULL,
  `hostname` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `hostgroupName` (`devicegroupName`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DeviceProperties`
--

DROP TABLE IF EXISTS `DeviceProperties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DeviceProperties` (
  `Id` int(6) NOT NULL AUTO_INCREMENT,
  `DeviceId` int(6) NOT NULL,
  `Properties` text COMMENT 'JSON of al the properties associated with the Device',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Address` (`DeviceId`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Reporting`
--

DROP TABLE IF EXISTS `Reporting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Reporting` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `reportName` varchar(200) NOT NULL,
  `reportResult` longtext,
  `reportDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `filterValues` text NOT NULL,
  `status` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nameAndDate` (`reportName`,`reportDate`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activeMonitors`
--

DROP TABLE IF EXISTS `activeMonitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activeMonitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'just an id value',
  `address` varchar(32) NOT NULL COMMENT 'IP address for filtering which poller will be running the check',
  `checkName` varchar(255) NOT NULL COMMENT 'checkName duh',
  `checkAction` text NOT NULL COMMENT 'Action to perform or OID',
  `type` varchar(20) NOT NULL COMMENT 'nrpe shell curl snmp, etc',
  `iteration` int(5) NOT NULL DEFAULT '300' COMMENT 'iteration cycle as integer',
  `storage` varchar(25) NOT NULL COMMENT 'storage type for results',
  `hostid` text NOT NULL COMMENT 'csv of hosts',
  `visible` varchar(4) NOT NULL DEFAULT 'no' COMMENT 'yes no (unused)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `address` (`address`,`checkName`,`type`,`iteration`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `eceGroups`
--

DROP TABLE IF EXISTS `eceGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eceGroups` (
  `categoryId` int(6) NOT NULL AUTO_INCREMENT COMMENT 'auto increment on new row',
  `parentId` int(6) DEFAULT NULL COMMENT 'associate with categoryId',
  `categoryName` varchar(255) NOT NULL COMMENT 'General Service or public name',
  `associatedHost` text NOT NULL COMMENT 'list of id values from device id number',
  `associatedCheck` text NOT NULL COMMENT 'list of service checks associated with a given category name',
  PRIMARY KEY (`categoryId`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `eceRules`
--

DROP TABLE IF EXISTS `eceRules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eceRules` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `ruleName` varchar(255) NOT NULL,
  `ruleValue` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event` (
  `evid` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `device` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `stateChange` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `startEvent` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `endEvent` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `eventAgeOut` int(9) NOT NULL DEFAULT '0',
  `eventCounter` int(11) NOT NULL DEFAULT '1',
  `eventRaw` text COLLATE utf8_unicode_ci,
  `eventReceiver` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'undefined',
  `eventSeverity` smallint(6) NOT NULL DEFAULT '2',
  `eventAddress` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  `eventDetails` text COLLATE utf8_unicode_ci,
  `eventProxyIp` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  `eventName` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'undefined',
  `eventType` smallint(4) NOT NULL DEFAULT '3',
  `eventMonitor` smallint(4) NOT NULL DEFAULT '3',
  `eventSummary` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Undefined summary',
  `application` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'false',
  `customerVisible` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'false',
  `osEvent` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'false',
  PRIMARY KEY (`device`,`eventName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `okEventSet` BEFORE UPDATE ON `event` FOR EACH ROW BEGIN
  IF (NEW.eventSeverity ='0') THEN
    INSERT INTO history SET
    evid=OLD.evid, device=OLD.device,
    stateChange=OLD.stateChange, startEvent=OLD.startEvent, 
    endEvent=CURRENT_TIMESTAMP, eventAgeOut=OLD.eventAgeOut, eventCounter=OLD.eventCounter,
    eventRaw=OLD.eventRaw, eventReceiver=OLD.eventReceiver, eventSeverity=OLD.eventSeverity, eventAddress=OLD.eventAddress, eventDetails=OLD.eventDetails,
    eventProxyIp=OLD.eventProxyIp, eventName=OLD.eventName, eventType=OLD.eventType, eventMonitor=OLD.eventMonitor, eventSummary=OLD.eventSummary;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `eventCorrelationEngine`
--

DROP TABLE IF EXISTS `eventCorrelationEngine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eventCorrelationEngine` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` varchar(6) NOT NULL DEFAULT 'false',
  `appName` varchar(255) NOT NULL,
  `appRuleOrder` int(6) NOT NULL DEFAULT '0',
  `serviceName` varchar(255) NOT NULL,
  `raw` varchar(6) NOT NULL DEFAULT 'false',
  `appCorrelation` text NOT NULL,
  `eceSummary` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appService with serviceName` (`appName`,`serviceName`,`appRuleOrder`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `heartbeat`
--

DROP TABLE IF EXISTS `heartbeat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `heartbeat` (
  `device` varchar(128) NOT NULL,
  `component` varchar(128) NOT NULL DEFAULT '',
  `lastTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `pid` int(9) DEFAULT NULL,
  PRIMARY KEY (`device`,`component`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `history`
--

DROP TABLE IF EXISTS `history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `history` (
  `evid` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `device` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `stateChange` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `startEvent` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `endEvent` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `eventAgeOut` int(9) NOT NULL DEFAULT '0',
  `eventCounter` int(11) NOT NULL DEFAULT '1',
  `eventRaw` text COLLATE utf8_unicode_ci,
  `eventReceiver` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'undefined',
  `eventSeverity` smallint(6) NOT NULL DEFAULT '2',
  `eventAddress` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  `eventDetails` text COLLATE utf8_unicode_ci,
  `eventProxyIp` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  `eventName` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'undefined',
  `eventType` smallint(4) NOT NULL DEFAULT '3',
  `eventMonitor` smallint(4) NOT NULL DEFAULT '3',
  `eventSummary` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Undefined summary',
  `application` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'false',
  `customerVisible` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'false',
  `osEvent` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'false'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `infrastructure`
--

DROP TABLE IF EXISTS `infrastructure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `infrastructure` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `category_name` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `category_link` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `infrastructureProducts`
--

DROP TABLE IF EXISTS `infrastructureProducts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `infrastructureProducts` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL DEFAULT '0',
  `product_name` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `product_link` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maintenance`
--

DROP TABLE IF EXISTS `maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance` (
  `device` varchar(255) DEFAULT NULL,
  `component` varchar(255) DEFAULT NULL,
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `monitoringDevicePoller`
--

DROP TABLE IF EXISTS `monitoringDevicePoller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monitoringDevicePoller` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `checkName` varchar(128) NOT NULL COMMENT 'monitoring name.  Camel case, do not use spaces',
  `checkAction` text CHARACTER SET utf8 NOT NULL COMMENT 'command args needed.  oids, nrpe args, shell args, etc...  Variables allowed in here match hostname and IP address only ',
  `type` varchar(20) CHARACTER SET utf8 NOT NULL COMMENT 'nrpe shell curl (snmp)get (snmp)walk  (snmp)future: bulk',
  `iteration` int(5) NOT NULL DEFAULT '300',
  `storage` varchar(25) NOT NULL DEFAULT 'graphite' COMMENT 'databaseMetric, graphite, rrd, file, debugger',
  `hostid` text NOT NULL COMMENT 'This is simply a list of hostnames that we are going to run against.  This is a JSON save.  Decode it internally at runtime for list.',
  `hostGroup` text NOT NULL,
  `visible` varchar(4) NOT NULL DEFAULT 'no',
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `checkName` (`checkName`,`type`,`iteration`)
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 trigger autoMap BEFORE INSERT on monitoringDevicePoller  FOR EACH ROW  INSERT INTO trapEventMap VALUES( new.checkName, new.CheckName, 1, '', 1, '', '', 86400, '') ON DUPLICATE KEY UPDATE oid=new.checkName */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `performance`
--

DROP TABLE IF EXISTS `performance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance` (
  `hostname` varchar(90) DEFAULT NULL,
  `checkName` varchar(99) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `value` text NOT NULL,
  UNIQUE KEY `hostname_checkName_constraint` (`hostname`,`checkName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='single entry per check';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `templates`
--

DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `templates` (
  `Id` int(6) NOT NULL AUTO_INCREMENT,
  `Class` varchar(64) NOT NULL DEFAULT 'Device' COMMENT 'Defined as Devices, Monitors, IP Services, etc',
  `Name` varchar(64) NOT NULL DEFAULT 'Default' COMMENT 'A distinct name reflecting what the template is for',
  `templateValue` text COMMENT 'Json values for the given template',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Id` (`Id`),
  UNIQUE KEY `Class` (`Class`,`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `trapEventMap`
--

DROP TABLE IF EXISTS `trapEventMap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trapEventMap` (
  `oid` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL DEFAULT 'undefined',
  `severity` tinyint(6) NOT NULL DEFAULT '1',
  `pre_processing` text,
  `type` int(9) DEFAULT '1',
  `parent_of` text,
  `child_of` text,
  `age_out` varchar(32) NOT NULL DEFAULT '14400',
  `post_processing` text,
  UNIQUE KEY `oid` (`oid`),
  UNIQUE KEY `uniquePair` (`oid`,`display_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `userId` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'using utf8_bin makes case sensitive for userid',
  `email` varchar(100) NOT NULL,
  `realName` varchar(255) DEFAULT NULL,
  `userPass` varchar(255) NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timer` int(6) NOT NULL DEFAULT '8',
  `accessList` text,
  `enable` int(1) NOT NULL DEFAULT '0' COMMENT '0 disabled, 1 enabled',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`),
  UNIQUE KEY `userId_2` (`userId`,`email`)
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `versionUpdates`
--

DROP TABLE IF EXISTS `versionUpdates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `versionUpdates` (
  `sequence` int(6) NOT NULL,
  `file` varchar(255) NOT NULL DEFAULT 'missingFileName.php' COMMENT 'filename by itself, no paths.  foo.php\r\n',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `fileName` (`file`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-04-29 10:31:24
-- Current state as of commit 8daf414100ee4d459e62b255ad3e3e13b3819341

INSERT INTO `versionUpdates` VALUE(001, '001_base_schema_and_triggers.sql', NOW());
