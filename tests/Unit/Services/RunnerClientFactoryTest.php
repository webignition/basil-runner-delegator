<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Unit\Services;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use webignition\BasilRunnerDelegator\Services\RunnerClient;
use webignition\BasilRunnerDelegator\Services\RunnerClientFactory;
use webignition\TcpCliProxyClient\Handler;
use webignition\TcpCliProxyClient\Services\ConnectionStringFactory;

class RunnerClientFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider loadFromEnvDataProvider
     *
     * @param array<mixed> $env
     * @param Handler $handler
     * @param RunnerClient[] $expectedClients
     */
    public function testLoadFromEnv(array $env, Handler $handler, array $expectedClients)
    {
        $factory = new RunnerClientFactory($handler);

        $clients = $factory->loadFromEnv($env);

        self::assertEquals($expectedClients, $clients);
    }

    public function loadFromEnvDataProvider(): array
    {
        $connectionStringFactory = new ConnectionStringFactory();
        $handler = \Mockery::mock(Handler::class);

        return [
            'empty' => [
                'env' => [],
                'handler' => $handler,
                'expectedClients' => [],
            ],
            'single client, host then port' => [
                'env' => [
                    'CHROME_RUNNER_HOST' => 'chrome-runner',
                    'CHROME_RUNNER_PORT' => '9000',
                ],
                'handler' => $handler,
                'expectedClients' => [
                    'chrome' => (new RunnerClient(
                        $connectionStringFactory->createFromHostAndPort('chrome-runner', 9000),
                        $handler
                    )),
                ],
            ],
            'single client, junk host then junk then port' => [
                'env' => [
                    1,
                    'CHROME_RUNNER_HOST' => 'chrome-runner',
                    true,
                    'CHROME_RUNNER_JUNK01' => 'red-herring-1',
                    'CHROME_RUNNER_PORT' => '9000',
                ],
                'handler' => $handler,
                'expectedClients' => [
                    'chrome' => (new RunnerClient(
                        $connectionStringFactory->createFromHostAndPort('chrome-runner', 9000),
                        $handler
                    )),
                ],
            ],
            'single client, port then' => [
                'env' => [
                    'CHROME_RUNNER_PORT' => '9000',
                    'CHROME_RUNNER_HOST' => 'chrome-runner',
                ],
                'handler' => $handler,
                'expectedClients' => [
                    'chrome' => (new RunnerClient(
                        $connectionStringFactory->createFromHostAndPort('chrome-runner', 9000),
                        $handler
                    )),
                ],
            ],
            'two clients' => [
                'env' => [
                    'CHROME_RUNNER_HOST' => 'chrome-runner',
                    'CHROME_RUNNER_PORT' => '9000',
                    'FIREFOX_RUNNER_HOST' => 'firefox-runner',
                    'FIREFOX_RUNNER_PORT' => '9001',
                ],
                'handler' => $handler,
                'expectedClients' => [
                    'chrome' => (new RunnerClient(
                        $connectionStringFactory->createFromHostAndPort('chrome-runner', 9000),
                        $handler
                    )),
                    'firefox' => (new RunnerClient(
                        $connectionStringFactory->createFromHostAndPort('firefox-runner', 9001),
                        $handler
                    )),
                ],
            ],
        ];
    }
}
