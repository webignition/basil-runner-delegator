<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Functional\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Model\GenerateCommand\Configuration;
use webignition\BasilRunner\Model\GenerateCommand\ErrorOutput;
use webignition\BasilRunner\Model\GenerateCommand\SuccessOutput;
use webignition\BasilRunner\Services\CommandFactory;
use webignition\BasilRunner\Services\ProjectRootPathProvider;

class GenerateCommandTest extends \PHPUnit\Framework\TestCase
{
    private GenerateCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = CommandFactory::createFactory()->createGenerateCommand();
    }

    /**
     * @param array<string, string> $input
     * @param array<string> $expectedGeneratedTestOutputSources
     * @param array<string, string> $expectedGeneratedCode
     *
     * @dataProvider runSuccessDataProvider
     */
    public function testRunSuccess(
        array $input,
        array $expectedGeneratedTestOutputSources,
        array $expectedGeneratedCode
    ) {
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
                'expectedGeneratedTestOutputSources' => [
                    'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                ],
                'expectedGeneratedCode' => $this->createExpectedGeneratedCodeSet([
                    'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        $root . '/tests/Fixtures/php/Test/Generated0233b88be49ad918bec797dcba9b01afTest.php'
                ]),
            ],
        ];
    }

    /**
     * @param array<mixed> $input
     * @param int $expectedExitCode
     * @param ErrorOutput $expectedCommandOutput
     *
     * @dataProvider runFailureEmptyTestDataProvider
     */
    public function testRunFailure(
        array $input,
        int $expectedExitCode,
        ErrorOutput $expectedCommandOutput,
        ?callable $initializer = null
    ) {
        if (null !== $initializer) {
            $initializer($this);
        }

        $output = new BufferedOutput();

        $exitCode = $this->command->run(new ArrayInput($input), $output);
        $this->assertSame($expectedExitCode, $exitCode);

        $commandOutput = ErrorOutput::fromJson($output->fetch());

        $this->assertEquals($expectedCommandOutput, $commandOutput);
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
