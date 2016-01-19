<?php

define('CLI_SCRIPT', 1);

require_once(__DIR__ . '/../../../../config.php');

tool_composer\manager::write_composer_json();
tool_composer\manager::install_composer_dependencies();