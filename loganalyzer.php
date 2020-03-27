<?php

require_once 'vendor/autoload.php';

$commands = [
    new \FourViewture\PshLogAnalyzer\Command\GoAccess\AnalyzeCommand()
];

$application = new \Symfony\Component\Console\Application(
    'Platform.sh log analyzer',
    '1.0.0'
);

foreach ($commands as $command) {
    $application->add($command);
}

#$application->setDefaultCommand('list', true);
$application->run();