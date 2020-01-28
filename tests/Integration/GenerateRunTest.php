<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Integration;

use PHPUnit\Framework\TestCase;
use webignition\BasePantherTestCase\AbstractBrowserTestCase;
use webignition\BasilRunner\Model\GenerateCommand\SuccessOutput;
use webignition\BasilRunner\Services\ResultPrinter\Formatter;
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
        $generateCommandOutput = SuccessOutput::fromJson((string) shell_exec($generateCommand));

        foreach ($generateCommandOutput->getTestPaths() as $testPath) {
            $this->mutateTestContent($testPath, function (string $testContent) {
                return str_replace(
                    'parent::setUpBeforeClass();',
                    "self::\$webServerDir = __DIR__ . '/../../Fixtures/html';\n        parent::setUpBeforeClass();",
                    $testContent
                );
            });
        }

        $runCommand = $this->createRunCommand($target);

        $runCommandOutput = (string) shell_exec($runCommand);
        $phpUnitOutput = new PhpUnitOutput($runCommandOutput);

        $this->assertSame($expectedOutputBody, $phpUnitOutput->getBody());

        foreach ($generateCommandOutput->getTestPaths() as $testPath) {
            unlink($testPath);
        }
    }

    public function generateAndRunDataProvider(): array
    {
        $formatter = Formatter::create();

        return [
            'default' => [
                'source' => './tests/Fixtures/basil-integration/Test',
                'target' => './tests/build/target',
                'expectedOutputBody' => $formatter->makeBold(
                    'tests/Fixtures/basil-integration/Test/index-page-test.yml'
                ) . "\n" .
                    '  ✓ verify page is open' . "\n" .
                    '  ✓ verify primary heading' . "\n" .
                    '  ✓ verify links are present' . "\n" .
                    '  ✓ navigate to form' . "\n",
            ],
        ];
    }

    private function createGenerateCommand(string $source, string $target): string
    {
        return './bin/basil-runner generate ' .
            '--source=' . $source . ' ' .
            '--target=' . $target . ' ' .
            '--base-class="' . AbstractBrowserTestCase::class . '"';
    }

    private function createRunCommand(string $path): string
    {
        return './bin/basil-runner ' .
            '--path=' . $path;
    }

    private function mutateTestContent(string $path, callable $mutator): void
    {
        $testContent = (string) file_get_contents($path);
        $testContent = $mutator($testContent);

        file_put_contents($path, $testContent);
    }
}
