<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Functional\Services;

use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BasilCompilableSourceFactory\ClassDefinitionFactory;
use webignition\BasilCompilableSourceFactory\ClassNameFactory;
use webignition\BasilCompiler\Compiler;
use webignition\BasilModels\Test\TestInterface;
use webignition\BasilParser\Test\TestParser;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\TestGenerator;
use webignition\BasilRunner\Tests\Functional\AbstractFunctionalTest;
use webignition\ObjectReflector\ObjectReflector;

class TestGeneratorTest extends AbstractFunctionalTest
{
    private TestGenerator $testGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testGenerator = self::$container->get(TestGenerator::class);
    }

    /**
     * @dataProvider generateDataProvider
     */
    public function testGenerate(
        TestInterface $test,
        string $fullyQualifiedBaseClass,
        string $outputDirectory,
        string $generatedClassName,
        string $expectedGeneratedCode
    ) {
        $this->mockClassNameFactory($generatedClassName);

        $generatedTestOutput = $this->testGenerator->generate($test, $fullyQualifiedBaseClass, $outputDirectory);
        $expectedCodePath = $outputDirectory . '/' . $generatedTestOutput->getTarget();

        $this->assertFileExists($expectedCodePath);
        $this->assertFileIsReadable($expectedCodePath);

        $this->assertEquals($expectedGeneratedCode, file_get_contents($expectedCodePath));

        if (file_exists($expectedCodePath)) {
            unlink($expectedCodePath);
        }
    }

    public function generateDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();
        $testParser = TestParser::create();

        return [
            'default' => [
                'test' => $testParser->parse(
                    [
                        'config' => [
                            'browser' => 'chrome',
                            'url' => 'https://example.com/',
                        ],
                        'verify page is open' => [
                            'assertions' => [
                                '$page.url is "https://example.com/"',
                            ],
                        ],
                    ]
                )->withPath($root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml'),
                'fullyQualifiedBaseClass' => AbstractBaseTest::class,
                'outputDirectory' => $root . '/tests/build/target',
                'generatedClassName' => 'ExampleComVerifyOpenLiteralTest',
                'expectedGeneratedCode' => str_replace(
                    '{{ test_path }}',
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    (string) file_get_contents(
                        $root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php'
                    )
                ),
            ],
        ];
    }

    /**
     * TestGenerator calls Compiler::createClassName, ::compile()
     *   Compiler::createClassName(), ::compile() call ClassDefinitionFactory::createClassDefinition()
     *     ClassDefinitionFactory::createClassDefinition() calls ClassNameFactory::create()
     *     -> need to mock ClassNameFactory::create() to make it deterministic
     *
     * @param string $className
     */
    private function mockClassNameFactory(string $className): void
    {
        $classNameFactory = \Mockery::mock(ClassNameFactory::class);
        $classNameFactory
            ->shouldReceive('create')
            ->andReturn($className);

        $compiler = ObjectReflector::getProperty($this->testGenerator, 'compiler');
        $classDefinitionFactory = ObjectReflector::getProperty($compiler, 'classDefinitionFactory');

        ObjectReflector::setProperty(
            $classDefinitionFactory,
            ClassDefinitionFactory::class,
            'classNameFactory',
            $classNameFactory
        );

        ObjectReflector::setProperty(
            $compiler,
            Compiler::class,
            'classDefinitionFactory',
            $classDefinitionFactory
        );

        ObjectReflector::setProperty(
            $this->testGenerator,
            GenerateCommand::class,
            'compiler',
            $compiler
        );
    }
}
