<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Integration;

use PHPUnit\Framework\TestCase;
use webignition\BasePantherTestCase\AbstractBrowserTestCase;
use webignition\BasilRunner\Model\GenerateCommand\SuccessOutput;
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
        return [
            'default' => [
                'source' => './tests/Fixtures/basil-integration/Test',
                'target' => './tests/build/target',
                'expectedOutputBody' =>
                    "\033[1m" . 'tests/Fixtures/basil-integration/Test/index-page-test.yml' . "\033[0m" . "\n" .
                    "\033[32m" . '  ✓ verify page is open' . "\033[0m" . "\n" .
                    '     ' . "\033[32m" . '✓' . "\033[0m" . ' $page.url is "http://127.0.0.1:9080/index.html"' . "\n" .
                    '     ' . "\033[32m" . '✓' . "\033[0m" .
                    ' $page.title is "Test fixture web server default document"' . "\n" .
                    "\033[32m" . '  ✓ verify primary heading' . "\033[0m" . "\n" .
                    '     ' . "\033[32m" . '✓' . "\033[0m" . ' $"h1" exists' . "\n" .
                    '     ' . "\033[32m" . '✓' . "\033[0m" .
                    ' $"h1" is "Test fixture web server default document"' . "\n" .
                    "\033[32m" . '  ✓ verify links are present' . "\033[0m" . "\n" .
                    '     ' . "\033[32m" . '✓' . "\033[0m" . ' $"a[id=link-to-assertions]" exists' . "\n" .
                    '     ' . "\033[32m" . '✓' . "\033[0m" . ' $"a[id=link-to-form]" exists' . "\n" .
                    "\033[32m" . '  ✓ navigate to form' . "\033[0m" . "\n" .
                    '     ' . "\033[32m" . '✓' . "\033[0m" . ' $"a[id=link-to-form]" exists' . "\n" .
                    '     ' . "\033[32m" . '✓' . "\033[0m" . ' click $"a[id=link-to-form]"' . "\n" .
                    '     ' . "\033[32m" . '✓' . "\033[0m" . ' $page.url is "http://127.0.0.1:9080/form.html"' . "\n" .
                    '     ' . "\033[32m" . '✓' . "\033[0m" . ' $page.title is "Form"' . "\n"
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
