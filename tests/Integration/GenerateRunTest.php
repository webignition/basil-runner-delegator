<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Integration;

use PHPUnit\Framework\TestCase;
use webignition\BasilRunner\Model\GenerateCommand\SuccessOutput;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
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
        $cof = new ConsoleOutputFactory();

        return [
            'passing test' => [
                'source' => './tests/Fixtures/basil-integration/Test',
                'target' => './tests/build/target',
                'expectedOutputBody' =>
                    $cof->createTestPath('tests/Fixtures/basil-integration/Test/index-page-test.yml') . "\n" .
                    '  ' . $cof->createSuccess('✓') . ' ' . $cof->createSuccess('verify page is open') . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.url is "http://127.0.0.1:9080/index.html"' . "\n" .
                    '    ' . $cof->createSuccess('✓') .
                    ' $page.title is "Test fixture web server default document"' . "\n" .
                    '  ' . $cof->createSuccess('✓') . ' ' . $cof->createSuccess('verify primary heading') . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $"h1" exists' . "\n" .
                    '      ' . $cof->createComment('> derived from:') .
                    ' $"h1" is "Test fixture web server default document"' . "\n" .
                    '    ' . $cof->createSuccess('✓') .
                    ' $"h1" is "Test fixture web server default document"' . "\n" .
                    '  ' . $cof->createSuccess('✓') . ' ' . $cof->createSuccess('verify links are present') .
                    "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $"a[id=link-to-assertions]" exists' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $"a[id=link-to-form]" exists' . "\n" .
                    '  ' . $cof->createSuccess('✓') . ' ' . $cof->createSuccess('navigate to form') . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $"a[id=link-to-form]" exists' . "\n" .
                    '      ' . $cof->createComment('> derived from:') . ' click $"a[id=link-to-form]"' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' click $"a[id=link-to-form]"' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.url is "http://127.0.0.1:9080/form.html"' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.title is "Form"' . "\n"
            ],
            'failing test' => [
                'source' => './tests/Fixtures/basil-integration/FailingTest',
                'target' => './tests/build/target',
                'expectedOutputBody' =>
                    $cof->createTestPath('tests/Fixtures/basil-integration/FailingTest/index-page-test.yml') . "\n" .
                    '  ' . $cof->createSuccess('✓') . ' ' . $cof->createSuccess('verify page is open') . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.url is "http://127.0.0.1:9080/index.html"' . "\n" .
                    '    ' . $cof->createSuccess('✓') .
                    ' $page.title is "Test fixture web server default document"' . "\n" .
                    '  ' . $cof->createSuccess('✓') . ' ' . $cof->createSuccess('verify primary heading') . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $"h1" exists' . "\n" .
                    '      ' . $cof->createComment('> derived from:') .
                    ' $"h1" is "Test fixture web server default document"' . "\n" .
                    '    ' . $cof->createSuccess('✓') .
                    ' $"h1" is "Test fixture web server default document"' . "\n" .
                    '  ' . $cof->createFailure('x') . ' ' . $cof->createFailure('verify links are present') .
                    "\n" .
                    '    ' . $cof->createFailure('x') . ' ' . $cof->createHighlightedFailure(
                        '$"a[id=link-to-assertions]" not-exists'
                    ) . "\n" .
                    '    * Element ' . $cof->createComment('$"a[id=link-to-assertions]"') . ' identified by:' . "\n" .
                    '        - CSS selector: ' . $cof->createComment('a[id=link-to-assertions]') . "\n" .
                    '        - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '      does exist' . "\n"
            ],
        ];
    }

    private function createGenerateCommand(string $source, string $target): string
    {
        return './bin/basil-runner generate ' .
            '--source=' . $source . ' ' .
            '--target=' . $target . ' ' .
            '--base-class="' . AbstractGeneratedTestCase::class . '"';
    }

    private function createRunCommand(string $path): string
    {
        return './bin/basil-runner ' .
            '--path=' . $path;
    }
}
