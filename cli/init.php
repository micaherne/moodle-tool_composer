<?php

define('CLI_SCRIPT', 1);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'overwrite-local' => false,
        'target-dir' => false,
        'help' => false,
    ),
    array(
        'o' => 'overwrite-local',
        'h' => 'help'
    )
);

$help = "
Utility to install composer dependencies.

This finds all plugins with a composer.json file and

Usage:
  php init.php [--overwrite-local] [--target-dir=[dir]] [--help]

Options:
--overwrite-local   Overwrite autoloader in plugin code to use global
--target-dir        The directory to install the composer dependencies in

-h, --help     Print out this help

";

if (!empty($options['help'])) {
    echo $help;
    exit(0);
}

if ($options['target-dir']) {
    if (!$targetdir = realpath($options['target-dir'])) {
        mtrace("Target directory {$options['target-dir']} not found");
        exit(1);
    }
    tool_composer\manager::set_target_dir($targetdir);
}
tool_composer\manager::write_composer_json();
tool_composer\manager::update_composer_dependencies();
if ($options['overwrite-local']) {
    tool_composer\manager::overwrite_local_autoloads();
}