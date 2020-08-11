<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use webignition\BasilRunner\Model\RunnerClientConfiguration;
use webignition\BasilRunner\Services\RunnerClient;
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
        $chromeClientConfiguration = new RunnerClientConfiguration('chrome', 'chrome-runner', 9000);
        $firefoxClientConfiguration = new RunnerClientConfiguration('firefox', 'firefox-runner', 9001);

        return [
            'empty' => [
                'factory' => new RunnerClientFactory([]),
                'expectedClients' => [],
            ],
            'single client' => [
                'factory' => new RunnerClientFactory([
                    $chromeClientConfiguration,
                ]),
                'expectedClients' => [
                    'chrome' => new RunnerClient($chromeClientConfiguration),
                ],
            ],
            'multiple clients' => [
                'factory' => new RunnerClientFactory([
                    $chromeClientConfiguration,
                    $firefoxClientConfiguration,
                ]),
                'expectedClients' => [
                    'chrome' => new RunnerClient($chromeClientConfiguration),
                    'firefox' => new RunnerClient($firefoxClientConfiguration),
                ],
            ],
        ];
    }
}
