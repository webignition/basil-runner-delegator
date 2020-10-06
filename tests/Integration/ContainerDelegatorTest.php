<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Integration;

use webignition\TcpCliProxyClient\Client;
use webignition\TcpCliProxyClient\Handler;
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
            $delegatorClientOutput = '';
            $delegatorClient = Client::createFromHostAndPort('localhost', 9003);

            $delegatorClientHandler = (new Handler())
                ->addCallback(function (string $buffer) use (&$delegatorClientOutput) {
                    $delegatorClientOutput .= $buffer;
                });

            $delegatorClient->request(
                sprintf(
                    './bin/delegator --browser %s %s',
                    $testManifest->getConfiguration()->getBrowser(),
                    $testManifest->getTarget()
                ),
                $delegatorClientHandler
            );

            $delegatorClientOutputLines = explode("\n", $delegatorClientOutput);
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
