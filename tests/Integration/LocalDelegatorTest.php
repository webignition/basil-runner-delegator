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
        $manifestReadDirectory = $this->manifestWriteDirectory;

        $outputDocuments = [];

        $suiteManifest = $this->compile($source, $target);
        $manifestFilenames = $this->storeTestManifests($suiteManifest, $this->manifestWriteDirectory);
        $manifestReadPaths = $this->createManifestReadPaths($manifestReadDirectory, $manifestFilenames);

        $yamlDocumentSetParser = new Parser();

        foreach ($suiteManifest->getTestManifests() as $testManifest) {
            $manifestHash = $this->generateManifestHash($testManifest);
            $manifestReadPath = $manifestReadPaths[$manifestHash];

            $runnerProcess = Process::fromShellCommandline(
                './bin/delegator --path=' . $manifestReadPath
            );

            $runnerExitCode = $runnerProcess->run();
            self::assertSame(0, $runnerExitCode);

            $outputDocuments = array_merge(
                $outputDocuments,
                $yamlDocumentSetParser->parse($runnerProcess->getOutput())
            );
        }

        self::assertEquals($expectedOutputDocuments, $outputDocuments);

        $this->removeCompiledArtifacts($target, $this->manifestWriteDirectory);
    }
}
