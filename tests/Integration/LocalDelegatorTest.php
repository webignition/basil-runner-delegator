<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Integration;

use Symfony\Component\Process\Process;
use webignition\YamlDocumentSetParser\Parser;

class LocalDelegatorTest extends AbstractDelegatorTest
{
    /**
     * @dataProvider delegatorDataProvider
     *
     * @param string $source
     * @param string $target
     * @param array<mixed> $expectedOutputDocuments
     */
    public function testDelegator(string $source, string $target, array $expectedOutputDocuments)
    {
        $outputDocuments = [];

        $suiteManifest = $this->compile($source, $target);

        $yamlDocumentSetParser = new Parser();

        foreach ($suiteManifest->getTestManifests() as $testManifest) {
            $runnerProcess = Process::fromShellCommandline(
                sprintf(
                    './bin/delegator --browser %s %s',
                    $testManifest->getConfiguration()->getBrowser(),
                    $testManifest->getTarget()
                )
            );

            $runnerExitCode = $runnerProcess->run();
            self::assertSame(0, $runnerExitCode);

            $outputDocuments = array_merge(
                $outputDocuments,
                $yamlDocumentSetParser->parse($runnerProcess->getOutput())
            );
        }

        self::assertEquals($expectedOutputDocuments, $outputDocuments);

        $this->removeCompiledArtifacts($target);
    }
}
