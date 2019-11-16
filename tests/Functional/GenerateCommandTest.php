<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Functional;

use Mockery\MockInterface;
use Symfony\Component\Console\Tester\CommandTester;
use webignition\BasilCodeGenerator\ClassGenerator;
use webignition\BasilCompilableSourceFactory\ClassDefinitionFactory;
use webignition\BasilCompilableSourceFactory\ClassNameFactory;
use webignition\BasilCompilableSourceFactory\StepMethodFactory;
use webignition\BasilCompiler\Compiler;
use webignition\BasilCompiler\VariableIdentifierGenerator;
use webignition\BasilLoader\TestLoader;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Model\GenerateCommandOutput;
use webignition\BasilRunner\Model\GeneratedTestOutput;
use webignition\BasilRunner\Services\ExternalVariableIdentifiersFactory;
use webignition\BasilRunner\Services\PhpFileCreator;
use webignition\BasilRunner\Services\ProjectRootPathProvider;

class GenerateCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider generateDataProvider
     */
    public function testGenerate(
        array $input,
        string $generatedClassName,
        GenerateCommandOutput $expectedCommandOutput,
        array $expectedGeneratedCode
    ) {
        /* @var ClassNameFactory|MockInterface $classNameFactory */
        $classNameFactory = \Mockery::mock(ClassNameFactory::class);
        $classNameFactory
            ->shouldReceive('create')
            ->andReturn($generatedClassName);

        $classDefinitionFactory = new ClassDefinitionFactory(
            $classNameFactory,
            StepMethodFactory::createFactory()
        );

        $identifiersFactory = new ExternalVariableIdentifiersFactory();

        $compiler = new Compiler(
            $classDefinitionFactory,
            ClassGenerator::create(),
            new VariableIdentifierGenerator(),
            $identifiersFactory->create()
        );

        $command = new GenerateCommand(
            TestLoader::createLoader(),
            $compiler,
            new PhpFileCreator(),
            new ProjectRootPathProvider()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute($input);

        $output = $commandTester->getDisplay();

        $commandOutput = GenerateCommandOutput::fromJson($output);

        $this->assertEquals($expectedCommandOutput, $commandOutput);

        $outputTarget = $commandOutput->getTarget();

        foreach ($commandOutput->getOutput() as $generatedTestOutput) {
            $generatedFilePath = $outputTarget . '/' . $generatedTestOutput->getTarget();

            $this->assertFileExists($generatedFilePath);
            $this->assertFileIsReadable($generatedFilePath);

            $this->assertEquals(
                $expectedGeneratedCode[$generatedTestOutput->getSource()],
                file_get_contents($generatedFilePath)
            );

            unlink($generatedFilePath);
        }
    }

    public function generateDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'default' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                ],
                'generatedClassName' => 'ExampleComVerifyOpenLiteralTest',
                'expectedCommandOutput' => new GenerateCommandOutput(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/build/target',
                    [
                        new GeneratedTestOutput(
                            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                            'ExampleComVerifyOpenLiteralTest.php'
                        )
                    ]
                ),
                'expectedGeneratedCode' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php'),
                ],
            ],
        ];
    }
}
