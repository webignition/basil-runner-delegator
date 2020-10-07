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
        $configurations = [];

        foreach ($env as $key => $value) {
            if (false === is_string($value)) {
                continue;
            }

            $this->matchRunnerConfigurationComponent($configurations, self::ENV_HOST_SUFFIX, 'host', $key, $value);
            $this->matchRunnerConfigurationComponent($configurations, self::ENV_PORT_SUFFIX, 'port', $key, $value);
        }

        return $this->createFromArray($configurations);
    }

    /**
     * @param array<array<int, string>> $configurations
     * @param string $suffix
     * @param string $component
     * @param string $key
     * @param string $value
     */
    private function matchRunnerConfigurationComponent(
        array &$configurations,
        string $suffix,
        string $component,
        string $key,
        string $value
    ): void {
        $matches = [];
        $matchPattern = '/^[A-Z]+' . $suffix . '$/';

        if (preg_match($matchPattern, $key, $matches)) {
            $identifier = (string) preg_replace('/' . $suffix . '$/', '', $key);
            $identifier = strtolower($identifier);

            if (false === array_key_exists($identifier, $configurations)) {
                $configurations[$identifier] = [];
            }

            $configurations[$identifier][$component] = $value;
        }
    }

    /**
     * @param array<mixed> $data
     *
     * @return RunnerClient[]
     */
    private function createFromArray(array $data): array
    {
        $connectionStringFactory = new ConnectionStringFactory();
        $clients = [];

        foreach ($data as $name => $clientData) {
            $configuration = $this->createRunnerClientConfiguration($clientData);

            $client = new RunnerClient(
                $connectionStringFactory->createFromHostAndPort(
                    $configuration->getHost(),
                    $configuration->getPort()
                ),
                $this->handler
            );

            if ($client instanceof RunnerClient) {
                $clients[$name] = $client;
            }
        }

        return $clients;
    }

    /**
     * @param array<mixed> $data
     *
     * @return RunnerClientConfiguration
     */
    private function createRunnerClientConfiguration(array $data): RunnerClientConfiguration
    {
        $host = $data['host'] ?? '';
        if (!is_string($host)) {
            $host = '';
        }

        $port = $data['port'] ?? 0;

        if (ctype_digit($port)) {
            $port = (int) $port;
        }

        if (!is_int($port)) {
            $port = 0;
        }

        return new RunnerClientConfiguration('', $host, $port);
    }
}
