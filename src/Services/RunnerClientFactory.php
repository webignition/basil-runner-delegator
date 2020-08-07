<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilRunner\Model\RunnerClientConfiguration;
use webignition\TcpCliProxyClient\Client;

class RunnerClientFactory
{
    /**
     * @var RunnerClientConfiguration[]
     */
    private array $clientConfiguration;

    /**
     * @param array<mixed> $clientConfiguration
     */
    public function __construct(array $clientConfiguration)
    {
        $this->clientConfiguration = array_filter($clientConfiguration, function ($item) {
            return $item instanceof RunnerClientConfiguration;
        });
    }

    /**
     * @return Client[]
     */
    public function createClients(): array
    {
        $clients = [];

        foreach ($this->clientConfiguration as $configuration) {
            $clients[$configuration->getName()] = new Client(
                $configuration->getHost(),
                $configuration->getPort()
            );
        }

        return $clients;
    }
}
