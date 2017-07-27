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

class FleetIntegrationTest extends ApiRestTestCase
{

    /**
    * Email address of a registered user
    *
    * @var string
    */
   protected static $registeredUser;

    /**
    * password of a registered user
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
    * Entity ID of the registered user
    *
    * @var integer
    */
   protected static $entityId;

    /**
    *
    * @var string
    */
   protected static $guestEmail;

   public static function setUpBeforeClass() {
      global $DB;

      parent::setUpBeforeClass();
      self::$registeredUser = 'registereduser@localhost.local';
      self::$registeredPass = 'password';
      self::$guestEmail     = 'guestuser@localhost.local';

      self::login('glpi', 'glpi', true);

      // create a captcha
      $captchaTable = PluginFlyvemdmdemoCaptcha::getTable();
      $DB->query("INSERT INTO `$captchaTable`
                  (`ip_address`, `answer`)
                  VALUES ('127.0.0.1', 'aaaaa')");
      $captchaId = $DB->insert_id();

      $user = new PluginFlyvemdmdemoUser();
      $userId = $user->add(
          [
          'name'      => self::$registeredUser,
          'password'  => self::$registeredPass,
          'password2' => self::$registeredPass,
          '_plugin_flyvemdmdemo_captchas_id' => $captchaId,
          '_answer' => 'aaaaa',
          ]
      );

      self::login(self::$registeredUser, self::$registeredPass, true);

      config::setConfigurationValues(
          'flyvemdmdemo', [
          'demo_mode'       => 1,
          'webapp_url'      => 'https://localhost',
          'demo_time_limit' => '0',
          ]
      );

      $invitation = new PluginFlyvemdmInvitation();
      $invitationId = $invitation->add(
          [
          'entities_id'  => self::$entityId,
          '_useremails'  => self::$guestEmail,
          ]
      );

      self::loginWithUserToken(User::getToken($invitation->getField('users_id'), 'api_token'));

      $agent = new PluginFlyvemdmAgent();
      $agent ->add(
          [
          'entities_id'        => 0,
          '_email'             => self::$guestEmail,
          '_invitation_token'  => $invitation->getField('invitation_token'),
          '_serial'            => 'AZERTY',
          'csr'                => '',
          'firstname'          => 'John',
          'lastname'           => 'user',
          'version'            => '1.0.0',
          ]
      );
   }

    /**
    * login as a registered user
    */
   public function testInitGetSessionToken() {
      $this->initSessionByCredentials(self::$registeredUser, self::$registeredPass);
      $this->assertEquals(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      self::$sessionToken = $this->restResponse['session_token'];
      self::$entityId = $_SESSION['glpiactive_entity'];
   }


    /**
    * @depends testInitGetSessionToken
    */
   public function testDeleteDefaultFleet() {
      $fleet = new PluginFlyvemdmFleet();
      $entityId = self::$entityId;
      $this->assertTrue($fleet->getFromDBByQuery("WHERE `is_default`='1' AND `entities_id`='$entityId' LIMIT 1"));
      $body = json_encode(
          [
          'input' => [
                'id'     => $fleet->getID(),
          ]
          ]
      );
      $this->fleet('delete', self::$sessionToken, $body);
      $this->assertGreaterThanOrEqual(400, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLEssThan(500, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
   }
}
