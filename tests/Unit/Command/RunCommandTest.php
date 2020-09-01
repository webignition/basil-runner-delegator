<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Command;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\BasilCompilerModels\Configuration;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilRunner\Command\RunCommand;
use webignition\BasilRunner\Exception\MalformedSuiteManifestException;
use webignition\BasilRunner\Services\RunnerClient;
use webignition\BasilRunner\Services\SuiteManifestFactory;
use webignition\BasilRunner\Services\TestFactory;
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

        $chromeTestManifest = TestManifest::fromArray([
            'config' => [
                'browser' => 'chrome',
                'url' => 'http://example.com/chrome',
            ],
            'source' => '/basil/Test/test.yml',
            'target' => '/target/GeneratedChromeTest.php',
        ]);

        $firefoxTestManifest = TestManifest::fromArray([
            'config' => [
                'browser' => 'firefox',
                'url' => 'http://example.com',
            ],
            'source' => '/basil/Test/test.yml',
            'target' => '/target/GeneratedFireFoxTest.php',
        ]);

        $unknownBrowserTestManifest = TestManifest::fromArray([
            'config' => [
                'browser' => 'unknown',
                'url' => 'http://example.com',
            ],
            'source' => '/basil/Test/test.yml',
            'target' => '/target/GeneratedChromeTest.php',
        ]);

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
                    'chrome' => $this->createRunnerClient(
                        '/target/GeneratedChromeTest.php'
                    ),
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
                    'chrome' => $this->createRunnerClient(
                        '/target/GeneratedChromeTest.php'
                    ),
                    'firefox' => $this->createRunnerClient(
                        '/target/GeneratedFireFoxTest.php'
                    ),
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
                    'chrome' => $this->createRunnerClient(
                        '/target/GeneratedChromeTest.php'
                    ),
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
                            'target' => '/target/GeneratedChromeTest.php',
                        ],
                    ]
                ),
            ],
            'client request throws SocketErrorException' => [
                'runnerClients' => [
                    'chrome' => $this->createRunnerClient(
                        '/target/GeneratedChromeTest.php',
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
                        '/target/GeneratedChromeTest.php',
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
        $namespace = 'webignition\\BasilRunner\\Command';

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
