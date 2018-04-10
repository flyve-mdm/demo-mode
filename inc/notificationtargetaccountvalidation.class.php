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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.33
 */
class PluginFlyvemdmdemoNotificationTargetAccountvalidation extends NotificationTarget
{

    const EVENT_SELF_REGISTRATION          = 'plugin_flyvemdmdemo_self_registration';
    const EVENT_TRIAL_BEGIN                = 'plugin_flyvemdmdemo_trial_begin';
    const EVENT_TRIAL_EXPIRATION_REMIND_1  = 'plugin_flyvemdmdemo_trial_remind_1';
    const EVENT_TRIAL_EXPIRATION_REMIND_2  = 'plugin_flyvemdmdemo_trial_remind_2';
    const EVENT_POST_TRIAL_REMIND          = 'plugin_flyvemdmdemo_post_trial';

    /**
    *
    * @param number $nb
    * @return translated
    */
   static function getTypeName($nb=0) {
      return _n('Account validation', 'Account validations', $nb);
   }

    /**
    * Define plugins notification events
    *
    * @return Array Events ids => names
    */
   public function getEvents() {
      return array(
            self::EVENT_SELF_REGISTRATION          => __('User registration', 'flyvemdmdemo'),
            self::EVENT_TRIAL_BEGIN                => __('Start of trial period', 'flyvemdmdemo'),
            self::EVENT_TRIAL_EXPIRATION_REMIND_1  => __('First expiration reminder', 'flyvemdmdemo'),
            self::EVENT_TRIAL_EXPIRATION_REMIND_2  => __('Second expiration reminder', 'flyvemdmdemo'),
            self::EVENT_POST_TRIAL_REMIND          => __('Post-trial reminder', 'flyvemdmdemo'),
      );
   }

    /**
    * Get available tags for plugins notifications
    */
   public function getTags() {
      $tagCollection = array(
          'flyvemdmdemo.registration_url'      => __('Account validation URL', 'flyvemdmdemo'),
          'flyvemdmdemo.webapp_url'            => __('URL to the web application', 'flyvemdmdemo'),
          'flyvemdmdemo.activation_delay'      => __('Account activation delay', 'flyvemdmdemo'),
          'flyvemdmdemo.trial_duration'        => __('Duration of a trial account', 'flyvemdmdemo'),
          'flyvemdmdemo.days_remaining'        => __('Trial days remaining', 'flyvemdmdemo'),
      );

      foreach ($tagCollection as $tag => $label) {
         $this->addTagToList(
             array('tag'    => $tag,
             'label'  => $label,
             'value'  => true,
             'events' => NotificationTarget::TAG_FOR_ALL_EVENTS)
         );
      }

   }

    /**
    * @param string $event
    * @param array  $options
    */
   public function addDataForTemplate($event, $options = array()) {
      $signatureDocuments = array_values(
          Config::getConfigurationValues(
              'flyvemdmdemo', [
              'social_media_twit',
              'social_media_gplus',
              'social_media_facebook',
              ]
          )
      );

      switch ($event) {
         case self::EVENT_SELF_REGISTRATION:
            $config = Config::getConfigurationValues('flyvemdmdemo', array('webapp_url'));
            if (isset($this->obj)) {
                $accountValidation = $this->obj;
                $accountValidationId = $accountValidation->getID();
                $validationToken = $accountValidation->getField('validation_pass');
                $validationUrl = $config['webapp_url'] . "validateAccount/$accountValidationId/validation/$validationToken";

                $activationDelay = new DateInterval('P' . $accountValidation->getActivationDelay() . 'D');
                $activationDelay = $activationDelay->format('%d');
                $activationDelay.= " " . _n('day', 'days', $activationDelay, 'flyvemdmdemo');

                $trialDuration = new DateInterval('P' . $accountValidation->getTrialDuration() . 'D');
                $trialDuration = $trialDuration->format('%d');
                $trialDuration.= " " . _n('day', 'days', $trialDuration, 'flyvemdmdemo');

                // Fill the template
                $this->data['##flyvemdmdemo.registration_url##'] = $validationUrl;
                $this->data['##flyvemdmdemo.webapp_url##'] = $config['webapp_url'];
                $this->data['##flyvemdmdemo.activation_delay##'] = $activationDelay;
                $this->data['##flyvemdmdemo.trial_duration##'] = $trialDuration;

                $this->obj->documents = $signatureDocuments;
            }
            break;

         case self::EVENT_TRIAL_BEGIN:
            $config = Config::getConfigurationValues('flyvemdmdemo', array('webapp_url'));
            if (isset($this->obj)) {
                $this->data['##flyvemdmdemo.webapp_url##'] = $config['webapp_url'];

               $this->obj->documents = $signatureDocuments;
            }
            break;

         case self::EVENT_TRIAL_EXPIRATION_REMIND_1:
         case self::EVENT_TRIAL_EXPIRATION_REMIND_2:
            $config = Config::getConfigurationValues('flyvemdmdemo', array('webapp_url'));
            if (isset($this->obj)) {
                $accountValidation = $this->obj;

                // Compute the remaining trial days depending on the first or second reminder
                $delay = $accountValidation->getReminderDelay($event);
                $delay .= " " . _n('day', 'days', $delay, 'flyvemdmdemo');

                $this->data['##flyvemdmdemo.webapp_url##'] = $config['webapp_url'];
                $this->data['##flyvemdmdemo.days_remaining##'] = $delay;

                $this->obj->documents = $signatureDocuments;
            }
            break;

         case self::EVENT_POST_TRIAL_REMIND:
            if (isset($this->obj)) {
                $accountValidation = $this->obj;

                $this->obj->documents = $signatureDocuments;
            }
      }
   }

    /**
    * Return all the targets for this notification
    * Values returned by this method are the ones for the alerts
    * Can be updated by implementing the getAdditionnalTargets() method
    * Can be overwitten (like dbconnection)
    *
    * @param $entity the entity on which the event is raised
    */
   public function addNotificationTargets($entity) {
      $this->addTarget(Notification::USER, __('Registered user', 'flyvemdmdemo'));
   }

    /**
    *
    * @param  array $data
    * @param  array $options
    */
   public function addSpecificTargets($data, $options) {
      if ($data['type'] == Notification::USER_TYPE) {
         switch ($data['items_id']) {
            case Notification::USER:
               if ($this->obj->getType() == 'PluginFlyvemdmdemoAccountvalidation') {
                  $this->addToRecipientsList(
                      [
                      'users_id' => $this->obj->getField('users_id')
                      ]
                  );
               }
             break;
         }
      }
   }
}
