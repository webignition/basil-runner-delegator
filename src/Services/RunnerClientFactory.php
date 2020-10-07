<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Services;

use webignition\TcpCliProxyClient\Handler;
use webignition\TcpCliProxyClient\Services\ConnectionStringFactory;

class RunnerClientFactory
{
    private RunnerClientConfigurationFactory $runnerClientConfigurationFactory;
    private ConnectionStringFactory $connectionStringFactory;
    private Handler $handler;

    public function __construct(
        RunnerClientConfigurationFactory $runnerClientConfigurationFactory,
        ConnectionStringFactory $connectionStringFactory,
        Handler $handler
    ) {
        $this->runnerClientConfigurationFactory = $runnerClientConfigurationFactory;
        $this->connectionStringFactory = $connectionStringFactory;
        $this->handler = $handler;
    }

    /**
     * @param array<mixed> $env
     *
     * @return RunnerClient[]
     */
    public function loadFromEnv(array $env): array
    {
        $clients = [];
        $configurations = $this->runnerClientConfigurationFactory->createCollectionFromEnv($env);

        foreach ($configurations as $name => $configuration) {
            $clients[$name] = new RunnerClient(
                $this->connectionStringFactory->createFromHostAndPort(
                    $configuration->getHost(),
                    $configuration->getPort()
                ),
                $this->handler
            );
        }

        return $clients;
    }
}
