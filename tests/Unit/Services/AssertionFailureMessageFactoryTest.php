<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services;

use webignition\BasilAssertionFailureMessage\AssertionFailureMessage;
use webignition\BasilAssertionFailureMessage\FailureMessageException;
use webignition\BasilModels\Assertion\Assertion;
use webignition\BasilModels\Assertion\ComparisonAssertion;
use webignition\BasilModels\Assertion\Factory\UnknownComparisonException;
use webignition\BasilRunner\Exception\AssertionFailureMessageParseException;
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
     * @dataProvider createDataProvider
     */
    public function testCreate(
        string $failureMessage,
        AssertionFailureMessage $expectedAssertionFailureMessage
    ) {
        $this->assertEquals(
            $expectedAssertionFailureMessage,
            $this->factory->create($failureMessage)
        );
    }

    public function createDataProvider(): array
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
     * @dataProvider createThrowsAssertionFailureMessageParseExceptionDataProvider
     */
    public function testCreateThrowsAssertionFailureMessageParseException(
        string $failureMessage,
        AssertionFailureMessageParseException $expectedException
    ) {
        try {
            $this->factory->create($failureMessage);
            $this->fail('AssertionFailureMessageParseException not thrown');
        } catch (AssertionFailureMessageParseException $exception) {
            $this->assertEquals($expectedException, $exception);
            $this->assertSame($failureMessage, $exception->getFailureMessage());
        }
    }

    public function createThrowsAssertionFailureMessageParseExceptionDataProvider(): array
    {
        return [
            'empty' => [
                'failureMessage' => '',
                'expectedException' =>
                    AssertionFailureMessageParseException::createMalformedFailureMessageException(''),
            ],
            'non-json' => [
                'failureMessage' => 'This is not json',
                'expectedException' =>
                    AssertionFailureMessageParseException::createMalformedFailureMessageException('This is not json'),
            ],
            'foo' => [
                'failureMessage' => json_encode([
                    'assertion' => [],
                ]),
                'expectedException' =>
                    AssertionFailureMessageParseException::createMalformedDataException(
                        '{"assertion":[]}',
                        FailureMessageException::createMalformedAssertionException(
                            '{"assertion":[]}',
                            new UnknownComparisonException([], '')
                        )
                    ),
            ],
        ];
    }
}
