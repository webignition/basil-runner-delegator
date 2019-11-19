<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Command;

use Symfony\Component\Console\Tester\CommandTester;
use webignition\BasilCompiler\Compiler;
use webignition\BasilLoader\TestLoader;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Model\GenerateCommandSuccessOutput;
use webignition\BasilRunner\Model\GeneratedTestOutput;
use webignition\BasilRunner\Model\ValidationResult\Command\GenerateCommandValidationResult;
use webignition\BasilRunner\Services\ExternalVariableIdentifiersFactory;
use webignition\BasilRunner\Services\PhpFileCreator;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\Validator\Command\GenerateCommandValidator;

class GenerateCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider runSuccessDataProvider
     */
    public function testRunSuccess(
        array $input,
        string $generatedClassName,
        GenerateCommandSuccessOutput $expectedCommandOutput
    ) {
        $root = (new ProjectRootPathProvider())->get();
        $expectedPhpFileCreatorOutputDirectory = $root . '/' . $input['--target'];

        $phpFileCreator = \Mockery::mock(PhpFileCreator::class);
        $phpFileCreator
            ->shouldReceive('setOutputDirectory')
            ->with($expectedPhpFileCreatorOutputDirectory);

        $phpFileCreator
            ->shouldReceive('create')
            ->andReturn($generatedClassName . '.php');

        $generateCommandValidator = \Mockery::mock(GenerateCommandValidator::class);
        $generateCommandValidator
            ->shouldReceive('validate')
            ->andReturn(new GenerateCommandValidationResult(true));

        $command = $this->createCommand($phpFileCreator, $generateCommandValidator);

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
                'generatedClassName' => 'ExampleComVerifyOpenLiteralTest',
                'expectedCommandOutput' => new GenerateCommandSuccessOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/build/target',
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
     * @dataProvider runFailureDataProvider
     */
    public function testRunFailure(
        array $input,
        int $validationErrorCode,
        GenerateCommandErrorOutput $expectedCommandOutput
    ) {
        $generateCommandValidator = \Mockery::mock(GenerateCommandValidator::class);
        $generateCommandValidator
            ->shouldReceive('validate')
            ->andReturn(new GenerateCommandValidationResult(
                false,
                $validationErrorCode
            ));

        $command = $this->createCommand(new PhpFileCreator(), $generateCommandValidator);

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
                    'base class invalid: does not exist'
                ),
            ],
        ];
    }

    private function createCommand(
        PhpFileCreator $phpFileCreator,
        GenerateCommandValidator $generateCommandValidator
    ): GenerateCommand {
        return new GenerateCommand(
            TestLoader::createLoader(),
            Compiler::create(ExternalVariableIdentifiersFactory::create()),
            $phpFileCreator,
            new ProjectRootPathProvider(),
            $generateCommandValidator
        );
    }
}
