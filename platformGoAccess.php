#!/usr/bin/env php
<?php

echo "\n";
echo " ___ _      _    __                    _      _    _____  _____" . "\n";
echo "| _ \ |__ _| |_ / _|___ _ _ _ __    __| |_   | |  |  _  ||  ___|" . "\n";
echo "|  _/ / _` |  _|  _/ _ \ '_| '  \ _(_-< ' \  | |_ | |_| || |__/|" . "\n";
echo "|_| |_\__,_|\__|_| \___/_| |_|_|_(_)__/_||_| |___||_____||_____|" . "\n";
echo "                                             - A N A L Y Z E R -\n";
echo "\n";

if (!isset($argv[1])) {
    exec('platform projects --format=csv --no-header --count=0', $projectOutput);
    
    $platformProjects = [];
    foreach ($projectOutput as $platformProject) {
        $platformProjects[] = str_getcsv($platformProject);
    }
    
    echo 'Available Platform.sh projects:' . "\n";
    
    for ($i = 0; $i < count($platformProjects); $i++) {
        echo '    [' . ($i+1) . ']  ' . $platformProjects[$i][1] . ' (' . $platformProjects[$i][0] . ')' . "\n";
    }
    
    do {
        $selectedProjectNumber = (int) readline('Enter a project number > ');
    
        if (isset($platformProjects[$selectedProjectNumber-1])) {
            $selectedProject = $platformProjects[$selectedProjectNumber-1];
            break;
        } else {
            echo 'ERROR: Invalid project number. Please try again.' . "\n";
        }
    } while (true);
    
    echo "\n";
    echo $selectedProject[1] . ' was selected.' . "\n\n";
} else {
    exec('platform project:info title --format=csv --no-header --project=' . escapeshellarg($argv[1]), $projectTitle);
    $selectedProject[0] = $argv[1];
    $selectedProject[1] = $projectTitle[0];
}

if (!isset($argv[2])) {
    exec('platform environment:list --format=csv --no-header --project=' . escapeshellarg($selectedProject[0]), $environmentOutput);
    
    $platformEnvironments = [];
    foreach ($environmentOutput as $platformEnvironment) {
        $platformEnvironments[] = str_getcsv($platformEnvironment);
    }

    echo 'Available Platform.sh environments:' . "\n";

    for ($i = 0; $i < count($platformEnvironments); $i++) {
        echo '    [' . ($i+1) . ']  ' . $platformEnvironments[$i][1] . ' (' . $platformEnvironments[$i][0] . ')' . "\n";
    }

    do {
        $selectedEnvironmentNumber = (int) readline('Enter a environment number > ');

        if (isset($platformEnvironments[$selectedEnvironmentNumber-1])) {
            $selectedEnvironment = $platformEnvironments[$selectedEnvironmentNumber-1];
            break;
        } else {
            echo 'ERROR: Invalid environment number. Please try again.' . "\n";
        }
    } while (true);
} else {
    $selectedEnvironment[0] = $argv[2];
}

if (!isset($argv[3])) {
    echo "\n";
    echo 'Please choose the number of lines to fetch:' . "\n";
    echo '    [1] 5000' . "\n";
    echo '    [2] 20000' . "\n";
    echo '    [3] Max' . "\n";

    do {
        $lineSelection = (string)readline('Please select (default: [2]) > ');

        if (in_array($lineSelection, ['1', '2', '3', ''], true)) {
            switch ($lineSelection) {
                case '1':
                    $numberOfLines = '5000';
                    break 2;
                case '2':
                    $numberOfLines = '20000';
                    break 2;
                case '3':
                default:
                    $numberOfLines = '999999';
                    break 2;
            }
        } else {
            echo 'ERROR: Invalid option. Please try again.' . "\n";
        }
    } while (true);
    echo "\n";
} else {
    $numberOfLines = $argv[3];
}

echo 'Getting the log... ' . "\n";
exec('platform log -q --lines=' . escapeshellarg($numberOfLines) . ' --project=' . escapeshellarg($selectedProject[0]) . ' --environment='.$selectedEnvironment[0] . ' access | goaccess --log-format="COMBINED" --html-prefs=\'{"theme":"bright"}\' -', $goaccessOutput);

$fileName = preg_replace('/\\s+/', '_', preg_replace("/[^A-Za-z0-9]/", '', $selectedProject[1])) . '-goaccess-' .date('YmdHis') . '.html';
file_put_contents($fileName, implode("\n", $goaccessOutput));

echo 'Statistics written to: ' . $fileName . "\n";