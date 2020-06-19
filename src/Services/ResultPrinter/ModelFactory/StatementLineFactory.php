<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\ModelFactory;

use webignition\BasilModels\EncapsulatingStatementInterface;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\EncapsulatingStatementLine;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\StatementLine as RenderableStatementLine;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Model\TestOutput\Status;

class StatementLineFactory
{
    public function create(StatementLine $statementLine): RenderableInterface
    {
        $statement = $statementLine->getStatement();
        $status = $statementLine->getHasPassed() ? Status::SUCCESS : Status::FAILURE;

        return $statement instanceof EncapsulatingStatementInterface
            ? new EncapsulatingStatementLine($statement, $status)
            : new RenderableStatementLine($statement, $status);
    }
}
