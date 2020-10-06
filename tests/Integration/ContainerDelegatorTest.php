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
        $outputDocuments = [];

        $suiteManifest = $this->compile($source, $target);

        $yamlDocumentSetParser = new Parser();

        foreach ($suiteManifest->getTestManifests() as $testManifest) {
            $delegatorClientOutput = new BufferedOutput();
            $delegatorClient = Client::createFromHostAndPort('localhost', 9003);
            $delegatorClient = $delegatorClient->withOutput($delegatorClientOutput);

            $delegatorClient->request(sprintf(
                sprintf(
                    './bin/delegator --browser %s %s',
                    $testManifest->getConfiguration()->getBrowser(),
                    $testManifest->getTarget()
                )
            ));

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

        $this->removeCompiledArtifacts($target);
    }
}
