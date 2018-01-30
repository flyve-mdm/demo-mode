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

use Gregwar\Captcha\CaptchaBuilder;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlyvemdmdemoCaptcha extends CommonDBTM {

   /**
    *
    * @var array $creationLimit delay  => limit oc creation within the delay
    */
   protected static $creationLimit = [
      '5 minute'  => 6,
      '15 minute' => 15,
      '60 minute' => 20
   ];

   protected $answer;

   /**
    * @var string $rightname name of the right in DB
    */
   public static $rightname            = 'flyvemdmdemo:captcha';

   /**
    * Localized name of the type
    *
    * @param $nb  integer  number of item in the type (default 0)
    */
   public static function getTypeName($nb = 0) {
      return _n('Captcha', 'Captchas', $nb, 'flyvemdmdemo');
   }

   public function canViewItem() {
      $ip = Toolbox::getRemoteIpAddress();

      return parent::canViewItem() && ($this->fields['ip_address'] == $ip);
   }

   public function getCreationLimits() {
      return static::$creationLimit;
   }

   public function prepareInputForAdd($input) {
      // Get the IP of the remote client
      $ip = Toolbox::getRemoteIpAddress();

      // Check creation abuse within a minute
      foreach (static::$creationLimit as $delay => $limt) {
         $datetime = new DateTime("-$delay");
         $countPerDelay = countElementsInTable(static::getTable(),
                                               ['WHERE' => [
                                                  'date_creation' => ['>=', $datetime->format('Y-m-d H:i:s')],
                                                  'AND' => ['ip_address'    => $ip],
                                               ]]);
         if ($countPerDelay >= $limt) {
            Session::addMessageAfterRedirect(__('Too many captcha requests', 'flyvemdmdemo'));
            return false;
         }
      }

      // Create a captcha to generate a challenge
      $captchaBuilder      = new CaptchaBuilder();
      $input['answer']     = $captchaBuilder->getPhrase();

      // Add the IP address of the requester
      $input['ip_address'] = addslashes($ip);

      return $input;
   }

   public function post_getFromDB() {
      if (isAPI()
          && (isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == 'application/octet-stream'
             || isset($_GET['alt']) && $_GET['alt'] == 'media')) {

         // Build a captcha
         $captchaBuilder = new CaptchaBuilder($this->fields['answer']);
         $captchaBuilder->build();
         header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
         header('Pragma: private'); /// IE BUG + SSL
         header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
         header('Content-Type: image/jpeg');
         header("Content-Transfer-Encoding: binary\n");
         header('Connection: close');
         $this->fields['_challlenge'] = $captchaBuilder->output();
         exit();
      }

      // Don't disclose IP or answer
      $this->answer = $this->fields['answer'];
      $this->fields['answer'] = '';
      $this->fields['ip_address'] = '';
   }

   public function challengeAnswer($answer) {
      if (empty($answer)) {
         return false;
      }

      return $this->answer == $answer;
   }

   public function getSearchOptionsNew() {
      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => __('Characteristics')
      ];

      return $tab;
   }

   /**
    * Give cron information
    *
    * @param $name : automatic action's name
    *
    * @return arrray of information
    **/
   static function cronInfo($name) {

      switch ($name) {
         case 'captcha' :
            return array('description' => __('Cleanup old captchas'));
      }
      return array();
   }

   /**
    * Cron action on captchas : cleanup old captchas
    *
    * @param CronTask $task for log, if NULL display (default NULL)
    *
    * @return integer 1 if an action was done, 0 if not
    **/
   public static function cronCleanup(CronTask $task = NULL) {
      // Find retention to apply
      $retain = static::$creationLimit;
      end($retain);
      $delay = key($retain);
      $captcha = new static();
      $datetime = new DateTime("-$delay");

      $cron_status = 0;
      $rows = getAllDatasFromTable(static::getTable(), " `date_creation` < '" . $datetime->format('Y-m-d H:i:s') . "'");
      foreach ($rows as $row) {
         if ($captcha->delete(['id'  => $row['id']])) {
            $task->addVolume(1);
         }
         $cron_status = 1;
      }
      return $cron_status;
   }

   /**
    * Predisclosure of sensitive fields
    * @param array $fields
    */
   public static function unsetUndisclosedFields(&$fields) {
      unset($fields['ip_address']);
      unset($fields['answer']);
   }
}
