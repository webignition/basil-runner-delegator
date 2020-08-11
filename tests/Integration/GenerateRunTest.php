<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilPhpUnitResultPrinter\ResultPrinter;
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
        $suiteManifest = SuiteManifest::fromArray($generatedCommandOutput);

        $testManifest = $suiteManifest->getTestManifests()[0];

        $testPath = $testManifest->getTarget();
        self::assertFileExists($testPath);
        self::assertFileIsReadable($testPath);

        $runCommand = $this->createRunCommand($testPath);

        $runCommandOutput = (string) shell_exec($runCommand);
        $phpUnitOutput = new PhpUnitOutput($runCommandOutput);
        self::assertSame($expectedOutputBody, $phpUnitOutput->getBody());

        self::assertFileIsWritable($testPath);
        unlink($testPath);
    }

    public function generateAndRunDataProvider(): array
    {
        $root = getcwd();

        return [
            'passing: single test' => [
                'source' => $root . '/tests/Fixtures/basil-integration/Test/index-page-test.yml',
                'target' => $root . '/tests/build/target',
                'expectedOutputBody' => file_get_contents(__DIR__ . '/../Fixtures/Output/passing-index-page-test.yml'),
            ],
            'failing: single test' => [
                'source' => $root . '/tests/Fixtures/basil-integration/FailingTest/index-page-test.yml',
                'target' => $root . '/tests/build/target',
                'expectedOutputBody' => file_get_contents(__DIR__ . '/../Fixtures/Output/failing-index-page-test.yml'),
            ],
            'passing: page import with element de-referencing' => [
                'source' => $root . '/tests/Fixtures/basil-integration/Test/form-page-test.yml',
                'target' => $root . '/tests/build/target',
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
        return sprintf(
            './vendor/bin/phpunit --stop-on-error --stop-on-failure --printer="%s" %s',
            ResultPrinter::class,
            $path
        );
    }
}
