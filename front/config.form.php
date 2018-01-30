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

require '../../../inc/includes.php';
Session::checkRight("flyvemdm:flyvemdm", PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE);
Session::checkRight("config", UPDATE);

$plugin = new Plugin();
$config = new Config();
$pluginConfig = new PluginFlyvemdmdemoConfig();
if (isset($_POST["update"])) {
    $config->update($_POST);
    Html::back();
} else {
    // Header

    Html::header(
        __('Configuration'),
        '',
        'config',
        'plugin',
        "flyvemdmdemo"
    );
    $pluginConfig->showForm();
    // Footer

   if (strstr($_SERVER['PHP_SELF'], "popup")) {
      Html::popFooter();
   } else {
      Html::footer();
   }
}
