<?php
/**
 LICENSE

Copyright (C) 2016 Teclib'
Copyright (C) 2010-2016 by the FusionInventory Development Team.

This file is part of Flyve MDM Plugin for GLPI.

Flyve MDM Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile
device management software.

Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
You should have received a copy of the GNU Affero General Public License
along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 ------------------------------------------------------------------------------
 *
 @author    Thierry Bugier Pineau
 @copyright Copyright (c) 2016 Flyve MDM plugin team
 @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 @link      https://github.com/flyvemdm/backend
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

define("PLUGIN_FLYVEMDMDEMO_VERSION", "1.0.0-dev");
// is or is not an official release of the plugin
define('PLUGIN_FLYVEMDMDEMO_IS_OFFICIAL_RELEASE', false);
// Minimal GLPI version, inclusive
define("PLUGIN_FLYVEMDMDEMO_GLPI_MIN_VERSION", "9.2");
// Maximum GLPI version, exclusive
define("PLUGIN_FLYVEMDMDEMO_GLPI_MAX_VERSION", "9.3");
// Minimum PHP version inclusive
define("PLUGIN_FLYVEMDMDEMO_PHP_MIN_VERSION", "5.5");


// Init the hooks of the plugins -Needed
function plugin_init_flyvemdmdemo() {
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $PLUGIN_HOOKS['csrf_compliant']['flyvemdmdemo'] = true;

    $plugin = new Plugin();

   if ($plugin->isActivated('flyvemdmdemo')) {
      include_once __DIR__ . '/vendor/autoload.php';

      $PLUGIN_HOOKS['post_init']['flyvemdmdemo']                = 'plugin_flyvemdmdemo_postinit';

      // Notifications
      $PLUGIN_HOOKS['item_get_events']['flyvemdmdemo'] =
          array('PluginFlyvemdmdemoNotificationTargetAccountvalidation' => array('PluginFlyvemdmdemoNotificationTargetAccountvalidation', 'addEvents'));
      $PLUGIN_HOOKS['item_get_datas']['flyvemdmdemo'] =
          array('PluginFlyvemdmdemoNotificationTargetAccountvalidation' => array('PluginFlyvemdmdemoNotificationTargetAccountvalidation', 'getAdditionalDatasForTemplate'));

      Plugin::registerClass(
          'PluginFlyvemdmdemoAccountvalidation', array(
          'notificationtemplates_types' => true, // 'document_types' => true
          )
      );

      if (Session::haveRight(PluginFlyvemdmProfile::$rightname, PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE)) {
            $PLUGIN_HOOKS['config_page']['flyvemdmdemo'] = 'front/config.form.php';
      }

      // Hooks for the plugin : objects inherited from GLPI or
      $PLUGIN_HOOKS['pre_item_add']['flyvemdmdemo']     = array(
          'User'                  => 'plugin_flyvemdmdemo_hook_pre_entity_add',
      );

      $PLUGIN_HOOKS['pre_item_purge']['flyvemdmdemo']   = array(
          'PluginFlyvemdmFleet'   => 'plugin_flyvemdmdemo_pre_fleet_purge',
      );
   }
}

// Get the name and the version of the plugin - Needed
function plugin_version_flyvemdmdemo() {
   $author = "<a href='http://www.teclib.com'>Teclib</a>";
   if (defined('GLPI_PREVER') && PLUGIN_FLYVEMDMDEMO_IS_OFFICIAL_RELEASE == false) {
      $glpiVersion = version_compare(GLPI_PREVER, PLUGIN_FLYVEMDMDEMO_GLPI_MIN_VERSION, 'lt');
   } else {
      $glpiVersion = PLUGIN_FLYVEMDMDEMO_GLPI_MIN_VERSION;
   }
   return [
      'name'           => __s('Flyve MDM Demo', "flyvemdmdemo"),
      'version'        => PLUGIN_FLYVEMDMDEMO_VERSION,
      'author'         => $author,
      'license'        => 'AGPLv3+',
      'homepage'       => '',
      'minGlpiVersion' => $glpiVersion,
      'requirements'   => [
         'glpi' => [
            'min' => $glpiVersion,
            'max' => '9.3',
            'dev' => PLUGIN_FLYVEMDMDEMO_IS_OFFICIAL_RELEASE == false,
            'plugins'   => [
               'flyvemdm',
            ],
         ],
         'php' => [
            'min'    => PLUGIN_FLYVEMDMDEMO_PHP_MIN_VERSION,
         ]
      ]
   ];
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_flyvemdmdemo_check_prerequisites() {
    $prerequisitesSuccess = true;
   if (!is_readable(__DIR__ . '/vendor/autoload.php') || !is_file(__DIR__ . '/vendor/autoload.php')) {
      echo "Run composer install --no-dev in the plugin directory<br>";
      $prerequisitesSuccess = false;
   }

   return $prerequisitesSuccess;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_flyvemdmdemo_check_config() {
    return true;
}
