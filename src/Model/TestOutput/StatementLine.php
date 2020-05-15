<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\TestOutput;

use webignition\BasilModels\StatementInterface;

class StatementLine
{
    private $statement;
    private $hasPassed;

    private function __construct(StatementInterface $statement, bool $hasPassed)
    {
        $this->statement = $statement;
        $this->hasPassed = $hasPassed;
    }

    public static function createPassedStatementLine(StatementInterface $statement): self
    {
        return new StatementLine($statement, true);
    }

    public static function createFailedStatementLine(StatementInterface $statement): self
    {
        return new StatementLine($statement, false);
    }

    public function getStatement(): StatementInterface
    {
        return $this->statement;
    }

    public function getHasPassed(): bool
    {
        return $this->hasPassed;
    }
}
