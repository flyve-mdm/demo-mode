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
      parent::setUpBeforeClass();
      self::$registeredUser = 'registereduser@localhost.local';
      self::$registeredPass = 'password';
      self::$guestEmail     = 'guestuser@localhost.local';

      self::login('glpi', 'glpi', true);
      $user = new PluginFlyvemdmdemoUser();
      $userId = $user->add(
          [
          'name'      => self::$registeredUser,
          'password'  => self::$registeredPass,
          'password2' => self::$registeredPass
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
