<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Unit\Command;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\BasilRunnerDelegator\Command\RunCommand;
use webignition\BasilRunnerDelegator\Exception\InvalidRemotePathException;
use webignition\BasilRunnerDelegator\Exception\NonExecutableRemoteTestException;
use webignition\BasilRunnerDelegator\RunnerClient\RunnerClient;
use webignition\BasilRunnerDocuments\Exception;
use webignition\TcpCliProxyClient\Exception\ClientCreationException;
use webignition\TcpCliProxyClient\Exception\SocketErrorException;
use webignition\YamlDocumentGenerator\YamlGenerator;

class RunCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider runSuccessDataProvider
     *
     * @param RunnerClient[] $runnerClients
     * @param string $browser
     * @param string $path
     * @param LoggerInterface|null $logger
     */
    public function testRunSuccess(
        array $runnerClients,
        string $browser,
        string $path,
        OutputInterface $commandOutput,
        ?LoggerInterface $logger = null
    ): void {
        $input = new ArrayInput([
            '--browser' => $browser,
            'path' => $path,
        ]);

        $logger = $logger ?? \Mockery::mock(LoggerInterface::class);

        $command = new RunCommand(
            $runnerClients,
            $logger,
            new YamlGenerator()
        );
        $exitCode = $command->run($input, $commandOutput);

        self::assertSame(0, $exitCode);
    }

    /**
     * @return array[]
     */
    public function runSuccessDataProvider(): array
    {
        $testPath = '/target/GeneratedChromeTest.php';

        $chromeInvalidRemotePathException = new InvalidRemotePathException($testPath);
        $chromeNonExecutableTestException = new NonExecutableRemoteTestException($testPath);

        $yamlGenerator = new YamlGenerator();

        return [
            'has runner clients, test for unknown browser' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($testPath),
                ],
                'browser' => 'unknown',
                'path' => $testPath,
                'commandOutput' => \Mockery::mock(OutputInterface::class),
                'logger' => $this->createLogger(
                    'Unknown browser \'unknown\'',
                    [
                        'browser' => 'unknown',
                    ]
                ),
            ],
            'client request throws SocketErrorException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient(
                        $testPath,
                        new SocketErrorException(
                            new \ErrorException('socket error exception message')
                        )
                    ),
                ],
                'browser' => 'chrome',
                'path' => $testPath,
                'commandOutput' => \Mockery::mock(OutputInterface::class),
                'logger' => $this->createLogger('socket error exception message', []),
            ],
            'client request throws ClientCreationException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient(
                        $testPath,
                        new ClientCreationException('connection string', 'client creation exception message', 123)
                    ),
                ],
                'browser' => 'chrome',
                'path' => $testPath,
                'commandOutput' => \Mockery::mock(OutputInterface::class),
                'logger' => $this->createLogger('client creation exception message', [
                    'connection-string' => 'connection string',
                ]),
            ],
            'has runner clients, throws InvalidRemotePathException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($testPath, $chromeInvalidRemotePathException),
                ],
                'browser' => 'chrome',
                'path' => $testPath,
                'commandOutput' => $this->createCommandOutput([
                    'write' => [
                        $yamlGenerator->generate(
                            Exception::createFromThrowable($chromeInvalidRemotePathException)->withoutTrace()
                        ),
                    ],
                ]),
                'logger' => $this->createLogger(
                    'Path "/target/GeneratedChromeTest.php" not present on runner',
                    [
                        'remote-path' => '/target/GeneratedChromeTest.php',
                    ]
                ),
            ],
            'has runner clients, throws NonExecutableRemoteTestException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($testPath, $chromeNonExecutableTestException),
                ],
                'browser' => 'chrome',
                'path' => $testPath,
                'commandOutput' => $this->createCommandOutput([
                    'write' => [
                        $yamlGenerator->generate(
                            Exception::createFromThrowable($chromeNonExecutableTestException)->withoutTrace()
                        ),
                    ],
                ]),
                'logger' => $this->createLogger(
                    'Failed to execute test "/target/GeneratedChromeTest.php"',
                    [
                        'remote-path' => '/target/GeneratedChromeTest.php',
                    ]
                ),
            ],
        ];
    }

    private function createRunnerClient(string $expectedTarget, ?\Exception $throwable = null): RunnerClient
    {
        $client = \Mockery::mock(RunnerClient::class);

        if ($throwable instanceof \Throwable) {
            $client
                ->shouldReceive('request')
                ->with($expectedTarget)
                ->andThrow($throwable);
        } else {
            $client
                ->shouldReceive('request')
                ->with($expectedTarget);
        }

        return $client;
    }

    /**
     * @param string $debugExceptionMessage
     * @param array<mixed> $debugContext
     *
     * @return LoggerInterface
     */
    private function createLogger(string $debugExceptionMessage, array $debugContext): LoggerInterface
    {
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('debug')
            ->with($debugExceptionMessage, $debugContext);

        return $logger;
    }

    /**
     * @param array<string, string[]> $calls
     *
     * @return OutputInterface
     */
    private function createCommandOutput(array $calls): OutputInterface
    {
        $output = \Mockery::mock(OutputInterface::class);

        foreach ($calls as $methodName => $argumentCollection) {
            foreach ($argumentCollection as $argument) {
                $output
                    ->shouldReceive($methodName)
                    ->with($argument);
            }
        }

        return $output;
    }
}
