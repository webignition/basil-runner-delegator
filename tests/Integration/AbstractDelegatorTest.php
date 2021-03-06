<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\TcpCliProxyClient\Client;
use webignition\TcpCliProxyClient\Handler;
use webignition\YamlDocumentSetParser\Parser;

abstract class AbstractDelegatorTest extends TestCase
{
    private Client $compilerClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compilerClient = Client::createFromHostAndPort('localhost', 9000);
    }

    protected function compile(string $source, string $target): SuiteManifest
    {
        $output = '';

        $handler = (new Handler())
            ->addCallback(function (string $buffer) use (&$output) {
                $output .= $buffer;
            });

        $this->compilerClient->request(
            sprintf('./compiler --source=%s --target=%s', $source, $target),
            $handler
        );

        $outputContentLines = explode("\n", $output);

        $exitCode = (int) array_pop($outputContentLines);
        self::assertSame(0, $exitCode);

        $suiteManifestData = Yaml::parse(implode("\n", $outputContentLines));

        return SuiteManifest::fromArray($suiteManifestData);
    }

    protected function removeCompiledArtifacts(string $target): void
    {
        $this->compilerClient->request(sprintf('rm %s/*.php', $target));
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

    /**
     * @return array[]
     */
    public function delegatorDataProvider(): array
    {
        return [
            'index open chrome firefox' => [
                'source' => '/app/source/Test/index-open-chrome-firefox.yml',
                'target' => '/app/tests',
                'expectedOutputDocuments' => [
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
}
