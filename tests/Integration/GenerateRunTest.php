<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Integration;

use PHPUnit\Framework\TestCase;
use webignition\BasilRunner\Tests\Model\PhpUnitOutput;
use webignition\BasilRunner\Tests\Services\ConsoleStyler;

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

        $generatedCommandOutput = json_decode($generateCommandOutputText, true);
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
        $styler = new ConsoleStyler();

        return [
            'passing: single test' => [
                'source' => './tests/Fixtures/basil-integration/Test/index-page-test.yml',
                'target' => './tests/build/target',
                'expectedOutputBody' =>
                    $styler->bold('tests/Fixtures/basil-integration/Test/index-page-test.yml') . "\n" .
                    '  ' . $styler->success('✓') . ' ' . $styler->success('verify page is open') . "\n" .
                    '    ' . $styler->success('✓') . ' $page.url is "http://127.0.0.1:9080/index.html"' . "\n" .
                    '    ' . $styler->success('✓') .
                    ' $page.title is "Test fixture web server default document"' . "\n" .
                    '    ' . $styler->success('✓') . ' $page.title matches "/fixture web server/"' . "\n" .
                    "\n" .
                    '  ' . $styler->success('✓') . ' ' . $styler->success('verify primary heading') . "\n" .
                    '    ' . $styler->success('✓') . ' $"h1" is "Test fixture web server default document"' . "\n" .
                    "\n" .
                    '  ' . $styler->success('✓') . ' ' . $styler->success('verify links are present') .
                    "\n" .
                    '    ' . $styler->success('✓') . ' $"a[id=link-to-assertions]" exists' . "\n" .
                    '    ' . $styler->success('✓') . ' $"a[id=link-to-form]" exists' . "\n" .
                    "\n" .
                    '  ' . $styler->success('✓') . ' ' . $styler->success('navigate to form') . "\n" .
                    '    ' . $styler->success('✓') . ' click $"a[id=link-to-form]"' . "\n" .
                    '    ' . $styler->success('✓') . ' $page.url is "http://127.0.0.1:9080/form.html"' . "\n" .
                    '    ' . $styler->success('✓') . ' $page.title is "Form"' . "\n" .
                    "\n"
            ],
            'failing: single test' => [
                'source' => './tests/Fixtures/basil-integration/FailingTest/index-page-test.yml',
                'target' => './tests/build/target',
                'expectedOutputBody' =>
                    $styler->bold('tests/Fixtures/basil-integration/FailingTest/index-page-test.yml') . "\n" .
                    '  ' . $styler->success('✓') . ' ' . $styler->success('verify page is open') . "\n" .
                    '    ' . $styler->success('✓') . ' $page.url is "http://127.0.0.1:9080/index.html"' . "\n" .
                    '    ' . $styler->success('✓') .
                    ' $page.title is "Test fixture web server default document"' . "\n" .
                    "\n" .
                    '  ' . $styler->success('✓') . ' ' . $styler->success('verify primary heading') . "\n" .
                    '    ' . $styler->success('✓') . ' $"h1" is "Test fixture web server default document"' . "\n" .
                    "\n" .
                    '  ' . $styler->failure('x') . ' ' . $styler->failure('verify links are present') .
                    "\n" .
                    '    ' . $styler->failure('x') . ' ' . $styler->highlightedFailure(
                        '$"a[id=link-to-assertions]" not-exists'
                    ) . "\n" .
                    '    * Element ' . $styler->comment('$"a[id=link-to-assertions]"') . ' identified by:' . "\n" .
                    '        - CSS selector: ' . $styler->comment('a[id=link-to-assertions]') . "\n" .
                    '        - ordinal position: ' . $styler->comment('1') . "\n" .
                    '      does exist' . "\n" .
                    "\n"
            ],
            'passing: page import with element de-referencing' => [
                'source' => './tests/Fixtures/basil-integration/Test/form-page-test.yml',
                'target' => './tests/build/target',
                'expectedOutputBody' =>
                    $styler->bold('tests/Fixtures/basil-integration/Test/form-page-test.yml') . "\n" .
                    '  ' . $styler->success('✓') . ' ' . $styler->success('verify page is open') . "\n" .
                    '    ' . $styler->success('✓') . ' $page.url is "http://127.0.0.1:9080/form.html"' . "\n" .
                    '    ' . $styler->success('✓') . ' $page.title is "Form"' . "\n" .
                    '    ' . $styler->success('✓')
                    . ' $"form[action=\'/action1\']" >> $"input[name=\'input-with-value\']" is "test"' . "\n" .
                    '      ' . $styler->comment('> resolved from:')
                    . ' $form_page.elements.input_with_value is "test"' . "\n" .
                    '    ' . $styler->success('✓')
                    . ' $"form[action=\'/action1\']" >> $".textarea-non-empty" is "textarea content"' . "\n" .
                    '      ' . $styler->comment('> resolved from:')
                    . ' $form_page.elements.textarea_within_form_one is "textarea content"' . "\n" .
                    "\n"
            ],
        ];
    }

    private function createGenerateCommand(string $source, string $target): string
    {
        return
            './bin/basil-runner generate ' .
            '--source=' . $source . ' ' .
            '--target=' . $target . ' ' .
            '--base-class="' . AbstractGeneratedTestCase::class . '"';
    }

    private function createRunCommand(string $path): string
    {
        return './bin/basil-runner run --path=' . $path;
    }
}
