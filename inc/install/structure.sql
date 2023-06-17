SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `runalyze_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL,
  `name` varchar(50) NOT NULL DEFAULT '',
  `mail` varchar(100) NOT NULL,
  `language` varchar(5) NOT NULL DEFAULT '',
  `timezone` smallint(5) unsigned NOT NULL DEFAULT '0',
  `password` varchar(64) NOT NULL DEFAULT '',
  `salt` char(64) NOT NULL DEFAULT '',
  `registerdate` int(10) unsigned DEFAULT NULL,
  `lastaction` int(10) unsigned DEFAULT NULL,
  `changepw_hash` char(32) DEFAULT NULL,
  `changepw_timelimit` int(10) unsigned DEFAULT NULL,
  `activation_hash` char(32) DEFAULT NULL,
  `deletion_hash` char(32) DEFAULT NULL,
  `allow_mails` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `allow_support` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `role` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `gender` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `birthyear` int(4) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `mail` (`mail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TRIGGER IF EXISTS `del_tr_train`;
DELIMITER //
CREATE TRIGGER `del_tr_train` AFTER DELETE ON `runalyze_account`
 FOR EACH ROW BEGIN
		DELETE FROM runalyze_conf WHERE accountid = OLD.id;
		DELETE FROM runalyze_dataset WHERE accountid = OLD.id;
		DELETE FROM runalyze_plugin WHERE accountid = OLD.id;
		DELETE FROM runalyze_sport WHERE accountid = OLD.id;
		DELETE FROM runalyze_training WHERE accountid = OLD.id;
		DELETE FROM runalyze_type WHERE accountid = OLD.id;
		DELETE FROM runalyze_user WHERE accountid = OLD.id;
	END
//
DELIMITER ;

