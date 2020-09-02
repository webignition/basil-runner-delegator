<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Integration;

use Symfony\Component\Console\Output\BufferedOutput;
use webignition\TcpCliProxyClient\Client;

class ContainerDelegatorTest extends AbstractDelegatorTest
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

        $delegatorClientOutput = new BufferedOutput();
        $delegatorClient = Client::createFromHostAndPort('localhost', 9003);
        $delegatorClient = $delegatorClient->withOutput($delegatorClientOutput);

        $delegatorClient->request('./bin/delegator --path=' . $manifestPath);

        $delegatorClientOutputLines = explode("\n", $delegatorClientOutput->fetch());
        $delegatorExitCode = (int) array_pop($delegatorClientOutputLines);
        $delegatorClientOutputContent = implode("\n", $delegatorClientOutputLines);

        $this->removeCompiledArtifacts($target, $manifestPath);

        self::assertSame(0, $delegatorExitCode);
        self::assertDelegatorOutput($expectedOutputDocuments, $delegatorClientOutputContent);
    }
}
