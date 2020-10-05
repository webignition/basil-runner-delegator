<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Yaml\Yaml;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilCompilerModels\TestManifest;
use webignition\TcpCliProxyClient\Client;
use webignition\YamlDocumentSetParser\Parser;

abstract class AbstractDelegatorTest extends TestCase
{
    private Client $compilerClient;
    protected string $manifestWriteDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compilerClient = Client::createFromHostAndPort('localhost', 9000);
        $this->manifestWriteDirectory = getcwd() . '/tests/build/manifests';
    }

    protected function compile(string $source, string $target): SuiteManifest
    {
        $output = new BufferedOutput();
        $compilerClient = $this->compilerClient->withOutput($output);

        $compilerClient->request(sprintf(
            './compiler --source=%s --target=%s',
            $source,
            $target
        ));

        $outputContent = $output->fetch();
        $outputContentLines = explode("\n", $outputContent);

        $exitCode = (int) array_pop($outputContentLines);
        self::assertSame(0, $exitCode);

        $suiteManifestData = Yaml::parse(implode("\n", $outputContentLines));

        return SuiteManifest::fromArray($suiteManifestData);
    }

    protected function removeCompiledArtifacts(string $target, string $manifestWriteDirectory): void
    {
        $output = new BufferedOutput();
        $compilerClient = $this->compilerClient->withOutput($output);

        $compilerClient->request(sprintf('rm %s/*.php', $target));

        shell_exec(sprintf('rm %s/*.yml', $manifestWriteDirectory));
    }

    /**
     * @param array<mixed> $expectedOutputDocuments
     * @param string $content
     */
    protected static function assertDelegatorOutput(array $expectedOutputDocuments, string $content): void
    {
        $yamlDocumentSetParser = new Parser();
        $outputDocuments = $yamlDocumentSetParser->parse($content);

        self::assertSame($expectedOutputDocuments, $outputDocuments);
    }

    public function delegatorDataProvider(): array
    {
        return [
            'index open chrome firefox' => [
                'source' => '/app/source/Test/index-open-chrome-firefox.yml',
                'target' => '/app/tests',
                'expectedOutputDocuments' => [
                    [
                        'type' => 'test',
                        'path' => '/app/source/Test/index-open-chrome-firefox.yml',
                        'config' => [
                            'browser' => 'chrome',
                            'url' => 'http://nginx/index.html',
                        ],
                    ],
                    [
                        'type' => 'step',
                        'name' => 'verify page is open',
                        'status' => 'passed',
                        'statements' => [
                            [
                                'type' => 'assertion',
                                'source' => '$page.url is "http://nginx/index.html"',
                                'status' => 'passed',
                            ],
                            [
                                'type' => 'assertion',
                                'source' => '$page.title is "Test fixture web server default document"',
                                'status' => 'passed',
                            ],
                        ],
                    ],
                    [
                        'type' => 'test',
                        'path' => '/app/source/Test/index-open-chrome-firefox.yml',
                        'config' => [
                            'browser' => 'firefox',
                            'url' => 'http://nginx/index.html',
                        ],
                    ],
                    [
                        'type' => 'step',
                        'name' => 'verify page is open',
                        'status' => 'passed',
                        'statements' => [
                            [
                                'type' => 'assertion',
                                'source' => '$page.url is "http://nginx/index.html"',
                                'status' => 'passed',
                            ],
                            [
                                'type' => 'assertion',
                                'source' => '$page.title is "Test fixture web server default document"',
                                'status' => 'passed',
                            ],
                        ],
                    ],
                ],
            ],
            'index failing chrome' => [
                'source' => '/app/source/FailingTest/index-failing.yml',
                'target' => '/app/tests',
                'expectedOutputDocuments' => [
                    [
                        'type' => 'test',
                        'path' => '/app/source/FailingTest/index-failing.yml',
                        'config' => [
                            'browser' => 'chrome',
                            'url' => 'http://nginx/index.html',
                        ],
                    ],
                    [
                        'type' => 'step',
                        'name' => 'verify page is open',
                        'status' => 'passed',
                        'statements' => [
                            [
                                'type' => 'assertion',
                                'source' => '$page.url is "http://nginx/index.html"',
                                'status' => 'passed',
                            ],
                        ],
                    ],
                    [
                        'type' => 'step',
                        'name' => 'verify links are present',
                        'status' => 'failed',
                        'statements' => [
                            [
                                'type' => 'assertion',
                                'source' => '$"a[id=link-to-assertions]" not-exists',
                                'status' => 'failed',
                                'summary' => [
                                    'operator' => 'not-exists',
                                    'source' => [
                                        'type' => 'node',
                                        'body' => [
                                            'type' => 'element',
                                            'identifier' => [
                                                'source' => '$"a[id=link-to-assertions]"',
                                                'properties' => [
                                                    'type' => 'css',
                                                    'locator' => 'a[id=link-to-assertions]',
                                                    'position' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param SuiteManifest $suiteManifest
     *
     * @return string[]
     */
    protected function storeTestManifests(SuiteManifest $suiteManifest, string $manifestWriteDirectory): array
    {
        $paths = [];

        foreach ($suiteManifest->getTestManifests() as $testManifest) {
            $manifestHash = $this->generateManifestHash($testManifest);
            $manifestFilename = 'manifest' . $manifestHash . '.yml';
            $manifestPath = $manifestWriteDirectory . '/' . $manifestFilename;

            file_put_contents($manifestPath, Yaml::dump($testManifest->getData()));

            $paths[$manifestHash] = $manifestFilename;
        }

        return $paths;
    }

    protected function generateManifestHash(TestManifest $testManifest): string
    {
        return md5((string) json_encode($testManifest->getData()));
    }

    /**
     * @param string $manifestReadDirectory
     * @param string[] $manifestFilenames
     *
     * @return string[]
     */
    protected function createManifestReadPaths(string $manifestReadDirectory, array $manifestFilenames): array
    {
        $manifestReadPaths = [];

        foreach ($manifestFilenames as $index => $filename) {
            $manifestReadPaths[$index] = $manifestReadDirectory . '/' . $filename;
        }

        return $manifestReadPaths;
    }
}
