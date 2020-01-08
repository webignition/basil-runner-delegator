<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Command;

use Symfony\Component\Console\Tester\CommandTester;
use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BasilLoader\SourceLoader;
use webignition\BasilModels\Test\TestInterface;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Model\ErrorContext;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Model\GenerateCommandSuccessOutput;
use webignition\BasilRunner\Model\GeneratedTestOutput;
use webignition\BasilRunner\Model\ValidationResult\Command\GenerateCommandValidationResult;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\TestGenerator;
use webignition\BasilRunner\Services\Validator\Command\GenerateCommandValidator;

class GenerateCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array<string, string> $input
     * @param TestGenerator $testGenerator
     * @param GenerateCommandSuccessOutput $expectedCommandOutput
     *
     * @dataProvider runSuccessDataProvider
     */
    public function testRunSuccess(
        array $input,
        TestGenerator $testGenerator,
        GenerateCommandSuccessOutput $expectedCommandOutput
    ): void {
        $generateCommandValidator = \Mockery::mock(GenerateCommandValidator::class);
        $generateCommandValidator
            ->shouldReceive('validate')
            ->andReturn(new GenerateCommandValidationResult(true));

        $command = $this->createCommand($generateCommandValidator, $testGenerator);

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
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/build/target',
                    AbstractBaseTest::class,
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
        int $validationErrorCode,
        GenerateCommandErrorOutput $expectedCommandOutput
    ): void {
        $generateCommandValidator = \Mockery::mock(GenerateCommandValidator::class);
        $generateCommandValidator
            ->shouldReceive('validate')
            ->andReturn(new GenerateCommandValidationResult(
                false,
                $validationErrorCode
            ));

        $command = $this->createCommand($generateCommandValidator, \Mockery::mock(TestGenerator::class));

        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute($input);
        $this->assertSame($validationErrorCode, $exitCode);

        $output = $commandTester->getDisplay();

//        var_dump($output);
//        var_dump($expectedCommandOutput);
//        var_dump(GenerateCommandErrorOutput::fromJson($output));
//        exit();

        $commandOutput = GenerateCommandErrorOutput::fromJson($output);
        $this->assertEquals($expectedCommandOutput, $commandOutput);
    }

    public function runFailureDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'source empty' => [
                'input' => [
                    '--source' => '',
                    '--target' => 'tests/build/target',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    '',
                    $root . '/tests/build/target',
                    AbstractBaseTest::class,
                    'source empty; call with --source=SOURCE',
                    new ErrorContext(
                        ErrorContext::COMMAND_CONFIG,
                        ErrorContext::CODE_COMMAND_CONFIG,
                        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY
                    )
                ),
            ],
            'source does not exist' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/non-existent.yml',
                    '--target' => 'tests/build/target',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_DOES_NOT_EXIST,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    '',
                    $root . '/tests/build/target',
                    AbstractBaseTest::class,
                    'source invalid; does not exist',
                    new ErrorContext(
                        ErrorContext::COMMAND_CONFIG,
                        ErrorContext::CODE_COMMAND_CONFIG,
                        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_DOES_NOT_EXIST
                    )
                ),
            ],
            'source not readable' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/build/target',
                    AbstractBaseTest::class,
                    'source invalid; file is not readable',
                    new ErrorContext(
                        ErrorContext::COMMAND_CONFIG,
                        ErrorContext::CODE_COMMAND_CONFIG,
                        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE
                    )
                ),
            ],



            'target empty' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => '',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '',
                    AbstractBaseTest::class,
                    'target empty; call with --target=TARGET',
                    new ErrorContext(
                        ErrorContext::COMMAND_CONFIG,
                        ErrorContext::CODE_COMMAND_CONFIG,
                        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY
                    )
                ),
            ],
            'target does not exist' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target/non-existent',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_DOES_NOT_EXIST,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '',
                    AbstractBaseTest::class,
                    'target invalid; does not exist',
                    new ErrorContext(
                        ErrorContext::COMMAND_CONFIG,
                        ErrorContext::CODE_COMMAND_CONFIG,
                        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_DOES_NOT_EXIST
                    )
                ),
            ],
            'target not a directory, is a file' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_A_DIRECTORY,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    AbstractBaseTest::class,
                    'target invalid; is not a directory (is it a file?)',
                    new ErrorContext(
                        ErrorContext::COMMAND_CONFIG,
                        ErrorContext::CODE_COMMAND_CONFIG,
                        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_A_DIRECTORY
                    )
                ),
            ],
            'target not writable' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/build/target',
                    AbstractBaseTest::class,
                    'target invalid; directory is not writable',
                    new ErrorContext(
                        ErrorContext::COMMAND_CONFIG,
                        ErrorContext::CODE_COMMAND_CONFIG,
                        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE
                    )
                ),
            ],
            'base class does not exist' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                    '--base-class' => 'Foo',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/build/target',
                    'Foo',
                    'base class invalid: does not exist',
                    new ErrorContext(
                        ErrorContext::COMMAND_CONFIG,
                        ErrorContext::CODE_COMMAND_CONFIG,
                        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST
                    )
                ),
            ],
        ];
    }

    private function createCommand(
        GenerateCommandValidator $generateCommandValidator,
        TestGenerator $testGenerator
    ): GenerateCommand {
        return new GenerateCommand(
            SourceLoader::createLoader(),
            $testGenerator,
            new ProjectRootPathProvider(),
            $generateCommandValidator
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
}
