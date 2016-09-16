<?php

namespace tool_composer;

class manager {

    private $overwritelocal = false;
    private $installincodebase = false;

    public function set_overwritelocal($overwritelocal) {
        $this->overwritelocal = $overwritelocal;
    }

    public function set_installincodebase($installincodebase) {
        $this->installincodebase = $installincodebase;
    }

    public function run($command) {
        global $CFG;
        $data = $CFG->dataroot . '/tool_composer';
        $composerfile = $data . '/composer.json';
        if (!is_writable($data)) {
            throw new \moodle_exception('notwritable');
        }
        if (!file_exists($data)) {
            mkdir($data, $CFG->directorypermissions, true);
        }

        $vendordir = $data . '/vendor';
        $autoloadphp = '$CFG->dataroot . \'tool_composer/vendor/autoload.php\'';
        if ($this->installincodebase) {
            $vendordir = $CFG->dirroot . '/lib/vendor';
            $autoloadphp = '$CFG->dirroot . \'/lib/vendor/autoload.php\'';
        }

        $composer = new \stdClass();

        $composer->require = ['wikimedia/composer-merge-plugin' => '^1.3'];

        $include = [];
        foreach (\core_component::get_plugin_types() as $type => $dir) {
            $plugins = \core_component::get_plugin_list_with_file($type, 'composer.json');
            foreach ($plugins as $pluginname => $filepath) {

                // Ignore this plugin
                if ($type == 'tool' && $pluginname == 'composer') {
                    continue;
                }

                $include[] = $filepath;

                // Overwrite the autoload files if necessary
                $autoload = dirname($filepath) . '/vendor/autoload.php';
                if (file_exists($autoload)) {
                    if (!is_writable($autoload)) {
                        throw new \moodle_exception('notwritable');
                    }
                    // Back up the file if we haven't done so already.
                    if (!file_exists($autoload . '.bak')) {
                        file_put_contents($autoload . '.bak', file_get_contents($autoload));
                    }

                    file_put_contents($autoload, '<?php require_once ' . $autoloadphp . ';');
                }
            }
        }

        $composer->extra = (object)[
            'merge-plugin' => (object) ['include' => $include ]
        ];

        file_put_contents($composerfile, json_encode($composer));

        putenv('COMPOSER=' . $composerfile);
        putenv('COMPOSER_VENDOR_DIR=' . $vendordir);
        if ($this->installincodebase) {
            // Allow us to install Moodle plugins into the codebase
            chdir($CFG->dirroot);
        }

        // TODO: We may want to force --no-dev here for install / update
        passthru('composer --no-interaction ' . $command);
    }

}
