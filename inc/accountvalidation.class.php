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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * @since 1.0.2
 */
class PluginFlyvemdmdemoAccountvalidation extends CommonDBTM
{

   /**
    * Delay to activate an account; in days
    *
    * @var string
    */
   const ACTIVATION_DELAY = '1';

   /**
    * Trial duration in days
    *
    * @var string
    */
   const TRIAL_LIFETIME       = '90';

   /**
    * set the days before end of trial to send a notification
    * Must be +/- sign and integer, without space
    * @var array
    */
   private $reminderDelays = [
       PluginFlyvemdmdemoNotificationTargetAccountvalidation::EVENT_TRIAL_EXPIRATION_REMIND_1 => '15',
       PluginFlyvemdmdemoNotificationTargetAccountvalidation::EVENT_TRIAL_EXPIRATION_REMIND_2 => '5',
       PluginFlyvemdmdemoNotificationTargetAccountvalidation::EVENT_POST_TRIAL_REMIND => '-5',
   ];

   /**
    * Localized name of the type
    *
    * @param $nb  integer  number of item in the type (default 0)
    */
   public static function getTypeName($nb = 0) {
      return _n('Account validation', 'Account validations', $nb, 'flyvemdmdemo');
   }

    /**
    *
    * @return boolean
    */
   public static function canCreate() {
      return false;
   }

    /**
    *
    * @return boolean
    */
   public static function canUpdate() {
      $config = Config::getConfigurationValues('flyvemdmdemo', array('service_profiles_id'));
      $serviceProfileId = $config['service_profiles_id'];
      if ($serviceProfileId === null) {
         return false;
      }

      if ($_SESSION['glpiactiveprofile']['id'] != $serviceProfileId) {
         return false;
      }

      return true;
   }

   public function getActivationDelay() {
      return static::ACTIVATION_DELAY;
   }

   public function getTrialDuration() {
      return static::TRIAL_LIFETIME;
   }

   public function getPostReminderDelay($reminderNumber) {
      switch ($reminderNumber) {
         case 1:
            return (self::TRIAL_POST_REMIND);
         break;
      }
   }

    /**
    * {@inheritDoc}
    *
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      global $DB;

      $ok = false;
      $table = static::getTable();

      do {
         $validationPass = bin2hex(openssl_random_pseudo_bytes(32));
         $query  = "SELECT COUNT(*)
         FROM `$table`
         WHERE `validation_pass` = '$validationPass'";
         $result = $DB->query($query);

         if ($DB->result($result, 0, 0) == 0) {
            $input['validation_pass'] = $validationPass;
            $ok = true;
         }
      } while (!$ok);

      $input['validation_pass'] = $validationPass;

      return $input;
   }

   public function validateForRegisteredUser($input) {
      if (!isset($input['_validate']) || empty($input['_validate'])) {
         Session::addMessageAfterRedirect(__('Validation token missing', 'flyvemdmdemo'));
         return false;
      }

      if ($input['_validate'] != $this->fields['validation_pass']) {
         Session::addMessageAfterRedirect(__('Validation token is invalid', 'flyvemdmdemo'));
         return false;
      }

      if (preg_match('#^[a-f0-9]{64}$#', $input['_validate']) != 1) {
         Session::addMessageAfterRedirect(__('Validation token is invalid', 'flyvemdm'));
         return false;
      }

      // Check the token is still valid
      $currentDateTime = new DateTime($_SESSION["glpi_currenttime"]);
      $expirationDateTime = new DateTime($this->getField('date_creation'));
      $expirationDateTime->add(new DateInterval('P' . $this->getActivationDelay() . 'D'));
      if ($expirationDateTime < $currentDateTime) {
         Session::addMessageAfterRedirect(__('Validation token expired', 'flyvemdmdemo'));
         return false;
      }

      // The validation pass is valid
      $config = Config::getConfigurationValues(
          'flyvemdmdemo', array(
          'inactive_registered_profiles_id',
          )
      );

      // Activate the account
      if (!$this->activateAccount()) {
         return false;
      }

      // Set trial expiration date
      $input['validation_pass']  = '';
      $endTrialDateTime = $currentDateTime->add(new DateInterval('P' . $this->getTrialDuration() . 'D'));
      $input['date_end_trial'] = $endTrialDateTime->format('Y-m-d H:i:s');

      return $input;
   }

   /**
    * activate a user account by adding registered user profile and remiving inactive registered profile
    *
    * @return boolean
    */
   private function activateAccount() {
      if ($this->isNewItem()) {
         return false;
      }

      $config = Config::getConfigurationValues(
         'flyvemdmdemo',
         ['inactive_registered_profiles_id']
      );

      // Find the user_profile entry
      $userId     = $this->fields['users_id'];
      $profileId  = $config['inactive_registered_profiles_id'];
      $entityId   = $this->fields['assigned_entities_id'];
      $profile_user = new Profile_User();
      $profile_user->getFromDBByQuery(
         "WHERE `users_id` = '$userId'
          AND `profiles_id` = '$profileId'
          AND `entities_id` = '$entityId'"
      );
      if ($profile_user->isNewItem()) {
         Session::addMessageAfterRedirect(__('Failed to find your account', 'flyvemdmdemo'));
         return false;
      }

      $profile_user2 = new Profile_User();
      if ($profile_user2->add([
            'users_id'     => $userId,
            'profiles_id'  => $this->fields['profiles_id'],
            'entities_id'  => $entityId,
            'is_recursive' => $profile_user->getField('is_recursive'),
         ])
      ) {
         // If add succeeded, then delete inactive profile
         $profile_user->delete(['id' => $profile_user->getID()]);
      }
      return true;
   }

