<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\GenerateCommand;

use webignition\BasilRunner\Model\GenerateCommand\Configuration;
use webignition\BasilRunner\Model\GenerateCommand\SuccessOutput;
use webignition\BasilRunner\Model\GeneratedTestOutput;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class SuccessOutputTest extends AbstractBaseTest
{
    /**
     * @dataProvider getTestPathsDataProvider
     *
     * @param SuccessOutput $successOutput
     * @param array<string> $expectedTestPaths
     */
    public function testGetTestPaths(SuccessOutput $successOutput, array $expectedTestPaths)
    {
        $this->assertSame($expectedTestPaths, $successOutput->getTestPaths());
    }

    public function getTestPathsDataProvider(): array
    {
        return [
            'no generated tests' => [
                'successOutput' => new SuccessOutput(
                    new Configuration(
                        'source',
                        'target',
                        'base-class'
                    ),
                    []
                ),
                'expectedTestPaths' => [],
            ],
            'single generated test' => [
                'successOutput' => new SuccessOutput(
                    new Configuration(
                        'source',
                        'target',
                        'base-class'
                    ),
                    [
                        new GeneratedTestOutput('test.yml', 'GeneratedTest.php'),
                    ]
                ),
                'expectedTestPaths' => [
                    'target/GeneratedTest.php',
                ],
            ],
            'multiple generated tests' => [
                'successOutput' => new SuccessOutput(
                    new Configuration(
                        'source',
                        'target',
                        'base-class'
                    ),
                    [
                        new GeneratedTestOutput('test1.yml', 'GeneratedTest1.php'),
                        new GeneratedTestOutput('test2.yml', 'GeneratedTest2.php'),
                        new GeneratedTestOutput('test3.yml', 'GeneratedTest3.php'),
                    ]
                ),
                'expectedTestPaths' => [
                    'target/GeneratedTest1.php',
                    'target/GeneratedTest2.php',
                    'target/GeneratedTest3.php',
                ],
            ],
        ];
    }
}
