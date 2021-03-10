#!/usr/bin/env php
<?php

define('DATE_FORMAT', 'Y-m-d\TH:i:sP');

define('DATE_FORMAT_HOUR', 'Y-m-d\TH');

ini_set("memory_limit","500M");

echo "\n";
echo " ___ _      _    __                    _      _    _____  _____" . "\n";
echo "| _ \ |__ _| |_ / _|___ _ _ _ __    __| |_   | |  |  _  ||  ___|" . "\n";
echo "|  _/ / _` |  _|  _/ _ \ '_| '  \ _(_-< ' \  | |_ | |_| || |__/|" . "\n";
echo "|_| |_\__,_|\__|_| \___/_| |_|_|_(_)__/_||_| |___||_____||_____|" . "\n";
echo "                                             - A N A L Y Z E R -\n";
echo "\n";


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
echo '    [1] 250' . "\n";
echo '    [2] 1000' . "\n";
echo '    [3] 5000' . "\n";
echo '    [4] 20000' . "\n";
echo '    [5] Max' . "\n";

do {
    $lineSelection = (string) readline('Please select (default: [4]) > ');

    if (in_array($lineSelection, ['1', '2', '3', '4', '5', ''], true)) {
        switch ($lineSelection) {
            case '1':
                $numberOfLines = '250';
                break 2;
			case '2':
                $numberOfLines = '1000';
                break 2;
			case '3':
				$numberOfLines = '5000';
				break 2;
            case '5':
                $numberOfLines = '999999';
                break 2;
            default:
            case '4':
                $numberOfLines = '20000';
                break 2;
		}
    } else {
        echo 'ERROR: Invalid option. Please try again.' . "\n";
    }
} while (true);

echo "\n";
echo 'Getting the log... ' . "\n";
exec('platform log --lines=' . escapeshellarg($numberOfLines) . ' --project=' . escapeshellarg($selectedProject[0]) . ' --environment=master php.access', $logData);
echo 'Done' . "\n";
echo "\n";

echo "\n";
$excludeTypo3 = (string) readline('Exclude /typo3/ requests? [y/n] (default: y) > ');
if($excludeTypo3 === '' || $excludeTypo3 === 'y') {
	$excludeTypo3 = true;
	echo 'Excluding the /typo3/ path from statistics.' . "\n";
} else {
	$excludeTypo3 = false;
    echo 'Including the /typo3/ path in statistics.' . "\n";
}

echo 'Processing ' . count($logData) . ' lines... ';

$lineData = [];
$lineCount = 0;
for ($i = 0; $i < count($logData); $i++) {
    $line = str_getcsv($logData[$i],' ');
	$lineCount++;
	//progressBar($lineCount, count($logData));

    $dateTime = DateTimeImmutable::createFromFormat(DATE_FORMAT, $line[0]);
    if($dateTime === false || ($excludeTypo3 && substr($line[8], 0, 7 ) === "/typo3/")) {
        continue;
    }

    $lineData[] = [
        'dateTime' => $dateTime,
        'requestMethod' => $line[1],
        'responseCode' => (int) $line[2],
        'executionTime' => (float) $line[3],
        'peakMemory' => (int) $line[5],
        'cpuPercentage' => (float) substr($line[7], 0, -1),
        'requestUri' => $line[8],
        'parsedUri' => parse_url($line[8]),
    ];
}

