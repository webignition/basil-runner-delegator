<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\Step;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BasilModels\DataSet\DataSet;
use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\Step\Step as RenderableStep;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Model\TestOutput\Status;
use webignition\BasilRunner\Model\TestOutput\Step as OutputStep;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class StepTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(RenderableStep $step, string $expectedRenderedStep)
    {
        $this->assertSame($expectedRenderedStep, $step->render());
    }

    public function renderDataProvider(): array
    {
        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        return [
            'passed, no statements' => [
                'step' => new RenderableStep($this->createOutputStep(
                    Status::SUCCESS,
                    'passed step name',
                    []
                )),
                'expectedRenderedStep' => '<icon-success /> <success>passed step name</success>',
            ],
            'failed, no statements' => [
                'step' => new RenderableStep($this->createOutputStep(
                    Status::FAILURE,
                    'failed step name',
                    []
                )),
                'expectedRenderedStep' => '<icon-failure /> <failure>failed step name</failure>',
            ],
            'unknown, no statements' => [
                'step' => new RenderableStep($this->createOutputStep(
                    BaseTestRunner::STATUS_ERROR,
                    'unknown step name',
                    []
                )),
                'expectedRenderedStep' => '<icon-unknown /> <failure>unknown step name</failure>',
            ],
            'passed, click statement completed' => [
                'step' => new RenderableStep($this->createOutputStep(
                    BaseTestRunner::STATUS_PASSED,
                    'passed step name',
                    [
                        StatementLine::createPassedStatementLine($actionParser->parse('click $".selector"')),
                    ]
                )),
                'expectedRenderedStep' =>
                    '<icon-success /> <success>passed step name</success>' . "\n" .
                    '  <icon-success /> click $".selector"'
                ,
            ],
            'passed, has data' => [
                'step' => new RenderableStep($this->createOutputStep(
                    BaseTestRunner::STATUS_PASSED,
                    'passed step name',
                    [
                        StatementLine::createPassedStatementLine(
                            $actionParser->parse('set $".search" to $data.search')
                        ),
                        StatementLine::createPassedStatementLine(
                            $assertionParser->parse('$page.title matches $data.expected_title_pattern')
                        ),
                    ],
                    new DataSet(
                        'data set name',
                        [
                            'search' => 'value1',
                            'expected_title_pattern' => 'value2',
                        ]
                    )
                )),
                'expectedRenderedStep' =>
                    '<icon-success /> <success>passed step name: data set name</success>' . "\n" .
                    '    $search: <comment>value1</comment>' . "\n" .
                    '    $expected_title_pattern: <comment>value2</comment>' . "\n" .
                    "\n" .
                    '  <icon-success /> set $".search" to $data.search' . "\n" .
                    '  <icon-success /> $page.title matches $data.expected_title_pattern'
                ,
            ],
            'failed, has failure statement' => [
                'step' => $this->setFailedStatementOnStep(
                    new RenderableStep($this->createOutputStep(
                        BaseTestRunner::STATUS_FAILURE,
                        'failed step name',
                        [
                            StatementLine::createFailedStatementLine($assertionParser->parse('$".selector" exists')),
                        ]
                    )),
                    new Literal('failure statement')
                ),
                'expectedRenderedStep' =>
                    '<icon-failure /> <failure>failed step name</failure>' . "\n" .
                    '  <icon-failure /> <highlighted-failure>$".selector" exists</highlighted-failure>' . "\n" .
                    '  failure statement'
                ,
            ],
            'failed, has last exception' => [
                'step' => $this->setLastExceptionOnStep(
                    new RenderableStep($this->createOutputStep(
                        BaseTestRunner::STATUS_FAILURE,
                        'failed step name',
                        [
                            StatementLine::createFailedStatementLine(
                                $assertionParser->parse('$"a[href=https://example.com]" exists')
                            ),
                        ]
                    )),
                    new Literal('last exception')
                ),
                'expectedRenderedStep' =>
                    '<icon-failure /> <failure>failed step name</failure>' . "\n" .
                    '  <icon-failure /> '
                    . '<highlighted-failure>$"a[href=https://example.com]" exists</highlighted-failure>' . "\n" .
                    '  last exception'
                ,
            ],
            'failed, has failure statement, has last exception' => [
                'step' => $this->setLastExceptionOnStep(
                    $this->setFailedStatementOnStep(
                        new RenderableStep($this->createOutputStep(
                            BaseTestRunner::STATUS_FAILURE,
                            'failed step name',
                            [
                                StatementLine::createFailedStatementLine(
                                    $assertionParser->parse('$"a[href=https://example.com]" exists')
                                ),
                            ]
                        )),
                        new Literal('failure statement')
                    ),
                    new Literal('last exception')
                ),
                'expectedRenderedStep' =>
                    '<icon-failure /> <failure>failed step name</failure>' . "\n" .
                    '  <icon-failure /> '
                    . '<highlighted-failure>$"a[href=https://example.com]" exists</highlighted-failure>' . "\n" .
                    '  failure statement' . "\n" .
                    '  last exception'
                ,
            ],
        ];
    }

    /**
     * @param int $status
     * @param string $name
     * @param StatementLine[] $completedStatementLines
     * @param DataSetInterface|null $dataSet
     *
     * @return OutputStep
     */
    private function createOutputStep(
        int $status,
        string $name,
        array $completedStatementLines,
        ?DataSetInterface $dataSet = null
    ): OutputStep {
        $step = \Mockery::mock(OutputStep::class);

        $step
            ->shouldReceive('getStatus')
            ->andReturn($status);

        $step
            ->shouldReceive('getName')
            ->andReturn($name);

        $step
            ->shouldReceive('getCompletedStatementLines')
            ->andReturn($completedStatementLines);

        $step
            ->shouldReceive('getCurrentDataSet')
            ->andReturn($dataSet);

        return $step;
    }

    private function setFailedStatementOnStep(RenderableStep $step, RenderableInterface $renderable): RenderableStep
    {
        $step->setFailedStatement($renderable);

        return $step;
    }

    private function setLastExceptionOnStep(RenderableStep $step, RenderableInterface $renderable): RenderableStep
    {
        $step->setLastException($renderable);

        return $step;
    }
}
