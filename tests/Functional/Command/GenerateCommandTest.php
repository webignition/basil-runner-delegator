<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Functional\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BasilCompilableSourceFactory\ClassDefinitionFactory;
use webignition\BasilCompilableSourceFactory\ClassNameFactory;
use webignition\BasilCompiler\Compiler;
use webignition\BasilModels\Test\TestInterface;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Model\GenerateCommand\Configuration;
use webignition\BasilRunner\Model\GenerateCommand\ErrorOutput;
use webignition\BasilRunner\Model\GenerateCommand\SuccessOutput;
use webignition\BasilRunner\Services\GenerateCommand\ConfigurationValidator;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\TestGenerator;
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
     * @param array<string, string> $generatedCodeClassNames
     * @param array<string> $expectedGeneratedTestOutputSources
     * @param array<string, string> $expectedGeneratedCode
     *
     * @dataProvider runSuccessDataProvider
     */
    public function testRunSuccess(
        array $input,
        array $generatedCodeClassNames,
        array $expectedGeneratedTestOutputSources,
        array $expectedGeneratedCode
    ) {
        $this->mockClassNameFactory($generatedCodeClassNames);
        $this->mockConfigurationValidator();

        $output = new BufferedOutput();

        $exitCode = $this->command->run(new ArrayInput($input), $output);
        $this->assertSame(0, $exitCode);

        $commandOutput = SuccessOutput::fromJson($output->fetch());

        $outputData = $commandOutput->getOutput();
        $this->assertCount(count($expectedGeneratedTestOutputSources), $outputData);

        $generatedTestOutputIndex = 0;
        $generatedTestsToRemove = [];
        foreach ($outputData as $generatedTestOutput) {
            $expectedGeneratedTestOutputSource = $expectedGeneratedTestOutputSources[$generatedTestOutputIndex] ?? null;

            $generatedTestOutputSource = $generatedTestOutput->getSource();
            $this->assertSame($expectedGeneratedTestOutputSource, $generatedTestOutputSource);

            $expectedGeneratedCodeClassName = $generatedCodeClassNames[$generatedTestOutputSource] ?? '';
            $this->assertSame($expectedGeneratedCodeClassName . '.php', $generatedTestOutput->getTarget());

            $commandOutputConfiguration = $commandOutput->getConfiguration();
            $commandOutputTarget = $commandOutputConfiguration->getTarget();

            $expectedCodePath = $commandOutputTarget . '/' . $generatedTestOutput->getTarget();

            $this->assertFileExists($expectedCodePath);
            $this->assertFileIsReadable($expectedCodePath);

            $this->assertEquals(
                $expectedGeneratedCode[$generatedTestOutput->getSource()],
                file_get_contents($expectedCodePath)
            );

            $generatedTestsToRemove[] = $expectedCodePath;
            $generatedTestOutputIndex++;
        }

        $generatedTestsToRemove = array_unique($generatedTestsToRemove);

        foreach ($generatedTestsToRemove as $path) {
            $this->assertFileExists($path);
            $this->assertFileIsReadable($path);

            unlink($path);
        }
    }

    public function runSuccessDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'single test' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                ],
                'generatedCodeClassNames' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        'ExampleComVerifyOpenLiteralTest',
                ],
                'expectedGeneratedTestOutputSources' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                ],
                'expectedGeneratedCode' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php'),
                ],
            ],
            'test suite' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/TestSuite/example.com-all.yml',
                    '--target' => 'tests/build/target',
                ],
                'generatedCodeClassNames' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        'ExampleComVerifyOpenLiteralTest',
                    $root . '/tests/Fixtures/basil/Test/example.com.import-step-verify-open-literal.yml' =>
                        'ExampleComImportVerifyOpenLiteralTest',
                    $root . '/tests/Fixtures/basil/Test/example.com.follow-more-information.yml' =>
                        'ExampleComFollowMoreInformationTest',
                ],
                'expectedGeneratedTestOutputSources' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/Fixtures/basil/Test/example.com.import-step-verify-open-literal.yml',
                    $root . '/tests/Fixtures/basil/Test/example.com.follow-more-information.yml',
                ],
                'expectedGeneratedCode' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php'),
                    $root . '/tests/Fixtures/basil/Test/example.com.import-step-verify-open-literal.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComImportVerifyOpenLiteralTest.php'),
                    $root . '/tests/Fixtures/basil/Test/example.com.follow-more-information.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComFollowMoreInformationTest.php'),
                ],
            ],
            'collection of tests by directory' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test',
                    '--target' => 'tests/build/target',
                ],
                'generatedCodeClassNames' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.follow-more-information.yml' =>
                        'ExampleComFollowMoreInformationTest',
                    $root . '/tests/Fixtures/basil/Test/example.com.import-step-verify-open-literal.yml' =>
                        'ExampleComImportVerifyOpenLiteralTest',
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        'ExampleComVerifyOpenLiteralTest',
                ],
                'expectedGeneratedTestOutputSources' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.follow-more-information.yml',
                    $root . '/tests/Fixtures/basil/Test/example.com.import-step-verify-open-literal.yml',
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                ],
                'expectedGeneratedCode' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.follow-more-information.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComFollowMoreInformationTest.php'),
                    $root . '/tests/Fixtures/basil/Test/example.com.import-step-verify-open-literal.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComImportVerifyOpenLiteralTest.php'),
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php'),
                ],
            ],
            'collection of test suites by directory' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/TestSuite',
                    '--target' => 'tests/build/target',
                ],
                'generatedCodeClassNames' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        'ExampleComVerifyOpenLiteralTest',
                    $root . '/tests/Fixtures/basil/Test/example.com.import-step-verify-open-literal.yml' =>
                        'ExampleComImportVerifyOpenLiteralTest',
                    $root . '/tests/Fixtures/basil/Test/example.com.follow-more-information.yml' =>
                        'ExampleComFollowMoreInformationTest',
                ],
                'expectedGeneratedTestOutputSources' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/Fixtures/basil/Test/example.com.import-step-verify-open-literal.yml',
                    $root . '/tests/Fixtures/basil/Test/example.com.follow-more-information.yml',
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                ],
                'expectedGeneratedCode' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php'),
                    $root . '/tests/Fixtures/basil/Test/example.com.import-step-verify-open-literal.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComImportVerifyOpenLiteralTest.php'),
                    $root . '/tests/Fixtures/basil/Test/example.com.follow-more-information.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComFollowMoreInformationTest.php'),
                ],
            ],
        ];
    }

    /**
     * @param array<mixed> $input
     * @param int $expectedExitCode
     * @param GenerateCommandErrorOutput $expectedCommandOutput
     *
     * @dataProvider runFailureNonLoadableDataDataProvider
     * @dataProvider runFailureCircularStepImportDataProvider
     */
    public function testRunFailure(
        array $input,
        int $expectedExitCode,
        GenerateCommandErrorOutput $expectedCommandOutput
    ) {
        $output = new BufferedOutput();

        $exitCode = $this->command->run(new ArrayInput($input), $output);
        $this->assertSame($expectedExitCode, $exitCode);

        $commandOutput = GenerateCommandErrorOutput::fromJson($output->fetch());

        $this->assertEquals($expectedCommandOutput, $commandOutput);
    }

    public function runFailureNonLoadableDataDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'test contains invalid yaml' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/invalid.unparseable.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => GenerateCommandErrorOutput::CODE_LOADER_EXCEPTION,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    new GenerateCommandConfiguration(
                        $root . '/tests/Fixtures/basil/InvalidTest/invalid.unparseable.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unexpected characters near "https://example.com"" at line 3 (near "url: "https://example.com"").',
                    GenerateCommandErrorOutput::CODE_LOADER_EXCEPTION,
                    [
                        'path' => $root . '/tests/Fixtures/basil/InvalidTest/invalid.unparseable.yml',
                    ]
                ),
            ],
            'test suite imports test containing invalid yaml' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTestSuite/imports-unparseable.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => GenerateCommandErrorOutput::CODE_LOADER_EXCEPTION,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    new GenerateCommandConfiguration(
                        $root . '/tests/Fixtures/basil/InvalidTestSuite/imports-unparseable.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unexpected characters near "https://example.com"" at line 3 (near "url: "https://example.com"").',
                    GenerateCommandErrorOutput::CODE_LOADER_EXCEPTION,
                    [
                        'path' => $root . '/tests/Fixtures/basil/InvalidTest/invalid.unparseable.yml',
                    ]
                ),
            ],
            'test file contains non-array data' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/invalid.not-an-array.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => GenerateCommandErrorOutput::CODE_LOADER_EXCEPTION,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    new GenerateCommandConfiguration(
                        $root . '/tests/Fixtures/basil/InvalidTest/invalid.not-an-array.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Data is not an array',
                    GenerateCommandErrorOutput::CODE_LOADER_EXCEPTION,
                    [
                        'path' => $root . '/tests/Fixtures/basil/InvalidTest/invalid.not-an-array.yml',
                    ]
                ),
            ],
            'test suite imports test containing non-array data' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTestSuite/imports-not-an-array.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => GenerateCommandErrorOutput::CODE_LOADER_EXCEPTION,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    new GenerateCommandConfiguration(
                        $root . '/tests/Fixtures/basil/InvalidTestSuite/imports-not-an-array.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Data is not an array',
                    GenerateCommandErrorOutput::CODE_LOADER_EXCEPTION,
                    [
                        'path' => $root . '/tests/Fixtures/basil/InvalidTest/invalid.not-an-array.yml',
                    ]
                ),
            ],
        ];
    }

    public function runFailureCircularStepImportDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'test imports step which imports self' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/invalid.import-circular-reference-self.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => GenerateCommandErrorOutput::CODE_RESOLVER_EXCEPTION,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    new GenerateCommandConfiguration(
                        $root . '/tests/Fixtures/basil/InvalidTest/invalid.import-circular-reference-self.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Circular step import "circular_reference_self"',
                    GenerateCommandErrorOutput::CODE_RESOLVER_EXCEPTION,
                    [
                        'import_name' => 'circular_reference_self',
                    ]
                ),
            ],
            'test imports step which step imports self' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/invalid.import-circular-reference-indirect.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => GenerateCommandErrorOutput::CODE_RESOLVER_EXCEPTION,
                'expectedCommandOutput' => new GenerateCommandErrorOutput(
                    new GenerateCommandConfiguration(
                        $root . '/tests/Fixtures/basil/InvalidTest/invalid.import-circular-reference-indirect.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Circular step import "circular_reference_self"',
                    GenerateCommandErrorOutput::CODE_RESOLVER_EXCEPTION,
                    [
                        'import_name' => 'circular_reference_self',
                    ]
                ),
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
     * @param array<string, string> $classNames
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

    private function mockConfigurationValidator(): void
    {
        /* @var ObjectReflector $objectReflector */
        $objectReflector = self::$container->get(ObjectReflector::class);

        $generateCommandValidator = \Mockery::mock(ConfigurationValidator::class);
        $generateCommandValidator
            ->shouldReceive('isValid')
            ->andReturn(true);

        $objectReflector->setProperty(
            $this->command,
            GenerateCommand::class,
            'generateCommandValidator',
            $generateCommandValidator
        );
    }
}
