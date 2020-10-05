<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Unit\Command;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilRunnerDelegator\Command\RunCommand;
use webignition\BasilRunnerDelegator\Exception\InvalidRemotePathException;
use webignition\BasilRunnerDelegator\Exception\MalformedManifestException;
use webignition\BasilRunnerDelegator\Exception\NonExecutableRemoteTestException;
use webignition\BasilRunnerDelegator\Services\RunnerClient;
use webignition\BasilRunnerDelegator\Services\TestFactory;
use webignition\BasilRunnerDelegator\Services\TestManifestFactory;
use webignition\BasilRunnerDocuments\Exception;
use webignition\TcpCliProxyClient\Exception\ClientCreationException;
use webignition\TcpCliProxyClient\Exception\SocketErrorException;
use webignition\YamlDocumentGenerator\YamlGenerator;

class RunCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider runManifestFailureDataProvider
     */
    public function testRunManifestFailureFileError(
        callable $initializer,
        RunCommand $command,
        string $path,
        int $expectedExitCode
    ) {
        $initializer();

        $input = new ArrayInput([
            '--path' => $path,
        ]);

        $exitCode = $command->run($input, \Mockery::mock(OutputInterface::class));

        self::assertSame($expectedExitCode, $exitCode);
    }

    public function runManifestFailureDataProvider(): array
    {
        return [
            'not a file' => [
                'initializer' => function () {
                    $this->mockCommandFunctions('not-a-file', false);
                },
                'runCommand' => new RunCommand(
                    [],
                    TestManifestFactory::createFactory(),
                    \Mockery::mock(LoggerInterface::class),
                    \Mockery::mock(YamlGenerator::class),
                    \Mockery::mock(TestFactory::class)
                ),
                'path' => 'not-a-file',
                'expectedExitCode' => RunCommand::EXIT_CODE_PATH_NOT_A_FILE,
            ],
            'not readable' => [
                'initializer' => function () {
                    $this->mockCommandFunctions('not-readable', true, false);
                },
                'runCommand' => new RunCommand(
                    [],
                    TestManifestFactory::createFactory(),
                    \Mockery::mock(LoggerInterface::class),
                    \Mockery::mock(YamlGenerator::class),
                    \Mockery::mock(TestFactory::class)
                ),
                'path' => 'not-readable',
                'expectedExitCode' => RunCommand::EXIT_CODE_PATH_NOT_READABLE,
            ],
            'file read fail' => [
                'initializer' => function () {
                    $this->mockCommandFunctions('read-fail', true, true, false);
                },
                'runCommand' => new RunCommand(
                    [],
                    TestManifestFactory::createFactory(),
                    \Mockery::mock(LoggerInterface::class),
                    \Mockery::mock(YamlGenerator::class),
                    \Mockery::mock(TestFactory::class)
                ),
                'path' => 'read-fail',
                'expectedExitCode' => RunCommand::EXIT_CODE_MANIFEST_FILE_READ_FAILED,
            ],
            'non-parsable test manifest' => [
                'initializer' => function () {
                    $this->mockCommandFunctions(
                        'non-parsable-manifest.yml',
                        true,
                        true,
                        'invalid test manifest fixture'
                    );
                },
                'runCommand' => new RunCommand(
                    [],
                    $this->createTestManifestFactoryThrowingException(
                        MalformedManifestException::createMalformedYamlException('invalid test manifest fixture')
                    ),
                    $this->createLogger(
                        'Content is not parsable yaml',
                        [
                            'path' => 'non-parsable-manifest.yml',
                            'content' => 'invalid test manifest fixture',
                        ]
                    ),
                    \Mockery::mock(YamlGenerator::class),
                    \Mockery::mock(TestFactory::class)
                ),
                'path' => 'non-parsable-manifest.yml',
                'expectedExitCode' => RunCommand::EXIT_CODE_MANIFEST_DATA_PARSE_FAILED,
            ],
        ];
    }

    /**
     * @dataProvider runSuccessDataProvider
     *
     * @param RunnerClient[] $runnerClients
     * @param TestManifest $testManifest
     * @param LoggerInterface|null $logger
     */
    public function testRunSuccess(
        array $runnerClients,
        TestManifest $testManifest,
        OutputInterface $commandOutput,
        ?LoggerInterface $logger = null
    ) {
        $testManifestFileContents = 'valid manifest content';

        $this->mockCommandFunctions('manifest.yml', true, true, $testManifestFileContents);

        $input = new ArrayInput([
            '--path' => 'manifest.yml',
        ]);

        $testManifestFactory = \Mockery::mock(TestManifestFactory::class);
        $testManifestFactory
            ->shouldReceive('createFromString')
            ->with($testManifestFileContents)
            ->andReturn($testManifest);

        $logger = $logger ?? \Mockery::mock(LoggerInterface::class);

        $command = new RunCommand(
            $runnerClients,
            $testManifestFactory,
            $logger,
            new YamlGenerator(),
            new TestFactory()
        );
        $exitCode = $command->run($input, $commandOutput);

        self::assertSame(0, $exitCode);
    }

    public function runSuccessDataProvider(): array
    {
        $chromeTestPath = '/target/GeneratedChromeTest.php';
        $firefoxTestPath = '/target/GeneratedFireFoxTest.php';

        $chromeTestManifest = TestManifest::fromArray([
            'config' => [
                'browser' => 'chrome',
                'url' => 'http://example.com/chrome',
            ],
            'source' => '/basil/Test/test.yml',
            'target' => $chromeTestPath,
        ]);

        $firefoxTestManifest = TestManifest::fromArray([
            'config' => [
                'browser' => 'firefox',
                'url' => 'http://example.com',
            ],
            'source' => '/basil/Test/test.yml',
            'target' => $firefoxTestPath,
        ]);

        $unknownBrowserTestManifest = TestManifest::fromArray([
            'config' => [
                'browser' => 'unknown',
                'url' => 'http://example.com',
            ],
            'source' => '/basil/Test/test.yml',
            'target' => $chromeTestPath,
        ]);

        $chromeInvalidRemotePathException = new InvalidRemotePathException($chromeTestPath);
        $chromeNonExecutableTestException = new NonExecutableRemoteTestException($chromeTestPath);

        $yamlGenerator = new YamlGenerator();
        $testFactory = new TestFactory();

        return [
            'has runner client, chrome test' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($chromeTestPath),
                ],
                'testManifest' => $chromeTestManifest,
                'commandOutput' => $this->createCommandOutput([
                    'write' => [
                        $yamlGenerator->generate($testFactory->fromTestManifest($chromeTestManifest)),
                    ],
                    'writeln' => [
                        ''
                    ],
                ]),
            ],
            'has runner clients, firefox test' => [
                'runnerClients' => [
                    'firefox' => $this->createRunnerClient($firefoxTestPath),
                ],
                'testManifest' => $firefoxTestManifest,
                'commandOutput' => $this->createCommandOutput([
                    'write' => [
                        $yamlGenerator->generate($testFactory->fromTestManifest($chromeTestManifest)),
                        $yamlGenerator->generate($testFactory->fromTestManifest($firefoxTestManifest)),
                    ],
                    'writeln' => [
                        '',
                        '',
                    ],
                ]),
            ],
            'has runner clients, test for unknown browser' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($chromeTestPath),
                ],
                'testManifest' => $unknownBrowserTestManifest,
                'commandOutput' => $this->createCommandOutput([
                    'write' => [
                        $yamlGenerator->generate($testFactory->fromTestManifest($chromeTestManifest)),
                        $yamlGenerator->generate($testFactory->fromTestManifest($unknownBrowserTestManifest)),
                    ],
                    'writeln' => [
                        '',
                        '',
                    ],
                ]),
                'logger' => $this->createLogger(
                    'Unknown browser \'unknown\'',
                    [
                        'path' => 'manifest.yml',
                        'browser' => 'unknown',
                        'manifest-data' => [
                            'config' => [
                                'browser' => 'unknown',
                                'url' => 'http://example.com',
                            ],
                            'source' => '/basil/Test/test.yml',
                            'target' => $chromeTestPath,
                        ],
                    ]
                ),
            ],
            'client request throws SocketErrorException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient(
                        $chromeTestPath,
                        new SocketErrorException(
                            new \ErrorException('socket error exception message')
                        )
                    ),
                ],
                'testManifest' => $chromeTestManifest,
                'commandOutput' => $this->createCommandOutput([
                    'write' => [
                        $yamlGenerator->generate($testFactory->fromTestManifest($chromeTestManifest)),
                    ],
                ]),
                'logger' => $this->createLogger('socket error exception message', [
                    'path' => 'manifest.yml',
                ]),
            ],
            'client request throws ClientCreationException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient(
                        $chromeTestPath,
                        new ClientCreationException('connection string', 'client creation exception message', 123)
                    ),
                ],
                'testManifest' => $chromeTestManifest,
                'commandOutput' => $this->createCommandOutput([
                    'write' => [
                        $yamlGenerator->generate($testFactory->fromTestManifest($chromeTestManifest)),
                    ],
                ]),
                'logger' => $this->createLogger('client creation exception message', [
                    'path' => 'manifest.yml',
                    'connection-string' => 'connection string',
                ]),
            ],
            'has runner clients, throws InvalidRemotePathException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($chromeTestPath, $chromeInvalidRemotePathException),
                ],
                'testManifest' => $chromeTestManifest,
                'commandOutput' => $this->createCommandOutput([
                    'write' => [
                        $yamlGenerator->generate($testFactory->fromTestManifest($chromeTestManifest)),
                        $yamlGenerator->generate(
                            Exception::createFromThrowable($chromeInvalidRemotePathException)->withoutTrace()
                        ),
                    ],
                    'writeln' => [
                        '',
                    ],
                ]),
                'logger' => $this->createLogger(
                    'Path "/target/GeneratedChromeTest.php" not present on runner',
                    [
                        'path' => 'manifest.yml',
                        'test-manifest' => $chromeTestManifest->getData(),
                    ]
                ),
            ],
            'has runner clients, throws NonExecutableRemoteTestException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($chromeTestPath, $chromeNonExecutableTestException),
                ],
                'testManifest' => $chromeTestManifest,
                'commandOutput' => $this->createCommandOutput([
                    'write' => [
                        $yamlGenerator->generate($testFactory->fromTestManifest($chromeTestManifest)),
                        $yamlGenerator->generate(
                            Exception::createFromThrowable($chromeNonExecutableTestException)->withoutTrace()
                        ),
                    ],
                    'writeln' => [
                        '',
                    ],
                ]),
                'logger' => $this->createLogger(
                    'Failed to execute test "/target/GeneratedChromeTest.php"',
                    [
                        'path' => 'manifest.yml',
                        'test-manifest' => $chromeTestManifest->getData(),
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

    private function createTestManifestFactoryThrowingException(\Exception $exception): TestManifestFactory
    {
        $factory = \Mockery::mock(TestManifestFactory::class);
        $factory
            ->shouldReceive('createFromString')
            ->andThrow($exception);

        return $factory;
    }

    /**
     * @param string $path
     * @param bool $isFileReturn
     * @param bool $isReadableReturn
     * @param string|bool|null $fileGetContentsReturn
     */
    private function mockCommandFunctions(
        string $path,
        bool $isFileReturn,
        bool $isReadableReturn = false,
        $fileGetContentsReturn = null
    ): void {
        $namespace = 'webignition\\BasilRunnerDelegator\\Command';

        PHPMockery::mock($namespace, 'is_file')
            ->with($path)
            ->andReturn($isFileReturn);

        PHPMockery::mock($namespace, 'is_readable')
            ->with($path)
            ->andReturn($isReadableReturn);

        PHPMockery::mock($namespace, 'file_get_contents')
            ->with($path)
            ->andReturn($fileGetContentsReturn);
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
