<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use Symfony\Component\Console\Output\OutputInterface;
use webignition\BasilRunner\Model\RunnerClientConfiguration;

class RunnerClientFactory
{
    /**
     * @var RunnerClientConfiguration[]
     */
    private array $clientConfiguration;
    private OutputInterface $output;

    /**
     * @param array<mixed> $clientConfiguration
     * @param OutputInterface $output
     */
    public function __construct(array $clientConfiguration, OutputInterface $output)
    {
        $this->clientConfiguration = array_filter($clientConfiguration, function ($item) {
            return $item instanceof RunnerClientConfiguration;
        });

        $this->output = $output;
    }

    /**
     * @return RunnerClient[]
     */
    public function createClients(): array
    {
        $clients = [];

        foreach ($this->clientConfiguration as $configuration) {
            $runnerClient = new RunnerClient($configuration);
            $runnerClient = $runnerClient->withOutput($this->output);

            if ($runnerClient instanceof RunnerClient) {
                $clients[$configuration->getName()] = $runnerClient;
            }
        }

        return $clients;
    }
}
