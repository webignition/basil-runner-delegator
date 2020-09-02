<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Integration;

use Symfony\Component\Process\Process;

class LocalDelegatorTest extends AbstractDelegatorTest
{
    /**
     * @dataProvider delegatorDataProvider
     *
     * @param string $source
     * @param string $target
     * @param string $manifestPath
     * @param array<mixed> $expectedOutputDocuments
     */
    public function testDelegator(string $source, string $target, string $manifestPath, array $expectedOutputDocuments)
    {
        $this->compile($source, $target, $manifestPath);

        $runnerProcess = Process::fromShellCommandline(
            './bin/delegator --path=' . getcwd() . '/tests/build/manifests/manifest.yml'
        );

        $runnerExitCode = $runnerProcess->run();

        $this->removeCompiledArtifacts($target, $manifestPath);

        self::assertSame(0, $runnerExitCode);
        self::assertDelegatorOutput($expectedOutputDocuments, $runnerProcess->getOutput());
    }
}
