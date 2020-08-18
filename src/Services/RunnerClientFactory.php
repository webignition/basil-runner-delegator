<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use webignition\BasilRunner\Model\RunnerClientConfiguration;

class RunnerClientFactory
{
    private Parser $yamlParser;
    private LoggerInterface $logger;
    private OutputInterface $output;

    public function __construct(
        Parser $yamlParser,
        LoggerInterface $logger,
        OutputInterface $output
    ) {
        $this->yamlParser = $yamlParser;
        $this->logger = $logger;
        $this->output = $output;
    }

    /**
     * @param string $path
     *
     * @return RunnerClient[]
     */
    public function load(string $path): array
    {
        try {
            $data = $this->yamlParser->parseFile($path);
        } catch (ParseException $yamlParseException) {
            $this->logger->debug(
                $yamlParseException->getMessage(),
                ['path' => $path]
            );

            return [];
        }

        if (!is_array($data)) {
            $data = [];
        }

        return $this->createFromArray($data);
    }

    /**
     * @param array<mixed> $data
     *
     * @return RunnerClient[]
     */
    private function createFromArray(array $data): array
    {
        $clients = [];

        foreach ($data as $name => $clientData) {
            $configuration = $this->createRunnerClientConfiguration($clientData);

            $client = new RunnerClient($configuration->getHost(), $configuration->getPort());
            $client = $client->withOutput($this->output);

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
        if (!is_int($port)) {
            $port = 0;
        }

        return new RunnerClientConfiguration('', $host, $port);
    }
}
