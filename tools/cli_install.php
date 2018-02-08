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

(PHP_SAPI == 'cli') or die("Only available from command line");

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

include (__DIR__ . "/../vendor/docopt/docopt/src/docopt.php");

$doc = <<<DOC
cli_install.php

Usage:
   cli_install.php [--as-user USER] [ --tests ]

Options:
   --as-user USER       Do install/upgrade as specified USER. If not provided, 'glpi' user will be used
   --tests              Use GLPi test database

DOC;

$docopt = new \Docopt\Handler();
$args = $docopt->handle($doc);

$asUser = 'glpi';
if (!is_null($args['--as-user'])) {
   $asUser = $args['--as-user'];
}
if (isset($args['--tests']) && $args['--tests'] !== false ) {
   // Use test GLPi's database
   // Requires use of cliinstall of GLPI with --tests argument
   define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
   define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
}

include (__DIR__ . "/../../../inc/includes.php");

// Init debug variable
$_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
$_SESSION['glpilanguage']  = "en_GB";

Session::loadLanguage();

// Only show errors
$CFG_GLPI["debug_sql"]        = $CFG_GLPI["debug_vars"] = 0;
$CFG_GLPI["use_log_in_files"] = 1;
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
//set_error_handler('userErrorHandlerDebug');

// Prevent problem of execution time
ini_set("max_execution_time", "0");
ini_set("memory_limit", "-1");
ini_set("session.use_cookies","0");

$DB = new DB();
if (!$DB->connected) {
   die("No DB connection\n");
}

$user = new User();
$user->getFromDBbyName($asUser);
$auth = new Auth();
$auth->auth_succeded = true;
$auth->user = $user;
Session::init($auth);

/*---------------------------------------------------------------------*/

if (!TableExists("glpi_configs")) {
   echo "GLPI not installed\n";
   exit(1);
}

$plugin = new Plugin();

// Install the plugin
$plugin->getFromDBbyDir("flyvemdmdemo");
print("Installing Plugin Id: " . $plugin->fields['id'] . " version " . $plugin->fields['version'] . "\n");
ob_start(function($in) { return ''; });
$plugin->install($plugin->fields['id']);
ob_end_clean();
print("Install Done\n");

// Enable the plugin
print("Activating Plugin...\n");
$plugin->activate($plugin->fields['id']);
if (!$plugin->activate($plugin->fields['id'])) {
   print("Activation failed\n");
   exit(1);
}
print("Activation Done\n");

//Load the plugin
print("Loading Plugin...\n");
$plugin->load("flyvemdmdemo");
print("Load Done...\n");

