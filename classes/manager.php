<?php

namespace tool_composer;

class manager {

    public static $targetdir;

    public static function autoload() {
        global $CFG;
        require self::get_target_dir() . '/vendor/autoload.php';
    }

    public static function get_target_dir() {
        global $CFG;

        if (empty(self::$targetdir)) {
            // TODO: Choose between data root, codebase (/admin/tool/composer/install) or custom
            self::$targetdir = $CFG->dataroot . '/tool_composer';
        }

        return self::$targetdir;
    }

    public static function set_target_dir($dir) {
        self::$targetdir = $dir;
    }

    public static function get_composer_plugins() {
        // Find plugins with composer.json
        // TODO: Could be changed into a generator if we only support PHP 5.5 or greater
        $composerplugins = [];
        foreach (\core_component::get_plugin_types() as $type => $dir) {
            $plugins = \core_component::get_plugin_list_with_file($type, 'composer.json');
            foreach ($plugins as $name => $composerfile) {
                $composerplugins[$type . '_' . $name] = $composerfile;
            }
        }
        return $composerplugins;
    }

    public static function write_composer_json() {
        global $CFG;

        $targetdir = self::get_target_dir();

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

        foreach(self::get_composer_plugins() as $component => $path) {
            $composer['repositories'][] = ['type' => 'path', 'url' => realpath(dirname($path))];
            $meta = json_decode(file_get_contents($path));
            if (!empty($meta->name)) {
                $name = $meta->name;
                $composer['require'][$name] = '*@dev'; // dev required to allow install
            } else {
                mtrace("Name not found in $path. Ignoring.");
                continue;
            }
        }

        file_put_contents($targetdir . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT));
    }

    public static function overwrite_local_autoloads() {
        foreach(self::get_composer_plugins() as $component => $path) {
            $plugindir = dirname($path);
            $vendordir = $plugindir . '/vendor';
            if (!file_exists($vendordir)) {
                mkdir($vendordir);
            }
            $filecontents = '<?php require_once \'' . self::get_target_dir() . '/vendor/autoload.php\';';
            file_put_contents($plugindir . '/vendor/autoload.php', $filecontents);
        }
    }

    /**
     * Updates the composer installer and the dependencies.
     *
     * This is copied from testing_update_composer_dependencies()
     *
     * @return void exit() if something goes wrong
     */
    public static function update_composer_dependencies() {
        // To restore the value after finishing.
        $cwd = getcwd();

        // Set some paths.
        $dirroot = self::get_target_dir();
        $composerpath = $dirroot . DIRECTORY_SEPARATOR . 'composer.phar';
        $composerurl = 'https://getcomposer.org/composer.phar';

        // Switch to Moodle's dirroot for easier path handling.
        chdir($dirroot);

        // Download or update composer.phar. Unfortunately we can't use the curl
        // class in filelib.php as we're running within one of the test platforms.
        if (!file_exists($composerpath)) {
            $file = @fopen($composerpath, 'w');
            if ($file === false) {
                $errordetails = error_get_last();
                $error = sprintf("Unable to create composer.phar\nPHP error: %s",
                        $errordetails['message']);
                testing_error(TESTING_EXITCODE_COMPOSER, $error);
            }
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL,  $composerurl);
            curl_setopt($curl, CURLOPT_FILE, $file);
            $result = curl_exec($curl);

            $curlerrno = curl_errno($curl);
            $curlerror = curl_error($curl);
            $curlinfo = curl_getinfo($curl);

            curl_close($curl);
            fclose($file);

            if (!$result) {
                $error = sprintf("Unable to download composer.phar\ncURL error (%d): %s",
                        $curlerrno, $curlerror);
                testing_error(TESTING_EXITCODE_COMPOSER, $error);
            } else if ($curlinfo['http_code'] === 404) {
                if (file_exists($composerpath)) {
                    // Deleting the resource as it would contain HTML.
                    unlink($composerpath);
                }
                $error = sprintf("Unable to download composer.phar\n" .
                        "404 http status code fetching $composerurl");
                testing_error(TESTING_EXITCODE_COMPOSER, $error);
            }
        } else {
            passthru("php composer.phar self-update", $code);
            if ($code != 0) {
                exit($code);
            }
        }

        // Update composer dependencies.
        passthru("php composer.phar update", $code);
        if ($code != 0) {
            exit($code);
        }

        // Return to our original location.
        chdir($cwd);
    }


}