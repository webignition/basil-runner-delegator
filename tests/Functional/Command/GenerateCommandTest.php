<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Functional\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use webignition\BasilRunner\Command\GenerateCommand;
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
     * @param array<string, string> $expectedGeneratedCode
     *
     * @dataProvider runSuccessDataProvider
     */
    public function testRunSuccess(array $input, array $expectedGeneratedCode)
    {
        $output = new BufferedOutput();

        $exitCode = $this->command->run(new ArrayInput($input), $output);
        self::assertSame(0, $exitCode);

        $commandOutputText = $output->fetch();
        $commandOutput = json_decode($commandOutputText, true);

        $commandOutputConfig = $commandOutput['config'] ?? [];
        $baseTarget = $commandOutputConfig['target'] ?? '';
        $commandOutputData = $commandOutput['output'] ?? [];

        self::assertSame(
            count($expectedGeneratedCode),
            count($commandOutputData)
        );

        $generatedTestsToRemove = [];
        foreach ($commandOutputData as $generatedTestData) {
            $source = $generatedTestData['source'] ?? '';
            $target = $generatedTestData['target'] ?? '';

            self::assertArrayHasKey($source, $expectedGeneratedCode);

            $generatedCodePath = $baseTarget . '/' . $target;

            self::assertFileExists($generatedCodePath);
            self::assertFileIsReadable($generatedCodePath);

            $generatedCode = file_get_contents($generatedCodePath);

            self::assertSame($expectedGeneratedCode[$source], $generatedCode);

            $generatedTestsToRemove[] = $generatedCodePath;
        }

        foreach ($generatedTestsToRemove as $path) {
            $this->assertFileExists($path);
            $this->assertFileIsWritable($path);

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
     *
     * @dataProvider runFailureEmptyTestDataProvider
     */
    public function testRunFailure(
        array $input,
        int $expectedExitCode
    ) {
        $output = new BufferedOutput();

        $exitCode = $this->command->run(new ArrayInput($input), $output);
        self::assertSame($expectedExitCode, $exitCode);
    }

    public function runFailureEmptyTestDataProvider(): array
    {
        return [
            'test file is empty' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/InvalidTest/empty.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedExitCode' => 202,
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
