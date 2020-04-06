<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Functional\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BasilCompilableSourceFactory\ClassDefinitionFactory;
use webignition\BasilCompilableSourceFactory\ClassNameFactory;
use webignition\BasilCompilableSourceFactory\Exception\UnsupportedContentException;
use webignition\BasilCompilableSourceFactory\Exception\UnsupportedStatementException;
use webignition\BasilCompilableSourceFactory\Exception\UnsupportedStepException;
use webignition\BasilCompiler\Compiler;
use webignition\BasilCompiler\ExternalVariableIdentifiers;
use webignition\BasilModels\Step\Step;
use webignition\BasilModels\Test\TestInterface;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
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
                'expectedGeneratedCode' => $this->createExpectedGeneratedCodeSet([
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        $root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php'
                ]),
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
                'expectedGeneratedCode' => $this->createExpectedGeneratedCodeSet([
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        $root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php',
                    $root . '/tests/Fixtures/basil/Test/example.com.import-step-verify-open-literal.yml' =>
                        $root . '/tests/Fixtures/php/Test/ExampleComImportVerifyOpenLiteralTest.php',
                    $root . '/tests/Fixtures/basil/Test/example.com.follow-more-information.yml' =>
                        $root . '/tests/Fixtures/php/Test/ExampleComFollowMoreInformationTest.php'
                ]),
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
                'expectedGeneratedCode' => $this->createExpectedGeneratedCodeSet([
                    $root . '/tests/Fixtures/basil/Test/example.com.follow-more-information.yml' =>
                        $root . '/tests/Fixtures/php/Test/ExampleComFollowMoreInformationTest.php',
                    $root . '/tests/Fixtures/basil/Test/example.com.import-step-verify-open-literal.yml' =>
                        $root . '/tests/Fixtures/php/Test/ExampleComImportVerifyOpenLiteralTest.php',
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        $root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php',
                ]),
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
                'expectedGeneratedCode' => $this->createExpectedGeneratedCodeSet([
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        $root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php',
                    $root . '/tests/Fixtures/basil/Test/example.com.import-step-verify-open-literal.yml' =>
                        $root . '/tests/Fixtures/php/Test/ExampleComImportVerifyOpenLiteralTest.php',
                    $root . '/tests/Fixtures/basil/Test/example.com.follow-more-information.yml' =>
                        $root . '/tests/Fixtures/php/Test/ExampleComFollowMoreInformationTest.php',
                ]),
            ],
        ];
    }

    /**
     * @param array<mixed> $input
     * @param int $expectedExitCode
     * @param ErrorOutput $expectedCommandOutput
     *
     * @dataProvider runFailureNonLoadableDataDataProvider
     * @dataProvider runFailureCircularStepImportDataProvider
     * @dataProvider runFailureEmptyTestDataProvider
     * @dataProvider runInvalidPageDataProvider
     * @dataProvider runInvalidTestDataProvider
     * @dataProvider runNonRetrievableImportDataProvider
     * @dataProvider runParseExceptionDataProvider
     * @dataProvider runUnknownElementDataProvider
     * @dataProvider runUnknownItemDataProvider
     * @dataProvider runUnknownPageElementDataProvider
     * @dataProvider runUnknownTestDataProvider
     * @dataProvider runUnresolvedPlaceholderDataProvider
     */
    public function testRunFailure(
        array $input,
        int $expectedExitCode,
        ErrorOutput $expectedCommandOutput,
        ?callable $initializer = null
    ) {
        $this->command = self::$container->get(GenerateCommand::class);

        if (null !== $initializer) {
            $initializer($this);
        }

        $output = new BufferedOutput();

        $exitCode = $this->command->run(new ArrayInput($input), $output);
        $this->assertSame($expectedExitCode, $exitCode);

        $commandOutput = ErrorOutput::fromJson($output->fetch());

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
                'expectedExitCode' => ErrorOutput::CODE_LOADER_INVALID_YAML,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/invalid.unparseable.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unexpected characters near "https://example.com"" at line 3 (near "url: "https://example.com"").',
                    ErrorOutput::CODE_LOADER_INVALID_YAML,
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
                'expectedExitCode' => ErrorOutput::CODE_LOADER_INVALID_YAML,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTestSuite/imports-unparseable.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unexpected characters near "https://example.com"" at line 3 (near "url: "https://example.com"").',
                    ErrorOutput::CODE_LOADER_INVALID_YAML,
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
                'expectedExitCode' => ErrorOutput::CODE_LOADER_INVALID_YAML,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/invalid.not-an-array.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Data is not an array',
                    ErrorOutput::CODE_LOADER_INVALID_YAML,
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
                'expectedExitCode' => ErrorOutput::CODE_LOADER_INVALID_YAML,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTestSuite/imports-not-an-array.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Data is not an array',
                    ErrorOutput::CODE_LOADER_INVALID_YAML,
                    [
                        'path' => $root . '/tests/Fixtures/basil/InvalidTest/invalid.not-an-array.yml',
                    ]
                ),
            ],
            'test suite contains unparseable yaml' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTestSuite/unparseable-yaml.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_INVALID_YAML,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTestSuite/unparseable-yaml.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Malformed inline YAML string: ""../Test/lacking-closing-quote.yml" at line 2.',
                    ErrorOutput::CODE_LOADER_INVALID_YAML,
                    [
                        'path' => $root . '/tests/Fixtures/basil/InvalidTestSuite/unparseable-yaml.yml',
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
                'expectedExitCode' => ErrorOutput::CODE_LOADER_CIRCULAR_STEP_IMPORT,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/invalid.import-circular-reference-self.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Circular step import "circular_reference_self"',
                    ErrorOutput::CODE_LOADER_CIRCULAR_STEP_IMPORT,
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
                'expectedExitCode' => ErrorOutput::CODE_LOADER_CIRCULAR_STEP_IMPORT,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/invalid.import-circular-reference-indirect.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Circular step import "circular_reference_self"',
                    ErrorOutput::CODE_LOADER_CIRCULAR_STEP_IMPORT,
                    [
                        'import_name' => 'circular_reference_self',
                    ]
                ),
            ],
        ];
    }

    public function runFailureEmptyTestDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $emptyTestPath = 'tests/Fixtures/basil/InvalidTest/empty.yml';
        $emptyTestAbsolutePath = $root . '/' . $emptyTestPath;

        return [
            'test file is empty' => [
                'input' => [
                    '--source' => $emptyTestPath,
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_EMPTY_TEST,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $emptyTestAbsolutePath,
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Empty test at path "' . $emptyTestAbsolutePath . '"',
                    ErrorOutput::CODE_LOADER_EMPTY_TEST,
                    [
                        'path' => $emptyTestAbsolutePath,
                    ]
                ),
            ],
        ];
    }

    public function runInvalidPageDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $testPath = 'tests/Fixtures/basil/InvalidTest/import-empty-page.yml';
        $testAbsolutePath = $root . '/' . $testPath;

        $pagePath = 'tests/Fixtures/basil/InvalidPage/url-empty.yml';
        $pageAbsolutePath = $root . '/' . $pagePath;

        $testSuitePath = 'tests/Fixtures/basil/InvalidTestSuite/imports-invalid-page.yml';
        $testSuiteAbsolutePath = $root . '/' . $testSuitePath;

        return [
            'test imports invalid page; url empty' => [
                'input' => [
                    '--source' => $testPath,
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_INVALID_PAGE,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $testAbsolutePath,
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Invalid page "empty_url_page" at path "' . $pageAbsolutePath . '": page-url-empty',
                    ErrorOutput::CODE_LOADER_INVALID_PAGE,
                    [
                        'test_path' => $testAbsolutePath,
                        'import_name' => 'empty_url_page',
                        'page_path' => $pageAbsolutePath,
                        'validation_result' => [
                            'type' => 'page',
                            'reason' => 'page-url-empty',
                        ],
                    ]
                ),
            ],
            'test suite imports test which imports invalid page; url empty' => [
                'input' => [
                    '--source' => $testSuitePath,
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_INVALID_PAGE,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $testSuiteAbsolutePath,
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Invalid page "empty_url_page" at path "' . $pageAbsolutePath . '": page-url-empty',
                    ErrorOutput::CODE_LOADER_INVALID_PAGE,
                    [
                        'test_path' => $testAbsolutePath,
                        'import_name' => 'empty_url_page',
                        'page_path' => $pageAbsolutePath,
                        'validation_result' => [
                            'type' => 'page',
                            'reason' => 'page-url-empty',
                        ],
                    ]
                ),
            ],
        ];
    }

    public function runInvalidTestDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $testPath = 'tests/Fixtures/basil/InvalidTest/invalid-configuration.yml';
        $testAbsolutePath = $root . '/' . $testPath;

        $testSuitePath = 'tests/Fixtures/basil/InvalidTestSuite/imports-invalid-test.yml';
        $testSuiteAbsolutePath = $root . '/' . $testSuitePath;

        return [
            'test has invalid configuration' => [
                'input' => [
                    '--source' => $testPath,
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_INVALID_TEST,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $testAbsolutePath,
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Invalid test at path "' .
                    $testAbsolutePath .
                    '": test-configuration-invalid',
                    ErrorOutput::CODE_LOADER_INVALID_TEST,
                    [
                        'test_path' => $testAbsolutePath,
                        'validation_result' => [
                            'type' => 'test',
                            'reason' => 'test-configuration-invalid',
                            'previous' => [
                                'type' => 'test-configuration',
                                'reason' => 'test-configuration-browser-empty',
                            ],
                        ],
                    ]
                ),
            ],
            'test suite imports test with invalid configuration' => [
                'input' => [
                    '--source' => $testSuitePath,
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_INVALID_TEST,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $testSuiteAbsolutePath,
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Invalid test at path "' .
                    $testAbsolutePath .
                    '": test-configuration-invalid',
                    ErrorOutput::CODE_LOADER_INVALID_TEST,
                    [
                        'test_path' => $testAbsolutePath,
                        'validation_result' => [
                            'type' => 'test',
                            'reason' => 'test-configuration-invalid',
                            'previous' => [
                                'type' => 'test-configuration',
                                'reason' => 'test-configuration-browser-empty',
                            ],
                        ],
                    ]
                ),
            ],
        ];
    }

    public function runNonRetrievableImportDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $pagePath = 'tests/Fixtures/basil/InvalidPage/unparseable.yml';
        $pageAbsolutePath = $root . '/' . $pagePath;

        $testPath = 'tests/Fixtures/basil/InvalidTest/import-unparseable-page.yml';
        $testAbsolutePath = $root . '/' . $testPath;

        $testSuitePath = 'tests/Fixtures/basil/InvalidTestSuite/imports-unparseable-page.yml';
        $testSuiteAbsolutePath = $root . '/' . $testSuitePath;

        return [
            'test imports non-parsable page' => [
                'input' => [
                    '--source' => $testPath,
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_NON_RETRIEVABLE_IMPORT,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $testAbsolutePath,
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Cannot retrieve page "unparseable_page" from "' . $pageAbsolutePath . '"',
                    ErrorOutput::CODE_LOADER_NON_RETRIEVABLE_IMPORT,
                    [
                        'test_path' => $testAbsolutePath,
                        'type' => 'page',
                        'name' => 'unparseable_page',
                        'import_path' => $pageAbsolutePath,
                        'loader_error' => [
                            'message' => 'Malformed inline YAML string: ""http://example.com" at line 2.',
                            'path' => $pageAbsolutePath,
                        ],
                    ]
                ),
            ],
            'test suite imports test which imports non-parsable page' => [
                'input' => [
                    '--source' => $testSuiteAbsolutePath,
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_NON_RETRIEVABLE_IMPORT,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $testSuiteAbsolutePath,
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Cannot retrieve page "unparseable_page" from "' . $pageAbsolutePath . '"',
                    ErrorOutput::CODE_LOADER_NON_RETRIEVABLE_IMPORT,
                    [
                        'test_path' => $testAbsolutePath,
                        'type' => 'page',
                        'name' => 'unparseable_page',
                        'import_path' => $pageAbsolutePath,
                        'loader_error' => [
                            'message' => 'Malformed inline YAML string: ""http://example.com" at line 2.',
                            'path' => $pageAbsolutePath,
                        ],
                    ]
                ),
            ],
        ];
    }

    public function runParseExceptionDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'test declares step, step contains unparseable action' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/unparseable-action.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/unparseable-action.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unparseable test',
                    ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
                    [
                        'type' => 'test',
                        'test_path' => $root . '/tests/Fixtures/basil/InvalidTest/unparseable-action.yml',
                        'step_name' => 'contains unparseable action',
                        'statement_type' => 'action',
                        'statement' => 'click invalid-identifier',
                        'reason' => 'invalid-identifier',

                    ]
                ),
            ],
            'test declares step, step contains unparseable assertion' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/unparseable-assertion.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/unparseable-assertion.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unparseable test',
                    ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
                    [
                        'type' => 'test',
                        'test_path' => $root . '/tests/Fixtures/basil/InvalidTest/unparseable-assertion.yml',
                        'step_name' => 'contains unparseable assertion',
                        'statement_type' => 'assertion',
                        'statement' => '$page.url is',
                        'reason' => 'empty-value',

                    ]
                ),
            ],
            'test imports step, step contains unparseable action' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/import-unparseable-action.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/import-unparseable-action.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unparseable step',
                    ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
                    [
                        'type' => 'step',
                        'test_path' => $root . '/tests/Fixtures/basil/InvalidTest/import-unparseable-action.yml',
                        'step_path' => $root . '/tests/Fixtures/basil/Step/unparseable-action.yml',
                        'statement_type' => 'action',
                        'statement' => 'click invalid-identifier',
                        'reason' => 'invalid-identifier',

                    ]
                ),
            ],
            'test imports step, step contains unparseable assertion' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/import-unparseable-assertion.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/import-unparseable-assertion.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unparseable step',
                    ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
                    [
                        'type' => 'step',
                        'test_path' => $root . '/tests/Fixtures/basil/InvalidTest/import-unparseable-assertion.yml',
                        'step_path' => $root . '/tests/Fixtures/basil/Step/unparseable-assertion.yml',
                        'statement_type' => 'assertion',
                        'statement' => '$page.url is',
                        'reason' => 'empty-value',

                    ]
                ),
            ],
            'test suite imports test which declares step, step contains unparseable action' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTestSuite/imports-test-declaring-unparseable-action.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTestSuite/imports-test-declaring-unparseable-action.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unparseable test',
                    ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
                    [
                        'type' => 'test',
                        'test_path' => $root . '/tests/Fixtures/basil/InvalidTest/unparseable-action.yml',
                        'step_name' => 'contains unparseable action',
                        'statement_type' => 'action',
                        'statement' => 'click invalid-identifier',
                        'reason' => 'invalid-identifier',

                    ]
                ),
            ],
            'test suite imports test which imports step, step contains unparseable action' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTestSuite/imports-test-importing-unparseable-action.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTestSuite/imports-test-importing-unparseable-action.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unparseable step',
                    ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
                    [
                        'type' => 'step',
                        'test_path' => $root . '/tests/Fixtures/basil/InvalidTest/import-unparseable-action.yml',
                        'step_path' => $root . '/tests/Fixtures/basil/Step/unparseable-action.yml',
                        'statement_type' => 'action',
                        'statement' => 'click invalid-identifier',
                        'reason' => 'invalid-identifier',

                    ]
                ),
            ],
        ];
    }

    public function runUnknownElementDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'test declares step, step contains action with unknown element' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/action-contains-unknown-element.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNKNOWN_ELEMENT,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/action-contains-unknown-element.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unknown element "unknown_element_name"',
                    ErrorOutput::CODE_LOADER_UNKNOWN_ELEMENT,
                    [
                        'element_name' => 'unknown_element_name',
                        'test_path' => $root . '/tests/Fixtures/basil/InvalidTest/action-contains-unknown-element.yml',
                        'step_name' => 'action contains unknown element',
                        'statement' => 'click $elements.unknown_element_name',
                    ]
                ),
            ],
            'test imports step, step contains action with unknown element' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/import-action-containing-unknown-element.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNKNOWN_ELEMENT,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/import-action-containing-unknown-element.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unknown element "unknown_element_name"',
                    ErrorOutput::CODE_LOADER_UNKNOWN_ELEMENT,
                    [
                        'element_name' => 'unknown_element_name',
                        'test_path' => $root .
                            '/tests/Fixtures/basil/InvalidTest/import-action-containing-unknown-element.yml',
                        'step_name' => 'use action_contains_unknown_element',
                        'statement' => 'click $elements.unknown_element_name',
                    ]
                ),
            ],
            'test suite imports test declaring step, step contains action with unknown element' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTestSuite/' .
                        'imports-test-declaring-action-containing-unknown-element.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNKNOWN_ELEMENT,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTestSuite/' .
                        'imports-test-declaring-action-containing-unknown-element.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unknown element "unknown_element_name"',
                    ErrorOutput::CODE_LOADER_UNKNOWN_ELEMENT,
                    [
                        'element_name' => 'unknown_element_name',
                        'test_path' => $root . '/tests/Fixtures/basil/InvalidTest/action-contains-unknown-element.yml',
                        'step_name' => 'action contains unknown element',
                        'statement' => 'click $elements.unknown_element_name',
                    ]
                ),
            ],
            'test suite imports test importing step, step contains action with unknown element' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTestSuite/' .
                        'imports-test-importing-action-containing-unknown-element.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNKNOWN_ELEMENT,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTestSuite/' .
                        'imports-test-importing-action-containing-unknown-element.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unknown element "unknown_element_name"',
                    ErrorOutput::CODE_LOADER_UNKNOWN_ELEMENT,
                    [
                        'element_name' => 'unknown_element_name',
                        'test_path' => $root .
                            '/tests/Fixtures/basil/InvalidTest/import-action-containing-unknown-element.yml',
                        'step_name' => 'use action_contains_unknown_element',
                        'statement' => 'click $elements.unknown_element_name',
                    ]
                ),
            ],
        ];
    }

    public function runUnknownItemDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'test declares step, step uses unknown dataset' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/step-uses-unknown-dataset.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNKNOWN_ITEM,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/step-uses-unknown-dataset.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unknown dataset "unknown_data_provider_name"',
                    ErrorOutput::CODE_LOADER_UNKNOWN_ITEM,
                    [
                        'type' => 'dataset',
                        'name' => 'unknown_data_provider_name',
                        'test_path' => $root . '/tests/Fixtures/basil/InvalidTest/step-uses-unknown-dataset.yml',
                        'step_name' => 'step name',
                        'statement' => '',
                    ]
                ),
            ],
            'test declares step, step uses unknown page' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/step-uses-unknown-page.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNKNOWN_ITEM,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/step-uses-unknown-page.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unknown page "unknown_page_import"',
                    ErrorOutput::CODE_LOADER_UNKNOWN_ITEM,
                    [
                        'type' => 'page',
                        'name' => 'unknown_page_import',
                        'test_path' => $root . '/tests/Fixtures/basil/InvalidTest/step-uses-unknown-page.yml',
                        'step_name' => 'step name',
                        'statement' => '',
                    ]
                ),
            ],
            'test declares step, step uses step' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/step-uses-unknown-step.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNKNOWN_ITEM,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/step-uses-unknown-step.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unknown step "unknown_step"',
                    ErrorOutput::CODE_LOADER_UNKNOWN_ITEM,
                    [
                        'type' => 'step',
                        'name' => 'unknown_step',
                        'test_path' => $root . '/tests/Fixtures/basil/InvalidTest/step-uses-unknown-step.yml',
                        'step_name' => 'step name',
                        'statement' => '',
                    ]
                ),
            ],
            'test suite imports test declaring step, step uses unknown dataset' => [
                'input' => [
                    '--source' =>
                        'tests/Fixtures/basil/InvalidTestSuite/imports-test-declaring-step-using-unknown-dataset.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNKNOWN_ITEM,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root .
                        '/tests/Fixtures/basil/InvalidTestSuite/imports-test-declaring-step-using-unknown-dataset.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unknown dataset "unknown_data_provider_name"',
                    ErrorOutput::CODE_LOADER_UNKNOWN_ITEM,
                    [
                        'type' => 'dataset',
                        'name' => 'unknown_data_provider_name',
                        'test_path' => $root . '/tests/Fixtures/basil/InvalidTest/step-uses-unknown-dataset.yml',
                        'step_name' => 'step name',
                        'statement' => '',
                    ]
                ),
            ],
        ];
    }

    public function runUnknownPageElementDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'test declares step, step contains action using unknown page element' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/action-contains-unknown-page-element.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNKNOWN_PAGE_ELEMENT,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/action-contains-unknown-page-element.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unknown page element "unknown_element" in page "page_import_name"',
                    ErrorOutput::CODE_LOADER_UNKNOWN_PAGE_ELEMENT,
                    [
                        'import_name' => 'page_import_name',
                        'element_name' => 'unknown_element',
                        'test_path' =>
                            $root . '/tests/Fixtures/basil/InvalidTest/action-contains-unknown-page-element.yml',
                        'step_name' => 'action contains unknown page element',
                        'statement' => 'click $page_import_name.elements.unknown_element'
                    ]
                ),
            ],
            'test imports step, test passes step unknown page element' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/imports-test-passes-unknown-element.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNKNOWN_PAGE_ELEMENT,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTest/imports-test-passes-unknown-element.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unknown page element "unknown_element" in page "page_import_name"',
                    ErrorOutput::CODE_LOADER_UNKNOWN_PAGE_ELEMENT,
                    [
                        'import_name' => 'page_import_name',
                        'element_name' => 'unknown_element',
                        'test_path' =>
                            $root . '/tests/Fixtures/basil/InvalidTest/imports-test-passes-unknown-element.yml',
                        'step_name' => 'action contains unknown page element',
                        'statement' => ''
                    ]
                ),
            ],
            'test suite imports test declaring step, step contains action using unknown page element' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTestSuite/' .
                        'imports-test-declaring-action-containing-unknown-page-element.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNKNOWN_PAGE_ELEMENT,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTestSuite/' .
                        'imports-test-declaring-action-containing-unknown-page-element.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unknown page element "unknown_element" in page "page_import_name"',
                    ErrorOutput::CODE_LOADER_UNKNOWN_PAGE_ELEMENT,
                    [
                        'import_name' => 'page_import_name',
                        'element_name' => 'unknown_element',
                        'test_path' =>
                            $root . '/tests/Fixtures/basil/InvalidTest/action-contains-unknown-page-element.yml',
                        'step_name' => 'action contains unknown page element',
                        'statement' => 'click $page_import_name.elements.unknown_element'
                    ]
                ),
            ],
        ];
    }

    public function runUnknownTestDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'test suite imports test that does not exist' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTestSuite/imports-non-existent-test.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_LOADER_UNKNOWN_TEST,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/InvalidTestSuite/imports-non-existent-test.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unknown test "' . $root . '/tests/Fixtures/basil/Test/non-existent.yml"',
                    ErrorOutput::CODE_LOADER_UNKNOWN_TEST,
                    [
                        'import_name' => $root . '/tests/Fixtures/basil/Test/non-existent.yml',
                    ]
                ),
            ],
        ];
    }

    public function runUnresolvedPlaceholderDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'placeholder CLIENT is not defined' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => ErrorOutput::CODE_GENERATOR_UNRESOLVED_PLACEHOLDER,
                'expectedCommandOutput' => new ErrorOutput(
                    new Configuration(
                        $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                        $root . '/tests/build/target',
                        AbstractBaseTest::class
                    ),
                    'Unresolved placeholder "CLIENT" in content ' .
                    '"{{ CLIENT }}->request(\'GET\', \'https://example.com/\');"',
                    ErrorOutput::CODE_GENERATOR_UNRESOLVED_PLACEHOLDER,
                    [
                        'placeholder' => 'CLIENT',
                        'content' => '{{ CLIENT }}->request(\'GET\', \'https://example.com/\');',
                    ]
                ),
                'initializer' => function (GenerateCommandTest $generateCommandTest) {
                    $mockExternalVariableIdentifiers = \Mockery::mock(ExternalVariableIdentifiers::class);
                    $mockExternalVariableIdentifiers
                        ->shouldReceive('get')
                        ->andReturn([]);

                    $this->mockTestGeneratorCompilerExternalVariableIdentifiers(
                        $generateCommandTest->command,
                        $mockExternalVariableIdentifiers
                    );
                }
            ],
        ];
    }

    /**
     * @dataProvider runFailureUnsupportedStepDataProvider
     *
     * @param UnsupportedStepException $unsupportedStepException
     * @param array<mixed> $expectedErrorOutputContext
     */
    public function testRunFailureUnsupportedStepException(
        UnsupportedStepException $unsupportedStepException,
        array $expectedErrorOutputContext
    ) {
        $root = (new ProjectRootPathProvider())->get();

        $input = [
            '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            '--target' => 'tests/build/target',
        ];

        $testGenerator = \Mockery::mock(TestGenerator::class);
        $testGenerator
            ->shouldReceive('generate')
            ->andThrow($unsupportedStepException);

        $this->mockTestGenerator($this->command, $testGenerator);

        $output = new BufferedOutput();

        $exitCode = $this->command->run(new ArrayInput($input), $output);
        $this->assertSame(ErrorOutput::CODE_GENERATOR_UNSUPPORTED_STEP, $exitCode);

        $expectedCommandOutput = new ErrorOutput(
            new Configuration(
                $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                $root . '/tests/build/target',
                AbstractBaseTest::class
            ),
            'Unsupported step',
            ErrorOutput::CODE_GENERATOR_UNSUPPORTED_STEP,
            $expectedErrorOutputContext
        );

        $commandOutput = ErrorOutput::fromJson($output->fetch());

        $this->assertEquals($expectedCommandOutput, $commandOutput);
    }

    public function runFailureUnsupportedStepDataProvider(): array
    {
        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        return [
            'click action with attribute identifier' => [
                'unsupportedStepException' => new UnsupportedStepException(
                    new Step(
                        [
                            $actionParser->parse('click $".selector".attribute_name'),
                        ],
                        []
                    ),
                    new UnsupportedStatementException(
                        $actionParser->parse('click $".selector".attribute_name'),
                        new UnsupportedContentException(
                            UnsupportedContentException::TYPE_IDENTIFIER,
                            '$".selector".attribute_name'
                        )
                    )
                ),
                'expectedErrorOutputContext' => [
                    'statement_type' => 'action',
                    'statement' => 'click $".selector".attribute_name',
                    'content_type' => 'identifier',
                    'content' => '$".selector".attribute_name',
                ],
            ],
            'comparison assertion examined value identifier cannot be extracted' => [
                'unsupportedStepException' => new UnsupportedStepException(
                    new Step(
                        [],
                        [
                            $assertionParser->parse('$".selector" is "value"'),
                        ]
                    ),
                    new UnsupportedStatementException(
                        $assertionParser->parse('$".selector" is "value"'),
                        new UnsupportedContentException(
                            UnsupportedContentException::TYPE_IDENTIFIER,
                            '$".selector"'
                        )
                    )
                ),
                'expectedErrorOutputContext' => [
                    'statement_type' => 'assertion',
                    'statement' => '$".selector" is "value"',
                    'content_type' => 'identifier',
                    'content' => '$".selector"',
                ],
            ],
            'comparison assertion examined value is not supported' => [
                'unsupportedStepException' => new UnsupportedStepException(
                    new Step(
                        [],
                        [
                            $assertionParser->parse('$elements.element_name is "value"'),
                        ]
                    ),
                    new UnsupportedStatementException(
                        $assertionParser->parse('$elements.element_name is "value"'),
                        new UnsupportedContentException(
                            UnsupportedContentException::TYPE_VALUE,
                            '$elements.element_name'
                        )
                    )
                ),
                'expectedErrorOutputContext' => [
                    'statement_type' => 'assertion',
                    'statement' => '$elements.element_name is "value"',
                    'content_type' => 'value',
                    'content' => '$elements.element_name',
                ],
            ],
            'unsupported action type' => [
                'unsupportedStepException' => new UnsupportedStepException(
                    new Step(
                        [
                            $actionParser->parse('foo $".selector"'),
                        ],
                        []
                    ),
                    new UnsupportedStatementException(
                        $actionParser->parse('foo $".selector"')
                    )
                ),
                'expectedErrorOutputContext' => [
                    'statement_type' => 'action',
                    'statement' => 'foo $".selector"',
                ],
            ],
        ];
    }

    private function mockTestGenerator(GenerateCommand $command, TestGenerator $mockTestGenerator): void
    {
        /* @var ObjectReflector $objectReflector */
        $objectReflector = self::$container->get(ObjectReflector::class);

//        $testGenerator = $objectReflector->getProperty($command, 'testGenerator');
//        $compiler = $objectReflector->getProperty($testGenerator, 'compiler');
//
//        $objectReflector->setProperty(
//            $compiler,
//            Compiler::class,
//            'externalVariableIdentifiers',
//            $updatedExternalVariableIdentifiers
//        );
//
//        $objectReflector->setProperty(
//            $testGenerator,
//            TestGenerator::class,
//            'compiler',
//            $compiler
//        );

        $objectReflector->setProperty(
            $command,
            GenerateCommand::class,
            'testGenerator',
            $mockTestGenerator
        );
    }

    private function mockTestGeneratorCompilerExternalVariableIdentifiers(
        GenerateCommand $command,
        ExternalVariableIdentifiers $updatedExternalVariableIdentifiers
    ): void {
        /* @var ObjectReflector $objectReflector */
        $objectReflector = self::$container->get(ObjectReflector::class);

        $testGenerator = $objectReflector->getProperty($command, 'testGenerator');
        $compiler = $objectReflector->getProperty($testGenerator, 'compiler');

        $objectReflector->setProperty(
            $compiler,
            Compiler::class,
            'externalVariableIdentifiers',
            $updatedExternalVariableIdentifiers
        );

        $objectReflector->setProperty(
            $testGenerator,
            TestGenerator::class,
            'compiler',
            $compiler
        );

        $objectReflector->setProperty(
            $command,
            GenerateCommand::class,
            'testGenerator',
            $testGenerator
        );
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

    /**
     * @param array<string, string> $sourceToOutputMap
     *
     * @return array<string, string>
     */
    private function createExpectedGeneratedCodeSet(array $sourceToOutputMap): array
    {
        $data = [];

        foreach ($sourceToOutputMap as $testPath => $generatedCodePath) {
            $data[$testPath] = $this->createGeneratedCodeWithTestPath($testPath, $generatedCodePath);
        }

        return $data;
    }

    private function createGeneratedCodeWithTestPath(string $testPath, string $generatedCodePath): string
    {
        return str_replace(
            '{{ test_path }}',
            $testPath,
            (string) file_get_contents($generatedCodePath)
        );
    }
}
