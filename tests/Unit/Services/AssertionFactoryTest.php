<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services;

use webignition\BasilModels\Assertion\Assertion;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\Assertion\ComparisonAssertion;
use webignition\BasilRunner\Exception\AssertionFactory\MalformedFailureMessageException;
use webignition\BasilRunner\Exception\AssertionFactory\NonDecodableFailureMessageException;
use webignition\BasilRunner\Services\AssertionFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class AssertionFactoryTest extends AbstractBaseTest
{
    /**
     * @var AssertionFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = AssertionFactory::createFactory();
    }

    /**
     * @dataProvider createFromAssertionFailureMessageDataProvider
     */
    public function testCreateFromAssertionFailureMessage(
        string $failureMessage,
        AssertionInterface $expectedAssertion
    ) {
        $this->assertEquals(
            $expectedAssertion,
            $this->factory->createFromAssertionFailureMessage($failureMessage)
        );
    }

    public function createFromAssertionFailureMessageDataProvider(): array
    {
        return [
            'is comparison' => [
                'failureMessage' => '{
            "assertion": {
                "source": "$\"a\" is \"value\"",
                "identifier": "$\"a\"",
                "comparison": "is",
                "value": "\"value\""
            }
        }',
                'expectedAssertion' => new ComparisonAssertion('$"a" is "value"', '$"a"', 'is', '"value"'),
            ],
            'is-not comparison' => [
                'failureMessage' => '{
            "assertion": {
                "source": "$\"a\" is-not \"value\"",
                "identifier": "$\"a\"",
                "comparison": "is-not",
                "value": "\"value\""
            }
        }',
                'expectedAssertion' => new ComparisonAssertion('$"a" is-not "value"', '$"a"', 'is-not', '"value"'),
            ],
            'exists comparison' => [
                'failureMessage' => '{
            "assertion": {
                "source": "$\"a\" exists",
                "identifier": "$\"a\"",
                "comparison": "exists"
            }
        }',
                'expectedAssertion' => new Assertion('$"a" exists', '$"a"', 'exists'),
            ],
            'not-exists comparison' => [
                'failureMessage' => '{
            "assertion": {
                "source": "$\"a\" not-exists",
                "identifier": "$\"a\"",
                "comparison": "not-exists"
            }
        }',
                'expectedAssertion' => new Assertion('$"a" not-exists', '$"a"', 'not-exists'),
            ],
            'includes comparison' => [
                'failureMessage' => '{
            "assertion": {
                "source": "$\"a\" includes \"value\"",
                "identifier": "$\"a\"",
                "comparison": "includes",
                "value": "\"value\""
            }
        }',
                'expectedAssertion' => new ComparisonAssertion('$"a" includes "value"', '$"a"', 'includes', '"value"'),
            ],
            'excludes comparison' => [
                'failureMessage' => '{
            "assertion": {
                "source": "$\"a\" excludes \"value\"",
                "identifier": "$\"a\"",
                "comparison": "excludes",
                "value": "\"value\""
            }
        }',
                'expectedAssertion' => new ComparisonAssertion('$"a" excludes "value"', '$"a"', 'excludes', '"value"'),
            ],
            'matches comparison' => [
                'failureMessage' => '{
            "assertion": {
                "source": "$\"a\" matches \"value\"",
                "identifier": "$\"a\"",
                "comparison": "matches",
                "value": "\"value\""
            }
        }',
                'expectedAssertion' => new ComparisonAssertion('$"a" matches "value"', '$"a"', 'matches', '"value"'),
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

    /**
     * @dataProvider createFromAssertionFailureMessageThrowsNonDecodableFailureMessageExceptionDataProvider
     */
    public function testCreateFromAssertionFailureMessageThrowsNonDecodableFailureMessageException(
        string $failureMessage
    ) {
        try {
            $this->factory->createFromAssertionFailureMessage($failureMessage);
            $this->fail('NonDecodableFailureMessageException not thrown');
        } catch (NonDecodableFailureMessageException $nonDecodableFailureMessageException) {
            $this->assertSame($failureMessage, $nonDecodableFailureMessageException->getFailureMessage());
        }
    }

    public function createFromAssertionFailureMessageThrowsNonDecodableFailureMessageExceptionDataProvider(): array
    {
        return [
            'not json object' => [
                'failureMessage' => '{a}',
            ],
        ];
    }
}