    /**
    * {@inheritDoc}
    *
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {

      // Check the user is using the service profile
      $config = Config::getConfigurationValues('flyvemdmdemo', array('service_profiles_id'));
      $serviceProfileId = $config['service_profiles_id'];
      if ($serviceProfileId === null) {
         return false;
      }

      //if (isset($_SESSION['glpiID'])) {
         //if ($_SESSION['glpiactiveprofile']['id'] == $serviceProfileId) {
            //return $this->validateForRegisteredUser($input);
         //}
      //}

      if (isset($input['_validate'])) {
         return $this->validateForRegisteredUser($input);
      }

      return $input;
   }

    /**
    * {@inheritDoc}
    *
    * @see CommonDBTM::post_updateItem()
    */
   public function post_updateItem($history = 1) {
      if (array_search('validation_pass', $this->updates) !== false) {
         // Trial begins
         NotificationEvent::raiseEvent(
             PluginFlyvemdmdemoNotificationTargetAccountvalidation::EVENT_TRIAL_BEGIN,
             $this,
             array('entities_id' => $this->getField('assigned_entities_id'))
         );

         //Add the user to the newsletter
         if ($this->fields['newsletter'] != '0') {
            $subscription = new PluginFlyvemdmdemoNewsletterSubscriber();
            $subscription->add([
               'users_id'  => $this->fields['users_id'],
            ]);
         }
      }
   }

    /**
    * Remove accounts not activated and with expired validation token
    *
    * @param  CronTask $task
    * @return integer quantity of accounts removed
    */
   public static function cronCleanupAccountActivation(CronTask $task) {
      $task->log("Delete expired account activations");

      // Compute the oldest items to keep
      // substract the interval twice to delay deletion of expired items
      $accountValidation = new static();
      $oldestAllowedItems = new DateTime($_SESSION["glpi_currenttime"]);
      $dateInterval = new DateInterval('P' . $accountValidation->getActivationDelay() . 'D');
      $oldestAllowedItems->sub($dateInterval);
      $oldestAllowedItems->sub($dateInterval);
      $oldestAllowedItems = $oldestAllowedItems->format('Y-m-d H:i:s');

      $config = Config::getConfigurationValues('flyvemdmdemo', array('inactive_registered_profiles_id'));
      $profileId = $config['inactive_registered_profiles_id'];
      $rows = $accountValidation->find(
          "`validation_pass` <> ''
            AND (`date_creation` < '$oldestAllowedItems' OR `date_creation` IS NULL)",
          '',
          '200'
      );
      $volume = 0;
      foreach ($rows as $id => $row) {
         $accountValidation->removeProfile($row['users_id'], $profileId, $row['assigned_entities_id']);
         if ($accountValidation->delete(array('id' => $id))) {
            $volume++;
         }
      }

      $task->setVolume($volume);

      return 1;
   }

    /**
    * Disable accounts with trial over
    *
    * @param  CronTask $task
    * @return integer
    */
   public static function cronDisableExpiredTrial(CronTask $task) {
      $task->log("Disable expired trial accounts");
      $volume = 0;

      $config = Config::getConfigurationValues('flyvemdmdemo', array('demo_time_limit'));
      if ($config['demo_time_limit'] < 1) {
         // Time limit disabled; disable email campaign
         $task->setVolume($volume);
         return 1;
      }

      // Compute the oldest items to keep
      $currentDateTime = new DateTime($_SESSION["glpi_currenttime"]);
      $currentDateTime = $currentDateTime->format('Y-m-d H:i:s');
      $accountValidation = new static();
      $rows = $accountValidation->find(
          "`validation_pass` = ''
                                        AND (`date_end_trial` < '$currentDateTime')
                                        AND `is_trial_ended` = '0'",
          '',
          '200'
      );

      foreach ($rows as $id => $row) {
         $accountValidation->disableTrialAccount($row['users_id'], $row['profiles_id'], $row['assigned_entities_id']);
         if ($accountValidation->update(array('id' => $id, 'is_trial_ended' => '1'))) {
            $volume++;
         }
      }

      $task->setVolume($volume);

      return 1;
   }

