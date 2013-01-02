<?php
// Error Reporting Level
error_reporting(E_ALL | E_STRICT);

// add app and tests to include path
$src   = realpath(__DIR__ . '/../src');
$tests = realpath(__DIR__ . '/../tests');

$paths = array(
    $src,
    $tests,
    get_include_path() // attach original include paths
);
set_include_path(implode(PATH_SEPARATOR, $paths));

// Composer Autoloader
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    include_once __DIR__ . '/../vendor/autoload.php';
}
