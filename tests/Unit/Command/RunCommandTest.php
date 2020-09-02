<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Unit\Command;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\BasilCompilerModels\Configuration;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilRunnerDelegator\Command\RunCommand;
use webignition\BasilRunnerDelegator\Exception\InvalidRemotePathException;
use webignition\BasilRunnerDelegator\Exception\MalformedSuiteManifestException;
use webignition\BasilRunnerDelegator\Exception\NonExecutableRemoteTestException;
use webignition\BasilRunnerDelegator\Services\RunnerClient;
use webignition\BasilRunnerDelegator\Services\SuiteManifestFactory;
use webignition\BasilRunnerDelegator\Services\TestFactory;
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
                    SuiteManifestFactory::createFactory(),
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
                    SuiteManifestFactory::createFactory(),
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
                    SuiteManifestFactory::createFactory(),
                    \Mockery::mock(LoggerInterface::class),
                    \Mockery::mock(YamlGenerator::class),
                    \Mockery::mock(TestFactory::class)
                ),
                'path' => 'read-fail',
                'expectedExitCode' => RunCommand::EXIT_CODE_MANIFEST_FILE_READ_FAILED,
            ],
            'non-parsable suite manifest' => [
                'initializer' => function () {
                    $this->mockCommandFunctions(
                        'non-parsable-manifest.yml',
                        true,
                        true,
                        'invalid suite manifest fixture'
                    );
                },
                'runCommand' => new RunCommand(
                    [],
                    $this->createSuiteManifestFactoryThrowingException(
                        MalformedSuiteManifestException::createMalformedYamlException('invalid suite manifest fixture')
                    ),
                    $this->createLogger(
                        'Content is not parsable yaml',
                        [
                            'path' => 'non-parsable-manifest.yml',
                            'content' => 'invalid suite manifest fixture',
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
     * @param SuiteManifest $suiteManifest
     * @param LoggerInterface|null $logger
     */
    public function testRunSuccess(
        array $runnerClients,
        SuiteManifest $suiteManifest,
        OutputInterface $commandOutput,
        ?LoggerInterface $logger = null
    ) {
        $suiteManifestFileContents = 'valid manifest content';

        $this->mockCommandFunctions('manifest.yml', true, true, $suiteManifestFileContents);

        $input = new ArrayInput([
            '--path' => 'manifest.yml',
        ]);

        $suiteManifestFactory = \Mockery::mock(SuiteManifestFactory::class);
        $suiteManifestFactory
            ->shouldReceive('createFromString')
            ->with($suiteManifestFileContents)
            ->andReturn($suiteManifest);

        $logger = $logger ?? \Mockery::mock(LoggerInterface::class);

        $command = new RunCommand(
            $runnerClients,
            $suiteManifestFactory,
            $logger,
            new YamlGenerator(),
            new TestFactory()
        );
        $exitCode = $command->run($input, $commandOutput);

        self::assertSame(0, $exitCode);
    }

    public function runSuccessDataProvider(): array
    {
        $suiteManifestConfiguration = new Configuration('/source', '/target', 'BaseClass');

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
        $firefoxInvalidRemotePathException = new InvalidRemotePathException($firefoxTestPath);

        $chromeNonExecutableTestException = new NonExecutableRemoteTestException($chromeTestPath);
        $firefoxNonExecutableTestException = new NonExecutableRemoteTestException($firefoxTestPath);

        $yamlGenerator = new YamlGenerator();
        $testFactory = new TestFactory();

        return [
            'no runner clients, empty manifest, nothing written to output' => [
                'runnerClients' => [],
                'suiteManifest' => new SuiteManifest($suiteManifestConfiguration, []),
                'commandOutput' => \Mockery::mock(OutputInterface::class),
            ],
            'has runner clients, empty manifest, nothing written to output' => [
                'runnerClients' => [
                    'chrome' => \Mockery::mock(RunnerClient::class),
                    'firefox' => \Mockery::mock(RunnerClient::class),
                ],
                'suiteManifest' => new SuiteManifest($suiteManifestConfiguration, []),
                'commandOutput' => \Mockery::mock(OutputInterface::class),
            ],
            'has runner client, single chrome test' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($chromeTestPath),
                ],
                'suiteManifest' => new SuiteManifest($suiteManifestConfiguration, [
                    $chromeTestManifest,
                ]),
                'commandOutput' => $this->createCommandOutput([
                    'write' => [
                        $yamlGenerator->generate($testFactory->fromTestManifest($chromeTestManifest)),
                    ],
                    'writeln' => [
                        ''
                    ],
                ]),
            ],
            'has runner clients, single chrome test, single firefox test' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($chromeTestPath),
                    'firefox' => $this->createRunnerClient($firefoxTestPath),
                ],
                'suiteManifest' => new SuiteManifest($suiteManifestConfiguration, [
                    $chromeTestManifest,
                    $firefoxTestManifest,
                ]),
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
            'has runner clients, single chrome test, single test for unknown browser' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($chromeTestPath),
                ],
                'suiteManifest' => new SuiteManifest($suiteManifestConfiguration, [
                    $chromeTestManifest,
                    $unknownBrowserTestManifest,
                ]),
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
                'suiteManifest' => new SuiteManifest($suiteManifestConfiguration, [
                    $chromeTestManifest,
                ]),
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
                'suiteManifest' => new SuiteManifest($suiteManifestConfiguration, [
                    $chromeTestManifest,
                ]),
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
            'has runner clients, single test, throws InvalidRemotePathException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($chromeTestPath, $chromeInvalidRemotePathException),
                ],
                'suiteManifest' => new SuiteManifest($suiteManifestConfiguration, [
                    $chromeTestManifest,
                ]),
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
            'has runner clients, two tests, second test throws InvalidRemotePathException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($chromeTestPath),
                    'firefox' => $this->createRunnerClient($firefoxTestPath, $firefoxInvalidRemotePathException),
                ],
                'suiteManifest' => new SuiteManifest($suiteManifestConfiguration, [
                    $chromeTestManifest,
                    $firefoxTestManifest,
                ]),
                'commandOutput' => $this->createCommandOutput([
                    'write' => [
                        $yamlGenerator->generate($testFactory->fromTestManifest($chromeTestManifest)),
                        $yamlGenerator->generate($testFactory->fromTestManifest($firefoxTestManifest)),
                        $yamlGenerator->generate(
                            Exception::createFromThrowable($firefoxInvalidRemotePathException)->withoutTrace()
                        ),
                    ],
                    'writeln' => [
                        '',
                        '',
                    ],
                ]),
                'logger' => $this->createLogger(
                    'Path "/target/GeneratedFireFoxTest.php" not present on runner',
                    [
                        'path' => 'manifest.yml',
                        'test-manifest' => $firefoxTestManifest->getData(),
                    ]
                ),
            ],
            'has runner clients, single test, throws NonExecutableRemoteTestException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($chromeTestPath, $chromeNonExecutableTestException),
                ],
                'suiteManifest' => new SuiteManifest($suiteManifestConfiguration, [
                    $chromeTestManifest,
                ]),
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
            'has runner clients, two tests, second test throws NonExecutableRemoteTestException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient($chromeTestPath),
                    'firefox' => $this->createRunnerClient($firefoxTestPath, $firefoxNonExecutableTestException),
                ],
                'suiteManifest' => new SuiteManifest($suiteManifestConfiguration, [
                    $chromeTestManifest,
                    $firefoxTestManifest,
                ]),
                'commandOutput' => $this->createCommandOutput([
                    'write' => [
                        $yamlGenerator->generate($testFactory->fromTestManifest($chromeTestManifest)),
                        $yamlGenerator->generate($testFactory->fromTestManifest($firefoxTestManifest)),
                        $yamlGenerator->generate(
                            Exception::createFromThrowable($firefoxNonExecutableTestException)->withoutTrace()
                        ),
                    ],
                    'writeln' => [
                        '',
                        '',
                    ],
                ]),
                'logger' => $this->createLogger(
                    'Failed to execute test "/target/GeneratedFireFoxTest.php"',
                    [
                        'path' => 'manifest.yml',
                        'test-manifest' => $firefoxTestManifest->getData(),
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

    private function createSuiteManifestFactoryThrowingException(\Exception $exception): SuiteManifestFactory
    {
        $factory = \Mockery::mock(SuiteManifestFactory::class);
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