   /**
    *
    * @param CronTask $task
    *
    * @return number
    */
   public static function cronRemindTrialExpiration(CronTask $task) {
      $task->log("Remind the trial incoming expiration");
      $volume = 0;

      $config = Config::getConfigurationValues('flyvemdmdemo', array('demo_time_limit'));
      if ($config['demo_time_limit'] < 1) {
         // Time limit disabled; disable email campaign
         $task->setVolume($volume);
         return 1;
      }

      $accountValidation = new static();
      foreach ($accountValidation->reminderDelays as $notification => $days) {
         $deadlineDate = new DateTime($_SESSION["glpi_currenttime"] . "$days days");
         $deadlineDate = $deadlineDate->format('Y-m-d H:i:s');
         $flagColumn = $accountValidation->getReminderColumnFromNotificationId($notification);
         $rows = $accountValidation->find(
            "`validation_pass` = ''
             AND (`date_end_trial` < '$deadlineDate')
             AND `$flagColumn` = '0'",
            '',
            '100'
         );

         foreach ($rows as $row) {
            $accountValidation = new static();
            $accountValidation->getFromDB($row['id']);
            NotificationEvent::raiseEvent(
               $notification,
               $accountValidation,
               ['entities_id' => $accountValidation->getField('assigned_entities_id')]
            );
            if ($accountValidation->update(array('id' => $row['id'], $flagColumn => '1'))) {
               $volume++;
            }
         }
      }

      $task->setVolume($volume);

      return 1;
   }

    /**
    * Is the demo mode enabled ?
    *
    * @return boolean true if demo mode is enabled
    */
   public function isDemoEnabled() {
      $config = Config::getConfigurationValues(
          'flyvemdmdemo', array(
          'demo_mode',
          'webapp_url',
          'inactive_registered_profiles_id',
          )
      );

      if (!isset($config['demo_mode'])
          || !isset($config['webapp_url'])
          || !isset($config['inactive_registered_profiles_id'])
      ) {
         return false;
      }

      if ($config['demo_mode'] == '0' || empty($config['webapp_url'])) {
         return false;
      }

      return true;
   }

    /**
    * Remove habilitation to an entity with a profile from a user
    *
    * @param integer $userId
    * @param integer $profileId
    * @param integer $entityId
    */
   protected function removeProfile($userId, $profileId, $entityId) {
      $profileUser = new Profile_User();
      $rows = $profileUser->find("`users_id` = '$userId'");
      if (count($rows) > 1) {
         $success = $profileUser->deleteByCriteria(
             array(
             'users_id'     => $userId,
             'entities_id'  => $entityId,
             'profiles_id'  => $profileId,
             ), true
         );
         $entity = new Entity();
         $entity->delete(array('id' => $entityId), true);
      } else {
         $user = new User();
         $success = $user->delete(array('id' => $userId), true);
      }

      if ($success) {
         $entity = new Entity();
         $entity->delete(array('id' => $entityId), true);
      }
   }

    /**
    * Diable a trial user account
    *
    * @param integer $userId
    * @param integer $profileId
    * @param integer $entityId
    */
   protected function disableTrialAccount($userId, $profileId, $entityId) {
      $config = Config::getConfigurationValues('flyvemdmdemo', array('demo_time_limit', 'inactive_registered_profiles_id'));
      if ($config['demo_time_limit'] > 0) {
         $inactiveProfileId = $config['inactive_registered_profiles_id'];
         $profile_user = new Profile_User();
         $profile_users = $profile_user->find(
             "`entities_id` = '$entityId'
               AND `profiles_id` = '$profileId'"
         );
         foreach ($profile_users as $profile_user) {
            $userId = $profile_user['users_id'];
            $profile_user2 = new Profile_User();
            if ($profile_user2->add(
              array(
               'users_id'     => $userId,
               'profiles_id'  => $inactiveProfileId,
               'entities_id'  => $entityId,
               'is_recursive' => $profile_user['is_recursive'],
               )
            )
            ) {
               // If add succeeded, then delete active profile
               $oldProfile_user = new Profile_User();
               $oldProfile_user->delete(
                   array(
                    'id'        => $profile_user['id'],
                   )
                );
            }
         }
      }
   }


   public function getReminderDelay($notificationId) {
      return $this->reminderDelays[$notificationId];
   }

   /**
    * Return the column name of the sent flag for each reminder
    *
    * @param string $notificationId
    *
    * @return string
    */
   private function getReminderColumnFromNotificationId($notificationId) {
      switch ($notificationId) {
         case PluginFlyvemdmdemoNotificationTargetAccountvalidation::EVENT_TRIAL_EXPIRATION_REMIND_1:
            return 'is_reminder_1_sent';
            break;

         case PluginFlyvemdmdemoNotificationTargetAccountvalidation::EVENT_TRIAL_EXPIRATION_REMIND_2:
            return 'is_reminder_2_sent';
            break;

         case PluginFlyvemdmdemoNotificationTargetAccountvalidation::EVENT_POST_TRIAL_REMIND:
            return 'is_post_reminder_sent';
            break;
      }

      return '';
   }

   public function getReminderDelays() {
      return $this->reminderDelays;
   }
}