<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter;

use webignition\BasilRunner\Model\ActivityLine;
use webignition\BasilRunner\Model\KeyValueLine;
use webignition\BasilRunner\Model\SummaryLine;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\AssertionSummaryLineFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class AssertionSummaryLineFactoryTest extends AbstractBaseTest
{
    /**
     * @var AssertionSummaryLineFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new AssertionSummaryLineFactory(
            new ConsoleOutputFactory()
        );
    }

    /**
     * @dataProvider createForElementalExistenceAssertionDataProvider
     */
    public function testCreateForElementalExistenceAssertion(
        ElementIdentifierInterface $elementIdentifier,
        string $comparison,
        SummaryLine $expectedSummaryLine
    ) {
        $this->assertEquals(
            $expectedSummaryLine,
            $this->factory->createForElementalExistenceAssertion($elementIdentifier, $comparison)
        );
    }

    public function createForElementalExistenceAssertionDataProvider(): array
    {
        $consoleOutputFactory = new ConsoleOutputFactory();

        return [
            'non-derived non-descendant element exists assertion, CSS selector, default ordinal position' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'exists',
                'expectedSummaryLine' => $this->addChildrenToActivityLine(
                    new SummaryLine(sprintf(
                        'Element %s identified by:',
                        $consoleOutputFactory->createComment('$".selector"')
                    )),
                    [
                        new KeyValueLine(
                            'CSS selector',
                            $consoleOutputFactory->createComment('.selector')
                        ),
                        new KeyValueLine(
                            'ordinal position',
                            $consoleOutputFactory->createComment('1')
                        ),
                        (new ActivityLine(' ', 'does not exist'))->decreaseIndent()
                    ]
                ),
            ],
            'non-derived non-descendant element exists assertion, CSS selector, ordinal position 2' => [
                'elementIdentifier' => new ElementIdentifier('.selector', 2),
                'comparison' => 'exists',
                'expectedSummaryLine' => $this->addChildrenToActivityLine(
                    new SummaryLine(sprintf(
                        'Element %s identified by:',
                        $consoleOutputFactory->createComment('$".selector":2')
                    )),
                    [
                        new KeyValueLine(
                            'CSS selector',
                            $consoleOutputFactory->createComment('.selector')
                        ),
                        new KeyValueLine(
                            'ordinal position',
                            $consoleOutputFactory->createComment('2')
                        ),
                        (new ActivityLine(' ', 'does not exist'))->decreaseIndent()
                    ]
                ),
            ],
            'non-derived non-descendant attribute exists assertion, CSS selector, default ordinal position' => [
                'elementIdentifier' => new AttributeIdentifier('.selector', 'attribute_name'),
                'comparison' => 'exists',
                'expectedSummaryLine' => $this->addChildrenToActivityLine(
                    new SummaryLine(
                        sprintf(
                            'Attribute %s identified by:',
                            $consoleOutputFactory->createComment('$".selector".attribute_name')
                        )
                    ),
                    [
                        new KeyValueLine(
                            'CSS selector',
                            $consoleOutputFactory->createComment('.selector')
                        ),
                        new KeyValueLine(
                            'attribute name',
                            $consoleOutputFactory->createComment('attribute_name')
                        ),
                        new KeyValueLine(
                            'ordinal position',
                            $consoleOutputFactory->createComment('1')
                        ),
                        (new ActivityLine(' ', 'does not exist'))->decreaseIndent()
                    ]
                ),
            ],
            'non-derived non-descendant element exists assertion, XPath expression, default ordinal position' => [
                'elementIdentifier' => new ElementIdentifier('//div/h1'),
                'comparison' => 'exists',
                'expectedSummaryLine' => $this->addChildrenToActivityLine(
                    new SummaryLine(sprintf(
                        'Element %s identified by:',
                        $consoleOutputFactory->createComment('$"//div/h1"')
                    )),
                    [
                        new KeyValueLine(
                            'XPath expression',
                            $consoleOutputFactory->createComment('//div/h1')
                        ),
                        new KeyValueLine(
                            'ordinal position',
                            $consoleOutputFactory->createComment('1')
                        ),
                        (new ActivityLine(' ', 'does not exist'))->decreaseIndent()
                    ]
                ),
            ],
            'non-derived descendant element exists assertion (parent child)' => [
                'elementIdentifier' =>
                    (new ElementIdentifier('.child'))
                        ->withParentIdentifier(
                            new ElementIdentifier('.parent')
                        ),
                'comparison' => 'exists',
                'expectedSummaryLine' => $this->addChildrenToActivityLine(
                    new SummaryLine(sprintf(
                        'Element %s identified by:',
                        $consoleOutputFactory->createComment('$".parent" >> $".child"')
                    )),
                    [
                        new KeyValueLine(
                            'CSS selector',
                            $consoleOutputFactory->createComment('.child')
                        ),
                        new KeyValueLine(
                            'ordinal position',
                            $consoleOutputFactory->createComment('1')
                        ),
                        (new ActivityLine(' ', 'with parent:'))->decreaseIndent(),
                        new KeyValueLine(
                            'CSS selector',
                            $consoleOutputFactory->createComment('.parent')
                        ),
                        new KeyValueLine(
                            'ordinal position',
                            $consoleOutputFactory->createComment('1')
                        ),
                        (new ActivityLine(' ', 'does not exist'))->decreaseIndent()
                    ]
                ),
            ],
            'non-derived descendant element exists assertion (grandparent parent child)' => [
                'elementIdentifier' =>
                    (new ElementIdentifier('.child', 3))
                        ->withParentIdentifier(
                            (new ElementIdentifier('.parent', 4))
                                ->withParentIdentifier(
                                    new ElementIdentifier('.grandparent', 5)
                                )
                        ),
                'comparison' => 'exists',
                'expectedSummaryLine' => $this->addChildrenToActivityLine(
                    new SummaryLine(sprintf(
                        'Element %s identified by:',
                        $consoleOutputFactory->createComment('$".grandparent":5 >> $".parent":4 >> $".child":3')
                    )),
                    [
                        new KeyValueLine(
                            'CSS selector',
                            $consoleOutputFactory->createComment('.child')
                        ),
                        new KeyValueLine(
                            'ordinal position',
                            $consoleOutputFactory->createComment('3')
                        ),
                        (new ActivityLine(' ', 'with parent:'))->decreaseIndent(),
                        new KeyValueLine(
                            'CSS selector',
                            $consoleOutputFactory->createComment('.parent')
                        ),
                        new KeyValueLine(
                            'ordinal position',
                            $consoleOutputFactory->createComment('4')
                        ),
                        (new ActivityLine(' ', 'with parent:'))->decreaseIndent(),
                        new KeyValueLine(
                            'CSS selector',
                            $consoleOutputFactory->createComment('.grandparent')
                        ),
                        new KeyValueLine(
                            'ordinal position',
                            $consoleOutputFactory->createComment('5')
                        ),
                        (new ActivityLine(' ', 'does not exist'))->decreaseIndent()
                    ]
                ),
            ],
            'non-derived non-descendant element not-exists assertion' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'not-exists',
                'expectedSummaryLine' => $this->addChildrenToActivityLine(
                    new SummaryLine(sprintf(
                        'Element %s identified by:',
                        $consoleOutputFactory->createComment('$".selector"')
                    )),
                    [
                        new KeyValueLine(
                            'CSS selector',
                            $consoleOutputFactory->createComment('.selector')
                        ),
                        new KeyValueLine(
                            'ordinal position',
                            $consoleOutputFactory->createComment('1')
                        ),
                        (new ActivityLine(' ', 'does exist'))->decreaseIndent()
                    ]
                ),
            ],
        ];
    }

    /**
     * @dataProvider createForElementalToScalarComparisonAssertionDataProvider
     */
    public function testCreateForElementalToScalarComparisonAssertion(
        ElementIdentifierInterface $elementIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue,
        SummaryLine $expectedSummaryLine
    ) {
        $this->assertEquals(
            $expectedSummaryLine,
            $this->factory->createForElementalToScalarComparisonAssertion(
                $elementIdentifier,
                $comparison,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function createForElementalToScalarComparisonAssertionDataProvider(): array
    {
        $consoleOutputFactory = new ConsoleOutputFactory();

        return [
            'is' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummaryLine' => $this->addChildrenToActivityLine(
                    new SummaryLine(sprintf(
                        'Element %s identified by:',
                        $consoleOutputFactory->createComment('$".selector"')
                    )),
                    [
                        new KeyValueLine(
                            'CSS selector',
                            $consoleOutputFactory->createComment('.selector')
                        ),
                        new KeyValueLine(
                            'ordinal position',
                            $consoleOutputFactory->createComment('1')
                        ),
                        $this->addChildrenToActivityLine(
                            (new ActivityLine(
                                ' ',
                                'is not equal to expected value'
                            ))->decreaseIndent(),
                            [
                                (new KeyValueLine(
                                    'expected',
                                    $consoleOutputFactory->createComment('expected')
                                ))->decreaseIndent(),
                                (new KeyValueLine(
                                    'actual',
                                    '  ' . $consoleOutputFactory->createComment('actual')
                                ))->decreaseIndent(),
                            ]
                        )
                    ]
                ),
            ],
        ];
    }

    /**
     * @dataProvider createForScalarToScalarComparisonAssertionDataProvider
     */
    public function testCreateScalarToScalarComparisonAssertion(
        string $identifier,
        string $comparison,
        string $expectedValue,
        string $actualValue,
        SummaryLine $expectedSummaryLine
    ) {
        $this->assertEquals(
            $expectedSummaryLine,
            $this->factory->createForScalarToScalarComparisonAssertion(
                $identifier,
                $comparison,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function createForScalarToScalarComparisonAssertionDataProvider(): array
    {
        $consoleOutputFactory = new ConsoleOutputFactory();

        return [
            'is' => [
                'identifier' => '$page.title',
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummaryLine' => $this->addChildrenToActivityLine(
                    new SummaryLine('$page.title is not equal to expected value'),
                    [
                        new KeyValueLine(
                            'expected',
                            $consoleOutputFactory->createComment('expected')
                        ),
                        new KeyValueLine(
                            'actual',
                            '  ' . $consoleOutputFactory->createComment('actual')
                        ),
                    ]
                ),
            ],
        ];
    }

    /**
     * @param ActivityLine $summaryLine
     * @param ActivityLine[] $children
     *
     * @return ActivityLine
     */
    private function addChildrenToActivityLine(ActivityLine $summaryLine, array $children): ActivityLine
    {
        foreach ($children as $child) {
            $summaryLine->addChild($child);
        }

        return $summaryLine;
    }
}
