#!/usr/bin/env php
<?php

exec('platform projects --format=csv --no-header', $output);

$platformProjects = [];
foreach ($output as $platformProject) {
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
echo $selectedProject[1] . ' was selected.' . "\n";
echo "\n";
echo 'Please choose the number of lines to fetch:' . "\n";
echo '    [1] 5000' . "\n";
echo '    [2] 20000' . "\n";
echo '    [3] Max' . "\n";

do {
    $lineSelection = (string) readline('Please select (default: [2]) > ');

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
echo 'Getting the log... ' . "\n";
exec('platform log -q --lines=' . escapeshellarg($numberOfLines) . ' --project=' . escapeshellarg($selectedProject[0]) . ' --environment=master access | goaccess --log-format="COMBINED" --html-prefs=\'{"theme":"bright"}\' -', $goaccessOutput);

$fileName = preg_replace('/\\s+/', '_', preg_replace("/[^A-Za-z0-9]/", '', $selectedProject[1])) . '-goaccess-' .date('YmdHis') . '.html';
file_put_contents($fileName, implode("\n", $goaccessOutput));

echo 'Statistics written to: ' . $fileName . "\n";