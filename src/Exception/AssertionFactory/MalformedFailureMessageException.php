<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Exception\AssertionFactory;

use webignition\BasilRunner\Exception\AssertionFactory\AbstractFailureMessageException;

class MalformedFailureMessageException extends AbstractFailureMessageException
{
    public function __construct(string $failureMessage)
    {
        parent::__construct($failureMessage, 'Malformed failure message');
    }
}
