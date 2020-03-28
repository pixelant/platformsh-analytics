<?php

namespace FourViewture\PshLogAnalyzer\Command\Php;

use FourViewture\PshLogAnalyzer\LogReader\Php\AccessLogReader;
use FourViewture\PshLogAnalyzer\Util\PlatformShEnvSelector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyzeCommand extends Command
{
    protected static $defaultName = 'php:analyze';

    protected function configure()
    {
        $this
            ->setDescription('Create a PHP usage report')
            ->addOption(
                'project',
                'p',
                InputOption::VALUE_OPTIONAL,
                'The platform.sh project id',
                null
            )
            ->addOption(
                'lines',
                'l',
                InputOption::VALUE_OPTIONAL,
                'The Number of lines to analyze',
                1000000
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $platformEnvSelector = new PlatformShEnvSelector($input, $output);

        $project = $platformEnvSelector->selectProject($input->getOption('project'));
        $lines = $input->getOption('lines');
        $environment = 'master';

        $logReader = new AccessLogReader($project, $environment, $lines);
        $logReader->fetch();

        $output->writeln($logReader->getCountOfProcessedRequests());

    }
}