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
    public function testGenerateAndRun()
    {
        $buildPath = './tests/build/target';

        $generateCommand =
            './bin/basil-runner generate ' .
            '--source=./tests/Fixtures/basil-integration/Test ' .
            '--target=./tests/build/target ' .
            '--base-class="' . AbstractBrowserTestCase::class . '"';

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

        $runCommand =
            './bin/basil-runner ' .
            '--path=' . $buildPath;

        $runCommandOutput = (string) shell_exec($runCommand);

        $formatter = Formatter::create();
        $phpUnitOutput = new PhpUnitOutput($runCommandOutput);

        $expectedBody = $formatter->makeBold('tests/Fixtures/basil-integration/Test/index-page-test.yml') . "\n";

        $this->assertSame($expectedBody, $phpUnitOutput->getBody());

        foreach ($generateCommandOutput->getTestPaths() as $testPath) {
            unlink($testPath);
        }
    }

    private function mutateTestContent(string $path, callable $mutator): void
    {
        $testContent = (string) file_get_contents($path);
        $testContent = $mutator($testContent);

        file_put_contents($path, $testContent);
    }
}
