<?php

namespace FourViewture\PshLogAnalyzer\Command\GoAccess;

use FourViewture\PshLogAnalyzer\Util\PlatformShEnvSelector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyzeCommand extends Command
{
    protected static $defaultName = 'goaccess:analyze';

    protected function configure()
    {
        $this
            ->setDescription('Create a GoAccess report')
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
                'The Number of lines to analyze'
                1000000
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $platformEnvSelector = new PlatformShEnvSelector($input, $output);

        $project = $platformEnvSelector->selectProject($input->getOption('project'));
        $lines = $input->getOption('lines');
        $environment = 'master';

        $output->writeln('Pulling the logfile');

        $command = [
            'platform log -q',
            '--lines',
            escapeshellarg($lines),
            ' --project',
            escapeshellarg($project),
            '--environment',
            escapeshellarg($environment),
            'access',
            '|',
            'LC_ALL=en_US.UTF-8  goaccess --log-format="COMBINED" --html-prefs=\'{"theme":"bright"}\' -'
        ];

        $output->writeln(implode(' ', $command));

        exec(
            implode(' ', $command),
            $goaccessOutput
        );

        $fileName = preg_replace('/\\s+/', '_', preg_replace('/[^A-Za-z0-9]/', '', $project)) . '-goaccess-' .date('YmdHis') . '.html';
        file_put_contents('reports/' . $fileName, implode("\n", $goaccessOutput));

        return 0;
    }
}
