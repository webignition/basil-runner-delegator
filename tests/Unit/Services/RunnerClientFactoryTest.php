<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
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
        $output = \Mockery::mock(OutputInterface::class);

        $chromeClientConfiguration = new RunnerClientConfiguration('chrome', 'chrome-runner', 9000);
        $firefoxClientConfiguration = new RunnerClientConfiguration('firefox', 'firefox-runner', 9001);

        $chromeClient = (new RunnerClient($chromeClientConfiguration))->withOutput($output);
        $firefoxClient = (new RunnerClient($firefoxClientConfiguration))->withOutput($output);

        return [
            'empty' => [
                'factory' => new RunnerClientFactory([], $output),
                'expectedClients' => [],
            ],
            'single client' => [
                'factory' => new RunnerClientFactory(
                    [
                        $chromeClientConfiguration,
                    ],
                    $output
                ),
                'expectedClients' => [
                    'chrome' => $chromeClient,
                ],
            ],
            'multiple clients' => [
                'factory' => new RunnerClientFactory(
                    [
                        $chromeClientConfiguration,
                        $firefoxClientConfiguration,
                    ],
                    $output
                ),
                'expectedClients' => [
                    'chrome' => $chromeClient,
                    'firefox' => $firefoxClient,
                ],
            ],
        ];
    }
}
