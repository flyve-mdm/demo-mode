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

use Glpi\Test\CommonTestCase;

class RegisteredUserProfileIntegrationTest extends CommonTestCase
{

   public static function setupBeforeClass() {
      parent::setupBeforeClass();
   }

    /**
    * @return array Rights
    */
   public function testGetRights() {
      $config = Config::getConfigurationValues('flyvemdmdemo', ['registered_profiles_id']);

      $rights = ProfileRight::getProfileRights(
          $config['registered_profiles_id'],
          array(
                PluginFlyvemdmAgent::$rightname,
                PluginFlyvemdmFleet::$rightname,
                PluginFlyvemdmPackage::$rightname,
                PluginFlyvemdmFile::$rightname,
                PluginFlyvemdmGeolocation::$rightname,
                PluginFlyvemdmWellknownpath::$rightname,
                PluginFlyvemdmPolicy::$rightname,
                PluginFlyvemdmPolicyCategory::$rightname,
                PluginFlyvemdmProfile::$rightname,
                PluginFlyvemdmEntityconfig::$rightname,
                PluginFlyvemdmInvitationlog::$rightname,
                Config::$rightname,
                User::$rightname,
                Profile::$rightname,
                Entity::$rightname,
                Computer::$rightname,
                Software::$rightname,
                NetworkPort::$rightname,
                CommonDropdown::$rightname,
          )
      );
      $this->assertGreaterThan(0, count($rights));
      return $rights;
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileAgentRight($rights) {
      $this->assertEquals(READ | UPDATE | DELETE | PURGE | READNOTE | UPDATENOTE, $rights[PluginFlyvemdmAgent::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileFleetRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT | READNOTE | UPDATENOTE, $rights[PluginFlyvemdmFleet::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfilePackageRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT | READNOTE | UPDATENOTE, $rights[PluginFlyvemdmPackage::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileFileRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT | READNOTE | UPDATENOTE, $rights[PluginFlyvemdmFile::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileGeolocationRight($rights) {
      $this->assertEquals(READ | PURGE, $rights[PluginFlyvemdmGeolocation::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileWellknownpathRight($rights) {
      $this->assertEquals(READ, $rights[PluginFlyvemdmWellknownpath::$rightname]);
   }
    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUsernProfilePolicyRight($rights) {
      $this->assertEquals(READ, $rights[PluginFlyvemdmPolicy::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfilePolicyCategoryRight($rights) {
      $this->assertEquals(READ, $rights[PluginFlyvemdmPolicyCategory::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfilePluginProfileRight($rights) {
      $this->assertEquals(PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE, $rights[PluginFlyvemdmProfile::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileUserRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT, $rights[User::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileProfileRight($rights) {
      $this->assertEquals(CREATE, $rights[Profile::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileComputerRight($rights) {
      $this->assertEquals(READ, $rights[Computer::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileSoftwareRight($rights) {
      $this->assertEquals(READ, $rights[Software::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileEntityconfigRight($rights) {
      $this->assertEquals(
          READ
                | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL
                | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE,
          $rights[PluginFlyvemdmEntityconfig::$rightname]
      );
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileDropdownRight($rights) {
      $this->assertEquals(READ, $rights[CommonDropdown::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileInvitatioinLogRight($rights) {
      $this->assertEquals(READ, $rights[PluginFlyvemdmInvitationlog::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileEntityRight($rights) {
      $this->assertEquals(CREATE, $rights[Entity::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileNetworkPortRight($rights) {
      $this->assertEquals(READ, $rights[NetworkPort::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileConfigRight($rights) {
      $this->assertEquals(READ, $rights[Config::$rightname]);
   }

    /**
    * @depends testGetRights
    * @param array $rights
    */
    public function testRegisteredUserProfileKnowbaseItemRight($rights) {
      $this->assertEquals(READ | KnowbaseItem::READFAQ | KnowbaseItem::COMMENTS, $rights[Config::$rightname]);
   }
}
