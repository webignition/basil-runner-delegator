<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilAssertionFailureMessage\AssertionFailureMessage;
use webignition\BasilAssertionFailureMessage\Factory as ModelFactory;
use webignition\BasilAssertionFailureMessage\FailureMessageException;
use webignition\BasilRunner\Exception\AssertionFailureMessageParseException;

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
     * @param string $failureMessage
     *
     * @return AssertionFailureMessage
     *
     * @throws AssertionFailureMessageParseException
     */
    public function create(string $failureMessage): AssertionFailureMessage
    {
        $lastCurlyBracketPosition = strrpos($failureMessage, '}');
        if (false === $lastCurlyBracketPosition) {
            throw AssertionFailureMessageParseException::createMalformedFailureMessageException($failureMessage);
        }

        $json = substr($failureMessage, 0, $lastCurlyBracketPosition + 1);

        try {
            return $this->assertionFailureMessageModelFactory->fromJson($json);
        } catch (FailureMessageException $exception) {
            throw AssertionFailureMessageParseException::createMalformedDataException($failureMessage, $exception);
        }
    }
}
