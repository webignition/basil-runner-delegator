<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Exception\AssertionFactory;

class UnknownAssertionComparisonException extends AbstractFailureMessageException
{
    private $assertionData;
    private $comparison;

    /**
     * @param string $comparison
     * @param array<mixed> $assertionData
     * @param string $failureMessage
     */
    public function __construct(string $comparison, array $assertionData, string $failureMessage)
    {
        $this->comparison = $comparison;
        $this->assertionData = $assertionData;

        parent::__construct($failureMessage, 'Unknown assertion comparison: "' . $comparison . '"');
    }

    public function getComparison(): string
    {
        return $this->comparison;
    }

    /**
     * @return array<mixed>
     */
    public function getAssertionData(): array
    {
        return $this->assertionData;
    }
}
