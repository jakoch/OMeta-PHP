<?php

chdir(__DIR__);

$phpunit_bin    = __DIR__ . '/../vendor/bin/phpunit';
$phpunit_conf   = 'phpunit.xml.dist';
$phpunit_opts   = "-c $phpunit_conf";

$result = 0;
system("$phpunit_bin $phpunit_opts", $result);
echo "\n\n";
exit($result);
