<?php

/**
 * Coverage Checker
 *
 * @author ocramius
 * @link http://ocramius.github.com/blog/automated-code-coverage-check-for-github-pull-requests-with-travis/
 *
 * Usage: "php coverage-checker.php clover.xml 70"
 */
$inputFile = $argv[1];
$acceptedPercentage = min(100, max(0, (int) $argv[2]));

if (!file_exists($inputFile)) {
    throw new InvalidArgumentException('Invalid input file provided');
}

if (!$acceptedPercentage) {
    throw new InvalidArgumentException('An integer checked percentage must be given as second parameter');
}

$cliColor = array(
      'green' => "\x1b[30;42m",
      'red' => "\x1b[37;41m",
      'reset' => "\x1b[0m",
);

$xml = new SimpleXMLElement(file_get_contents($inputFile));
$metrics = $xml->xpath('//metrics');
$totalElements = 0;
$checkedElements = 0;

foreach ($metrics as $metric) {
    $totalElements += (int) $metric['elements'];
    $checkedElements += (int) $metric['coveredelements'];
}

$coverage = ($checkedElements / $totalElements) * 100;
$coverage = round($coverage, 2);

if ($coverage < $acceptedPercentage) {
    echo $cliColor['red'] . 'Code coverage is ' . $coverage . '%, which is below the accepted ' . $acceptedPercentage . '%' . $cliColor['reset'] . PHP_EOL;
    exit(1);
}

echo $cliColor['green'] . 'Code coverage is ' . $coverage . '% - OK!' . $cliColor['reset'] . PHP_EOL;
