<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilAssertionFailureMessage\AssertionFailureMessage;
use webignition\BasilAssertionFailureMessage\Factory as ModelFactory;
use webignition\BasilAssertionFailureMessage\FailureMessageException;
use webignition\BasilRunner\Exception\AssertionFactory\MalformedFailureMessageException;

class AssertionFailureMessageFactory
{
    private $assertionFailureMessageModelFactory;

    public function __construct(ModelFactory $assertionFailureMessageModelFactory)
    {
        $this->assertionFailureMessageModelFactory = $assertionFailureMessageModelFactory;
    }

    public static function createFactory(): AssertionFailureMessageFactory
    {
        return new AssertionFailureMessageFactory(ModelFactory::createFactory());
    }

    /**
     * @param string $assertionFailureMessage
     *
     * @return AssertionFailureMessage
     *
     * @throws FailureMessageException
     * @throws MalformedFailureMessageException
     */
    public function createFromAssertionFailureMessage(string $assertionFailureMessage): AssertionFailureMessage
    {
        $lastCurlyBracketPosition = strrpos($assertionFailureMessage, '}');
        if (false === $lastCurlyBracketPosition) {
            throw new MalformedFailureMessageException($assertionFailureMessage);
        }

        $json = substr($assertionFailureMessage, 0, $lastCurlyBracketPosition + 1);

        return $this->assertionFailureMessageModelFactory->fromJson($json);
    }
}
