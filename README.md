# moodle-tool_composer
This is an experimental tool which enables multiple Moodle plugins to use [Composer](https://getcomposer.org) for dependency management without conflicts.

_NB: The tool currently assumes that you have the composer tool available in your path as "composer"_

## Why does this tool exist?
Composer has become a de-facto standard for dependency management in the PHP world, and many developers use it as a matter of course in development. However, it presents a problem when used in Moodle due to the following scenario:

* block/something depends on my/library version 0.3
* block/another depends on my/library version 5.1
* These blocks appear on the same page.

What happens in this scenario is that whichever block accesses a class in my/library first will cause it to be autoloaded from its local vendor directory. This will be more or less randomly either version 0.3 or 5.1, causing unpredictable behaviour.

(This is obviously a simplified version of the issue. In real life, it's much more likely that the conflicts will arise deeper into the dependency chains.)

If we have a number of Moodle plugins autoloading Composer-managed dependencies, we need a way to ensure that these do not conflict across the Moodle installation, and that we can resolve a set of versions that are compatible with all.

This tool attempts to do this.

## Usage
From the help:

    Usage:
      php composer.php [--overwrite-autoload] [--install-in-codebase] [--help] [command]

    Options:
    --overwrite-autoload     Overwrite autoloader in plugin code to use global
    --install-in-codebase    Install to lib/vendor directory in dirroot rather than dataroot

    -h, --help     Print out this help

### Options
|Name              |Description                 |
|------------------|----------------------------|
|overwrite-autoload|Plugins using Composer normally have their dependencies installed into a vendor directory inside the plugin, and autoload classes by including vendor/autoload.php. Because we have installed our dependencies elsewhere, we use this flag to point the local vendor/autoload.php files at the central one. (The original autoload.php will be backed up as autoload.php.bak)     |
|install-in-codebase|If this is not selected, the Composer dependencies will be installed in the [dataroot]/tool_composer/vendor directory. If selected, the dependencies will be installed in [dirroot]/lib/vendor.|

#### Which options should I use?

Use install-in-codebase if you are assembling a custom Moodle installation to be installed on a server.

Use the other method if you are happy to have application code in your dataroot. In some ways this is good practice (library code is not generally kept under the web root nowadays), but in other ways it is bad (your Moodle codebase will be split across two directories, and code arguably doesn't belong in dataroot).

## How does it work?

Wikimedia created an excellent plugin for Composer, [composer-merge-plugin](https://github.com/wikimedia/composer-merge-plugin), which enables multiple composer.json files to be combined into a single one on-the-fly when running commands.

This tool simply generates a composer.json file with this plugin required, finds all Moodle plugins with a composer.json, and adds these to the list to be merged. This generated composer.json file is used to install and update plugins by tweaking the COMPOSER and COMPOSER_VENDOR_DIR environment variables to the required paths.
