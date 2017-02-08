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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginFlyvemdmdemoConfig extends CommonDBTM
{

    // Type reservation : https://forge.indepnet.net/projects/plugins/wiki/PluginTypesReservation
    const RESERVED_TYPE_RANGE_MIN = 11050;
    const RESERVED_TYPE_RANGE_MAX = 11099;

    const SERVICE_ACCOUNT_NAME = 'flyvenologin';

   static $config = array();

    /**
    * Display the configuration form for the plugin.
    */
   public function showForm() {
      $config = Config::getConfigurationValues('flyvemdmdemo');

      echo '<form id="pluginFlyvemdm-config" method="post" action="./config.form.php">';
      echo '<table class="tab_cadre" cellpadding="5">';
      echo '<tr><th colspan="3">'.__('Flyve MDM settings', "flyvemdmdemo").'</th></tr>';

      $user = new User();
      if ($user->getFromDBbyName(self::SERVICE_ACCOUNT_NAME)) {
         $apiKey = $user->getField('personal_token');
      } else {
         $apiKey = '';
      }

      echo '<tr><th colspan="3">'.__('Restrictions', "flyvemdmdemo").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Default device limit per entity", "flyvemdmdemo").'</td>';
      echo '<td><input type="number" name="default_device_limit"' .
          'value="'. $config['default_device_limit'] .'" min="0" />';
      echo '</td>';
      echo '<td>'. __("No more devices than this quantity are allowed per entity by default (0 = no limitation)", "flyvemdmdemo").'</td>';
      echo '</tr>';

      echo '<tr><th colspan="3">'.__('Demo mode', "flyvemdmdemo").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Demo mode", "flyvemdmdemo").'</td>';
      echo '<td>' . Dropdown::showYesNo('demo_mode', $config['demo_mode'], -1, array('display' => false));
      echo '</td>';
      echo '<td>'. __("Demo mode enables self account creation in a dedicated entity", "flyvemdmdemo").'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Time limit", "flyvemdmdemo").'</td>';
      echo '<td>' . Dropdown::showYesNo('demo_time_limit', $config['demo_time_limit'], -1, array('display' => false));
      echo '</td>';
      echo '<td>'. __("Limit lifetime of a demo account", "flyvemdmdemo").'</td>';
      echo '</tr>';

      echo '<tr><th colspan="3">'.__('Frontend setup', "flyvemdmdemo").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Webapp URL", "flyvemdmdemo").'</td>';
      echo '<td><input type="text" name="webapp_url"' .
          'value="'. $config['webapp_url'] .'" />';
      echo '</td>';
      echo '<td>'. __("URL of the web interface used for management", "flyvemdmdemo").'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Service's User Token", "flyvemdmdemo").'</td>';
      echo '<td>' . $apiKey;
      echo '</td>';
      echo '<td>'. __("To be saved in frontend's app/config.js file", "flyvemdmdemo").'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1"><td class="center" colspan="2">';
      echo '<input type="hidden" name="id" value="1" class="submit">';
      echo '<input type="hidden" name="config_context" value="flyvemdmdemo">';
      echo '<input type="hidden" name="config_class" value="PluginFlyvemdmdemoConfig">';
      echo '<input type="submit" name="update" value="'.__('Save').'" class="submit">';
      echo '</td></tr>';

      echo '</table>';

      Html::closeForm();
   }

    /**
    * {@inheritDoc}
    *
    * @see CommonDBTM::post_getEmpty()
    */
   public function post_getEmpty() {
      $this->fields['id'] = 1;
      $this->fields['mqtt_broker_address'] = '127.0.0.1';
      $this->fields['mqtt_broker_port'] = '1883';
   }

    /**
    * Hook for config validation before update
    *
    * @param array $input
    */
   public static function configUpdate($input) {
      if (isset($input['_CACertificateFile'])) {
         if (isset($input['_CACertificateFile'][0])) {
            $file = GLPI_TMP_DIR . "/" . $input['_CACertificateFile'][0];
            if (is_writable($file)) {
               rename($file, FLYVEMDM_CONFIG_CACERTMQTT);
            }
         }
      }
      if (isset($input['demo_mode'])) {
         if ($input['demo_mode'] != '0'
             && (!isset($input['webapp_url']) || empty($input['webapp_url']))
         ) {
            Session::addMessageAfterRedirect(__('To enable the demo mode, you must provide the webapp URL !', 'flyvemdmdemo', false, ERROR));
            unset($input['demo_mode']);
         } else {
            $config = new static();
            if ($input['demo_mode'] == 0) {
               $config->resetDemoNotificationSignature();
               $config->disableDemoAccountService();
            } else {
               $config->setDemoNotificationSignature();
               $config->enableDemoAccountService();
            }
         }
      }
      unset($input['_CACertificateFile']);
      unset($input['_tag_CACertificateFile']);
      unset($input['CACertificateFile']);
      return $input;
   }

    /**
    * {@inheritDoc}
    *
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      return $input;
   }

   protected function setDemoNotificationSignature() {
      $config = Config::setConfigurationValues(
          'core', [
          'mailing_signature' => '',
          ]
      );
   }

   protected function resetDemoNotificationSignature() {
      $config = Config::setConfigurationValues(
          'core', [
          'mailing_signature' => 'SIGNATURE',
          ]
      );
   }

   protected function enableDemoAccountService() {
      $user = new User();
      if ($user->getFromDBbyName(self::SERVICE_ACCOUNT_NAME)) {
         $user->update(
             array(
             'id'        => $user->getID(),
             'is_active' => 1
             )
         );
      }
   }

   protected function disableDemoAccountService() {
      $user = new User();
      if ($user->getFromDBbyName(self::SERVICE_ACCOUNT_NAME)) {
         $user->update(
             array(
             'id'        => $user->getID(),
             'is_active' => 0
             )
         );
      }
   }

}
