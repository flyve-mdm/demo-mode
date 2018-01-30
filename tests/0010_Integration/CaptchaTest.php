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

use Flyvemdm\Test\ApiRestTestCase;

class CaptchaTest extends ApiRestTestCase {

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

   public static function setUpBeforeClass() {
      parent::setUpBeforeClass();

      config::setConfigurationValues(
         'flyvemdmdemo', [
            'demo_mode'       => 1,
            'webapp_url'      => 'https://localhost',
            'demo_time_limit' => '1',
         ]
      );

      $user = new User();
      $user->getFromDBbyName(PluginFlyvemdmdemoConfig::SERVICE_ACCOUNT_NAME);
      $user->update(
         [
            'id'           => $user->getID(),
            'is_active'    => '1',
         ]
      );
   }

   /**
    * @return string
    */
   public function testInitGetServiceSessionToken() {
      $user = new User();
      $user->getFromDBbyName(PluginFlyvemdmdemoConfig::SERVICE_ACCOUNT_NAME);
      $this->assertFalse($user->isNewItem());
      $userToken = $user->getField('api_token');

      $headers = ['authorization' => "user_token $userToken"];
      $this->emulateRestRequest('get', 'initSession', $headers);

      $this->assertEquals(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      return $this->restResponse['session_token'];
   }

   /**
    *
    */
   public function captchaProvider() {
      return [
         [
            'remote_ip' => '123.123.123.123',
            'my_ip'     => '123.123.123.123',
            'expected'  => true,
         ],
         [
            'remote_ip'  => '231.231.231.231',
            'my_ip'      => '123.123.123.123',
            'expected'  => false,
         ]
      ];
   }

   /**
    * @dataProvider captchaProvider
    *
    * @param array $input the captcha to Create
    * @param string $remoteIp the IP address of the captcha creator
    * @param string $myIp  the IP address if the captcha challenger
    */
   public function testRight($remoteIp, $myIp, $expectedResult) {
      // save $_SERVER before altering it
      $saveServer = $_SERVER;

      // Create a captcha with an IP address
      $_SERVER['REMOTE_ADDR'] = $remoteIp;
      $captcha = new PluginFlyvemdmdemoCaptcha();
      $captcha->add([]);

      $_SERVER['REMOTE_ADDR'] = $myIp;
      $this->assertSame($expectedResult, $captcha->canViewItem());

      // restore initial $_SERVER
      $_SERVER = $saveServer;
   }

   /**
    * Test an IP address cannot generate too many captchas in a short delay
    */
   public function testCaptchaCreationAttack() {
      // save $_SERVER before altering it
      $saveServer = $_SERVER;

      // Create the maximum allowed captchas
      $captcha = new PluginFlyvemdmdemoCaptcha();

      // delete all captchas
      $captcha->deleteByCriteria(['date_creation' => ['>', '0000-00-00 00:00:00']]);

      // Test the first level of limits
      $limits = $captcha->getCreationLimits();
      reset($limits);
      $limit = current($limits);
      $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
      for ($i = 1; $i <= $limit; $i++) {
         $this->assertNotFalse($captcha->add([]));
      }

      $this->assertFalse($captcha->add([]));

      // restore initial $_SERVER
      $_SERVER = $saveServer;
   }


   /**
    * test the ip address and the answer are not disclosed
    *
    * @depends testInitGetServiceSessionToken
    */
   public function testGetCaptcha($sessionToken) {
      // test get without the API
      $saveServer = $_SERVER;
      $_SERVER["REMOTE_ADDR"] = '127.0.0.1';
      $captcha = new PluginFlyvemdmdemoCaptcha();
      $this->assertNotFalse($captcha->add([]));
      $this->assertNotFalse($captcha->getFromDB($captcha->getID()));
      $this->assertEquals('', $captcha->getField('ip_address'));
      $this->assertEquals('', $captcha->getField('answer'));
      $_SERVER = $saveServer;

      // test get with the API
      // disabled due to exit() in the captcha class
      /**
      $this->captcha('get', $sessionToken, '', [
         'alt' => 'media'
      ]);
      $this->assertEquals(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      // if a resource is created then this is a valid image
      $imageResource = imagecreatefromstring($this->restResponse);
      $this->assertNotFalse($imageResource);
      */

   }

   public function testCleanup() {
      global $DB;

      // create an old captcha
      $table = PluginFlyvemdmdemoCaptcha::getTable();
      $datetime = (new DateTime('-1 week'))->format('Y-m-d H:i:s');
      $DB->query("INSERT INTO `$table`
                  (`date_creation`) VALUES ('$datetime')");
      $idToDelete = $DB->insert_id();

      PluginFlyvemdmdemoCaptcha::cronCleanup(new CronTask());
      $captcha = new PluginFlyvemdmdemoCaptcha();
      $captcha->getFromDB($idToDelete);
      $this->assertTrue($captcha->isNewItem());
   }

   public function tearDown() {
      // delete all captchas
      $captcha = new PluginFlyvemdmdemoCaptcha();
      $captcha->deleteByCriteria(['date_creation' => ['>', '0000-00-00 00:00:00']]);
   }
}
