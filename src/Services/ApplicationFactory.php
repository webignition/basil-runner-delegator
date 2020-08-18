<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use Symfony\Component\Console\SingleCommandApplication;
use webignition\BasilRunner\Command\RunCommand;
use webignition\SingleCommandApplicationFactory\Factory;

class ApplicationFactory
{
    private RunnerClientFactory $runnerClientFactory;
    private SuiteManifestFactory $suiteManifestFactory;

    public function __construct(RunnerClientFactory $runnerClientFactory, SuiteManifestFactory $suiteManifestFactory)
    {
        $this->runnerClientFactory = $runnerClientFactory;
        $this->suiteManifestFactory = $suiteManifestFactory;
    }

    public function create(string $version): SingleCommandApplication
    {
        return (new Factory())->create(
            new RunCommand($this->runnerClientFactory, $this->suiteManifestFactory),
            $version
        );
    }
}
