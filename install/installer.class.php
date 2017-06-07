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
 *
 * @author tbugier
 * @since 0.1.0
 *
 */
class PluginFlyvemdmdemoInstaller {

   const SERVICE_PROFILE_NAME = 'Flyve MDM service profile';

   const FLYVE_MDM_PRODUCT_WEBSITE     = 'www.flyve-mdm.com';

   const FLYVE_MDM_PRODUCT_GOOGLEPLUS  = 'https://plus.google.com/collection/c32TsB';

   const FLYVE_MDM_PRODUCT_TWITTER     = 'https://twitter.com/FlyveMDM';

   const FLYVE_MDM_PRODUCT_FACEBOOK    = 'https://www.facebook.com/Flyve-MDM-1625450937768377/';

   protected static $currentVersion = null;

   protected $migration;

   /**
    * Autoloader for installation
    */
   public function autoload($classname) {
      // useful only for installer GLPi autoloader already handles inc/ folder
      $filename = dirname(__DIR__) . '/inc/' . strtolower(str_replace('PluginFlyvemdmdemo', '', $classname)). '.class.php';
      if (is_readable($filename) && is_file($filename)) {
         include_once($filename);
         return true;
      }
   }

   /**
    *
    * Install the plugin
    *
    * @return boolean true (assume success, needs enhancement)
    *
    */
   public function install() {
      global $DB;

      spl_autoload_register(array(__CLASS__, 'autoload'));

      $this->migration = new Migration(PLUGIN_FLYVEMDMDEMO_VERSION);

      // adding DB model from sql file
      // TODO : migrate in-code DB model setup here
      if (self::getCurrentVersion() == '') {
         // Setup DB model
         $dbFile = __DIR__ . '/mysql/plugin_flyvemdmdemo_empty.sql';
         if (!$DB->runFile($dbFile)) {
            $this->migration->displayWarning("Error creating tables : " . $DB->error(), true);
            return false;
         }

         $this->createInitialConfig();
      } else {
         if ($this->endsWith(PLUGIN_FLYVEMDMDEMO_VERSION, "-dev") || (version_compare(self::getCurrentVersion(), PLUGIN_FLYVEMDMDEMO_VERSION) != 0) ) {
            // TODO : Upgrade (or downgrade)
            $this->upgrade(self::getCurrentVersion());
         }
      }

      $this->migration->executeMigration();

      $this->createFirstAccess();
      $this->createInactiveRegisteredProfileAccess();
      $this->createServiceProfileAccess();
      $this->createRegisteredProfileAccess();
      $this->createServiceUserAccount();
      $this->createNotificationTargetAccountvalidation();
      $this->createSocialMediaIcons();
      $this->createJobs();

      Config::setConfigurationValues('flyvemdmdemo', array('version' => PLUGIN_FLYVEMDMDEMO_VERSION));

      return true;
   }

   /**
    * Find a profile having the given comment, or create it
    * @param string $name    Name of the profile
    * @param string $comment Comment of the profile
    * @return integer profile ID
    */
   protected static function getOrCreateProfile($name, $comment) {
      global $DB;

      $comment = $DB->escape($comment);
      $profile = new Profile();
      $profiles = $profile->find("`comment`='$comment'");
      $row = array_shift($profiles);
      if ($row === null) {
         $profile->fields["name"] = $DB->escape(__($name, "flyvemdmdemo"));
         $profile->fields["comment"] = $comment;
         $profile->fields["interface"] = "central";
         if ($profile->addToDB() === false) {
            die("Error while creating users profile : $name\n\n" . $DB->error());
         }
         return $profile->getID();
      } else {
         return $row['id'];
      }
   }

   public static function getCurrentVersion() {
      if (self::$currentVersion === NULL) {
         $config = Config::getConfigurationValues("flyvemdmdemo", array('version'));
         if (!isset($config['version'])) {
            self::$currentVersion = '';
         } else {
            self::$currentVersion = $config['version'];
         }
      }
      return self::$currentVersion;
   }

