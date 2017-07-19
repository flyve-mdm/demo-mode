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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.33
 */
class PluginFlyvemdmdemoNotificationTargetAccountvalidation extends NotificationTarget
{

    const EVENT_SELF_REGISTRATION          = 'plugin_flyvemdm_self_registration';
    const EVENT_TRIAL_BEGIN                = 'plugin_flyvemdm_trial_begin';
    const EVENT_TRIAL_EXPIRATION_REMIND_1  = 'plugin_flyvemdm_trial_remind_1';
    const EVENT_TRIAL_EXPIRATION_REMIND_2  = 'plugin_flyvemdm_trial_remind_2';
    const EVENT_POST_TRIAL_REMIND          = 'plugin_flyvemdm_post_trial';

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
          self::EVENT_SELF_REGISTRATION => __('User registration', 'flyvemdmdemo')
      );
   }

    /**
    * @param NotificationTarget $target
    */
   public static function addEvents($target) {
       Plugin::loadLang('flyvemdmdemo');
       $target->events[self::EVENT_SELF_REGISTRATION] = __('User registration', 'flyvemdmdemo');
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
    * @param NotificationTarget $event
    * @param array              $options
    */
   public static function getAdditionalDatasForTemplate(NotificationTarget $event) {
      $signatureDocuments = array_values(
          Config::getConfigurationValues(
              'flyvemdmdemo', [
              'social_media_twit',
              'social_media_gplus',
              'social_media_facebook',
              ]
          )
      );

      switch ($event->raiseevent) {
         case self::EVENT_SELF_REGISTRATION:
            $config = Config::getConfigurationValues('flyvemdmdemo', array('webapp_url'));
            if (isset($event->obj)) {
                $accountValidation = $event->obj;
                $accountValidationId = $accountValidation->getID();
                $validationToken = $accountValidation->getField('validation_pass');
                $validationUrl = $config['webapp_url'] . "#!/account/$accountValidationId/validation/$validationToken";

                $activationDelay = new DateInterval('P' . $accountValidation->getActivationDelay() . 'D');
                $activationDelay = $activationDelay->format('%d');
                $activationDelay.= " " . _n('day', 'days', $activationDelay, 'flyvemdmdemo');

                $trialDuration = new DateInterval('P' . $accountValidation->getTrialDuration() . 'D');
                $trialDuration = $trialDuration->format('%d');
                $trialDuration.= " " . _n('day', 'days', $trialDuration, 'flyvemdmdemo');

                // Fill the template
                $event->data['##flyvemdmdemo.registration_url##'] = $validationUrl;
                $event->data['##flyvemdmdemo.webapp_url##'] = $config['webapp_url'];
                $event->data['##flyvemdmdemo.activation_delay##'] = $activationDelay;
                $event->data['##flyvemdmdemo.trial_duration##'] = $trialDuration;

                $event->obj->documents = $signatureDocuments;
            }
            break;

         case self::EVENT_TRIAL_BEGIN:
            $config = Config::getConfigurationValues('flyvemdmdemo', array('webapp_url'));
            if (isset($event->obj)) {
                $event->data['##flyvemdmdemo.webapp_url##'] = $config['webapp_url'];

                $event->obj->documents = $signatureDocuments;
            }
            break;

         case self::EVENT_TRIAL_EXPIRATION_REMIND_1:
         case self::EVENT_TRIAL_EXPIRATION_REMIND_2:
            $config = Config::getConfigurationValues('flyvemdmdemo', array('webapp_url'));
            if (isset($event->obj)) {
                $accountValidation = $event->obj;

                // Compute the remaining trial days depending on the first or second reminder
               switch ($event->raiseevent) {
                  case  self::EVENT_TRIAL_EXPIRATION_REMIND_1:
                      $delay = $accountValidation->getReminderDelay(1);
                    break;

                  case  self::EVENT_TRIAL_EXPIRATION_REMIND_2:
                      $delay = $accountValidation->getReminderDelay(2);
                    break;
               }
                $delay.= " " . _n('day', 'days', $delay, 'flyvemdmdemo');

                $event->data['##flyvemdmdemo.webapp_url##'] = $config['webapp_url'];
                $event->data['##flyvemdmdemo.days_remaining##'] = $delay;

                $event->obj->documents = $signatureDocuments;
            }
            break;

         case self::EVENT_POST_TRIAL_REMIND:
            if (isset($event->obj)) {
                $accountValidation = $event->obj;

                $event->obj->documents = $signatureDocuments;
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
