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
 * @since 0.1.0
 */
class PluginFlyvemdmdemoEntity extends Entity
{

    /**
    * {@inheritDoc}
    *
    * @see CommonDBTM::getTable()
    */
   public static function getTable() {
      if (empty($_SESSION['glpi_table_of'][get_called_class()])) {
         $_SESSION['glpi_table_of'][get_called_class()] = Entity::getTable();
      }

      return $_SESSION['glpi_table_of'][get_called_class()];
   }

    //public function addNeededInfoToInput($input) {
      //// the entity is managed by FlyveMDM
      //$input['managed'] = '1';
    //}

    /**
    * {@inheritDoc}
    *
    * @see Entity::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      global $DB;

      $input = CommonTreeDropdown::prepareInputForAdd($input);

      $query = "SELECT MAX(`id`)+1 AS newID
                FROM `glpi_entities`";
      if ($result = $DB->query($query)) {
         $input['id'] = $DB->result($result, 0, 0);
      } else {
         return false;
      }
      $input['max_closedate'] = $_SESSION["glpi_currenttime"];
      return $input;
   }

}