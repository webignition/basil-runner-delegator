<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use webignition\BasilRunner\Model\RunnerClientConfiguration;
use webignition\BasilRunner\Services\RunnerClientFactory;
use webignition\TcpCliProxyClient\Client;

class RunnerClientFactoryTest extends TestCase
{
    /**
     * @dataProvider createClientsDataProvider
     *
     * @param RunnerClientFactory $factory
     * @param Client[] $expectedClients
     */
    public function testCreateClients(RunnerClientFactory $factory, array $expectedClients)
    {
        self::assertEquals($expectedClients, $factory->createClients());
    }

    public function createClientsDataProvider(): array
    {
        return [
            'empty' => [
                'factory' => new RunnerClientFactory([]),
                'expectedClients' => [],
            ],
            'single client' => [
                'factory' => new RunnerClientFactory([
                    new RunnerClientConfiguration('chrome', 'chrome-runner', 9000),
                ]),
                'expectedClients' => [
                    'chrome' => new Client('chrome-runner', 9000),
                ],
            ],
            'multiple clients' => [
                'factory' => new RunnerClientFactory([
                    new RunnerClientConfiguration('chrome', 'chrome-runner', 9000),
                    new RunnerClientConfiguration('firefox', 'firefox-runner', 9001),
                ]),
                'expectedClients' => [
                    'chrome' => new Client('chrome-runner', 9000),
                    'firefox' => new Client('firefox-runner', 9001),
                ],
            ],
        ];
    }
}
