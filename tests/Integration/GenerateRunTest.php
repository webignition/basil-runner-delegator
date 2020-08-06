<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use webignition\BasilRunner\Tests\Model\PhpUnitOutput;

class GenerateRunTest extends TestCase
{
    /**
     * @dataProvider generateAndRunDataProvider
     *
     * @param string $source
     */
    public function testGenerateAndRun(string $source, string $target, string $expectedOutputBody)
    {
        $generateCommand = $this->createGenerateCommand($source, $target);
        $generateCommandOutputText = (string) shell_exec($generateCommand);

        $generatedCommandOutput = Yaml::parse($generateCommandOutputText);
        $generateCommandOutputConfig = $generatedCommandOutput['config'] ?? [];
        $baseTarget = $generateCommandOutputConfig['target'] ?? '';
        $generateCommandOutputData = $generatedCommandOutput['output'] ?? [];

        $generatedTestsToRemove = [];
        foreach ($generateCommandOutputData as $generatedTestData) {
            $generatedTestTarget = $generatedTestData['target'] ?? '';

            $generatedCodePath = $baseTarget . '/' . $generatedTestTarget;

            self::assertFileExists($generatedCodePath);
            self::assertFileIsReadable($generatedCodePath);

            $generatedTestsToRemove[] = $generatedCodePath;
        }

        $runCommand = $this->createRunCommand($target);

        $runCommandOutput = (string) shell_exec($runCommand);
        $phpUnitOutput = new PhpUnitOutput($runCommandOutput);

        self::assertSame($expectedOutputBody, $phpUnitOutput->getBody());

        foreach ($generatedTestsToRemove as $path) {
            self::assertFileExists($path);
            self::assertFileIsWritable($path);

            unlink($path);
        }

        self::assertTrue(true);
    }

    public function generateAndRunDataProvider(): array
    {
        return [
            'passing: single test' => [
                'source' => './tests/Fixtures/basil-integration/Test/index-page-test.yml',
                'target' => './tests/build/target',
                'expectedOutputBody' => file_get_contents(__DIR__ . '/../Fixtures/Output/passing-index-page-test.yml'),
            ],
            'failing: single test' => [
                'source' => './tests/Fixtures/basil-integration/FailingTest/index-page-test.yml',
                'target' => './tests/build/target',
                'expectedOutputBody' => file_get_contents(__DIR__ . '/../Fixtures/Output/failing-index-page-test.yml'),
            ],
            'passing: page import with element de-referencing' => [
                'source' => './tests/Fixtures/basil-integration/Test/form-page-test.yml',
                'target' => './tests/build/target',
                'expectedOutputBody' => file_get_contents(__DIR__ . '/../Fixtures/Output/passing-form-page-test.yml'),
            ],
        ];
    }

    private function createGenerateCommand(string $source, string $target): string
    {
        return
            './compiler.phar ' .
            '--source=' . $source . ' ' .
            '--target=' . $target . ' ' .
            '--base-class="' . AbstractGeneratedTestCase::class . '"';
    }

    private function createRunCommand(string $path): string
    {
        return './bin/basil-runner run --path=' . $path;
    }
}