   /**
    * Give all rights on the plugin to the profile of the current user
    */
   protected function createFirstAccess() {
      $profileRight = new ProfileRight();

      $profileRight->updateProfileRights($_SESSION['glpiactiveprofile']['id'], array(
            PluginFlyvemdmProfile::$rightname         => PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE,
            PluginFlyvemdmEntityconfig::$rightname    => READ
                                                         | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT
                                                         | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL
                                                         | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE,
      ));
   }

   protected function createServiceProfileAccess() {
      // create profile for service account (provides the API key allowing self account cezation for registered users)
      $profileId = self::getOrCreateProfile(
            self::SERVICE_PROFILE_NAME,
            __('service Flyve MDM user\'s profile. Created by Flyve MDM - do NOT modify this comment.', 'flyvemdmdemo')
            );
      Config::setConfigurationValues('flyvemdmdemo', array('service_profiles_id' => $profileId));
      $profileRight = new ProfileRight();
      $profileRight->updateProfileRights($profileId, array(
            Entity::$rightname                     => CREATE | UPDATE,
            User::$rightname                       => CREATE,
            Profile::$rightname                    => READ
      ));
   }

   /**
    * Setup rights for inactive registered users profile
    */
   protected function createInactiveRegisteredProfileAccess() {
      // create profile for registered users
      $profileId = self::getOrCreateProfile(
            __("Flyve MDM inactive registered users", "flyvemdmdemo"),
            __("inactive registered FlyveMDM users. Created by Flyve MDM - do NOT modify this comment.", "flyvemdmdemo")
            );
      Config::setConfigurationValues('flyvemdmdemo', array('inactive_registered_profiles_id' => $profileId));
   }

   /**
    * Setup rights for registered users profile
    */
   protected function createRegisteredProfileAccess() {
      // create profile for registered users
      $profileId = self::getOrCreateProfile(
            __('Flyve MDM registered users', 'flyvemdmdemo'),
            __('registered Flyve MDM users. Created by Flyve MDM - do NOT modify this comment.', 'flyvemdmdemo')
      );
      Config::setConfigurationValues('flyvemdmdemo', array('registered_profiles_id' => $profileId));
      $profileRight = new ProfileRight();
      $profileRight->updateProfileRights($profileId, array(
            PluginFlyvemdmAgent::$rightname           => READ | UPDATE | DELETE | PURGE | READNOTE | UPDATENOTE, // No create right
            PluginFlyvemdmInvitation::$rightname      => ALLSTANDARDRIGHT,
            PluginFlyvemdmFleet::$rightname           => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginFlyvemdmPackage::$rightname         => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginFlyvemdmFile::$rightname            => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginFlyvemdmGeolocation::$rightname     => READ | PURGE,
            PluginFlyvemdmWellknownpath::$rightname   => READ,
            PluginFlyvemdmPolicy::$rightname          => READ,
            PluginFlyvemdmPolicyCategory::$rightname  => READ,
            PluginFlyvemdmProfile::$rightname         => PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE,
            PluginFlyvemdmEntityconfig::$rightname    => READ
                                                         | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL
                                                         | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE,
            PluginFlyvemdmInvitationlog::$rightname   => READ,
            Config::$rightname                        => READ,
            User::$rightname                          => ALLSTANDARDRIGHT,
            Profile::$rightname                       => CREATE,
            Entity::$rightname                        => CREATE,
            Computer::$rightname                      => READ,
            Software::$rightname                      => READ,
            NetworkPort::$rightname                   => READ,
            CommonDropdown::$rightname                => READ,
      ));
      $profile = new Profile();
      $profile->update([
            'id'                 => $profileId,
            '_password_update'   => 1
      ]);
   }


   /**
    * Create service account
    */
   protected static function createServiceUserAccount() {
      $user = new User();

      $config = Config::getConfigurationValues('flyvemdmdemo', array('service_profiles_id'));
      $profile = new Profile();
      $profile->getFromDB($config['service_profiles_id']);

      if (!$user->getIdByName(PluginFlyvemdmdemoConfig::SERVICE_ACCOUNT_NAME)) {
         if (!$user->add([
               'name'            => PluginFlyvemdmdemoConfig::SERVICE_ACCOUNT_NAME,
               'comment'         => 'Flyve MDM service account',
               'firstname'       => 'Plugin Flyve MDM demo',
               'password'        => '42',
               '_profiles_id'    => $profile->getID(),
               'is_active'       => '0',
         ])) {
            die ('Could not create the service account');
         }
         User::getToken($user->getID(), 'api_token');
      }
   }

   protected function upgrade($fromVersion) {
      $toVersion   = str_replace('.', '-', PLUGIN_FLYVEMDMDEMO_VERSION);

      switch ($fromVersion) {
         default:
      }
      if ($this->endsWith(PLUGIN_FLYVEMDMDEMO_VERSION, "-dev")) {
         if (is_readable(__DIR__ . "/update_dev.php") && is_file(__DIR__ . "/update_dev.php")) {
            include __DIR__ . "/update_dev.php";
            if (function_exists('update_dev')) {
               update_dev($this->migration);
            }
         }
      }

      $this->createPolicies();
   }

   /**
    * http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
    * @param unknown $haystack
    * @param unknown $needle
    */
   protected function startsWith($haystack, $needle) {
      // search backwards starting from haystack length characters from the end
      return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
   }

   /**
    * http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
    * @param unknown $haystack
    * @param unknown $needle
    */
   protected function endsWith($haystack, $needle) {
      // search forward starting from end minus needle length characters
      return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
   }

   /**
    * Uninstall the plugin
    * @return boolean true (assume success, needs enhancement)
    */
   public function uninstall() {
      $this->deleteRelations();
      $this->deleteProfileRights();
      $this->deleteProfiles();
      $this->deleteSocialMediaIcons();
      $this->deleteTables();
      $this->deleteDisplayPreferences();
      $this->deleteNotificationTargetAccountvalidation();

      $config = new Config();
      $config->deleteByCriteria(array('context' => 'flyvemdmdemo'));

      return true;
   }

   /**
    * Cannot use the method from PluginFlyvemdmToolbox if the plugin is being uninstalled
    * @param string $dir
    */
   protected function rrmdir($dir) {
      if (file_exists($dir) && is_dir($dir)) {
         $objects = scandir($dir);
         foreach ( $objects as $object ) {
            if ($object != "." && $object != "..") {
               if (filetype($dir . "/" . $object) == "dir")
                  $this->rrmdir($dir . "/" . $object);
               else
                  unlink($dir . "/" . $object);
            }
         }
         reset($objects);
         rmdir($dir);
      }
   }

   /**
    * Generate default configuration for the plugin
    */
   protected function createInitialConfig() {
      global $CFG_GLPI;

      // New config management provided by GLPi

      $instanceId = base64_encode(openssl_random_pseudo_bytes(64, $crypto_strong));
      $newConfig = [
            'service_profiles_id'            => '',
            'registered_profiles_id'         => '',
            'inactive_registered_profiles_id'=> '',
            'default_device_limit'           => '0',
            'demo_mode'                      => '0',
            'webapp_url'                     => '',
            'demo_time_limit'                => '0',
            'social_media_facebook'          => '',
            'social_media_gplus'             => '',
            'social_media_twit'              => '',
      ];
      Config::setConfigurationValues("flyvemdmdemo", $newConfig);
   }

   /**
    * Generate HTML version of a text
    * Replaces \n by <br>
    * Encloses the text un <p>...</p>
    * Add anchor to URLs
    * @param string $text
    */
   protected static function convertTextToHtml($text) {
      $text = '<p>' . str_replace("\n\n", '</p><p>', $text) . '</p>';
      $text = '<p>' . str_replace("\n", '<br>', $text) . '</p>';
      return $text;
   }

   protected function deleteTables() {
      global $DB;

      $tables = array(
            PluginFlyvemdmdemoAccountvalidation::getTable(),
      );

      foreach ($tables as $table) {
         $DB->query("DROP TABLE IF EXISTS `$table`");
      }
   }

