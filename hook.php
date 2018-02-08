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

/**
 * Entry point for installation process
 */
function plugin_flyvemdmdemo_install() {
    global $DB;

    include_once __DIR__ . "/install/installer.class.php";
    $installer = new PluginFlyvemdmdemoInstaller();

    return $installer->install();
}

/**
 * Uninstalls the plugin
 *
 * @return boolean True if success
 */
function plugin_flyvemdmdemo_uninstall() {

    include_once __DIR__ . "/install/installer.class.php";
    $installer = new PluginFlyvemdmdemoInstaller();

    return $installer->uninstall();
}

/**
 * Second pass of initialization after all other initiaization of other plugins
 * Also force inclusion of this file
 */
function plugin_flyvemdmdemo_postinit() {

}


/**
 * Ensure the service account is not used to directly create entities
 *
 * @param CommonDBTM $item
 */
function plugin_flyvemdmdemo_hook_pre_entity_add(CommonDBTM $item) {
    $config = Config::getConfigurationValues('flyvemdmdemo', array('service_profiles_id'));
    $serviceProfileId = $config['service_profiles_id'];
   if ($serviceProfileId === null) {
      $item->input = null;
      return false;
   }

   if ($_SESSION['glpiactiveprofile']['id'] == $serviceProfileId) {
      if (PluginFlyvemdmdemoUser::getCreation() !== true) {
         $item->input = null;
         Session::addMessageAfterRedirect(__('Not allowed', 'flyvemdm'));
         return false;
      }
   }
}

function plugin_flyvemdmdemo_hook_pre_fleet_delete(CommonDBTM $item) {
    return plugin_flyvemdmdemo_pre_fleet_purge($item);
}

function plugin_flyvemdmdemo_pre_fleet_purge(CommonDBTM $item) {
   if ($item instanceof PluginFlyvemdmFleet) {
      if (isset($_SESSION['glpiID']) && $item->fields['is_default'] == '1') {
         $config = Config::getConfigurationValues('flyvemdmdemo', array('service_profiles_id'));
         if (!Entity::canPurge() && $_SESSION['glpiactiveprofile']['id'] != $config['service_profiles_id']) {
            Session::addMessageAfterRedirect(__('Cannot delete the default fleet', 'flyvemdm'));
            $item->input = false;
            return false;
         }
      }
   }

    return true;
}
