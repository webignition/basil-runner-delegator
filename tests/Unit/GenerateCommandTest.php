<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit;

use phpmock\mockery\PHPMockery;
use Symfony\Component\Console\Tester\CommandTester;
use webignition\BasilCompiler\Compiler;
use webignition\BasilLoader\TestLoader;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Model\GenerateCommandSuccessOutput;
use webignition\BasilRunner\Model\GeneratedTestOutput;
use webignition\BasilRunner\Services\ExternalVariableIdentifiersFactory;
use webignition\BasilRunner\Services\PhpFileCreator;
use webignition\BasilRunner\Services\ProjectRootPathProvider;

class GenerateCommandTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        PHPMockery::define('webignition\BasilRunner\Command', 'is_readable');
    }

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

        $command = new GenerateCommand(
            TestLoader::createLoader(),
            Compiler::create(ExternalVariableIdentifiersFactory::create()),
            $phpFileCreator,
            new ProjectRootPathProvider()
        );

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
        int $expectedExitCode,
        GenerateCommandErrorOutput $expectedCommandOutput
    ) {
        $command = new GenerateCommand(
            TestLoader::createLoader(),
            Compiler::create(ExternalVariableIdentifiersFactory::create()),
            new PhpFileCreator(),
            new ProjectRootPathProvider()
        );

        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute($input);
        $this->assertSame($expectedExitCode, $exitCode);

        $output = $commandTester->getDisplay();
        $commandOutput = GenerateCommandErrorOutput::fromJson($output);
        $this->assertEquals($expectedCommandOutput, $commandOutput);
    }

    public function runFailureDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'empty input' => [
                'input' => [],
                'expectedExitCode' => 1,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    '',
                    '',
                    'source empty; call with --source=SOURCE'
                ),
            ],
            'source empty, target valid' => [
                'input' => [
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => 1,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    '',
                    $root . '/tests/build/target',
                    'source empty; call with --source=SOURCE'
                ),
            ],
            'source does not exist, target valid' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/non-existent.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => 2,
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
                'expectedExitCode' => 3,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    $root . '/tests/Fixtures/basil/Test',
                    $root . '/tests/build/target',
                    'source invalid; is not a file (is it a directory?)'
                ),
            ],
        ];
    }

    public function testRunFailureSourceNotReadable()
    {
        $input = [
            '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            '--target' => 'tests/build/target',
        ];

        $expectedExitCode = 4;

        $root = (new ProjectRootPathProvider())->get();
        $expectedCommandOutput = new GenerateCommandErrorOutput(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            $root . '/tests/build/target',
            'source invalid; file is not readable'
        );

        $command = new GenerateCommand(
            TestLoader::createLoader(),
            Compiler::create(ExternalVariableIdentifiersFactory::create()),
            new PhpFileCreator(),
            new ProjectRootPathProvider()
        );

        $commandTester = new CommandTester($command);

        PHPMockery::mock('webignition\BasilRunner\Command', 'is_readable')->andReturn(false);

        $exitCode = $commandTester->execute($input);
        $this->assertSame($expectedExitCode, $exitCode);

        $output = $commandTester->getDisplay();
        $commandOutput = GenerateCommandErrorOutput::fromJson($output);
        $this->assertEquals($expectedCommandOutput, $commandOutput);

        \Mockery::close();
    }
}
