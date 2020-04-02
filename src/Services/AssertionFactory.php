<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\Assertion\Factory\Factory;
use webignition\BasilModels\Assertion\Factory\MalformedDataException;
use webignition\BasilModels\Assertion\Factory\UnknownComparisonException;
use webignition\BasilRunner\Exception\AssertionFactory\MalformedFailureMessageException;
use webignition\BasilRunner\Exception\AssertionFactory\NonDecodableFailureMessageException;

class AssertionFactory
{
    private $modelFactory;

    public function __construct(Factory $modelFactory)
    {
        $this->modelFactory = $modelFactory;
    }

    public static function createFactory(): AssertionFactory
    {
        return new AssertionFactory(new Factory());
    }

    /**
     * @param string $assertionFailureMessage
     *
     * @return AssertionInterface|null
     *
     * @throws MalformedFailureMessageException
     * @throws NonDecodableFailureMessageException
     * @throws MalformedDataException
     * @throws UnknownComparisonException
     */
    public function createFromAssertionFailureMessage(string $assertionFailureMessage): ?AssertionInterface
    {
        $lastCurlyBracketPosition = strrpos($assertionFailureMessage, '}');
        if (false === $lastCurlyBracketPosition) {
            throw new MalformedFailureMessageException($assertionFailureMessage);
        }

        $jsonString = substr($assertionFailureMessage, 0, $lastCurlyBracketPosition + 1);
        $data = json_decode($jsonString, true);
        if (null === $data) {
            throw new NonDecodableFailureMessageException($assertionFailureMessage);
        }

        return $this->modelFactory->createFromArray($data['assertion'] ?? []);
    }
}
