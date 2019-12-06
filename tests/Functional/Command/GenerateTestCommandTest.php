<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Functional\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use webignition\BasilCompilableSourceFactory\ClassDefinitionFactory;
use webignition\BasilCompilableSourceFactory\ClassNameFactory;
use webignition\BasilCompiler\Compiler;
use webignition\BasilRunner\Command\GenerateTestCommand;
use webignition\BasilRunner\Model\GenerateCommandSuccessOutput;
use webignition\BasilRunner\Model\ValidationResult\Command\GenerateCommandValidationResult;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\Validator\Command\GenerateCommandValidator;
use webignition\BasilRunner\Tests\Functional\AbstractFunctionalTest;
use webignition\BasilRunner\Tests\Services\ObjectReflector;

class GenerateTestCommandTest extends AbstractFunctionalTest
{
    /**
     * @var GenerateTestCommand
     */
    private $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = self::$container->get(GenerateTestCommand::class);
    }

    /**
     * @param array<string, string> $input
     * @param string $generatedCodeClassName
     * @param array<string, string> $expectedGeneratedCode
     *
     * @dataProvider generateDataProvider
     */
    public function testRunSuccess(array $input, string $generatedCodeClassName, array $expectedGeneratedCode): void
    {
        $this->mockClassNameFactory($generatedCodeClassName);
        $this->mockGenerateCommandValidator();

        $output = new BufferedOutput();

        $exitCode = $this->command->run(new ArrayInput($input), $output);
        $this->assertSame(0, $exitCode);

        $commandOutput = GenerateCommandSuccessOutput::fromJson($output->fetch());

        $outputData = $commandOutput->getOutput();
        $this->assertCount(1, $outputData);

        foreach ($outputData as $generatedTestOutput) {
            $this->assertSame($commandOutput->getSource(), $generatedTestOutput->getSource());
            $this->assertSame($generatedCodeClassName . '.php', $generatedTestOutput->getTarget());

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

    public function generateDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'default' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                ],
                'generatedCodeClassName' => 'ExampleComVerifyOpenLiteralTest',
                'expectedGeneratedCode' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php'),
                ],
            ],
        ];
    }

    /**
     * GenerateCommand calls Compiler::createClassName, ::compile()
     * Compiler::createClassName(), ::compile() call ClassDefinitionFactory::createClassDefinition()
     * ClassDefinitionFactory::createClassDefinition() calls ClassNameFactory::create()
     *  -> need to mock ClassNameFactory::create() to make it deterministic
     *
     * @param string $className
     */
    private function mockClassNameFactory(string $className): void
    {
        /* @var ObjectReflector $objectReflector */
        $objectReflector = self::$container->get(ObjectReflector::class);

        $classNameFactory = \Mockery::mock(ClassNameFactory::class);
        $classNameFactory
            ->shouldReceive('create')
            ->andReturn($className);

        $compiler = $objectReflector->getProperty($this->command, 'compiler');
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
            $this->command,
            GenerateTestCommand::class,
            'compiler',
            $compiler
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
            GenerateTestCommand::class,
            'generateCommandValidator',
            $generateCommandValidator
        );
    }
}
