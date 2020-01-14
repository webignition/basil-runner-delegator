<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use webignition\BasilRunner\Model\ErrorContext;
use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class GenerateCommandErrorOutputTest extends AbstractBaseTest
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param GenerateCommandErrorOutput $output
     * @param array<mixed> $expectedSerializedData
     */
    public function testJsonSerialize(GenerateCommandErrorOutput $output, array $expectedSerializedData)
    {
        $this->assertEquals($expectedSerializedData, $output->jsonSerialize());
    }

    public function jsonSerializeDataProvider(): array
    {
        return [
            'without context' => [
                'output' => new GenerateCommandErrorOutput(
                    new GenerateCommandConfiguration(
                        'source-value',
                        'target-value',
                        TestCase::class
                    ),
                    'error-message-01',
                    1
                ),
                'expectedSerializedData' => [
                    'config' => [
                        'source' => 'source-value',
                        'target' => 'target-value',
                        'base-class' => TestCase::class,
                    ],
                    'status' => 'failure',
                    'error' => [
                        'message' => 'error-message-01',
                        'code' => 1,
                    ],
                ],
            ],
            'with context' => [
                'output' => new GenerateCommandErrorOutput(
                    new GenerateCommandConfiguration(
                        'source-value',
                        'target-value',
                        TestCase::class
                    ),
                    'error-message-01',
                    1,
                    new ErrorContext([
                        'context-key-01' => 'context-value-01',
                        'context-key-02' => 'context-value-02',
                    ])
                ),
                'expectedSerializedData' => [
                    'config' => [
                        'source' => 'source-value',
                        'target' => 'target-value',
                        'base-class' => TestCase::class,
                    ],
                    'status' => 'failure',
                    'error' => [
                        'message' => 'error-message-01',
                        'code' => 1,
                        'context' => [
                            'context-key-01' => 'context-value-01',
                            'context-key-02' => 'context-value-02',
                        ],
                    ],
                ],
            ],
        ];
    }
}
