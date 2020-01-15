<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services;

use webignition\BasilDataValidator\Action\ActionValidator;
use webignition\BasilDataValidator\Assertion\AssertionValidator;
use webignition\BasilDataValidator\ResultType;
use webignition\BasilDataValidator\Step\StepValidator;
use webignition\BasilDataValidator\Test\TestValidator;
use webignition\BasilDataValidator\ValueValidator;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilParser\StepParser;
use webignition\BasilParser\Test\TestParser;
use webignition\BasilRunner\Services\ValidatorInvalidResultSerializer;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\BasilValidationResult\InvalidResultInterface;

class ValidatorInvalidResultSerializerTest extends AbstractBaseTest
{
    /**
     * @var ValidatorInvalidResultSerializer
     */
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = new ValidatorInvalidResultSerializer();
    }

    /**
     * @dataProvider serializeToArrayDataProvider
     *
     * @param InvalidResultInterface $invalidResult
     * @param array<mixed> $expectedData
     */
    public function testSerializeToArray(InvalidResultInterface $invalidResult, array $expectedData)
    {
        $this->assertSame($expectedData, $this->serializer->serializeToArray($invalidResult));
    }

    public function serializeToArrayDataProvider(): array
    {
        $actionParser = ActionParser::create();
        $actionValidator = ActionValidator::create();
        $assertionParser = AssertionParser::create();
        $assertionValidator = AssertionValidator::create();
        $stepParser = StepParser::create();
        $stepValidator = StepValidator::create();
        $testParser = TestParser::create();
        $testValidator = TestValidator::create();

        $actionWithInvalidIdentifier = $actionParser->parse('click $".selector".attribute_name');
        $actionWithInvalidIdentifierResult = $actionValidator->validate($actionWithInvalidIdentifier);

        $actionWithInvalidValue = $actionParser->parse('set $".selector" to $page.invalid');
        $actionWithInvalidValueResult = $actionValidator->validate($actionWithInvalidValue);

        $assertionWithInvalidComparison = $assertionParser->parse('$".button" glows');
        $assertionWithInvalidComparisonResult = $assertionValidator->validate($assertionWithInvalidComparison);

        $stepWithInvalidAction = $stepParser->parse([
            'actions' => [
                (string) $actionWithInvalidValue,
            ],
            'assertions' => [
                '$page.url is "http://example.com"',
            ],
        ]);
        $stepWithInvalidActionResult = $stepValidator->validate($stepWithInvalidAction);

        $testWithStpWithInvalidAction = $testParser->parse([
            'config' => [
                'url' => 'http://example.com',
                'browser' => 'chrome',
            ],
            'invalid step name' => [
                'actions' => [
                    (string) $actionWithInvalidValue,
                ],
                'assertions' => [
                    '$page.url is "http://example.com"',
                ],
            ],
        ]);
        $testWithStpWithInvalidActionResult = $testValidator->validate($testWithStpWithInvalidAction);

        return [
            'action, no context, no previous' => [
                'invalidResult' => $actionWithInvalidIdentifierResult,
                'expectedData' => [
                    'type' => ResultType::ACTION,
                    'reason' => ActionValidator::REASON_INVALID_IDENTIFIER,
                    'subject' => '"click $\".selector\".attribute_name"',
                ],
            ],
            'action, has previous' => [
                'invalidResult' => $actionWithInvalidValueResult,
                'expectedData' => [
                    'type' => ResultType::ACTION,
                    'reason' => ActionValidator::REASON_INVALID_VALUE,
                    'subject' => '"set $\".selector\" to $page.invalid"',
                    'previous' => [
                        'type' => ResultType::VALUE,
                        'reason' => ValueValidator::REASON_PROPERTY_INVALID,
                        'subject' => '"$page.invalid"',
                    ],
                ],
            ],
            'assertion, has context' => [
                'invalidResult' => $assertionWithInvalidComparisonResult,
                'expectedData' => [
                    'type' => ResultType::ASSERTION,
                    'reason' => AssertionValidator::REASON_INVALID_COMPARISON,
                    'context' => [
                        'comparison' => 'glows',
                    ],
                    'subject' => '"$\".button\" glows"',
                ],
            ],
            'step with invalid action' => [
                'invalidResult' => $stepWithInvalidActionResult,
                'expectedData' => [
                    'type' => ResultType::STEP,
                    'reason' => StepValidator::REASON_INVALID_ACTION,
                    'previous' => [
                        'type' => ResultType::ACTION,
                        'reason' => ActionValidator::REASON_INVALID_VALUE,
                        'subject' => '"set $\".selector\" to $page.invalid"',
                        'previous' => [
                            'type' => ResultType::VALUE,
                            'reason' => ValueValidator::REASON_PROPERTY_INVALID,
                            'subject' => '"$page.invalid"',
                        ],
                    ],
                ],
            ],
            'test with step with invalid action' => [
                'invalidResult' => $testWithStpWithInvalidActionResult,
                'expectedData' => [
                    'type' => ResultType::TEST,
                    'reason' => TestValidator::REASON_STEP_INVALID,
                    'context' => [
                        'step-name' => 'invalid step name',
                    ],
                    'previous' => [
                        'type' => ResultType::STEP,
                        'reason' => StepValidator::REASON_INVALID_ACTION,
                        'previous' => [
                            'type' => ResultType::ACTION,
                            'reason' => ActionValidator::REASON_INVALID_VALUE,
                            'subject' => '"set $\".selector\" to $page.invalid"',
                            'previous' => [
                                'type' => ResultType::VALUE,
                                'reason' => ValueValidator::REASON_PROPERTY_INVALID,
                                'subject' => '"$page.invalid"',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
