<?php

define('CLI_SCRIPT', 1);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'overwrite-autoload' => false,
        'install-in-codebase' => false,
        'help' => false,
    ),
    array(
        'o' => 'overwrite-autoload',
        'h' => 'help'
    )
);

$help = "
Utility to install composer dependencies.

This finds all plugins with a composer.json file and creates a composer.json
that will resolve them all together.

Usage:
  php composer.php [--overwrite-autoload] [--install-in-codebase] [--help] [command]

Options:
--overwrite-autoload     Overwrite autoloader in plugin code to use global
--install-in-codebase    Install to lib/vendor directory in dirroot rather than dataroot

-h, --help     Print out this help

";

if (!empty($options['help'])) {
    echo $help;
    exit(0);
}

$manager = new \tool_composer\manager();

if ($options['overwrite-autoload']) {
    $manager->set_overwritelocal(true);
}

if ($options['install-in-codebase']) {
    $manager->set_installincodebase(true);
}

$manager->run(implode(' ', $unrecognized));
