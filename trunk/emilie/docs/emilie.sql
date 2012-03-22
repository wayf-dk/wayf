CREATE TABLE `entities` (
     `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
     `entityid` varchar(255) CHARACTER SET utf8 NOT NULL,
     `sporidp` enum('sp','idp') CHARACTER SET utf8 NOT NULL,
     `name_da` varchar(255) CHARACTER SET utf8 NOT NULL,
     `name_en` varchar(255) CHARACTER SET utf8 NOT NULL,
     `integration_costs` int(10) unsigned DEFAULT NULL,
     `integration_costs_wayf` int(10) unsigned DEFAULT NULL,
     `number_of_users` int(10) unsigned DEFAULT NULL,
     `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
     `schacHomeOrganization` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
     `ressort` varchar(255) COLLATE utf8_danish_ci DEFAULT NULL,
     PRIMARY KEY (`id`),
     UNIQUE KEY `entityid` (`entityid`(100),`sporidp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci
