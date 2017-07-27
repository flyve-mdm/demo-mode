<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2016 by the FusionInventory Development Team.
 *
 * This file is part of Flyve MDM Plugin for GLPI.
 *
 * Flyve MDM Plugin for GLPI is a subproject of Flyve MDM. Flyve MDM is a mobile
 * device management software.
 *
 * Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 * along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 * ------------------------------------------------------------------------------
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginFlyvemdmdemoProfile extends Profile {

   public static function purgeProfiles(Profile $prof) {
      $plugprof = new self();
      $plugprof->deleteByCriteria(array('profiles_id' => $prof->getField("id")));
   }

   /**
    * @see Profile::showForm()
    */
   public function showForm($ID, $options = array()) {
      if (!Profile::canView()) {
         return false;
      }
      $canedit = Profile::canUpdate();
      $profile    = new Profile();
      if ($ID) {
         $profile->getFromDB($ID);
      }
      if ($canedit) {
         echo "<form action='" . $profile->getFormURL() . "' method='post'>";
      }

      $rights = $this->getAssetsRights();
      $profile->displayRightsChoiceMatrix($rights, array('canedit'       => $canedit,
                                                         'default_class' => 'tab_bg_2',
                                                         'title' => __('Assets')
       ));

      if ($canedit) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value=".$ID.">";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>";
      }
      Html::closeForm();
      $this->showLegend();
   }

   /**
    * @see Profile::getTabNameForItem()
    */
   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == Profile::class) {
         return __('Flyve MDM Demo', 'flyvemdmdemo');
      }
      return '';
   }

   /**
    * @param CommonGLPI $item
    * @param number $tabnum
    * @param number $withtemplate
    * @return boolean
    */
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == Profile::class) {
         $profile = new self();
         $profile->showForm($item->getField('id'));
      }
      return true;
   }

   /**
    * Get rights matrix for plugin's assets
    * @return array:array:string rights matrix
    */
   public function getAssetsRights() {
      $itemtypes = array(
         PluginFlyvemdmdemoCaptcha::class,
      );

      $rights = array();
      foreach ($itemtypes as $itemtype) {
         $rights[] = [
            'itemtype'  => $itemtype,
            'label'     => $itemtype::getTypeName(2),
            'field'     => $itemtype::$rightname
         ];
      }

      return $rights;
   }

   /**
    * Callback when a user logins or switch profile
    */
   public static function changeProfile() {
      $rights = ProfileRight::getProfileRights($_SESSION['glpiactiveprofile']['id'], array(
            self::$rightname
      ));

      $config = Config::getConfigurationValues('flyvemdm', array('guest_profiles_id'));
      if (isset($config['guest_profiles_id'])) {
         $_SESSION['plugin_flyvemdm_guest_profiles_id'] = $config['guest_profiles_id'];
      } else {
         $_SESSION['plugin_flyvemdm_guest_profiles_id'] = '';
      }
   }
}
