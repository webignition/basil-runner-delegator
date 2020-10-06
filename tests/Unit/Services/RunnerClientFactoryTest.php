<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Unit\Services;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use webignition\BasilRunnerDelegator\Services\RunnerClient;
use webignition\BasilRunnerDelegator\Services\RunnerClientFactory;
use webignition\TcpCliProxyClient\Handler;
use webignition\TcpCliProxyClient\Services\ConnectionStringFactory;

class RunnerClientFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider loadSuccessDataProvider
     *
     * @param mixed $clientData
     * @param RunnerClient[] $expectedClients
     */
    public function testLoadSuccess($clientData, Handler $handler, array $expectedClients)
    {
        $yamlParser = \Mockery::mock(Parser::class);
        $yamlParser
            ->shouldReceive('parseFile')
            ->andReturn($clientData);

        $factory = new RunnerClientFactory(
            $yamlParser,
            \Mockery::mock(LoggerInterface::class),
            $handler
        );

        $path = 'path/to/clients.yaml';
        $clients = $factory->load($path);

        self::assertEquals($expectedClients, $clients);
    }

    public function loadSuccessDataProvider(): array
    {
        $connectionStringFactory = new ConnectionStringFactory();
        $handler = \Mockery::mock(Handler::class);

        return [
            'clients are loaded' => [
                'clientData' => [
                    'chrome' => [
                        'host' => 'chrome-runner',
                        'port' => 9000,
                    ],
                    'firefox' => [
                        'host' => 'firefox-runner',
                        'port' => 9001,
                    ],
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
            'loaded data is not an array' => [
                'clientData' => 'not an array',
                'handler' => $handler,
                'expectedClients' => [],
            ],
        ];
    }

    public function testLoadYamlParseException()
    {
        $path = 'path/to/clients.yaml';
        $parseException = new ParseException('parse error message');

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('debug')
            ->with('parse error message', [
                'path' => $path,
            ]);

        $yamlParser = \Mockery::mock(Parser::class);
        $yamlParser
            ->shouldReceive('parseFile')
            ->andThrow($parseException);

        $factory = new RunnerClientFactory($yamlParser, $logger, \Mockery::mock(Handler::class));

        $clients = $factory->load($path);

        self::assertEquals([], $clients);
    }
}
