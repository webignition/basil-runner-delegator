<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Command;

use Symfony\Component\Console\Tester\CommandTester;
use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BasilLoader\SourceLoader;
use webignition\BasilModels\Test\TestInterface;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Model\GenerateCommandSuccessOutput;
use webignition\BasilRunner\Model\GeneratedTestOutput;
use webignition\BasilRunner\Services\GenerateCommandConfigurationFactory;
use webignition\BasilRunner\Services\GenerateCommandConfigurationValidator;
use webignition\BasilRunner\Services\GenerateCommandErrorOutputFactory;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\TestGenerator;

class GenerateCommandTest extends AbstractBaseTest
{
    /**
     * @dataProvider runSuccessDataProvider
     *
     * @param array<string, string> $input
     * @param TestGenerator $testGenerator
     * @param GenerateCommandSuccessOutput $expectedCommandOutput
     *
     */
    public function testRunSuccess(
        array $input,
        GenerateCommandConfigurationFactory $configurationFactory,
        TestGenerator $testGenerator,
        GenerateCommandSuccessOutput $expectedCommandOutput
    ): void {
        $configurationValidator = \Mockery::mock(GenerateCommandConfigurationValidator::class);
        $configurationValidator
            ->shouldReceive('isValid')
            ->andReturnTrue();

        $command = $this->createCommand($configurationFactory, $configurationValidator, $testGenerator);

        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute($input);
        $this->assertSame(0, $exitCode);

        $output = $commandTester->getDisplay();
        $commandOutput = GenerateCommandSuccessOutput::fromJson($output);
        $this->assertEquals($expectedCommandOutput, $commandOutput);
    }

