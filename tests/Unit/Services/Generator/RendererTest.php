<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\Generator;

use Symfony\Component\Console\Output\BufferedOutput;
use webignition\BasilRunner\Model\GenerateCommand\Configuration;
use webignition\BasilRunner\Model\GenerateCommand\ErrorOutput;
use webignition\BasilRunner\Model\GenerateCommand\OutputInterface;
use webignition\BasilRunner\Model\GenerateCommand\SuccessOutput;
use webignition\BasilRunner\Model\GeneratedTestOutput;
use webignition\BasilRunner\Services\Generator\Renderer;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class RendererTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(OutputInterface $commandOutput, string $expectedRenderedContent)
    {
        $output = new BufferedOutput();

        $renderer = new Renderer();
        $renderer->setOutput($output);

        $renderer->render($commandOutput);

        $this->assertSame($expectedRenderedContent, $output->fetch());
    }

    public function renderDataProvider(): array
    {
        return [
            'success output with no generated test data' => [
                'commandOutput' => new SuccessOutput(
                    new Configuration('source-value', 'target-value', 'base-class-value'),
                    []
                ),
                'expectedRenderedContent' => (string) json_encode(
                    [
                        'config' => [
                            'source' => 'source-value',
                            'target' => 'target-value',
                            'base-class' => 'base-class-value',
                        ],
                        'status' => 'success',
                        'output' => [],
                    ],
                    JSON_PRETTY_PRINT
                ) . "\n",
            ],
            'success output with generated test data' => [
                'commandOutput' => new SuccessOutput(
                    new Configuration('source-value', 'target-value', 'base-class-value'),
                    [
                        new GeneratedTestOutput('test-source-1', 'test-target-1'),
                        new GeneratedTestOutput('test-source-2', 'test-target-2'),
                    ]
                ),
                'expectedRenderedContent' => (string) json_encode(
                    [
                        'config' => [
                            'source' => 'source-value',
                            'target' => 'target-value',
                            'base-class' => 'base-class-value',
                        ],
                        'status' => 'success',
                        'output' => [
                            [
                                'source' => 'test-source-1',
                                'target' => 'test-target-1',
                            ],
                            [
                                'source' => 'test-source-2',
                                'target' => 'test-target-2',
                            ],
                        ],
                    ],
                    JSON_PRETTY_PRINT
                ) . "\n",
            ],
            'failure output without context data' => [
                'commandOutput' => new ErrorOutput(
                    new Configuration('source-value', 'target-value', 'base-class-value'),
                    'error message without context data',
                    999
                ),
                'expectedRenderedContent' => (string) json_encode(
                    [
                        'config' => [
                            'source' => 'source-value',
                            'target' => 'target-value',
                            'base-class' => 'base-class-value',
                        ],
                        'status' => 'failure',
                        'error' => [
                            'message' => 'error message without context data',
                            'code' => 999,
                        ],
                    ],
                    JSON_PRETTY_PRINT
                ) . "\n",
            ],
            'failure output with context data' => [
                'commandOutput' => new ErrorOutput(
                    new Configuration('source-value', 'target-value', 'base-class-value'),
                    'error message with context data',
                    998,
                    [
                        'key1' => 'value1',
                        'key2' => [
                            'key2-key1' => 'value2',
                            'key2-key2' => 'value3',
                        ],
                    ]
                ),
                'expectedRenderedContent' => (string) json_encode(
                    [
                        'config' => [
                            'source' => 'source-value',
                            'target' => 'target-value',
                            'base-class' => 'base-class-value',
                        ],
                        'status' => 'failure',
                        'error' => [
                            'message' => 'error message with context data',
                            'code' => 998,
                            'context' => [
                                'key1' => 'value1',
                                'key2' => [
                                    'key2-key1' => 'value2',
                                    'key2-key2' => 'value3',
                                ],
                            ],
                        ],
                    ],
                    JSON_PRETTY_PRINT
                ) . "\n",
            ],
        ];
    }
}
