<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\Step;

use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilRunner\Model\ResultPrinter\DataSet\KeyValueCollection;
use webignition\BasilRunner\Model\ResultPrinter\IndentedContent;
use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableCollection;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\StatementLine as RenderableStatementLine;
use webignition\BasilRunner\Model\TestOutput\Step as OutputStep;

class Step implements RenderableInterface
{
    private Name $name;
    private ?RenderableInterface $dataSet;
    private ?RenderableInterface $completedStatements ;
    private ?RenderableInterface $failedStatement = null;
    private ?RenderableInterface $lastException = null;

    public function __construct(OutputStep $outputStep)
    {
        $this->name = new Name($outputStep);
        $this->dataSet = $this->createDataSet($outputStep);
        $this->completedStatements = $this->createCompletedStatements($outputStep);
    }

    public function setFailedStatement(RenderableInterface $failedStatement): void
    {
        $this->failedStatement = $failedStatement;
    }

    public function setLastException(RenderableInterface $lastException): void
    {
        $this->lastException = $lastException;
    }

    public function render(): string
    {
        $dataSet = null === $this->dataSet
            ? null
            : new RenderableCollection([
                new IndentedContent($this->dataSet, 2),
                new Literal(''),
            ]);

        $failedStatement = null === $this->failedStatement
            ? null
            : new IndentedContent($this->failedStatement);

        $lastException = null === $this->lastException
            ? null
            : new IndentedContent($this->lastException);

        $items = [
            $this->name,
            $dataSet,
            $this->completedStatements,
            $failedStatement,
            $lastException,
        ];

        return (new RenderableCollection($items))->render();
    }

    private function createDataSet(OutputStep $step): ?RenderableInterface
    {
        $dataSet = $step->getCurrentDataSet();
        if ($dataSet instanceof DataSetInterface) {
            return new RenderableCollection([
                KeyValueCollection::fromDataSet($dataSet),
            ]);
        }

        return null;
    }

    private function createCompletedStatements(OutputStep $step): ?RenderableInterface
    {
        $completedStatementLines = $step->getCompletedStatementLines();
        if (0 === count($completedStatementLines)) {
            return null;
        }

        $renderableStatements = [];
        foreach ($step->getCompletedStatementLines() as $completedStatementLine) {
            if (false === $completedStatementLine->getIsDerived()) {
                $renderableStatements[] = RenderableStatementLine::fromOutputStatementLine($completedStatementLine);
            }
        }

        return new IndentedContent(new RenderableCollection($renderableStatements));
    }
}