    public function runSuccessDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'default' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                ],
                'configurationFactory' => $this->createConfigurationFactory(
                    [
                         'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                         'tests/build/target',
                        AbstractBaseTest::class
                    ],
                    new GenerateCommandConfiguration(
                        $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    )
                ),
                'testGenerator' => $this->createTestGenerator($this->createTestGeneratorAndReturnUsingCallable([
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' => [
                        'expectedFullyQualifiedBaseClass' => AbstractBaseTest::class,
                        'expectedTarget' => $root . '/tests/build/target',
                        'generatedTestOutput' => new GeneratedTestOutput(
                            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                            'ExampleComVerifyOpenLiteralTest.php'
                        ),
                    ],
                ])),
                'expectedCommandOutput' => new GenerateCommandSuccessOutput(
                    new GenerateCommandConfiguration(
                        $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    [
                        new GeneratedTestOutput(
                            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                            'ExampleComVerifyOpenLiteralTest.php'
                        )
                    ]
                ),
            ],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $generatedTestOutputExpectations
     *
     * @return callable
     */
    private function createTestGeneratorAndReturnUsingCallable(array $generatedTestOutputExpectations): callable
    {
        return function (
            TestInterface $test,
            string $fullyQualifiedBaseClass,
            string $target
        ) use ($generatedTestOutputExpectations) {
            $instanceData = $generatedTestOutputExpectations[$test->getPath()] ?? null;
            if (null === $instanceData) {
                return null;
            }

            $expectedFullyQualifiedBaseClass = $instanceData['expectedFullyQualifiedBaseClass'] ?? null;
            $this->assertSame($expectedFullyQualifiedBaseClass, $fullyQualifiedBaseClass);

            $expectedTarget = $instanceData['expectedTarget'] ?? null;
            $this->assertSame($expectedTarget, $target);

            return $instanceData['generatedTestOutput'];
        };
    }

    /**
     * @param array<string, string> $input
     * @param int $validationErrorCode
     * @param GenerateCommandErrorOutput $expectedCommandOutput
     *
     * @dataProvider runFailureDataProvider
     */
    public function testRunFailure(
        array $input,
        GenerateCommandConfigurationFactory $configurationFactory,
        GenerateCommandConfigurationValidator $configurationValidator,
        int $validationErrorCode,
        GenerateCommandErrorOutput $expectedCommandOutput
    ): void {
        $command = $this->createCommand(
            $configurationFactory,
            $configurationValidator,
            \Mockery::mock(TestGenerator::class)
        );

        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute($input);
        $this->assertSame($validationErrorCode, $exitCode);

        $output = $commandTester->getDisplay();

        $commandOutput = GenerateCommandErrorOutput::fromJson($output);
        $this->assertEquals($expectedCommandOutput, $commandOutput);
    }

    public function runFailureDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $emptySourceConfiguration = new GenerateCommandConfiguration(
            '',
            $root . '/tests/build/target',
            AbstractBaseTest::class
        );

        $emptyTargetConfiguration = new GenerateCommandConfiguration(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            '',
            AbstractBaseTest::class
        );

        $invalidConfiguration = new GenerateCommandConfiguration(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            $root . '/tests/build/target',
            'NonExistentBaseClass'
        );

        return [
            'source empty' => [
                'input' => [
                    '--source' => '',
                    '--target' => 'tests/build/target',
                ],
                'configurationFactory' => $this->createConfigurationFactory(
                    [
                        '',
                        'tests/build/target',
                        AbstractBaseTest::class,
                    ],
                    $emptySourceConfiguration
                ),
                'configurationValidator' => \Mockery::mock(GenerateCommandConfigurationValidator::class),
                'validationErrorCode' => GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $emptySourceConfiguration,
                    'source empty; call with --source=SOURCE',
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY
                ),
            ],
            'target empty' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => '',
                ],
                'configurationFactory' => $this->createConfigurationFactory(
                    [
                        'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                        '',
                        AbstractBaseTest::class,
                    ],
                    $emptyTargetConfiguration
                ),
                'configurationValidator' => \Mockery::mock(GenerateCommandConfigurationValidator::class),
                'validationErrorCode' => GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $emptyTargetConfiguration,
                    'target empty; call with --target=TARGET',
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY
                ),
            ],
            'invalid configuration: source does not exist' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                    '--base-class' => 'NonExistentBaseClass',
                ],
                'configurationFactory' => $this->createConfigurationFactory(
                    [
                        'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                        'tests/build/target',
                        'NonExistentBaseClass',
                    ],
                    $invalidConfiguration
                ),
                'configurationValidator' => $this->createGenerateCommandConfigurationValidator(
                    $invalidConfiguration,
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST
                ),
                'validationErrorCode' => GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $invalidConfiguration,
                    'base class invalid: does not exist',
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST
                ),
            ],
        ];
    }

    private function createCommand(
        GenerateCommandConfigurationFactory $configurationFactory,
        GenerateCommandConfigurationValidator $configurationValidator,
        TestGenerator $testGenerator
    ): GenerateCommand {
        return new GenerateCommand(
            SourceLoader::createLoader(),
            $testGenerator,
            new ProjectRootPathProvider(),
            $configurationFactory,
            $configurationValidator,
            new GenerateCommandErrorOutputFactory($configurationValidator)
        );
    }

    private function createTestGenerator(callable $andReturnUsingCallable): TestGenerator
    {
        $testGenerator = \Mockery::mock(TestGenerator::class);

        $testGenerator
            ->shouldReceive('generate')
            ->andReturnUsing($andReturnUsingCallable);

        return $testGenerator;
    }

    /**
     * @param array<mixed> $args
     * @param GenerateCommandConfiguration $configuration
     *
     * @return GenerateCommandConfigurationFactory
     */
    private function createConfigurationFactory(
        array $args,
        GenerateCommandConfiguration $configuration
    ): GenerateCommandConfigurationFactory {
        $factory = \Mockery::mock(GenerateCommandConfigurationFactory::class);
        $factory
            ->shouldReceive('create')
            ->withArgs($args)
            ->andReturn($configuration);

        return $factory;
    }

    private function createGenerateCommandConfigurationValidator(
        GenerateCommandConfiguration $expectedConfiguration,
        int $errorCode
    ): GenerateCommandConfigurationValidator {
        $validator = \Mockery::mock(GenerateCommandConfigurationValidator::class);

        $validator
            ->shouldReceive('isValid')
            ->withArgs(function (GenerateCommandConfiguration $configuration) use ($expectedConfiguration) {
                $this->assertEquals($expectedConfiguration, $configuration);

                return true;
            })
            ->andReturnFalse();

        $validator
            ->shouldReceive('deriveInvalidConfigurationErrorCode')
            ->withArgs(function (GenerateCommandConfiguration $configuration) use ($expectedConfiguration) {
                $this->assertEquals($expectedConfiguration, $configuration);

                return true;
            })
            ->andReturn($errorCode);

        return $validator;
    }
}
