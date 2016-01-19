<?php

define('CLI_SCRIPT', 1);

require_once('../../../../config.php');

$pm = core_plugin_manager::instance();
$composerplugins = [];
foreach (core_component::get_plugin_types() as $type => $dir) {
    $plugins = core_component::get_plugin_list_with_file($type, 'composer.json');
    foreach ($plugins as $name => $composerfile) {
        $composerplugins[$type . '_' . $name] = $composerfile;
    }
}

// TODO: Choose between data root, codebase (/admin/tool/composer/install) or custom
$targetdir = $CFG->dataroot . '/temp/tool_composer';
if (!file_exists($targetdir . '/vendor')) {
    mkdir($targetdir . '/vendor', 0777, true);
}

$targetdir = realpath($targetdir); // PHP doesn't mind - composer might

$composer = [
    'config' => [
        'optimize-autoloader' => true
    ]
];

$composer['repositories'] = [];
$composer['require'] = [];

foreach($composerplugins as $component => $path) {
    $composer['repositories'][] = ['type' => 'path', 'url' => realpath(dirname($path))];
    $meta = json_decode(file_get_contents($path));
    if ($name = $meta->name) {
        $composer['require'][$name] = '*@dev'; // dev required to allow install
    } else {
        mtrace("Name not found");
    }
}

file_put_contents($targetdir . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT));