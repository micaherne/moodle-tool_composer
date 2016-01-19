<?php

require_once('vendor/autoload.php');

echo Symfony\Polyfill\Mbstring\Mbstring::mb_convert_case("hello", MB_CASE_UPPER);