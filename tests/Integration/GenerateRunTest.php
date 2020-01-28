<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Integration;

use PHPUnit\Framework\TestCase;
use webignition\BasePantherTestCase\AbstractBrowserTestCase;
use webignition\BasilRunner\Model\GenerateCommand\SuccessOutput;

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

        echo "\n" . $runCommandOutput . "\n";

        $runCommandOutputLines = explode("\n", $runCommandOutput);

//        $this->assertRegExp('#^PHPUnit.+#', $runCommandOutputLines[0]);
//        $this->assertSame('', $runCommandOutputLines[1]);
//        $this->assertRegExp('#^\.\.\.\. +4 / 4 \(100%\)$#', $runCommandOutputLines[2]);
//        $this->assertSame('', $runCommandOutputLines[3]);
//        $this->assertRegExp('#^Time: .+, Memory: .+$#', $runCommandOutputLines[4]);
//        $this->assertSame('', $runCommandOutputLines[5]);
//        $this->assertStringContainsString('OK (4 tests, 9 assertions)', $runCommandOutputLines[6]);
//        $this->assertSame('', $runCommandOutputLines[7]);
//
//        $this->assertNotEmpty($runCommandOutput);
//
//        foreach ($generateCommandOutput->getTestPaths() as $testPath) {
//            unlink($testPath);
//        }
    }

    private function mutateTestContent(string $path, callable $mutator): void
    {
        $testContent = (string) file_get_contents($path);
        $testContent = $mutator($testContent);

        file_put_contents($path, $testContent);
    }
}
