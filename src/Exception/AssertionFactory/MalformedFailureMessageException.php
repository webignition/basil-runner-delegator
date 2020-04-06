<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Exception\AssertionFactory;

class MalformedFailureMessageException extends \Exception
{
    private $failureMessage;

    public function __construct(string $failureMessage)
    {
        $this->failureMessage = $failureMessage;

        parent::__construct('');
    }

    public function getFailureMessage(): string
    {
        return $this->failureMessage;
    }
}
