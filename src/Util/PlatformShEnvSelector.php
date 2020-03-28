<?php

namespace FourViewture\PshLogAnalyzer\Util;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PlatformShEnvSelector
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
    }

    public function selectProject(string $project = null): string
    {
        if ($project !== null) {
            return $project;
        }

        $projects = $this->getProjects();

        $q = new \Symfony\Component\Console\Helper\QuestionHelper();
        $value = $q->ask(
            $this->input,
            $this->output,
            new \Symfony\Component\Console\Question\ChoiceQuestion(
                'Please choose a project',
                $projects
            )
        );

        $projectInformation = str_getcsv($value);

        return $projectInformation[0];
    }

    protected function getProjects()
    {
        exec('platform projects --format=csv --no-header', $output);
        return $output;
    }
}