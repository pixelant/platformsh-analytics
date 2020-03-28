<?php

namespace FourViewture\PshLogAnalyzer\LogReader\Php;

class AccessLogReader
{
    public const DATE_FORMAT = 'Y-m-d\TH:i:sP';

    public const DATE_FORMAT_HOUR = 'Y-m-d\TH';

    protected $project;
    protected $environment;
    protected $lines;
    protected $exclude;

    protected $logStructure;

    public function __construct(string $project, string $environment, int $lines)
    {
        $this->project = $project;
        $this->environment = $environment;
        $this->lines = $lines;

        ini_set('memory_limit', '2G');
    }

    public function setExclude(string $exclude)
    {
        $this->exclude = $exclude;
    }

    public function fetch()
    {
        exec(
            implode(
                ' ',
                [
                    'platform log',
                    '--lines',
                    escapeshellarg($this->lines),
                    '--project',
                    escapeshellarg($this->project),
                    '--environment',
                    escapeshellarg($this->environment),
                    'php.access'
                ]
            ),
            $logData
        );

        $this->logStructure = [];
        foreach ($logData as $iValue) {
            $line = str_getcsv($iValue,' ');
            $dateTime = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $line[0]);
            // @todo add condition for excluding pathes
            if($dateTime === false) {
                continue;
            }

            $this->logStructure[] = [
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
    }

    public function getStartDate()
    {
        return $this->logStructure[0]['dateTime']->format(DATE_FORMAT);
    }

    public function getEndDate()
    {
        return $this->logStructure[count($this->logStructure)-1]['dateTime']->format(DATE_FORMAT);
    }

    public function getProject()
    {
        return $this->project;
    }

    public function getCountOfProcessedRequests()
    {
        return count($this->logStructure);
    }

    public function getIntervalInSeconds()
    {
        return $this->logStructure[count($this->logStructure)-1]['dateTime']->getTimestamp() - $this->logStructure[0]['dateTime']->getTimestamp();
    }

    public function getRequestsPerMinute()
    {
        return count($this->logStructure) / ($this->getIntervalInSeconds()/60);
    }

    public function getRequestsPerDay()
    {
        return count($this->logStructure) / $this->getIntervalInSeconds() / 86400;
    }

    public function getRequestsPerMonth()
    {
        return count($this->logStructure) / $this->getIntervalInSeconds() / 2592000;
    }

    public function getAverageMemoryUsage()
    {
        $sum = 0;
        foreach($this->logStructure as $line) {
            $sum += $line['peakMemory']
        }
        return $sum/count($this->logStructure);
    }

    public function getPeakMemoryUsage()
    {
        $peakMemoryUsage = 0;
        foreach($this->logStructure as $line) {
            if ($line['peakMemory'] > $peakMemoryUsage) {
                $peakMemoryUsage = $line['peakMemory'];
            }
        }
        return $peakMemoryUsage;
    }
}