   protected  function deleteProfiles() {
      $config = Config::getConfigurationValues('flyvemdmdemo',
                                               array('inactive_registered_profiles_id', 'registered_profiles_id'));

      foreach ($config as $profileId) {
         $profile = new Profile();
         $profile->getFromDB($profileId);
         if (!$profile->deleteFromDB()) {
            // TODO : log or warn for not deletion of the profile
         } else {
            $profileUser = new Profile_User();
            $profileUser->deleteByCriteria(array('profiles_id' => $profileId), true);
         }
      }
   }

   protected function deleteProfileRights() {
      $rights = array(
      );
      foreach ($rights as $right) {
         ProfileRight::deleteProfileRights(array($right));
         unset($_SESSION["glpiactiveprofile"][$right]);
      }
   }

   protected function deleteRelations() {
      $pluginItemtypes = array(
      );
      foreach ($pluginItemtypes as $pluginItemtype) {
         foreach (array('Notepad', 'DisplayPreference', 'DropdownTranslation', 'Log', 'Bookmark') as $itemtype) {
            $item = new $itemtype();
            $item->deleteByCriteria(array('itemtype' => $pluginItemtype));
         }
      }
   }

   protected function createJobs() {
      CronTask::Register('PluginFlyvemdmdemoAccountvalidation', 'CleanupAccountActivation', 12 * HOUR_TIMESTAMP,
            array(
                  'comment'   => __('Remove expired account activations (demo mode)', 'flyvemdmdemo'),
                  'mode'      => CronTask::MODE_EXTERNAL
            ));

      CronTask::Register('PluginFlyvemdmdemoAccountvalidation', 'DisableExpiredTrial', 12 * HOUR_TIMESTAMP,
            array(
                  'comment'   => __('Disable expired accounts (demo mode)', 'flyvemdmdemo'),
                  'mode'      => CronTask::MODE_EXTERNAL
            ));

      CronTask::Register('PluginFlyvemdmdemoAccountvalidation', 'RemindTrialExpiration', 12 * HOUR_TIMESTAMP,
            array(
                  'comment'   => __('Remind imminent end of trial period (demo mode)', 'flyvemdmdemo'),
                  'mode'      => CronTask::MODE_EXTERNAL
            ));
   }

   public function createNotificationTargetAccountvalidation() {
      // Create the notification template
      $notification = new Notification();
      $template = new NotificationTemplate();
      $translation = new NotificationTemplateTranslation();
      $notificationTarget = new PluginFlyvemdmNotificationTargetInvitation();

      foreach ($this->getNotificationTargetRegistrationEvents() as $event => $data) {
         $itemtype = $data['itemtype'];
         if (count($template->find("`itemtype`='$itemtype' AND `name`='" . $data['name'] . "'")) < 1) {
            // Add template
            $templateId = $template->add([
                  'name'      => addcslashes($data['name'], "'\""),
                  'comment'   => '',
                  'itemtype'  => $itemtype
            ]);

            // Add default translation
            if (!isset($data['content_html'])) {
               $contentHtml = self::convertTextToHtml($data['content_text']);
            } else {
               $contentHtml = self::convertTextToHtml($data['content_html']);
            }
            $translation->add([
                  'notificationtemplates_id' => $templateId,
                  'language'                 => '',
                  'subject'                  => addcslashes($data['subject'], "'\""),
                  'content_text'             => addcslashes($data['content_text'], "'\""),
                  'content_html'             => addcslashes($contentHtml, "'\"")
            ]);

            // Create the notification
            $notificationId = $notification->add([
                  'name'                     => addcslashes($data['name'], "'\""),
                  'comment'                  => '',
                  'entities_id'              => 0,
                  'is_recursive'             => 1,
                  'is_active'                => 1,
                  'itemtype'                 => $itemtype,
                  'notificationtemplates_id' => $templateId,
                  'event'                    => $event,
                  'mode'                     => 'mail'
            ]);

            $notificationTarget->add([
                  'items_id'           => Notification::USER,
                  'type'               => Notification::USER_TYPE,
                  'notifications_id'   => $notificationId
            ]);

         }
      }
   }

