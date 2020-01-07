<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Functional\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use webignition\BasilCompilableSourceFactory\ClassDefinitionFactory;
use webignition\BasilCompilableSourceFactory\ClassNameFactory;
use webignition\BasilCompiler\Compiler;
use webignition\BasilModels\Test\TestInterface;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Model\GenerateCommandSuccessOutput;
use webignition\BasilRunner\Model\ValidationResult\Command\GenerateCommandValidationResult;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\TestGenerator;
use webignition\BasilRunner\Services\Validator\Command\GenerateCommandValidator;
use webignition\BasilRunner\Tests\Functional\AbstractFunctionalTest;
use webignition\BasilRunner\Tests\Services\ObjectReflector;

class GenerateCommandTest extends AbstractFunctionalTest
{
    /**
     * @var GenerateCommand
     */
    private $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = self::$container->get(GenerateCommand::class);
    }

    /**
     * @param array<string, string> $input
     * @param array $generatedCodeClassName
     * @param array<string, string> $expectedGeneratedCode
     *
     * @dataProvider runSuccessDataProvider
     */
    public function testRunSuccess(array $input, array $generatedCodeClassNames, array $expectedGeneratedCode): void
    {
        $this->mockClassNameFactory($generatedCodeClassNames);
        $this->mockGenerateCommandValidator();

        $output = new BufferedOutput();

        $exitCode = $this->command->run(new ArrayInput($input), $output);
        $this->assertSame(0, $exitCode);

        $commandOutput = GenerateCommandSuccessOutput::fromJson($output->fetch());

        $outputData = $commandOutput->getOutput();
        $this->assertCount(1, $outputData);

        foreach ($outputData as $generatedTestOutput) {
            $generatedTestOutputSource = $generatedTestOutput->getSource();
            $this->assertSame($commandOutput->getSource(), $generatedTestOutputSource);

            $expectedGeneratedCodeClassName = $generatedCodeClassNames[$generatedTestOutputSource] ?? '';
            $this->assertSame($expectedGeneratedCodeClassName . '.php', $generatedTestOutput->getTarget());

            $expectedCodePath = $commandOutput->getTarget() . '/' . $generatedTestOutput->getTarget();

            $this->assertFileExists($expectedCodePath);
            $this->assertFileIsReadable($expectedCodePath);

            $this->assertEquals(
                $expectedGeneratedCode[$generatedTestOutput->getSource()],
                file_get_contents($expectedCodePath)
            );

            unlink($expectedCodePath);
        }
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
                'generatedCodeClassNames' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        'ExampleComVerifyOpenLiteralTest',
                ],
                'expectedGeneratedCode' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php'),
                ],
            ],
        ];
    }

    /**
     *
     * GenerateCommand calls TestGenerator::generate()
     *   TestGenerator calls Compiler::createClassName, ::compile()
     *     Compiler::createClassName(), ::compile() call ClassDefinitionFactory::createClassDefinition()
     *       ClassDefinitionFactory::createClassDefinition() calls ClassNameFactory::create()
     *       -> need to mock ClassNameFactory::create() to make it deterministic
     *
     * @param string $className
     */
    private function mockClassNameFactory(array $classNames): void
    {
        /* @var ObjectReflector $objectReflector */
        $objectReflector = self::$container->get(ObjectReflector::class);

        $classNameFactory = \Mockery::mock(ClassNameFactory::class);
        $classNameFactory
            ->shouldReceive('create')
            ->andReturnUsing(function (TestInterface $test) use ($classNames) {
                return $classNames[$test->getPath()] ?? null;
            });

        $testGenerator = $objectReflector->getProperty($this->command, 'testGenerator');
        $compiler = $objectReflector->getProperty($testGenerator, 'compiler');
        $classDefinitionFactory = $objectReflector->getProperty($compiler, 'classDefinitionFactory');

        $objectReflector->setProperty(
            $classDefinitionFactory,
            ClassDefinitionFactory::class,
            'classNameFactory',
            $classNameFactory
        );

        $objectReflector->setProperty(
            $compiler,
            Compiler::class,
            'classDefinitionFactory',
            $classDefinitionFactory
        );

        $objectReflector->setProperty(
            $testGenerator,
            TestGenerator::class,
            'compiler',
            $compiler
        );

        $objectReflector->setProperty(
            $this->command,
            GenerateCommand::class,
            'testGenerator',
            $testGenerator
        );
    }

    private function mockGenerateCommandValidator(): void
    {
        /* @var ObjectReflector $objectReflector */
        $objectReflector = self::$container->get(ObjectReflector::class);

        $generateCommandValidator = \Mockery::mock(GenerateCommandValidator::class);
        $generateCommandValidator
            ->shouldReceive('validate')
            ->andReturn(new GenerateCommandValidationResult(true));

        $objectReflector->setProperty(
            $this->command,
            GenerateCommand::class,
            'generateCommandValidator',
            $generateCommandValidator
        );
    }
}
