<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services;

use webignition\BasilAssertionFailureMessage\AssertionFailureMessage;
use webignition\BasilModels\Assertion\Assertion;
use webignition\BasilModels\Assertion\ComparisonAssertion;
use webignition\BasilRunner\Exception\AssertionFactory\MalformedFailureMessageException;
use webignition\BasilRunner\Services\AssertionFailureMessageFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class AssertionFailureMessageFactoryTest extends AbstractBaseTest
{
    /**
     * @var AssertionFailureMessageFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = AssertionFailureMessageFactory::createFactory();
    }

    /**
     * @dataProvider createFromAssertionFailureMessageDataProvider
     */
    public function testCreateFromAssertionFailureMessage(
        string $failureMessage,
        AssertionFailureMessage $expectedAssertionFailureMessage
    ) {
        $this->assertEquals(
            $expectedAssertionFailureMessage,
            $this->factory->createFromAssertionFailureMessage($failureMessage)
        );
    }

    public function createFromAssertionFailureMessageDataProvider(): array
    {
        $existsAssertion = new Assertion(
            '$".selector" exists',
            '$".selector"',
            'exists'
        );

        $isAssertion = new ComparisonAssertion(
            '$".selector" is "value"',
            '$".selector"',
            'is',
            '"value"'
        );

        $existsAssertionFailureMessage = new AssertionFailureMessage($existsAssertion);
        $derivedExistsAssertionFailureMessage = new AssertionFailureMessage($existsAssertion, $isAssertion);

        $phpUnitSuffix = 'Failed asserting that false is true.';

        return [
            'without derivation source' => [
                'failureMessage' =>
                    json_encode($existsAssertionFailureMessage, JSON_PRETTY_PRINT) . "\n" .
                    $phpUnitSuffix,
                'expectedAssertionFailureMessage' => $existsAssertionFailureMessage,
            ],
            'with derivation source' => [
                'failureMessage' =>
                    json_encode($derivedExistsAssertionFailureMessage, JSON_PRETTY_PRINT) . "\n" .
                    $phpUnitSuffix,
                'expectedAssertionFailureMessage' => $derivedExistsAssertionFailureMessage,
            ],
        ];
    }

    /**
     * @dataProvider createFromAssertionFailureMessageThrowsMalformedFailureMessageExceptionDataProvider
     */
    public function testCreateFromAssertionFailureMessageThrowsMalformedFailureMessageException(
        string $failureMessage
    ) {
        try {
            $this->factory->createFromAssertionFailureMessage($failureMessage);
            $this->fail('MalformedFailureMessageException not thrown');
        } catch (MalformedFailureMessageException $malformedFailureMessageException) {
            $this->assertSame($failureMessage, $malformedFailureMessageException->getFailureMessage());
        }
    }

    public function createFromAssertionFailureMessageThrowsMalformedFailureMessageExceptionDataProvider(): array
    {
        return [
            'empty' => [
                'failureMessage' => '',
            ],
            'non-json' => [
                'failureMessage' => 'This is not json',
            ],
        ];
    }
}