   protected function deleteNotificationTargetAccountvalidation() {
      global $DB;

      // Define DB tables
      $tableTargets      = getTableForItemType('NotificationTarget');
      $tableNotification = getTableForItemType('Notification');
      $tableTranslations = getTableForItemType('NotificationTemplateTranslation');
      $tableTemplates    = getTableForItemType('NotificationTemplate');

      foreach ($this->getNotificationTargetRegistrationEvents() as $event => $data) {
         $itemtype = $data['itemtype'];
         $name = $data['name'];
         //TODO : implement cleanup
         // Delete translations
         $query = "DELETE FROM `$tableTranslations`
         WHERE `notificationtemplates_id` IN (
         SELECT `id` FROM `$tableTemplates` WHERE `itemtype` = '$itemtype' AND `name`='$name')";
         $DB->query($query);

         // Delete notification templates
         $query = "DELETE FROM `$tableTemplates`
         WHERE `itemtype` = '$itemtype' AND `name`='" . $data['name'] . "'";
         $DB->query($query);

         // Delete notification targets
         $query = "DELETE FROM `$tableTargets`
         WHERE `notifications_id` IN (
         SELECT `id` FROM `$tableNotification` WHERE `itemtype` = '$itemtype' AND `event`='$event')";
         $DB->query($query);

         // Delete notifications
         $query = "DELETE FROM `$tableNotification`
         WHERE `itemtype` = '$itemtype' AND `event`='$event'";
         $DB->query($query);
      }
   }

