<?php
/**
 * LICENSE
 *
 * Copyright © 2017 Teclib'
 * Copyright © 2010-2017 by the FusionInventory Development Team.
 *
 * This file is part of Flyve MDM Demo Plugin for GLPI.
 *
 * Flyve MDM Demo Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile
 * device management software.
 *
 * Flyve MDM Demo Plugin for GLPI is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * Flyve MDM Demo Plugin for GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 * along with Flyve MDM Demo Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 * ------------------------------------------------------------------------------
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi-demo
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

use Glpi\Test\PluginDB;
use Glpi\Test\CommonTestCase;

class PluginInstallTest extends CommonTestCase
{

   public static function setupBeforeClass() {
      // Do not run parent::setupBeforeClass()
   }

   public function setUp() {
      parent::setUp();
      $this->assertTrue(self::login('glpi', 'glpi', true));
   }

   public function testInstallPlugin() {
      global $DB;

      $this->assertTrue($DB->connected, "Problem connecting to the Database");

      $this->login('glpi', 'glpi');

      //Drop plugin configuration if exists
      $config = new Config();
      $config->deleteByCriteria(array('context' => 'flyvemdmdemo'));

      // Drop tables of the plugin if they exist
      $query = "SHOW TABLES";
      $result = $DB->query($query);
      while ($data = $DB->fetch_array($result)) {

         if (strstr($data[0], "glpi_plugin_flyvemdmdemo") !== false) {
            $DB->query("DROP TABLE ".$data[0]);
         }
      }

      self::resetGLPILogs();

      $plugin = new Plugin();
      $plugin->getFromDBbyDir("flyvemdmdemo");

      ob_start(
            function ($in) {
                return '';
            }
      );
      $plugin->install($plugin->fields['id']);
      ob_end_clean();

      // Check schema
      $PluginDBTest = new PluginDB();
      $PluginDBTest->checkInstall("flyvemdmdemo", "install");

      // Check cron jobs
      $crontask = new CronTask();

      // Enable the plugin
      $plugin->activate($plugin->fields['id']);
      $this->assertTrue($plugin->isActivated("flyvemdmdemo"), "Cannot enable the plugin");

   }

   public function testConfigurationExists() {
      $config = Config::getConfigurationValues('flyvemdmdemo');
      $expected = [
          'registered_profiles_id',
          'inactive_registered_profiles_id',
          'service_profiles_id',
          'default_device_limit',
          'demo_mode',
          'webapp_url',
          'demo_time_limit',
      ];
      $diff = array_diff_key(array_flip($expected), $config);
      $this->assertEquals(0, count($diff));

      return $config;
   }

   public function testServiceAccountExists() {
      $user = new User();
      $this->assertTrue($user->getFromDBbyName('flyvenologin'));
      return $user;
   }

    /**
    * @depends testConfigurationExists
    */
   public function testRegisteredProfileExists($config) {
      $profileId = $config['registered_profiles_id'];
      $profile = new Profile();
      $this->assertTrue($profile->getFromDB($profileId));
   }

    /**
    * @depends testConfigurationExists
    */
   public function testInactiveRegisteredProfileExists($config) {
      $profileId = $config['inactive_registered_profiles_id'];
      $profile = new Profile();
      $this->assertTrue($profile->getFromDB($profileId));
   }
}
