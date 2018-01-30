<?php
/**
 * LICENSE
 *
 * This file is part of Flyve MDM Demo Plugin for GLPI,
 * a subproject of Flyve MDM.
 *
 * Flyve MDM Demo Plugin for GLPI is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Flyve MDM Demo Plugin for GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 * ------------------------------------------------------------------------------
 * @author    Thierry Bugier - <tbugier@teclib.com>
 * @copyright Copyright © 2017 - 2018 Teclib
 * @license   AGPLv3 https://www.gnu.org/licenses/agpl-3.0.html
 * @link      https://github.com/flyve-mdm/demo-mode
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

function plugin_flyvemdmdemo_update_to_dev(Migration $migration) {
   global $DB;

   $table = PluginFlyvemdmdemoAccountvalidation::getTable();
   $migration->addKey($table, 'assigned_entities_id', 'assigned_entities_id');
   $migration->addKey($table, 'profiles_id', 'profiles_id');
   $migration->addField($table, 'newsletter', 'boolean', ['after' => 'is_post_reminder_sent']);

   // update schema
   $table = PluginFlyvemdmdemoNewsletterSubscriber::getTable();
   if (!$DB->tableExists($table)) {
      $query = "CREATE TABLE `$table` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `users_id` INT(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  INDEX `users_id` (`users_id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die("Could not create newsletter subscriptions table " . $DB->error());
   }

   $table = PluginFlyvemdmdemoCaptcha::getTable();
   if (!$DB->tableExists($table)) {
      $query = "CREATE TABLE `glpi_plugin_flyvemdmdemo_captchas` (
               `id`                                int(11)                  NOT NULL AUTO_INCREMENT,
               `ip_address`                        varchar(45)              DEFAULT NULL,
               `answer`                            varchar(255)             DEFAULT NULL,
               PRIMARY KEY (`id`),
               INDEX `ip_address` (`ip_address`)
             ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die("Could not create captcha table " . $DB->error());
   }

   $migration->setVersion(PLUGIN_FLYVEMDMDEMO_VERSION);
}
