<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Integration;

use Symfony\Component\Console\Output\BufferedOutput;
use webignition\TcpCliProxyClient\Client;
use webignition\YamlDocumentSetParser\Parser;

class ContainerDelegatorTest extends AbstractDelegatorTest
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
        $manifestReadDirectory = '/app/manifests';

        $outputDocuments = [];

        $suiteManifest = $this->compile($source, $target);
        $manifestFilenames = $this->storeTestManifests($suiteManifest, $this->manifestWriteDirectory);
        $manifestReadPaths = $this->createManifestReadPaths($manifestReadDirectory, $manifestFilenames);

        $yamlDocumentSetParser = new Parser();

        foreach ($suiteManifest->getTestManifests() as $testManifest) {
            $manifestHash = $this->generateManifestHash($testManifest);
            $manifestReadPath = $manifestReadPaths[$manifestHash];

            $delegatorClientOutput = new BufferedOutput();
            $delegatorClient = Client::createFromHostAndPort('localhost', 9003);
            $delegatorClient = $delegatorClient->withOutput($delegatorClientOutput);

            $delegatorClient->request('./bin/delegator --path=' . $manifestReadPath);

            $delegatorClientOutputLines = explode("\n", $delegatorClientOutput->fetch());
            $delegatorExitCode = (int) array_pop($delegatorClientOutputLines);
            $delegatorClientOutputContent = implode("\n", $delegatorClientOutputLines);

            self::assertSame(0, $delegatorExitCode);

            $outputDocuments = array_merge(
                $outputDocuments,
                $yamlDocumentSetParser->parse($delegatorClientOutputContent)
            );
        }

        self::assertEquals($expectedOutputDocuments, $outputDocuments);

        $this->removeCompiledArtifacts($target, $this->manifestWriteDirectory);
    }
}
