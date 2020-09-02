<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Process\Process;
use webignition\TcpCliProxyClient\Client;
use webignition\YamlDocumentSetParser\Parser;

class RunnerTest extends TestCase
{
    /**
     * @dataProvider runnerDataProvider
     *
     * @param string $source
     * @param string $target
     * @param string $manifestPath
     * @param array<mixed> $expectedOutputDocuments
     */
    public function testRunner(string $source, string $target, string $manifestPath, array $expectedOutputDocuments)
    {
        $compilerClient = Client::createFromHostAndPort('localhost', 9000);
        $compilerClient = $compilerClient->withOutput(new NullOutput());
        $compilerClient->request(sprintf(
            './compiler --source=%s --target=%s 1>%s',
            $source,
            $target,
            $manifestPath
        ));

        $runnerProcess = Process::fromShellCommandline(
            './bin/basil-runner --path=' . getcwd() . '/tests/build/manifests/manifest.yml'
        );

        $runnerExitCode = $runnerProcess->run();

        $compilerClient->request(sprintf('rm %s', $manifestPath));
        $compilerClient->request(sprintf('rm %s/*.php', $target));

        self::assertSame(0, $runnerExitCode);

        $yamlDocumentSetParser = new Parser();
        $outputDocuments = $yamlDocumentSetParser->parse($runnerProcess->getOutput());

        self::assertSame($expectedOutputDocuments, $outputDocuments);
    }

    public function runnerDataProvider(): array
    {
        return [
            'index open form open chrome firefox' => [
                'source' => '/app/source/TestSuite/index-open-form-open-chrome-firefox.yml',
                'target' => '/app/tests',
                'manifestPath' => '/app/manifests/manifest.yml',
                'expectedOutputDocuments' => [
                    [
                        'type' => 'test',
                        'path' => '/app/source/Test/index-open-chrome.yml',
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
                        'path' => '/app/source/Test/index-open-firefox.yml',
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
                    [
                        'type' => 'test',
                        'path' => '/app/source/Test/form-open-chrome.yml',
                        'config' => [
                            'browser' => 'chrome',
                            'url' => 'http://nginx/form.html',
                        ],
                    ],
                    [
                        'type' => 'step',
                        'name' => 'verify page is open',
                        'status' => 'passed',
                        'statements' => [
                            [
                                'type' => 'assertion',
                                'source' => '$page.url is "http://nginx/form.html"',
                                'status' => 'passed',
                            ],
                            [
                                'type' => 'assertion',
                                'source' => '$page.title is "Form"',
                                'status' => 'passed',
                            ],
                        ],
                    ],
                    [
                        'type' => 'test',
                        'path' => '/app/source/Test/form-open-firefox.yml',
                        'config' => [
                            'browser' => 'firefox',
                            'url' => 'http://nginx/form.html',
                        ],
                    ],
                    [
                        'type' => 'step',
                        'name' => 'verify page is open',
                        'status' => 'passed',
                        'statements' => [
                            [
                                'type' => 'assertion',
                                'source' => '$page.url is "http://nginx/form.html"',
                                'status' => 'passed',
                            ],
                            [
                                'type' => 'assertion',
                                'source' => '$page.title is "Form"',
                                'status' => 'passed',
                            ],
                        ],
                    ],
                ],
            ],
            'index failing chrome' => [
                'source' => '/app/source/FailingTest/index-failing.yml',
                'target' => '/app/tests',
                'manifestPath' => '/app/manifests/manifest.yml',
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
}
