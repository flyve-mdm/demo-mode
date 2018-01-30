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

// fix empty CFG_GLPI on boostrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI, $PLUGIN_HOOKS, $_CFG_GLPI;

class UnitTestAutoload
{

   public static function register() {
      include_once __DIR__ . '/../vendor/autoload.php';
      spl_autoload_register(array('UnitTestAutoload', 'autoload'));
   }

   public static function autoload($className) {
      $file = __DIR__ . "/inc/$className.php";
      if (is_readable($file) && is_file($file)) {
         include_once __DIR__ . "/inc/$className.php";
         return true;
      }
      return false;
   }

}

UnitTestAutoload::register();

/* To disable warning about mcrypt in Toolbox */
define('TU_USER', '_test_user');

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
define("GLPI_LOG_DIR", __DIR__ . '/logs');

@mkdir(GLPI_LOG_DIR);

require GLPI_ROOT . "/inc/includes.php";

// need to set theses in DB, because tests for API use http call and this bootstrap file is not called
Config::setConfigurationValues(
    'core', [
      'url_base'     => GLPI_URI,
      'url_base_api' => GLPI_URI . '/apirest.php'
    ]
);
$CFG_GLPI['url_base']      = GLPI_URI;
$CFG_GLPI['url_base_api']  = GLPI_URI . '/apirest.php';

// Mock PluginFlyvemdmMqttClient
require __DIR__ . "/inc/MqttClient.php";

