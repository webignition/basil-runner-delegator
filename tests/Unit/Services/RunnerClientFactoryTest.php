<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Unit\Services;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use webignition\BasilRunnerDelegator\Services\RunnerClient;
use webignition\BasilRunnerDelegator\Services\RunnerClientFactory;
use webignition\TcpCliProxyClient\Services\ConnectionStringFactory;

class RunnerClientFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider loadSuccessDataProvider
     *
     * @param mixed $clientData
     * @param OutputInterface $output
     * @param RunnerClient[] $expectedClients
     */
    public function testLoadSuccess($clientData, OutputInterface $output, array $expectedClients)
    {
        $yamlParser = \Mockery::mock(Parser::class);
        $yamlParser
            ->shouldReceive('parseFile')
            ->andReturn($clientData);

        $factory = new RunnerClientFactory($yamlParser, \Mockery::mock(LoggerInterface::class), $output);

        $path = 'path/to/clients.yaml';
        $clients = $factory->load($path);

        self::assertEquals($expectedClients, $clients);
    }

    public function loadSuccessDataProvider(): array
    {
        $output = \Mockery::mock(OutputInterface::class);
        $connectionStringFactory = new ConnectionStringFactory();

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
                'output' => $output,
                'expectedClients' => [
                    'chrome' => (new RunnerClient(
                        $connectionStringFactory->createFromHostAndPort('chrome-runner', 9000)
                    ))->withOutput($output),
                    'firefox' => (new RunnerClient(
                        $connectionStringFactory->createFromHostAndPort('firefox-runner', 9001)
                    ))->withOutput($output),
                ],
            ],
            'loaded data is not an array' => [
                'clientData' => 'not an array',
                'output' => $output,
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

        $output = \Mockery::mock(OutputInterface::class);
        $factory = new RunnerClientFactory($yamlParser, $logger, $output);

        $clients = $factory->load($path);

        self::assertEquals([], $clients);
    }
}