   protected function getNotificationTargetRegistrationEvents() {
      // Force locale for localized strings
      $currentLocale = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');

      $notifications = array(
            PluginFlyvemdmdemoNotificationTargetAccountvalidation::EVENT_SELF_REGISTRATION => array(
                  'itemtype'        => PluginFlyvemdmdemoAccountvalidation::class,
                  'name'            => __('Self registration', "flyvemdmdemo"),
                  'subject'         => __('Flyve MDM Account Activation', 'flyvemdmdemo'),
                  'content_text'    => __('Hi there,

You or someone else created an account on Flyve MDM with your email address.

If you did not register for an account, please discard this email message, we apologize for any inconveniences.

If you created an account, please activate it with the link below. The link will be active for ##flyvemdmdemo.activation_delay##.

##flyvemdmdemo.registration_url##

After activating your account, please login and enjoy Flyve MDM for ##flyvemdmdemo.trial_duration##, entering :

##flyvemdmdemo.webapp_url##

Regards,

', 'flyvemdmdemo') . $this->getTextMailingSignature(),
                  'content_html'    => __('Hi there,

You or someone else created an account on Flyve MDM with your email address.

If you did not register for an account, please discard this email message, we apologize for any inconveniences.

If you created an account, please activate it with the link below. The link will be active for ##flyvemdmdemo.activation_delay##.

<a href="##flyvemdmdemo.registration_url##">##flyvemdmdemo.registration_url##</a>

After activating your account, please login and <span style="text-weight: bold">enjoy Flyve MDM for ##flyvemdmdemo.trial_duration##</span>, entering :

<a href="##flyvemdmdemo.webapp_url##">##flyvemdmdemo.webapp_url##</a>

Regards,

', 'flyvemdmdemo') . $this->getHTMLMailingSignature()
            ),
            PluginFlyvemdmdemoNotificationTargetAccountvalidation::EVENT_TRIAL_BEGIN => array(
                  'itemtype'        => PluginFlyvemdmdemoAccountvalidation::class,
                  'name'            => __('Account activated', "flyvemdmdemo"),
                  'subject'         => __('Get started with Flyve MDM', 'flyvemdmdemo'),
                  'content_text'    => __('Hi there,

Thank you for joining us, you have successfully activated your Flyve MDM account!

Flyve MDM is an open source Mobile Device Management Solution that allows you to manage and control the entire mobile fleet of your organization, in just a few clicks!
Install or delete applications remotely, send files, erase data and/or lock your device if you lose it, and enjoy many other functionalities that will make your daily life easier!

To use it during your 90 days trial, sign in to ##flyvemdmdemo.webapp_url##, with your account’s login.

We would love to hear whether you think Flyve MDM helps fulfill your goals or what we can do to improve. If you have any questions about getting started, we would be happy to help. Just send us an email to contact@flyve-mdm.com!

You want to upgrade?

You can upgrade to a full and unlimited Flyve MDM account at any time during your trial. Contact directly our experts to discuss your project and get a tailor-made quotation for your business! Email us at: sales@flyve-mdm.com!

Regards,

', 'flyvemdmdemo') . $this->getTextMailingSignature(),
                  'content_html'    => __('Hi there,

Thank you for joining us, you have successfully activated your Flyve MDM account!

Flyve MDM is an open source Mobile Device Management Solution that allows you to manage and control the entire mobile fleet of your organization, in just a few clicks!
Install or delete applications remotely, send files, erase data and/or lock your device if you lose it, and enjoy many other functionalities that will make your daily life easier!

To use it during your 90 days trial, sign in to <a href="##flyvemdmdemo.webapp_url##">##flyvemdmdemo.webapp_url##</a>, with your account’s login.

We would love to hear whether you think Flyve MDM helps fulfill your goals or what we can do to improve. If you have any questions about getting started, we would be happy to help. Just send us an email to <a href="contact@flyve-mdm.com">contact@flyve-mdm.com</a>!

<span style="font-weight: bold;">You want to upgrade?</span>

You can upgrade to a full and unlimited Flyve MDM account at any time during your trial. Contact directly our experts to discuss your project and get a tailor-made quotation for your business! Email us at: <a href="mailto:sales@flyve-mdm.com">sales@flyve-mdm.com</a>!

Regards,

', 'flyvemdmdemo') . $this->getHTMLMailingSignature()
            ),
            PluginFlyvemdmdemoNotificationTargetAccountvalidation::EVENT_TRIAL_EXPIRATION_REMIND_1 => array(
                  'itemtype'        => PluginFlyvemdmdemoAccountvalidation::class,
                  'name'            => __('First trial reminder', "flyvemdmdemo"),
                  'subject'         => __('Your Flyve MDM trial will end soon! - Only ##flyvemdmdemo.days_remaining## left!', 'flyvemdmdemo'),
                  'content_text'    => __('Hi there,

Your 90 days trial for ##flyvemdmdemo.webapp_url## is coming to an end in ##flyvemdmdemo.days_remaining## and we deeply hope you have been enjoying the experience!

Ready to upgrade?

To continue enjoying Flyve MDM features, contact our experts and get a personalized advice and quotation at: sales@flyve-mdm.com!

Regards,

', 'flyvemdmdemo') . $this->getTextMailingSignature(),
                  'content_html'    => __('Hi there,

Your 90 days trial for <a href="##flyvemdmdemo.webapp_url##">##flyvemdmdemo.webapp_url##</a> is coming to an end in ##flyvemdmdemo.days_remaining## and we deeply hope you have been enjoying the experience!

<span style="font-weight: bold;">Ready to upgrade?</span>

To continue enjoying Flyve MDM features, contact our experts and get a personalized advice and quotation at: <a href="mailto:sales@flyve-mdm.com">sales@flyve-mdm.com</a>!

Regards,

', 'flyvemdmdemo') . $this->getHTMLMailingSignature()
            ),
            PluginFlyvemdmdemoNotificationTargetAccountvalidation::EVENT_TRIAL_EXPIRATION_REMIND_2 => array(
                  'itemtype'        => PluginFlyvemdmdemoAccountvalidation::class,
                  'name'            => __('Second trial reminder', "flyvemdmdemo"),
                  'subject'         => __('Your free Flyve MDM trial expires in ##flyvemdmdemo.days_remaining##!', 'flyvemdmdemo'),
                  'content_text'    => __('Hi there,

We want to give you a heads-up that in ##flyvemdmdemo.days_remaining## your Flyve MDM trial comes to an end!

We would love to keep you as a customer, and there is still time to upgrade to a full and unlimited paid plan.

Ready to upgrade?

To continue enjoying Flyve MDM features, contact our experts and get a personalized advice and quotation at: sales@flyve-mdm.com!

Regards,

', 'flyvemdmdemo') . $this->getTextMailingSignature(),
                  'content_html'    => __('Hi there,

We want to give you a heads-up that <span style="font-weight: bold;">in ##flyvemdmdemo.days_remaining## your Flyve MDM trial comes to an end!</span>

We would love to keep you as a customer, and there is still time to upgrade to a full and unlimited paid plan.

<span style="font-weight: bold;">Ready to upgrade?</span>

To continue enjoying Flyve MDM features, contact our experts and get a personalized advice and quotation at: <a href="mailto:sales@flyve-mdm.com">sales@flyve-mdm.com</a>!

Regards,

', 'flyvemdmdemo') . $this->getHTMLMailingSignature()
            ),
            PluginFlyvemdmdemoNotificationTargetAccountvalidation::EVENT_POST_TRIAL_REMIND => array(
                  'itemtype'        => PluginFlyvemdmdemoAccountvalidation::class,
                  'name'            => __('End of trial reminder', "flyvemdmdemo"),
                  'subject'         => __('Your free Flyve MDM trial has expired.', 'flyvemdmdemo'),
                  'content_text'    => __('Hi there,

The trial period for Flyve MDM has ended!

We hope you enjoyed our solution and that it helped you increase your productivity, saving you time and energy!

Upgrade to the next level!

Upgrade to a full and unlimited Flyve MDM account right now and continue benefiting from its numerous features! Contact directly our experts to discuss your project and get a tailor-made quotation for your business!
Email us at: sales@flyve-mdm.com, we will be happy to hear from you!

Regards,

', 'flyvemdmdemo') . $this->getTextMailingSignature(),
                  'content_html'    => __('Hi there,

<span style="font-weight: bold;">The trial period for Flyve MDM has ended!</span>

We hope you enjoyed our solution and that it helped you increase your productivity, saving you time and energy!

<span style="font-weight: bold;">Upgrade to the next level!</span>

Upgrade to a full and unlimited Flyve MDM account right now and continue benefiting from its numerous features! Contact directly our experts to discuss your project and get a tailor-made quotation for your business!
Email us at: <a href="mailto:sales@flyve-mdm.com">sales@flyve-mdm.com</a>, we will be happy to hear from you!

Regards,

', 'flyvemdmdemo') . $this->getHTMLMailingSignature()
            ),
      );

      // Restore user's locale
      Session::loadLanguage($currentLocale);

      return $notifications;
   }

   protected function getHTMLMailingSignature() {
      $config = Config::getConfigurationValues('flyvemdmdemo', [
            'social_media_twit',
            'social_media_gplus',
            'social_media_facebook',
      ]);

      $document = new Document();
      $document->getFromDB($config['social_media_twit']);
      $twitterTag = Document::getImageTag($document->getField('tag'));

      $document = new Document();
      $document->getFromDB($config['social_media_gplus']);
      $gplusTag = Document::getImageTag($document->getField('tag'));

      $document = new Document();
      $document->getFromDB($config['social_media_facebook']);
      $facebookTag = Document::getImageTag($document->getField('tag'));

      // Force locale for localized strings
      $currentLocale = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');

      $signature = __("Flyve MDM Team", 'flyvemdmdemo') . "\n";
      $signature.= '<a href="' . self::FLYVE_MDM_PRODUCT_WEBSITE . '">' . self::FLYVE_MDM_PRODUCT_WEBSITE . "</a>\n";
      $signature.= '<a href="' . self::FLYVE_MDM_PRODUCT_FACEBOOK .'">'
            . '<img src="cid:' . $facebookTag . '" alt="Facebook" title="Facebook" width="30" height="30">'
                  . '</a>'
                        . '&nbsp;<a href="' . self::FLYVE_MDM_PRODUCT_TWITTER . '">'
                              . '<img src="cid:' . $twitterTag . '" alt="Twitter" title="Twitter" width="30" height="30">'
                                    . '</a>'
                                          . '&nbsp;<a href="' . self::FLYVE_MDM_PRODUCT_GOOGLEPLUS . '">'
                                                . '<img src="cid:' . $gplusTag . '" alt="Google+" title="Google+" width="30" height="30">'
                                                      .'</a>';

                                                      // Restore user's locale
                                                      Session::loadLanguage($currentLocale);

                                                      return $signature;
   }

   protected function getTextMailingSignature() {
      // Force locale for localized strings
      $currentLocale = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');

      $signature = __("Flyve MDM Team", 'flyvemdmdemo') . "\n";
      $signature.= self::FLYVE_MDM_PRODUCT_WEBSITE . "\n";
      $signature.= self::FLYVE_MDM_PRODUCT_FACEBOOK . "\n"
            . self::FLYVE_MDM_PRODUCT_GOOGLEPLUS . "\n"
                  . self::FLYVE_MDM_PRODUCT_TWITTER . "\n";

                  // Restore user's locale
                  Session::loadLanguage($currentLocale);

                  return $signature;
   }

   protected function deleteDisplayPreferences() {
      // To cleanup display preferences if any
      //$displayPreference = new DisplayPreference();
      //$displayPreference->deleteByCriteria(array("`num` >= " . PluginFlyvemdmConfig::RESERVED_TYPE_RANGE_MIN . " AND `num` <= " . PluginFlyvemdmConfig::RESERVED_TYPE_RANGE_MAX));
   }

   /**
    * create documents for demo mode social media icons
    */
   protected function createSocialMediaIcons() {
      $config = Config::getConfigurationValues('flyvemdmdemo', [
            'social_media_twit',
            'social_media_gplus',
            'social_media_facebook',
      ]);

      if (!isset($config['social_media_twit'])) {
         copy(PLUGIN_FLYVEMDM_ROOT . '/pics/flyve-twitter.jpg', GLPI_TMP_DIR . '/flyve-twitter.jpg');
         $input = array();
         $document = new Document();
         $input['entities_id']               = '0';
         $input['is_recursive']              = '1';
         $input['name']                      = __('Flyve MDM Twitter icon', 'flyvemdmdemo');
         $input['_filename']                 = array('flyve-twitter.jpg');
         $input['_only_if_upload_succeed']   = true;
         if ($document->add($input)) {
            $config['social_media_twit']     = $document->getID();
         }
      }

      if (!isset($config['social_media_gplus'])) {
         copy(PLUGIN_FLYVEMDM_ROOT . '/pics/flyve-gplus.jpg', GLPI_TMP_DIR . '/flyve-gplus.jpg');
         $input = array();
         $document = new Document();
         $input['entities_id']               = '0';
         $input['is_recursive']              = '1';
         $input['name']                      = __('Flyve MDM Google Plus icon', 'flyvemdmdemo');
         $input['_filename']                 = array('flyve-gplus.jpg');
         $input['_only_if_upload_succeed']   = true;
         if ($document->add($input)) {
            $config['social_media_gplus']    = $document->getID();
         }
      }

      if (!isset($config['social_media_facebook'])) {
         copy(PLUGIN_FLYVEMDM_ROOT . '/pics/flyve-facebook.jpg', GLPI_TMP_DIR . '/flyve-facebook.jpg');
         $input = array();
         $document = new Document();
         $input['entities_id']               = '0';
         $input['is_recursive']              = '1';
         $input['name']                      = __('Flyve MDM Facebook  icon', 'flyvemdmdemo');
         $input['_filename']                 = array('flyve-facebook.jpg');
         $input['_only_if_upload_succeed']   = true;
         if ($document->add($input)) {
            $config['social_media_facebook'] = $document->getID();
         }
      }

      Config::setConfigurationValues('flyvemdmdemo', $config);
   }

   protected function deleteSocialMediaIcons() {
      $config = Config::getConfigurationValues('flyvemdmdemo', [
            'social_media_twit',
            'social_media_gplus',
            'social_media_facebook',
      ]);

      foreach ($config as $documentId) {
         $document = new Document();
         $document->delete(['id'    => $documentId], 1);
      }
   }
}
