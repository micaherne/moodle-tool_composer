<?php

require_once('../../../config.php');
tool_composer\manager::autoload();

echo Symfony\Polyfill\Mbstring\Mbstring::mb_convert_case("hello", MB_CASE_UPPER);