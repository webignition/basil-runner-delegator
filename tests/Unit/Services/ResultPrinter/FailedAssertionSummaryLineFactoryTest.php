<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter;

use webignition\BasilRunner\Model\ActivityLine;
use webignition\BasilRunner\Model\KeyValueLine;
use webignition\BasilRunner\Model\SummaryLine;
use webignition\BasilRunner\Model\TerminalString\TerminalString;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertionSummaryLineFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class FailedAssertionSummaryLineFactoryTest extends AbstractBaseTest
{
    /**
     * @var FailedAssertionSummaryLineFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new FailedAssertionSummaryLineFactory();
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        ElementIdentifierInterface $elementIdentifier,
        SummaryLine $expectedSummaryLine
    ) {
        $this->assertEquals($expectedSummaryLine, $this->factory->create($elementIdentifier));
    }

    public function createDataProvider(): array
    {
        return [
            'non-derived non-descendant element exists assertion, CSS selector, default ordinal position' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'expectedSummaryLine' => $this->addChildrenToSummaryLine(
                    new SummaryLine(
                        new TerminalString('Element identified by:')
                    ),
                    [
                        new KeyValueLine('CSS selector', '.selector'),
                        new KeyValueLine('ordinal position', '1'),
                        (new ActivityLine(
                            new TerminalString(' '),
                            new TerminalString('does not exist')
                        ))->decreaseIndent()
                    ]
                ),
            ],
            'non-derived non-descendant element exists assertion, CSS selector, ordinal position 2' => [
                'elementIdentifier' => new ElementIdentifier('.selector', 2),
                'expectedSummaryLine' => $this->addChildrenToSummaryLine(
                    new SummaryLine(
                        new TerminalString('Element identified by:')
                    ),
                    [
                        new KeyValueLine('CSS selector', '.selector'),
                        new KeyValueLine('ordinal position', '2'),
                        (new ActivityLine(
                            new TerminalString(' '),
                            new TerminalString('does not exist')
                        ))->decreaseIndent()
                    ]
                ),
            ],
            'non-derived non-descendant attribute exists assertion, CSS selector, default ordinal position' => [
                'elementIdentifier' => new AttributeIdentifier('.selector', 'attribute_name'),
                'expectedSummaryLine' => $this->addChildrenToSummaryLine(
                    new SummaryLine(
                        new TerminalString('Attribute identified by:')
                    ),
                    [
                        new KeyValueLine('CSS selector', '.selector'),
                        new KeyValueLine('attribute name', 'attribute_name'),
                        new KeyValueLine('ordinal position', '1'),
                        (new ActivityLine(
                            new TerminalString(' '),
                            new TerminalString('does not exist')
                        ))->decreaseIndent()
                    ]
                ),
            ],
            'non-derived non-descendant element exists assertion, XPath expression, default ordinal position' => [
                'elementIdentifier' => new ElementIdentifier('//div/h1'),
                'expectedSummaryLine' => $this->addChildrenToSummaryLine(
                    new SummaryLine(
                        new TerminalString('Element identified by:')
                    ),
                    [
                        new KeyValueLine('XPath expression', '//div/h1'),
                        new KeyValueLine('ordinal position', '1'),
                        (new ActivityLine(
                            new TerminalString(' '),
                            new TerminalString('does not exist')
                        ))->decreaseIndent()
                    ]
                ),
            ],
            'non-derived descendant element exists assertion (parent child)' => [
                'elementIdentifier' =>
                    (new ElementIdentifier('.child'))
                        ->withParentIdentifier(
                            new ElementIdentifier('.parent')
                        ),
                'expectedSummaryLine' => $this->addChildrenToSummaryLine(
                    new SummaryLine(
                        new TerminalString('Element identified by:')
                    ),
                    [
                        new KeyValueLine('CSS selector', '.child'),
                        new KeyValueLine('ordinal position', '1'),
                        (new ActivityLine(
                            new TerminalString(' '),
                            new TerminalString('with parent:')
                        ))->decreaseIndent(),
                        new KeyValueLine('CSS selector', '.parent'),
                        new KeyValueLine('ordinal position', '1'),
                        (new ActivityLine(
                            new TerminalString(' '),
                            new TerminalString('does not exist')
                        ))->decreaseIndent()
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
                'expectedSummaryLine' => $this->addChildrenToSummaryLine(
                    new SummaryLine(
                        new TerminalString('Element identified by:')
                    ),
                    [
                        new KeyValueLine('CSS selector', '.child'),
                        new KeyValueLine('ordinal position', '3'),
                        (new ActivityLine(
                            new TerminalString(' '),
                            new TerminalString('with parent:')
                        ))->decreaseIndent(),
                        new KeyValueLine('CSS selector', '.parent'),
                        new KeyValueLine('ordinal position', '4'),
                        (new ActivityLine(
                            new TerminalString(' '),
                            new TerminalString('with parent:')
                        ))->decreaseIndent(),
                        new KeyValueLine('CSS selector', '.grandparent'),
                        new KeyValueLine('ordinal position', '5'),
                        (new ActivityLine(
                            new TerminalString(' '),
                            new TerminalString('does not exist')
                        ))->decreaseIndent()
                    ]
                ),
            ],
        ];
    }

    /**
     * @param SummaryLine $summaryLine
     * @param ActivityLine[] $children
     *
     * @return SummaryLine
     */
    private function addChildrenToSummaryLine(SummaryLine $summaryLine, array $children): SummaryLine
    {
        foreach ($children as $child) {
            $summaryLine->addChild($child);
        }

        return $summaryLine;
    }
}
