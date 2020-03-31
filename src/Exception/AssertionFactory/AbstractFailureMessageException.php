<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Exception\AssertionFactory;

abstract class AbstractFailureMessageException extends \Exception
{
    private $failureMessage;

    public function __construct(string $failureMessage, string $exceptionMessage)
    {
        $this->failureMessage = $failureMessage;

        parent::__construct($exceptionMessage);
    }

    public function getFailureMessage(): string
    {
        return $this->failureMessage;
    }
}
