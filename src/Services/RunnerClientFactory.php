<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Services;

use webignition\BasilRunnerDelegator\Model\RunnerClientConfiguration;
use webignition\TcpCliProxyClient\Handler;
use webignition\TcpCliProxyClient\Services\ConnectionStringFactory;

class RunnerClientFactory
{
    public const ENV_HOST_SUFFIX = '_RUNNER_HOST';
    public const ENV_PORT_SUFFIX = '_RUNNER_PORT';

    private Handler $handler;

    public function __construct(Handler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param array<mixed> $env
     *
     * @return RunnerClient[]
     */
    public function loadFromEnv(array $env): array
    {
        $configurations = $this->extractConfigurationDataFromEnv($env);

        return $this->createCollectionFromArray($configurations);
    }

    /**
     * @param array<mixed> $env
     *
     * @return array<mixed>
     */
    private function extractConfigurationDataFromEnv(array $env): array
    {
        $configurations = [];

        foreach ($env as $key => $value) {
            if (false === is_string($value)) {
                continue;
            }

            $hostMatch = $this->findRunnerConfigurationComponent(self::ENV_HOST_SUFFIX, 'host', $key, $value);
            if ([] !== $hostMatch) {
                $configurations = array_merge_recursive($configurations, $hostMatch);
            }

            $portMatch = $this->findRunnerConfigurationComponent(self::ENV_PORT_SUFFIX, 'port', $key, $value);
            if ([] !== $portMatch) {
                $configurations = array_merge_recursive($configurations, $portMatch);
            }
        }

        return $configurations;
    }

    /**
     * @param string $suffix
     * @param string $component
     * @param string $key
     * @param string $value
     *
     * @return array<mixed>
     */
    private function findRunnerConfigurationComponent(
        string $suffix,
        string $component,
        string $key,
        string $value
    ): array {
        $result = [];

        $matchPattern = '/^[A-Z]+' . $suffix . '$/';
        if (preg_match($matchPattern, $key)) {
            $replacePattern = '/' . $suffix . '$/';

            $identifier = (string) preg_replace($replacePattern, '', $key);
            $identifier = strtolower($identifier);

            $result[$identifier][$component] = $value;
        }

        return $result;
    }

    /**
     * @param array<mixed> $data
     *
     * @return RunnerClient[]
     */
    private function createCollectionFromArray(array $data): array
    {
        $clients = [];

        foreach ($data as $name => $clientData) {
            $clients[$name] = $this->createFromArray($clientData);
        }

        return $clients;
    }

    /**
     * @param array<mixed> $data
     *
     * @return RunnerClient
     */
    private function createFromArray(array $data): RunnerClient
    {
        $connectionStringFactory = new ConnectionStringFactory();

        $configuration = RunnerClientConfiguration::fromArray($data);

        return new RunnerClient(
            $connectionStringFactory->createFromHostAndPort(
                $configuration->getHost(),
                $configuration->getPort()
            ),
            $this->handler
        );
    }
}
