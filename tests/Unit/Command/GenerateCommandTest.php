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
            ->shouldReceive('validateSource')
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

    public function testRunFailure()
    {
        $input = [];
        $expectedExitCode = 1;
        $expectedCommandOutput = new GenerateCommandErrorOutput(
            '',
            '',
            'source empty; call with --source=SOURCE'
        );

        $generateCommandValidator = \Mockery::mock(GenerateCommandValidator::class);
        $generateCommandValidator
            ->shouldReceive('validateSource')
            ->andReturn(new GenerateCommandValidationResult(
                false,
                $expectedCommandOutput,
                $expectedExitCode
            ));

        $command = $this->createCommand(new PhpFileCreator(), $generateCommandValidator);

        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute($input);
        $this->assertSame($expectedExitCode, $exitCode);

        $output = $commandTester->getDisplay();
        $commandOutput = GenerateCommandErrorOutput::fromJson($output);
        $this->assertEquals($expectedCommandOutput, $commandOutput);
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
