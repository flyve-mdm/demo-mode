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
use Flyvemdm\Test\ApiRestTestCase;

class RegisteredUserEditEntityConfig extends ApiRestTestCase
{

    /**
    * Email address of a registered user
    *
    * @var string
    */
   protected static $registeredUser;

    /**
    * Password of a registered user
    *
    * @var string
    */
   protected static $registeredPass;

    /**
    * The current session token
    *
    * @var string
    */
   protected static $sessionToken;

    /**
    * entity id of the registered user
    *
    * @var integer
    */
   protected static $entityId;

   public static function setupBeforeClass() {
      parent::setupBeforeClass();

      self::login('glpi', 'glpi');
      self::$registeredUser = 'johndoe@localhost.local';
      self::$registeredPass = 'password';

      $user = new PluginFlyvemdmdemoUser();
      $user->add(
          [
          'name'      => self::$registeredUser,
          'password'  => self::$registeredPass,
          'password2' => self::$registeredPass,
          ]
      );

      config::setConfigurationValues(
          'flyvemdmdemo', [
          'demo_mode'       => 1,
          'webapp_url'      => 'https://localhost',
          'demo_time_limit' => '0',
          ]
      );
   }

    /**
    *
    */
   public function testInitGetSessionToken() {
      $this->initSessionByCredentials(self::$registeredUser, self::$registeredPass);
      $this->assertEquals(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      self::$sessionToken = $this->restResponse['session_token'];
      self::$entityId = $_SESSION['glpiactive_entity'];
   }

   public function testEditDeviceLimit() {
      // getDefault limit
      $config = Config::getConfigurationValues('flyvemdm', array('default_device_limit'));

      $body = json_encode(
          [
          'input' => [
                'id'              => self::$entityId,
                'device_limit'    => '999',
          ]]
      );

      // update the device limit
      $this->entityConfig('put', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(400, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(500, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      $entityConfig = new PluginFlyvemdmEntityconfig();
      $entityConfig->getFromDB($_SESSION['glpiactive_entity']);

      $this->assertTrue(
          $entityConfig->update(
              [
              'id'              => $_SESSION['glpiactive_entity'],
              'device_limit'    => 999
              ]
          )
      );

      // Check the limit has not changed
      $this->assertEquals($config['default_device_limit'], $entityConfig->getField('device_limit'));

   }

   public function testEditDownloadUrl() {
      // getDefault limit
      $config = Config::getConfigurationValues('flyvemdm', array('default_agent_url'));

      // update the device limit
      $entityConfig = new PluginFlyvemdmEntityconfig();
      $entityConfig->getFromDB($_SESSION['glpiactive_entity']);

      $this->assertTrue(
          $entityConfig->update(
              [
              'id'              => $_SESSION['glpiactive_entity'],
              'download_url'    => 'http://myserver.com/agent_v0123.apk'
              ]
          )
      );

      // Check the limit has not changed
      $this->assertEquals('http://myserver.com/agent_v0123.apk', $entityConfig->getField('download_url'));
   }


   public function testEditInvitationTokenLife() {

      // update the device limit
      $entityConfig = new PluginFlyvemdmEntityconfig();
      $entityConfig->getFromDB($_SESSION['glpiactive_entity']);

      $this->assertTrue(
          $entityConfig->update(
              [
              'id'                 => $_SESSION['glpiactive_entity'],
              'agent_token_life'   => 'P99D'
              ]
          )
      );

      // Check the limit has not changed
      $this->assertEquals('P99D', $entityConfig->getField('agent_token_life'));
   }
}