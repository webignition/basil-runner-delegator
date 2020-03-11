<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BaseBasilTestCase\StatementInterface;
use webignition\BasilRunner\Model\ActivityLine;
use webignition\BasilRunner\Model\TerminalString\Style;

class ActivityLineFactory
{
    private const DEFAULT_ICON = '?';

    /**
     * @var array<int, string>
     */
    private $icons = [
        BaseTestRunner::STATUS_PASSED => '✓',
        BaseTestRunner::STATUS_FAILURE => 'x',
    ];

    public function createStepNameLine(BasilTestCaseInterface $test): ActivityLine
    {
        $testEndStatus = $test->getStatus();

        $icon = $this->icons[$testEndStatus] ?? self::DEFAULT_ICON;
        $content = $test->getBasilStepName();

        $style = new Style([
            Style::FOREGROUND_COLOUR => BaseTestRunner::STATUS_PASSED === $testEndStatus
                ? Style::COLOUR_GREEN
                : Style::COLOUR_RED,
        ]);

        return new ActivityLine($icon, $style, $content, $style);
    }

    public function createCompletedStatementLine(StatementInterface $statement): ActivityLine
    {
        return new ActivityLine(
            $this->icons[BaseTestRunner::STATUS_PASSED],
            new Style([
                Style::FOREGROUND_COLOUR => Style::COLOUR_GREEN,
            ]),
            $statement->getContent(),
            new Style()
        );
    }

    public function createFailedStatementLine(StatementInterface $statement): ActivityLine
    {
        return new ActivityLine(
            $this->icons[BaseTestRunner::STATUS_FAILURE],
            new Style([
                Style::FOREGROUND_COLOUR => Style::COLOUR_RED,
            ]),
            $statement->getContent(),
            new Style([
                Style::FOREGROUND_COLOUR => Style::COLOUR_WHITE,
                Style::BACKGROUND_COLOUR => Style::COLOUR_RED,
            ])
        );
    }
}
