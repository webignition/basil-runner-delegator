<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use webignition\BasilRunnerDelegator\Model\RunnerClientConfiguration;

class RunnerClientConfigurationTest extends TestCase
{
    public function testCreate()
    {
        $host = 'chrome-runner';
        $port = 9000;

        $configuration = new RunnerClientConfiguration($host, $port);

        self::assertSame($host, $configuration->getHost());
        self::assertSame($port, $configuration->getPort());
    }

    /**
     * @dataProvider fromArrayDataProvider
     *
     * @param array<mixed> $data
     * @param RunnerClientConfiguration $expectedConfiguration
     */
    public function testCreateFromArray(array $data, RunnerClientConfiguration $expectedConfiguration)
    {
        self::assertEquals($expectedConfiguration, RunnerClientConfiguration::fromArray($data));
    }

    public function fromArrayDataProvider(): array
    {
        return [
            'empty' => [
                'data' => [],
                'expectedConfiguration' => new RunnerClientConfiguration('', 0),
            ],
            'host not present' => [
                'data' => [
                    RunnerClientConfiguration::KEY_PORT => 123,
                ],
                'expectedConfiguration' => new RunnerClientConfiguration('', 123),
            ],
            'port not present' => [
                'data' => [
                    RunnerClientConfiguration::KEY_HOST => 'hostname',
                ],
                'expectedConfiguration' => new RunnerClientConfiguration('hostname', 0),
            ],
            'host not string' => [
                'data' => [
                    RunnerClientConfiguration::KEY_HOST => 456,
                    RunnerClientConfiguration::KEY_PORT => 123,
                ],
                'expectedConfiguration' => new RunnerClientConfiguration('', 123),
            ],
            'port not numeric' => [
                'data' => [
                    RunnerClientConfiguration::KEY_HOST => 'hostname',
                    RunnerClientConfiguration::KEY_PORT => 'port',
                ],
                'expectedConfiguration' => new RunnerClientConfiguration('hostname', 0),
            ],
            'valid' => [
                'data' => [
                    RunnerClientConfiguration::KEY_HOST => 'hostname',
                    RunnerClientConfiguration::KEY_PORT => 123,
                ],
                'expectedConfiguration' => new RunnerClientConfiguration('hostname', 123),
            ],
        ];
    }
}