ob_start();
?>
<html>
    <head>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
        <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.min.js"></script>
        <script>
	        Chart.defaults.global.defaultColor = '#88888888'

            var defaultOptions = {
	            responsive: false
            }

            var redColor = '#FF0000';
            var greenColor = '#00bb00';
            var blueColor = '#4444FF';
            var yellowColor = '#ffaa00';
	        var magentaColor = '#ff00ab';
	        var cyanColor = '#00cbd7';
	        var violetColor = '#8b00ff';
	        var oliveColor = '#81ac6d';

            var lineOpacity = 'DD';
            var fillOpacity = '88';

            var clearFill = '#FFFFFFFF';

            var pieBackgroundColors = [redColor+fillOpacity, greenColor+fillOpacity, blueColor+fillOpacity, yellowColor+fillOpacity, magentaColor+fillOpacity, cyanColor+fillOpacity, violetColor+fillOpacity, oliveColor+fillOpacity,redColor+fillOpacity, greenColor+fillOpacity, blueColor+fillOpacity, yellowColor+fillOpacity, magentaColor+fillOpacity, cyanColor+fillOpacity, violetColor+fillOpacity, oliveColor+fillOpacity];
	        var pieBorderColors = [redColor+lineOpacity, greenColor+lineOpacity, blueColor+lineOpacity, yellowColor+lineOpacity, magentaColor+lineOpacity, cyanColor+lineOpacity, violetColor+lineOpacity, oliveColor+lineOpacity,redColor+lineOpacity, greenColor+lineOpacity, blueColor+lineOpacity, yellowColor+lineOpacity, magentaColor+lineOpacity, cyanColor+lineOpacity, violetColor+lineOpacity, oliveColor+lineOpacity];
        </script>
    </head>
    <body>
        <div class="container">
            <div class="row mt-4">
                <div class="col-md-12">
                    <h1>Platform.sh PHP Analysis</h1>

                    <p>
                        <strong>Start: </strong> <?php echo $lineData[0]['dateTime']->format(DATE_FORMAT) ?> &bull;
                        <strong>End: </strong>  <?php echo $lineData[count($lineData)-1]['dateTime']->format(DATE_FORMAT) ?>  <br>
						<strong>Project: </strong>  <?php echo htmlspecialchars($selectedProject[1]) ?> &bull;
						<strong>Requests Processed: </strong>  <?php echo count($lineData) ?> <br>
						<?php
						$dateIntervalInSeconds = $lineData[count($lineData)-1]['dateTime']->getTimestamp() - $lineData[0]['dateTime']->getTimestamp();
						$requestsPerMinute = count($lineData) / ($dateIntervalInSeconds/60);
						$requestsPerDay = count($lineData) / ($dateIntervalInSeconds/86400);
						$requestsPerMonth = count($lineData) / ($dateIntervalInSeconds/2592000);
						?>
						<strong>Average Request Load: </strong> <?php echo round($requestsPerMinute,2) ?>/minute &bull; <?php echo round($requestsPerDay) ?>/day &bull; <?php echo round($requestsPerMonth) ?>/month <br>
						<?php
                        $averageMemoryUsage = 0;
                        $peakMemoryUsage = 0;
                        $totalMemoryUsage = 0;

                        for ($lineNumber = 0; $lineNumber < count($lineData); $lineNumber++) {
                            $line = $lineData[$lineNumber];
                            $totalMemoryUsage += $line['peakMemory'];
                        	if ($line['peakMemory'] > $peakMemoryUsage) {
                                $peakMemoryUsage = $line['peakMemory'];
							}
                        }

                        $averageMemoryUsage = $totalMemoryUsage / count($lineData);

                        $platformPlans = [
                            [
                                'memory' => .5,
                                'pageViewsMin' => 100000,
                                'label' => 'Small'
                            ],
                        	[
                        		'memory' => .8,
								'pageViewsMin' => 200000,
								'label' => 'Standard'
							],
                            [
                                'memory' => 3,
                                'pageViewsMin' => 500000,
                                'label' => 'Medium'
                            ],
                            [
                                'memory' => 6,
                                'pageViewsMin' => 1000000,
                                'label' => 'Large'
                            ],
                            [
                                'memory' => 12,
                                'pageViewsMin' => 2000000,
                                'label' => 'X-Large'
                            ],
                            [
                                'memory' => 24,
                                'pageViewsMin' => 4000000,
                                'label' => '2X-Large'
                            ],
						];

                        ?>
						<strong>Platform Plans:</strong> <br>
						(% of page views / avg. % of total memory/minute / % of peak memory/minute)
						<ul>
						<?php
                        foreach ($platformPlans as $platformPlan) {
                        	?>
							<li>
								<?php echo htmlspecialchars($platformPlan['label']) ?>:
                                <?php echo round(
                                    ($requestsPerMonth/$platformPlan['pageViewsMin'])*100
                                ) ?>% /
								<?php echo round(
									((($averageMemoryUsage*$requestsPerMinute)/1048576)/$platformPlan['memory']), 2
								) ?>% /
                                <?php echo round(
                                    ((($peakMemoryUsage*$requestsPerMinute)/1048576)/$platformPlan['memory']), 2
                                ) ?>%
							</li>
							<?php
						}
						?>
						</ul>
					</p>

                    <hr>

                    <?php
                    //Memory usage
                    $memoryUsage = [];
                    $executionTimes = [];
                    $cpus = [];
                    $errorResponses = [];

                    for ($lineNumber = 0; $lineNumber < count($lineData); $lineNumber++) {
                        $line = $lineData[$lineNumber];
                        $key = round($line['peakMemory']/1024) . 'M';
                        if (!array_key_exists($key, $memoryUsage)) {
                        	$memoryUsage[$key] = 0;
                        }
                        $memoryUsage[$key]++;
                        $executionTimes[$key][] = $line['executionTime'];
                        $cpus[$key][] = $line['cpuPercentage'];
                        if ($line['responseCode'] >= 400) {
                        	if (!array_key_exists($key, $errorResponses)) {
                        		$errorResponses[$key] = 0;
	                        }
                            $errorResponses[$key]++;
                        }
                    }

                    $averageExecutionTime = [];
                    $averageCpu = [];

                    foreach ($executionTimes as $key => $values) {
                        $averageExecutionTime[$key] = round(array_sum($values) / count($values));
                    }

                    foreach ($cpus as $key => $values) {
                        $averageCpu[$key] = round(array_sum($values) / count($values));
                    }

                    natksort($memoryUsage);
                    natksort($averageExecutionTime);
                    natksort($averageCpu);
                    natksort($errorResponses);

                    $memoryUsageSum = array_sum($memoryUsage);
                    $memoryUsagePercentOfTotal = [];
                    $runningSum = $memoryUsageSum;
                    foreach ($memoryUsage as $key=>$value) {
                        $runningSum -= $value;
                        $memoryUsagePercentOfTotal[$key] = round(($runningSum/$memoryUsageSum)*100, 1);
                        if (!array_key_exists($key, $errorResponses)) {
                            $errorResponses[$key] = 0;
                        }
                        $errorResponses[$key] = round(($errorResponses[$key]/$value)*100, 1);
                    }
                    ?>
                    <script language="JavaScript">
                        $(function() {
                            var memoryUsageOptions = defaultOptions;
                            memoryUsageOptions.scales = {
                                yAxes: [{
                                    id: 'memoryUsage'
                                }, {
                                    id: 'percentOfTotal',
                                    display: false,
                                    ticks: {
                                        max: 100,
                                        min: 0
                                    }
                                }, {
                                    id: 'averageExecutionTime',
                                    display: false,
                                    ticks: {
                                        max: <?php echo max($averageExecutionTime) ?>,
                                        min: 0
                                    }
                                }, {
                                    id: 'averageCpu',
                                    display: false,
                                    ticks: {
                                        max: <?php echo max($averageCpu) ?>,
                                        min: 0
                                    }
                                }]
                            };

                            var context = $("#memoryUsage");
                            var memoryUsage = new Chart(context, {
                                type: 'bar',
                                data: {
                                    labels: <?php echo json_encode(array_keys($memoryUsage)) ?>,
                                    datasets: [
                                        {
                                            label: 'Request Memory Usage',
                                            data: <?php echo json_encode(array_values($memoryUsage)) ?>,
                                            yAxisID: 'memoryUsage',
                                            backgroundColor: greenColor + fillOpacity,
                                            borderColor: greenColor + lineOpacity,
                                        },
                                        {
                                            label: 'Percentage of Total Requests',
                                            data: <?php echo json_encode(array_values($memoryUsagePercentOfTotal)) ?>,
                                            type: 'line',
                                            yAxisID: 'percentOfTotal',
                                            fill: false,
                                            borderColor: blueColor + lineOpacity,
											backgroundColor: blueColor + fillOpacity
                                        },
                                        {
                                            label: 'Average Execution Time',
                                            data: <?php echo json_encode(array_values($averageExecutionTime)) ?>,
                                            type: 'line',
                                            yAxisID: 'averageExecutionTime',
                                            fill: false,
                                            borderColor: redColor + lineOpacity,
	                                        backgroundColor: redColor + fillOpacity
                                        },
                                        {
                                            label: 'Average CPU %',
                                            data: <?php echo json_encode(array_values($averageCpu)) ?>,
                                            type: 'line',
                                            yAxisID: 'averageCpu',
                                            fill: false,
                                            borderColor: yellowColor + lineOpacity,
	                                        backgroundColor: yellowColor + fillOpacity
                                        },
                                        {
                                            label: 'Error Responses %',
                                            data: <?php echo json_encode(array_values($errorResponses)) ?>,
                                            type: 'line',
                                            yAxisID: 'percentOfTotal',
                                            fill: false,
                                            borderColor: '#aaaaaa' + lineOpacity,
	                                        backgroundColor: '#aaaaaa' + fillOpacity
                                        }
                                    ]
                                },
                                options: defaultOptions
                            });
                        });

                    </script>
                    <h2>Memory usage</h2>

                    <canvas id="memoryUsage" width="800" height="400"></canvas>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row mt-4">
                <div class="col-md-12">
                    <hr>

                    <h2>Top Response Codes</h2>
                </div>
            </div>
            <div class="row mt-4">
                <?php
                $memoryUsage = null;
                $memoryUsagePercentOfTotal = null;
                $averageExecutionTime = null;
                $averageCpu = null;

                $topResponseCodes = [];
                $responseCodeMemory = [];
                $responseCodeCpu = [];
                $responseCodeExecutionTime = [];

                foreach ($lineData as $line) {
                    $responseCode = $line['responseCode'];
                    if (!array_key_exists($responseCode, $topResponseCodes)) {
                        $topResponseCodes[$responseCode] = 0;
                    }
                    $topResponseCodes[$responseCode]++;
                    $responseCodeMemory[$responseCode][] = $line['peakMemory'];
                    $responseCodeCpu[$responseCode][] = $line['cpuPercentage'];
                    $responseCodeExecutionTime[$responseCode][] = $line['executionTime'];
                }

                $responseCodeMemoryAverage = [];
                $responseCodeExecutionTimeAverage = [];
                $responseCodeCpuAverage = [];

                foreach ($responseCodeMemory as $key => $values) {
                    $responseCodeMemoryAverage[$key] = round(array_sum($values) / count($values));
                }

                foreach ($responseCodeExecutionTime as $key => $values) {
                    $responseCodeExecutionTimeAverage[$key] = round(array_sum($values) / count($values));
                }

                foreach ($responseCodeCpu as $key => $values) {
                    $responseCodeCpuAverage[$key] = round(array_sum($values) / count($values));
                }

                ?>
                <script language="JavaScript">
                    $(function() {
                        var context = $("#topResponseCodes");
                        var topResponseCodes = new Chart(context, {
                            type: 'pie',
                            data: {
                                labels: <?php echo json_encode(array_keys($topResponseCodes)) ?>,
                                datasets: [
                                    {
                                        label: 'Request Memory Usage',
                                        data: <?php echo json_encode(array_values($topResponseCodes)) ?>,
                                        backgroundColor: pieBackgroundColors,
                                        borderColor: pieBorderColors
                                    }
                                ]
                            },
                            options: defaultOptions
                        });

                        var context = $("#responseCodeMemoryAverage");
                        var topResponseCodes = new Chart(context, {
                            type: 'pie',
                            data: {
                                labels: <?php echo json_encode(array_keys($responseCodeMemoryAverage)) ?>,
                                datasets: [
                                    {
                                        label: 'Request Memory Usage',
                                        data: <?php echo json_encode(array_values($responseCodeMemoryAverage)) ?>,
                                        backgroundColor: pieBackgroundColors,
                                        borderColor: pieBorderColors
                                    }
                                ]
                            },
                            options: defaultOptions
                        });

                        var context = $("#responseCodeCpuAverage");
                        var topResponseCodes = new Chart(context, {
                            type: 'pie',
                            data: {
                                labels: <?php echo json_encode(array_keys($responseCodeCpuAverage)) ?>,
                                datasets: [
                                    {
                                        label: 'Request Memory Usage',
                                        data: <?php echo json_encode(array_values($responseCodeCpuAverage)) ?>,
                                        backgroundColor: pieBackgroundColors,
                                        borderColor: pieBorderColors
                                    }
                                ]
                            },
                            options: defaultOptions
                        });

                        var context = $("#responseCodeExecutionTimeAverage");
                        var topResponseCodes = new Chart(context, {
                            type: 'pie',
                            data: {
                                labels: <?php echo json_encode(array_keys($responseCodeExecutionTimeAverage)) ?>,
                                datasets: [
                                    {
                                        label: 'Request Memory Usage',
                                        data: <?php echo json_encode(array_values($responseCodeExecutionTimeAverage)) ?>,
                                        backgroundColor: pieBackgroundColors,
                                        borderColor: pieBorderColors
                                    }
                                ]
                            },
                            options: defaultOptions
                        });
                    });

                </script>

                <div class="col-md-6">
                    <h3>By Requests</h3>

                    <canvas id="topResponseCodes" width="400" height="400"></canvas>
                </div>

                <div class="col-md-6">
                    <h3>By Average Memory Usage</h3>

                    <canvas id="responseCodeMemoryAverage" width="400" height="400"></canvas>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <h3>By Average CPU Usage %</h3>

                    <canvas id="responseCodeCpuAverage" width="400" height="400"></canvas>
                </div>

                <div class="col-md-6">
                    <h3>By Average Execution Time</h3>

                    <canvas id="responseCodeExecutionTimeAverage" width="400" height="400"></canvas>
                </div>
            </div>

            <?php
            $topResponseCodes = null;
            $responseCodeMemoryAverage = null;
            $responseCodeCpuAverage = null;
            $responseCodeExecutionTimeAverage = null;

            $responseCodesByTime = [];
            $dateTimeSlots = [];
            $totalRequestsPerTimeSlot = [];

            $cpuPerTimeSlot = [];
            $memoryPerTimeSlot = [];
            $executionTimePerTimeSlot = [];

            foreach ($lineData as $line) {
                $timeSlot = $line['dateTime']->format(DATE_FORMAT_HOUR);
                if (!array_key_exists($line['responseCode'], $responseCodesByTime)) {
                    $responseCodesByTime[$line['responseCode']] = [];
                }
                if (!array_key_exists($timeSlot, $responseCodesByTime[$line['responseCode']])) {
                    $responseCodesByTime[$line['responseCode']][$timeSlot] = 0;
                }
                $responseCodesByTime[$line['responseCode']][$timeSlot]++;
                if (!array_key_exists($timeSlot, $totalRequestsPerTimeSlot)) {
                    $totalRequestsPerTimeSlot[$timeSlot] = 0;
                }
                $totalRequestsPerTimeSlot[$timeSlot]++;
                $cpuPerTimeSlot[$timeSlot][] = $line['cpuPercentage'];
                $memoryPerTimeSlot[$timeSlot][] = $line['peakMemory'];
                $executionTimePerTimeSlot[$timeSlot][] = $line['executionTime'];

                $dateTimeSlots[] = $timeSlot;
            }

            $cpuPerTimeSlotAverage = [];
            $memoryPerTimeSlotAverage = [];
            $executionTimePerTimeSlotAverage = [];

            foreach ($cpuPerTimeSlot as $key => $values) {
                $cpuPerTimeSlotAverage[$key] = round(array_sum($values) / count($values));
            }

            foreach ($memoryPerTimeSlot as $key => $values) {
                $memoryPerTimeSlotAverage[$key] = round(array_sum($values) / count($values));
            }

            foreach ($executionTimePerTimeSlot as $key => $values) {
                $executionTimePerTimeSlotAverage[$key] = round(array_sum($values) / count($values));
            }

            $dateTimeSlots = array_unique($dateTimeSlots);
            sort($dateTimeSlots);

            $hoursPerSlot = 1;
            if(count($dateTimeSlots) > 128) {
                $hoursPerSlot = ceil(count($dateTimeSlots) / 128);
            }

            //Reduce response code data to percentage and x y coordinates
            $responseCodeDataSets = [];
            $xLabels = [];
            foreach (array_keys($responseCodesByTime) as $responseCode) {
                $responseCodeDataSets[$responseCode] = [];
                $timeSlotCounter = 0;
                for ($i = 0; $i < count($dateTimeSlots); $i++) {
                    if (!in_array($dateTimeSlots[$i], $xLabels)) {
                        $xLabels[] = $dateTimeSlots[$i];
                    }

                    $endI = $i + $hoursPerSlot;
                    $combinedSum = 0;
                    $j = 0;
                    for(; $i < $endI && $i < count($dateTimeSlots); $i++) {
                        $j++;
                        $timeSlot = $dateTimeSlots[$i];
                        if (isset($responseCodesByTime[$responseCode][$timeSlot])) {
                            $combinedSum += round(($responseCodesByTime[$responseCode][$timeSlot] / $totalRequestsPerTimeSlot[$timeSlot]) * 100,
                                1);
                        } else {
                            $combinedSum += 0;
                        }
                    }

                    $responseCodeDataSets[$responseCode][] = $combinedSum / $j;
                }
            }

            $totalRequestsDataSets = [];
            $averageCpuDataSets = [];
            $averageMemoryDataSets = [];
            $averageExecutionTimeDataSets = [];
            for ($i = 0; $i < count($dateTimeSlots); $i++) {

                $endI = $i + $hoursPerSlot;
                $combinedRequestsSum = 0;
                $combinedCpuDataSum = 0;
                $combinedMemoryDataSum = 0;
                $combinedExecutionTimeSum = 0;
                $j = 0;
                for(; $i < $endI && $i < count($dateTimeSlots); $i++) {
                    $j++;
                    $timeSlot = $dateTimeSlots[$i];

                    $combinedRequestsSum += $totalRequestsPerTimeSlot[$timeSlot];
                    $combinedCpuDataSum += $cpuPerTimeSlotAverage[$timeSlot];
                    $combinedMemoryDataSum += $memoryPerTimeSlotAverage[$timeSlot];
                    $combinedExecutionTimeSum += $executionTimePerTimeSlotAverage[$timeSlot];
                }

                $totalRequestsDataSets[] = round($combinedRequestsSum / $j, 1);
                $averageCpuDataSets[] = round($combinedCpuDataSum / $j, 1);
                $averageMemoryDataSets[] = round($combinedMemoryDataSum / $j, 1);
                $averageExecutionTimeDataSets[] = round($combinedExecutionTimeSum / $j, 1);
            }

            ksort($responseCodeDataSets);
            ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <hr>

                    <h2 class="mt-4">Activity by Hour</h2>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <h3>Response Codes</h3>

                    <?php if ($hoursPerSlot > 1) { ?>
                        <p>1 bar = <?php echo $hoursPerSlot ?> hours.</p>
                    <?php } ?>

                    <script language="JavaScript">
		                $(function() {
			                var activityByHourOptions = defaultOptions;
			                activityByHourOptions.scales = {
				                yAxes: [{
					                id: 'responseCodesPercentageY',
					                stacked: true,
                                    display: false,
                                    ticks: {
                                        max: 100,
                                        min: 0
                                    }
				                }],
				                xAxes: [{
					                id: 'responseCodesPercentageX',
					                stacked: true,
					                categoryPercentage: 1.0,
					                barPercentage: 1.0,
                                    gridLines: {
	                                    offsetGridLines: false
                                    }
				                }]
			                };

			                var context = $("#activityByHour");
			                var activityByHour = new Chart(context, {
				                type: 'bar',
				                data: {
					                labels: <?php echo json_encode($xLabels) ?>,
					                datasets: [
                                        <?php
                                        $keys = array_keys($responseCodeDataSets);

                                        for ($i=0; $i<count($keys); $i++) {
                                        $responseCode = $keys[$i];
                                        $data = $responseCodeDataSets[$keys[$i]];
                                        ?>
                                        <?php if ($i !== 0) { echo ','; } ?> {
							                label: '<?php echo $responseCode ?>',
							                data: <?php echo json_encode($data) ?>,
							                yAxisID: 'responseCodesPercentageY',
							                xAxisID: 'responseCodesPercentageX',
							                backgroundColor: pieBackgroundColors[<?php echo $i ?>],
							                borderColor: pieBorderColors[<?php echo $i ?>]
						                }
                                        <?php
                                        }
                                        ?>
					                ]
				                },
				                options: activityByHourOptions
			                });
		                });

                    </script>

                    <canvas id="activityByHour" width="1110" height="400"></canvas>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <h3>Resource Usage by Hour</h3>

                    <?php if ($hoursPerSlot > 1) { ?>
                        <p>1 bar = <?php echo $hoursPerSlot ?> hours.</p>
                    <?php } ?>

                    <script language="JavaScript">
				        $(function() {
					        var resourceUsageByHourOptions = defaultOptions;
					        resourceUsageByHourOptions.scales = {
						        yAxes: [
						        	{
										id: 'requestsAxis',
										ticks: {
											max: <?php echo max($totalRequestsDataSets) ?>,
											min: 0
										},
										display: false
									},
									{
								        id: 'memoryAxis',
								        ticks: {
									        max: <?php echo max($averageMemoryDataSets) ?>,
									        min: 0
								        },
										display: false
							        },
							        {
								        id: 'cpuAxis',
								        ticks: {
									        max: <?php echo max($averageCpuDataSets) ?>,
									        min: 0
								        },
								        display: false
							        },
							        {
								        id: 'executionTimeAxis',
								        ticks: {
									        max: <?php echo max($averageExecutionTimeDataSets) ?>,
									        min: 0
								        },
								        display: false,
										type: 'logarithmic'
							        }
						        ]
					        };

					        var context = $("#resourceUsageByHour");
					        var activityByHour = new Chart(context, {
						        type: 'line',
						        data: {
							        labels: <?php echo json_encode($xLabels) ?>,
							        datasets: [
                                        {
									        label: 'Requests',
									        data: <?php echo json_encode($totalRequestsDataSets) ?>,
									        backgroundColor: blueColor + fillOpacity,
									        borderColor:  blueColor + lineOpacity,
                                            fill: false,
											yAxeiID: 'requestAxis'
								        },
								        {
									        label: 'Memory',
									        data: <?php echo json_encode($averageMemoryDataSets) ?>,
									        backgroundColor: greenColor + fillOpacity,
									        borderColor:  greenColor + lineOpacity,
									        fill: false,
									        yAxisID: 'memoryAxis'
								        },
								        {
									        label: 'CPU %',
									        data: <?php echo json_encode($averageCpuDataSets) ?>,
									        backgroundColor: yellowColor + fillOpacity,
									        borderColor:  yellowColor + lineOpacity,
									        fill: false,
									        yAxisID: 'cpuAxis'
								        },
								        {
									        label: 'Execution Time (log)',
									        data: <?php echo json_encode($averageExecutionTimeDataSets) ?>,
									        backgroundColor: redColor + fillOpacity,
									        borderColor:  redColor + lineOpacity,
									        fill: false,
									        yAxisID: 'executionTimeAxis'
								        }
							        ]
						        },
						        options: resourceUsageByHourOptions
					        });
				        });

                    </script>

                    <canvas id="resourceUsageByHour" width="1110" height="400"></canvas>
                </div>
            </div>

            <?php

                $topRequestsByMemory = [0=>[]];
                $topRequestsByCpu = [0=>[]];
                $topRequestsByExecutionTime = [0=>[]];
                foreach ($lineData as $line) {
                    $lowest = min(array_keys($topRequestsByMemory));
                    if ($line['peakMemory'] > $lowest) {
                        $topRequestsByMemory[$line['peakMemory']] = $line;

                        if (count($topRequestsByMemory) > 10) {
                            unset($topRequestsByMemory[$lowest]);
                        }
                    }

                    $lowest = min(array_keys($topRequestsByCpu));
                    if ($line['cpuPercentage'] > $lowest) {
                        $topRequestsByCpu[$line['cpuPercentage']] = $line;

                        if (count($topRequestsByCpu) > 10) {
                            unset($topRequestsByCpu[$lowest]);
                        }
                    }

                    $lowest = min(array_keys($topRequestsByExecutionTime));
                    if ($line['executionTime'] > $lowest) {
                        $topRequestsByExecutionTime[$line['executionTime']] = $line;

                        if (count($topRequestsByExecutionTime) > 10) {
                            unset($topRequestsByExecutionTime[$lowest]);
                        }
                    }
                }

                ksortReverse($topRequestsByMemory);
                ksortReverse($topRequestsByCpu);
                ksortReverse($topRequestsByExecutionTime);
            ?>

            <div class="row mt-4">
                <div class="col-md-12">
                    <hr>

                    <h2 class="mt-4">Top Requests</h2>

                    <h3 class="mt-4">By Memory Usage</h3>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col" class="table-info">Memory</th>
                                <th scope="col">CPU %</th>
                                <th scope="col">Execution Time</th>
                                <th scope="col">Response</th>
                                <th scope="col">Request</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach ($topRequestsByMemory as $request) {
                                    ?>
                                        <tr>
                                            <td class="table-info"><?php echo $request['peakMemory'] ?></td>
                                            <td><?php echo $request['cpuPercentage'] ?></td>
                                            <td><?php echo $request['executionTime'] ?></td>
                                            <td><?php echo $request['responseCode'] ?></td>
                                            <td><?php echo htmlspecialchars($request['requestUri']) ?></td>
                                        </tr>
                                    <?php
                                }
                            ?>
                        </tbody>
                    </table>

                    <h3 class="mt-4">By CPU</h3>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Memory</th>
                                <th scope="col" class="table-info">CPU %</th>
                                <th scope="col">Execution Time</th>
                                <th scope="col">Response</th>
                                <th scope="col">Request</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($topRequestsByCpu as $request) {
                            ?>
                            <tr>
                                <td><?php echo $request['peakMemory'] ?></td>
                                <td class="table-info"><?php echo $request['cpuPercentage'] ?></td>
                                <td><?php echo $request['executionTime'] ?></td>
                                <td><?php echo $request['responseCode'] ?></td>
                                <td><?php echo htmlspecialchars($request['requestUri']) ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>

                    <h3 class="mt-4">By Execution Time</h3>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Memory</th>
                                <th scope="col">CPU %</th>
                                <th scope="col" class="table-info">Execution Time</th>
                                <th scope="col">Response</th>
                                <th scope="col">Request</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($topRequestsByExecutionTime as $request) {
                            ?>
                            <tr>
                                <td><?php echo $request['peakMemory'] ?></td>
                                <td><?php echo $request['cpuPercentage'] ?></td>
                                <td class="table-info"><?php echo $request['executionTime'] ?></td>
                                <td><?php echo $request['responseCode'] ?></td>
                                <td><?php echo htmlspecialchars($request['requestUri']) ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>
<?php
$fileName = preg_replace('/\\s+/', '_', preg_replace("/[^A-Za-z0-9]/", '', $selectedProject[1])) . '-' .$lineData[0]['dateTime']->format('YmdHis') . '-' . $lineData[count($lineData)-1]['dateTime']->format('YmdHis') . '.html';
file_put_contents($fileName, ob_get_clean());

echo 'Done' . "\n";
echo "\n";
echo 'Analysis saved to file: ' . $fileName . "\n";
echo "\n";

passthru('open ' . escapeshellarg($fileName));

function natksort(&$array) {
    ksort($array, SORT_NATURAL);
}

function ksortReverse(&$array, $preserveKeys = false) {
    ksort($array);
    $array = array_reverse($array);
}

function progressBar($done, $total) {
    $perc = floor(($done / $total) * 100);
    $left = 100 - $perc;
    $write = sprintf("\033[0G\033[2K[%'={$perc}s>%-{$left}s] - $perc%% - $done/$total", "", "");
    fwrite(STDERR, $write);
}

?>
