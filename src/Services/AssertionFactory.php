<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilModels\Assertion\Assertion;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\Assertion\ComparisonAssertion;
use webignition\BasilRunner\Exception\AssertionFactory\MalformedFailureMessageException;
use webignition\BasilRunner\Exception\AssertionFactory\UnknownAssertionComparisonException;
use webignition\BasilRunner\Exception\AssertionFactory\NonDecodableFailureMessageException;

class AssertionFactory
{
    /**
     * @param string $assertionFailureMessage
     *
     * @return AssertionInterface|null
     *
     * @throws MalformedFailureMessageException
     * @throws NonDecodableFailureMessageException
     * @throws UnknownAssertionComparisonException
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

        $assertionData = $data['assertion'] ?? [];
        $comparison = $assertionData['comparison'] ?? '';

        if (Assertion::createsFromComparison($comparison)) {
            return Assertion::fromArray($assertionData);
        }

        if (ComparisonAssertion::createsFromComparison($comparison)) {
            return ComparisonAssertion::fromArray($assertionData);
        }

        throw new UnknownAssertionComparisonException($comparison, $assertionData, $assertionFailureMessage);
    }
}
