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

class DeviceEnrollmentTest extends ApiRestTestCase
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

      Config::setConfigurationValues(
          'flyvemdmdemo', [
          'demo_mode'       => 1,
          'webapp_url'      => 'https://localhost',
          'demo_time_limit' => '0',
          ]
      );

      self::login('glpi', 'glpi');
      $user = new User();
      $user->getFromDBbyName(PluginFlyvemdmdemoConfig::SERVICE_ACCOUNT_NAME);
      $user->update(
          [
          'id'           => $user->getID(),
          'is_active'    => '1',
          ]
      );

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

      $validation = new PluginFlyvemdmdemoAccountvalidation();
      $validation->getFromDBByCrit(['users_id' => $user->getID()]);
      $validation->update([
         'id'        => $validation->getID(),
         '_validate' => $validation->getField('validation_pass'),
      ]);

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

    /**
    * @depends testInitGetSessionToken
    */
   public function testRegisteredUserAddsOwnDevice() {
      $invitationCount = count($this->getInvitationsForUser(self::$registeredUser));

      $body = json_encode(
          [
          'input'     => [
                'entities_id'  => self::$entityId,
                '_useremails'  => self::$registeredUser,
          ],
          ]
      );

      $this->getFullSession(self::$sessionToken);
      $this->restResponse = $this->restResponse;

      $this->invitation('post', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      // Check the registered user has the guest profile
      $config = Config::getConfigurationValues('flyvemdm',  ['guest_profiles_id']);
      $user = new User();
      $user->getFromDBbyEmail(self::$registeredUser, '');
      $profile = new Profile();
      $profile->getFromDB($config['guest_profiles_id']);
      $profile_User = new Profile_User();
      $profile_UserId = $profile_User->getFromDBForItems($user, $profile);
      $this->assertTrue($profile_UserId);

      // Check the invitation count increaded
      $this->assertCount($invitationCount + 1, $this->getInvitationsForUser(self::$registeredUser));

      $invitation = new PluginFlyvemdmInvitation();
      $invitation->getFromDB($this->restResponse['id']);

      return $invitation;
   }

    /**
    * @depends testRegisteredUserAddsOwnDevice
    */
   public function testRegisteredUserMayHaveAnOtherDevice() {
      $invitationCount = count($this->getInvitationsForUser(self::$registeredUser));

      $headers = ['Session-Token' => self::$sessionToken];
      $body = json_encode(
          [
          'input'     => [
                'entities_id'  => self::$entityId,
                '_useremails'  => self::$registeredUser,
          ],
          ]
      );

      $this->invitation('post', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      // Check the invitation count increaded
      $this->assertCount($invitationCount + 1, $this->getInvitationsForUser(self::$registeredUser));

      $invitation = new PluginFlyvemdmInvitation();
      $invitation->getFromDB($this->restResponse['id']);

      return $invitation;
   }

   protected function getInvitationsForUser($userEmail) {
      $user = new User();
      $user->getFromDBbyEmail($userEmail, '');
      $userId = $user->getID();
      $invitation = new PluginFlyvemdmInvitation();
      $rows = $invitation->find("`users_id` = '$userId'");

      return $rows;
   }

    /**
    * @depends testRegisteredUserAddsOwnDevice
    */
   public function testRegisteredUserEnrollsFirstDevice($invitation) {
      //Login again to refresk the available profiles
      $this->killSession(self::$sessionToken);
      $this->testInitGetSessionToken();

      $config = Config::getConfigurationValues('flyvemdm', ['guest_profiles_id']);
      $this->changeActiveProfile(self::$sessionToken, $config['guest_profiles_id']);
      $this->assertEquals(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      $body = json_encode(
          [
          'input'     => [
                'entities_id'        => self::$entityId,
                '_email'             => self::$registeredUser,
                '_invitation_token'  => $invitation->getField('invitation_token'),
                '_serial'            => 'GHJK',
                'csr'                => '',
                'firstname'          => 'Registered',
                'lastname'           => 'user',
                'version'            => '1.0.0',
          ],
          ]
      );

      $this->agent('post', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      $agent = new PluginFlyvemdmAgent();
      $agent->getFromDB($this->restResponse['id']);

      return $agent;
   }

    /**
    * @depends testRegisteredUserMayHaveAnOtherDevice
    * @depends testRegisteredUserAddsOwnDevice
    * @param PluginFlyvemdmInvitation $invitation
    */
   public function testRegisteredUserEnrollsSecondDevice($invitation) {
      $headers = ['Session-Token' => self::$sessionToken];
      $body = json_encode(
          [
          'input'     => [
                'entities_id'        => self::$entityId,
                '_email'             => self::$registeredUser,
                '_invitation_token'  => $invitation->getField('invitation_token'),
                '_serial'            => 'WXCV',
                'csr'                => '',
                'firstname'          => 'Registered',
                'lastname'           => 'user',
                'version'            => '1.0.0',
          ],
          ]
      );

      $this->agent('post', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      $agent = new PluginFlyvemdmAgent();
      $agent->getFromDB($this->restResponse['id']);

      return $agent;
   }

    /**
    * @depends testRegisteredUserEnrollsFirstDevice
    * @param PluginSorkmdmAgent $agent
    */
   public function testRegisteredUserDeletesOneOfHisDevices($agent) {
      $config = Config::getConfigurationValues('flyvemdmdemo', ['registered_profiles_id']);
      $this->changeActiveProfile(self::$sessionToken, $config['registered_profiles_id']);
      $this->assertEquals(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      $body = json_encode(
          [
          'input'     => [
                'id'        => $agent->getID(),
          ]
          ]
      );
      $this->agent('delete', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
   }

    /**
    * @depends testRegisteredUserEnrollsSecondDevice
    * @depends testRegisteredUserDeletesOneOfHisDevices
    * @param PluginSorkmdmAgent $agent
    */
   public function testRegisteredUserDeletesHisLastDevice($agent) {
      $body = json_encode(
          [
          'input'     => [
                'id'        => $agent->getID(),
          ]
          ]
      );
      $this->agent('delete', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      // Check the  user do no longer have guest profile
      $config = Config::getConfigurationValues("flyvemdm", array('guest_profiles_id'));
      $user = new User();
      $user->getFromDBbyEmail(self::$registeredUser, '');
      $profile = new Profile();
      $profile->getFromDB($config['guest_profiles_id']);
      $profile_User = new Profile_User();
      $profile_UserId = $profile_User->getFromDBForItems($user, $profile);
      $this->assertFalse($profile_UserId);
   }
}