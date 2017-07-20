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

use Glpi\Test\CommonTestCase;

class GLPIInstallTest extends CommonTestCase
{

   protected function setupGLPI() {
      global $CFG_GLPI;

      $settings = [
            'use_notifications' => '1',
            'notifications_mailing' => '1',
            'enable_api'  => '1',
            'enable_api_login_credentials'  => '1',
            'enable_api_login_external_token'  => '1',
      ];
      config::setConfigurationValues('core', $settings);

      $CFG_GLPI = $settings + $CFG_GLPI;
   }

   public function testInstallDependencies() {
      global $DB;

      $this->setupGLPI();

      self::setupGLPIFramework();
      $this->assertTrue(self::login('glpi', 'glpi', true));

      //$DB = new DB();

      // Install FusionInventory
      //define('FUSINV_ROOT', GLPI_ROOT . DIRECTORY_SEPARATOR . '/plugins/fusioninventory');

      $plugin = new Plugin;
      $plugin->getFromDBbyDir('fusioninventory');
      ob_start(
            function ($in) {
                return '';
            }
      );
      $plugin->install($plugin->getID());
      ob_end_clean();
      $plugin->activate($plugin->getID());
      $this->assertTrue($plugin->isInstalled('fusioninventory') && $plugin->isActivated('fusioninventory'));

      $plugin = new Plugin;
      $plugin->getFromDBbyDir('flyvemdm');
      ob_start(
            function ($in) {
                return '';
            }
      );
      $plugin->install($plugin->getID());
      ob_end_clean();
      $plugin->activate($plugin->getID());
      $this->assertTrue($plugin->isInstalled('flyvemdm') && $plugin->isActivated('flyvemdm'));
   }

}