<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\Renderer;

use webignition\BasilModels\EncapsulatingStatementInterface;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\EncapsulatingStatementLine;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\StatementLine as RenderableStatementLine;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Model\TestOutput\Status;

class StatementLineRenderer
{
    public function render(StatementLine $statementLine): string
    {
        $statement = $statementLine->getStatement();
        $status = $statementLine->getHasPassed() ? Status::SUCCESS : Status::FAILURE;

        $renderableStatement = $statement instanceof EncapsulatingStatementInterface
            ? new EncapsulatingStatementLine($statement, $status)
            : new RenderableStatementLine($statement, $status);

        return $renderableStatement->render();
    }
}
