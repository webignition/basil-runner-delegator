<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Command;

use Symfony\Component\Console\Tester\CommandTester;
use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BasilLoader\SourceLoader;
use webignition\BasilModels\Test\TestInterface;
use webignition\BasilRunner\Command\GenerateCommand;
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
     * @param string $generatedClassName
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

    private function createTestGeneratorAndReturnUsingCallable(array $bar): callable
    {
        return function (
            TestInterface $test,
            string $fullyQualifiedBaseClass,
            string $target
        ) use ($bar) {
            $instanceData = $bar[$test->getPath()] ?? null;
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
                'validationErrorCode' => GenerateCommandErrorOutput::ERROR_CODE_SOURCE_EMPTY,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    '',
                    $root . '/tests/build/target',
                    AbstractBaseTest::class,
                    'source empty; call with --source=SOURCE'
                ),
            ],
            'source does not exist' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/non-existent.yml',
                    '--target' => 'tests/build/target',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_DOES_NOT_EXIST,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    '',
                    $root . '/tests/build/target',
                    AbstractBaseTest::class,
                    'source invalid; does not exist'
                ),
            ],
            'source not a file, is a directory' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/',
                    '--target' => 'tests/build/target',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_NOT_A_FILE,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test',
                    $root . '/tests/build/target',
                    AbstractBaseTest::class,
                    'source invalid; is not a file (is it a directory?)'
                ),
            ],
            'source not readable' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_NOT_READABLE,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/build/target',
                    AbstractBaseTest::class,
                    'source invalid; file is not readable'
                ),
            ],
            'target empty' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => '',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::ERROR_CODE_TARGET_EMPTY,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '',
                    AbstractBaseTest::class,
                    'target empty; call with --target=TARGET'
                ),
            ],
            'target does not exist' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target/non-existent',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_DOES_NOT_EXIST,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '',
                    AbstractBaseTest::class,
                    'target invalid; does not exist'
                ),
            ],
            'target not a directory, is a file' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_NOT_A_DIRECTORY,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    AbstractBaseTest::class,
                    'target invalid; is not a directory (is it a file?)'
                ),
            ],
            'target not writable' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_NOT_WRITABLE,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/build/target',
                    AbstractBaseTest::class,
                    'target invalid; directory is not writable'
                ),
            ],
            'base class does not exist' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                    '--base-class' => 'Foo',
                ],
                'validationErrorCode' => GenerateCommandErrorOutput::ERROR_CODE_BASE_CLASS_DOES_NOT_EXIST,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/build/target',
                    'Foo',
                    'base class invalid: does not exist'
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