CREATE TABLE IF NOT EXISTS `runalyze_equipment_type` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `input` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `max_km` mediumint unsigned DEFAULT NULL,
  `max_time` mediumint unsigned DEFAULT NULL,
  `accountid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `accountid` (`accountid`),
  CONSTRAINT `runalyze_equipment_type_ibfk_1` FOREIGN KEY (`accountid`) REFERENCES `runalyze_account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_conf` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(32) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  `accountid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `accountid` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_dataset` (
  `keyid` tinyint(3) unsigned NOT NULL,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `style` varchar(100) NOT NULL DEFAULT '',
  `position` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `privacy` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `accountid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`accountid`,`keyid`),
  KEY `position` (`accountid`,`position`),
  UNIQUE KEY `unique_key` (`accountid`, `keyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_equipment` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `typeid` int(10) unsigned NOT NULL,
  `notes` tinytext NOT NULL,
  `distance` decimal(8,2) unsigned NOT NULL DEFAULT '0.00',
  `time` int(10) unsigned NOT NULL DEFAULT '0',
  `additional_km` int(10) unsigned NOT NULL DEFAULT '0',
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `accountid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `accountid` (`accountid`),
  KEY `typeid` (`typeid`),
  CONSTRAINT `runalyze_equipment_ibfk_1` FOREIGN KEY (`typeid`) REFERENCES `runalyze_equipment_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `runalyze_equipment_ibfk_2` FOREIGN KEY (`accountid`) REFERENCES `runalyze_account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_type` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `abbr` varchar(5) NOT NULL DEFAULT '',
  `sportid` int(10) unsigned NOT NULL,
  `short` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `hr_avg` tinyint(3) unsigned NOT NULL DEFAULT '100',
  `quality_session` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `accountid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `accountid` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_sport` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `img` varchar(100) NOT NULL DEFAULT 'unknown.gif',
  `short` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `kcal` smallint(4) unsigned NOT NULL DEFAULT '0',
  `HFavg` tinyint unsigned NOT NULL DEFAULT '120',
  `distances` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `speed` varchar(10) NOT NULL DEFAULT 'min/km',
  `power` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `outside` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `default_privacy` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `main_equipmenttypeid` int(10) unsigned DEFAULT NULL,
  `default_typeid` int(10) unsigned DEFAULT NULL,
  `is_main` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `internal_sport_id` tinyint(4) DEFAULT NULL,
  `accountid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `accountid` (`accountid`),
  CONSTRAINT `runalyze_sport_ibfk_1` FOREIGN KEY (`main_equipmenttypeid`) REFERENCES `runalyze_equipment_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `runalyze_sport_ibfk_2` FOREIGN KEY (`default_typeid`) REFERENCES `runalyze_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_equipment_sport` (
  `sportid` int(10) unsigned NOT NULL,
  `equipment_typeid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`sportid`,`equipment_typeid`),
  KEY `equipment_typeid` (`equipment_typeid`),
  CONSTRAINT `runalyze_equipment_sport_ibfk_1` FOREIGN KEY (`sportid`) REFERENCES `runalyze_sport` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `runalyze_equipment_sport_ibfk_2` FOREIGN KEY (`equipment_typeid`) REFERENCES `runalyze_equipment_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(50) NOT NULL,
  `accountid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `accountid` (`accountid`),
  CONSTRAINT `runalyze_tag_ibfk_1` FOREIGN KEY (`accountid`) REFERENCES `runalyze_account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_training` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sportid` int(10) unsigned NOT NULL,
  `typeid` int(10) unsigned DEFAULT NULL,
  `time` int(11) NOT NULL,
  `timezone_offset` smallint(6) DEFAULT NULL,
  `created` int(11) unsigned DEFAULT NULL,
  `edited` int(11) unsigned DEFAULT NULL,
  `is_public` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_track` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `distance` decimal(6,2) unsigned DEFAULT NULL,
  `s` decimal(8,2) unsigned NOT NULL,
  `elapsed_time` mediumint unsigned DEFAULT NULL,
  `elevation` smallint unsigned DEFAULT NULL,
  `climb_score` decimal(3,1) unsigned DEFAULT NULL,
  `percentage_hilly` decimal(3,2) unsigned DEFAULT NULL,
  `kcal` smallint unsigned DEFAULT NULL,
  `pulse_avg` tinyint unsigned DEFAULT NULL,
  `pulse_max` tinyint unsigned DEFAULT NULL,
  `vo2max` decimal(5,2) unsigned DEFAULT NULL,
  `vo2max_by_time` decimal(5,2) unsigned DEFAULT NULL,
  `vo2max_with_elevation` decimal(5,2) unsigned DEFAULT NULL,
  `use_vo2max` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `fit_vo2max_estimate` decimal(4,2) unsigned DEFAULT NULL,
  `fit_recovery_time` smallint(5) unsigned DEFAULT NULL,
  `fit_hrv_analysis` smallint(5) unsigned DEFAULT NULL,
  `fit_training_effect` decimal(2,1) unsigned DEFAULT NULL,
  `fit_performance_condition` tinyint(3) unsigned DEFAULT NULL,
  `fit_performance_condition_end` tinyint(3) unsigned DEFAULT NULL,
  `rpe` tinyint(2) unsigned DEFAULT NULL,
  `trimp` smallint unsigned DEFAULT NULL,
  `cadence` int(3) unsigned DEFAULT NULL,
  `power` int(4) unsigned DEFAULT NULL,
  `is_power_calculated` TINYINT(1) DEFAULT NULL,
  `total_strokes` smallint(5) unsigned DEFAULT NULL,
  `swolf` tinyint(3) unsigned DEFAULT NULL,
  `stride_length` tinyint(3) unsigned DEFAULT NULL,
  `groundcontact` smallint(5) unsigned DEFAULT NULL,
  `groundcontact_balance` SMALLINT unsigned DEFAULT NULL,
  `vertical_oscillation` tinyint(3) unsigned DEFAULT NULL,
  `vertical_ratio` SMALLINT unsigned DEFAULT NULL,
  `avg_impact_gs_left` double DEFAULT NULL,
  `avg_impact_gs_right` double DEFAULT NULL,
  `avg_braking_gs_left` double DEFAULT NULL,
  `avg_braking_gs_right` double DEFAULT NULL,
  `avg_footstrike_type_left` tinyint(3) unsigned DEFAULT NULL COMMENT '(DC2Type:tinyint)',
  `avg_footstrike_type_right` tinyint(3) unsigned DEFAULT NULL COMMENT '(DC2Type:tinyint)',
  `avg_pronation_excursion_left` double DEFAULT NULL,
  `avg_pronation_excursion_right` double DEFAULT NULL,
  `temperature` tinyint(4) DEFAULT NULL,
  `wind_speed` tinyint(3) unsigned DEFAULT NULL,
  `wind_deg` smallint(3) unsigned DEFAULT NULL,
  `humidity` tinyint(3) unsigned DEFAULT NULL,
  `pressure` smallint(4) unsigned DEFAULT NULL,
  `is_night` tinyint(1) unsigned DEFAULT NULL,
  `weatherid` smallint(6) unsigned NOT NULL DEFAULT '1',
  `weather_source` tinyint(2) unsigned DEFAULT NULL,
  `route` text,
  `routeid` int(10) unsigned DEFAULT NULL,
  `splits` mediumtext,
  `title` text,
  `partner` text,
  `notes` text,
  `accountid` int(10) unsigned NOT NULL,
  `creator` varchar(100) NOT NULL DEFAULT '',
  `creator_details` tinytext,
  `activity_id` int(10) unsigned DEFAULT NULL,
  `lock` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `time` (`accountid`,`time`),
  KEY `sportid` (`accountid`,`sportid`),
  KEY `typeid` (`accountid`,`typeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 PACK_KEYS=0;

CREATE TABLE IF NOT EXISTS `runalyze_activity_tag` (
  `activityid` int(10) unsigned NOT NULL,
  `tagid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`activityid`,`tagid`),
  KEY `tagid` (`tagid`),
  CONSTRAINT `runalyze_activity_tag_ibfk_1` FOREIGN KEY (`tagid`) REFERENCES `runalyze_tag` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `runalyze_activity_tag_ibfk_2` FOREIGN KEY (`activityid`) REFERENCES `runalyze_training` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_hrv` (
  `accountid` int(10) unsigned NOT NULL,
  `activityid` int(10) unsigned NOT NULL,
  `data` longtext,
  PRIMARY KEY (`activityid`),
  KEY `accountid` (`accountid`),
  CONSTRAINT `runalyze_hrv_ibfk_1` FOREIGN KEY (`accountid`) REFERENCES `runalyze_account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `runalyze_hrv_ibfk_2` FOREIGN KEY (`activityid`) REFERENCES `runalyze_training` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_plugin` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `type` varchar(5) NOT NULL DEFAULT 'stat',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `order` tinyint unsigned NOT NULL DEFAULT '0',
  `accountid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `accountid` (`accountid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_plugin_conf` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pluginid` int(10) unsigned NOT NULL,
  `config` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pluginid` (`pluginid`),
  CONSTRAINT `runalyze_plugin_conf_ibfk_1` FOREIGN KEY (`pluginid`) REFERENCES `runalyze_plugin` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_route` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `accountid` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `cities` varchar(255) NOT NULL DEFAULT '',
  `distance` decimal(6,2) unsigned NOT NULL DEFAULT '0.00',
  `elevation` smallint(5) unsigned NOT NULL DEFAULT '0',
  `elevation_up` smallint(5) unsigned NOT NULL DEFAULT '0',
  `elevation_down` smallint(5) unsigned NOT NULL DEFAULT '0',
  `geohashes` longtext,
  `elevations_original` longtext,
  `elevations_corrected` longtext,
  `elevations_source` varchar(255) NOT NULL DEFAULT '',
  `startpoint` char(10) DEFAULT NULL,
  `endpoint` char(10) DEFAULT NULL,
  `min` char(10) DEFAULT NULL,
  `max` char(10) DEFAULT NULL,
  `in_routenet` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `lock` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `accountid` (`accountid`),
  CONSTRAINT `runalyze_route_ibfk_1` FOREIGN KEY (`accountid`) REFERENCES `runalyze_account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_swimdata` (
  `accountid` int(10) unsigned NOT NULL,
  `activityid` int(10) unsigned NOT NULL,
  `stroke` longtext,
  `stroketype` longtext,
  `pool_length` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`activityid`),
  KEY `accountid` (`accountid`),
  CONSTRAINT `runalyze_swimdata_ibfk_1` FOREIGN KEY (`accountid`) REFERENCES `runalyze_account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `runalyze_swimdata_ibfk_2` FOREIGN KEY (`activityid`) REFERENCES `runalyze_training` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_trackdata` (
  `accountid` int(10) unsigned NOT NULL,
  `activityid` int(10) unsigned NOT NULL,
  `time` longtext,
  `distance` longtext,
  `speed` longtext,
  `heartrate` longtext,
  `cadence` longtext,
  `power` longtext,
  `temperature` longtext,
  `groundcontact` longtext,
  `vertical_oscillation` longtext,
  `groundcontact_balance` longtext,
  `smo2_0` longtext,
  `smo2_1` longtext,
  `thb_0` longtext,
  `thb_1` longtext,
  `impact_gs_left` longtext,
  `impact_gs_right` longtext,
  `braking_gs_left` longtext,
  `braking_gs_right` longtext,
  `footstrike_type_left` longtext,
  `footstrike_type_right` longtext,
  `pronation_excursion_left` longtext,
  `pronation_excursion_right` longtext,
  `pauses` text,
  `lock` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`activityid`),
  KEY `accountid` (`accountid`),
  CONSTRAINT `runalyze_trackdata_ibfk_1` FOREIGN KEY (`accountid`) REFERENCES `runalyze_account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `runalyze_trackdata_ibfk_2` FOREIGN KEY (`activityid`) REFERENCES `runalyze_training` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_activity_equipment` (
  `activityid` int(10) unsigned NOT NULL,
  `equipmentid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`activityid`,`equipmentid`),
  KEY `equipmentid` (`equipmentid`),
  CONSTRAINT `runalyze_activity_equipment_ibfk_1` FOREIGN KEY (`equipmentid`) REFERENCES `runalyze_equipment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `runalyze_activity_equipment_ibfk_2` FOREIGN KEY (`activityid`) REFERENCES `runalyze_training` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_user` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL,
  `weight` decimal(5,2) DEFAULT NULl,
  `pulse_rest` tinyint unsigned DEFAULT NULl,
  `pulse_max` tinyint unsigned DEFAULT NULl,
  `fat` decimal(3,1) DEFAULT NULl,
  `water` decimal(3,1) DEFAULT NULl,
  `muscles` decimal(3,1) DEFAULT NULl,
  `sleep_duration` smallint(3) unsigned DEFAULT NULl,
  `notes` text,
  `accountid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `time` (`accountid`,`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_weathercache` (
  `time` int(11) NOT NULL,
  `geohash` char(5) DEFAULT NULL,
  `temperature` tinyint(4) DEFAULT NULL,
  `wind_speed` tinyint(3) unsigned DEFAULT NULL,
  `wind_deg` smallint(3) unsigned DEFAULT NULL,
  `humidity` tinyint(3) unsigned DEFAULT NULL,
  `pressure` smallint(4) unsigned DEFAULT NULL,
  `weatherid` smallint(6) NOT NULL DEFAULT '1',
  `weather_source` tinyint(2) unsigned DEFAULT NULL,
  PRIMARY KEY (`geohash`,`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_raceresult` (
  `official_distance` decimal(6,2) DEFAULT NULL,
  `official_time` decimal(8,2) NOT NULL,
  `officially_measured` tinyint(1)  unsigned NOT NULL DEFAULT 0,
  `name` varchar(50) NOT NULL DEFAULT '',
  `place_total` mediumint(8) unsigned DEFAULT NULL,
  `place_gender` mediumint(8) unsigned DEFAULT NULL,
  `place_ageclass` mediumint(8) unsigned DEFAULT NULL,
  `participants_total` mediumint(8) unsigned DEFAULT NULL,
  `participants_gender` mediumint(8) unsigned DEFAULT NULL,
  `participants_ageclass` mediumint(8) unsigned DEFAULT NULL,
  `activity_id` int(10) unsigned NOT NULL,
  `accountid` int(10) unsigned NOT NULL,
  KEY `accountid` (`accountid`),
  CONSTRAINT `runalyze_raceresult_ibfk_1` FOREIGN KEY (`accountid`) REFERENCES `runalyze_account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `runalyze_raceresult_ibfk_2` FOREIGN KEY (`activity_id`) REFERENCES `runalyze_training` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `runalyze_notification` (
`id` int(10) unsigned AUTO_INCREMENT NOT NULL,
  `messageType` tinyint unsigned NOT NULL,
  `createdAt` int(10) unsigned NOT NULL,
  `expirationAt` int(10) unsigned DEFAULT NULL,
  `data` TINYTEXT NOT NULL,
  `wasRead` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `account_id` int(10) unsigned NOT NULL,
  PRIMARY KEY(`id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `runalyze_notification_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `runalyze_account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;