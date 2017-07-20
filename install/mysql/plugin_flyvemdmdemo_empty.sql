DROP TABLE IF EXISTS `glpi_plugin_flyvemdmdemo_accountvalidations`;
CREATE TABLE `glpi_plugin_flyvemdmdemo_accountvalidations` (
  `id`                                int(11)                  NOT NULL AUTO_INCREMENT,
  `users_id`                          int(11)                  NOT NULL DEFAULT '0',
  `assigned_entities_id`              int(11)                  NOT NULL DEFAULT '0',
  `profiles_id`                       int(11)                  NOT NULL DEFAULT '0',
  `validation_pass`                   varchar(255)             NOT NULL DEFAULT '',
  `date_creation`                     datetime                 NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_end_trial`                    datetime                 NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_trial_ended`                    tinyint(1)               NOT NULL DEFAULT '0',
  `is_reminder_1_sent`                tinyint(1)               NOT NULL DEFAULT '0',
  `is_reminder_2_sent`                tinyint(1)               NOT NULL DEFAULT '0',
  `is_post_reminder_sent`             tinyint(1)               NOT NULL DEFAULT '0',
  `newsletter`                        tinyint(1)               NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `assigned_entities_id` (`assigned_entities_id`),
  INDEX `profiles_id` (`profiles_id`) 
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_flyvemdmdemo_newslettersubscribers`;
CREATE TABLE `glpi_plugin_flyvemdmdemo_newslettersubscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `users_id` (`users_id`) 
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_flyvemdmdemo_captchas`;
CREATE TABLE `glpi_plugin_flyvemdmdemo_captchas` (
  `id`                                int(11)                  NOT NULL AUTO_INCREMENT,
  `ip_address`                        varchar(45)              DEFAULT NULL,
  `answer`                            varchar(255)             DEFAULT NULL,
  `date_creation`                     datetime                 NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  INDEX `ip_address` (`ip_address`),
  INDEX `date_creation` (`date_creation`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
