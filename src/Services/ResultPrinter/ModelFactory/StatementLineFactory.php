<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\ModelFactory;

use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\StatementLine as RenderableStatementLine;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Model\TestOutput\Status;

class StatementLineFactory
{
    public function create(StatementLine $statementLine): RenderableInterface
    {
        $statement = $statementLine->getStatement();
        $status = $statementLine->getHasPassed() ? Status::SUCCESS : Status::FAILURE;

        return new RenderableStatementLine($statement, $status);
    }
}
