<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Runner\BaseTestRunner;
use PHPUnit\Util\Printer;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BaseBasilTestCase\Statement;
use webignition\BasilRunner\Model\TerminalString;
use webignition\BasilRunner\Services\ProjectRootPathProvider;

class ResultPrinter extends Printer implements TestListener
{
    private const DEFAULT_ICON = '?';

    /**
     * @var string
     */
    private $projectRootPath = '';

    /**
     * @var string
     */
    private $currentTestPath = '';

    /**
     * @var bool
     */
    private $isFirstTest;

    /**
     * @var array<int, string>
     */
    private $icons = [
        BaseTestRunner::STATUS_PASSED => '✓',
        BaseTestRunner::STATUS_FAILURE => 'x',
    ];

    public function __construct($out = null)
    {
        parent::__construct($out);

        $projectRootPath = (ProjectRootPathProvider::create())->get();

        $this->projectRootPath = $projectRootPath;
        $this->isFirstTest = true;
    }

    /**
     * @inheritDoc
     */
    public function addError(Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addError() method.
    }

    /**
     * @inheritDoc
     */
    public function addWarning(Test $test, Warning $e, float $time): void
    {
        // TODO: Implement addWarning() method.
    }

    /**
     * @inheritDoc
     */
    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
        // TODO: Implement addFailure() method.
    }

    /**
     * @inheritDoc
     */
    public function addIncompleteTest(Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addIncompleteTest() method.
    }

    /**
     * @inheritDoc
     */
    public function addRiskyTest(Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addRiskyTest() method.
    }

    /**
     * @inheritDoc
     */
    public function addSkippedTest(Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addSkippedTest() method.
    }

    /**
     * @param TestSuite<Test> $suite
     */
    public function startTestSuite(TestSuite $suite): void
    {
        // TODO: Implement startTestSuite() method.
    }

    /**
     * @param TestSuite<Test> $suite
     */
    public function endTestSuite(TestSuite $suite): void
    {
        // TODO: Implement endTestSuite() method.
    }

    /**
     * @inheritDoc
     */
    public function startTest(Test $test): void
    {
        if ($test instanceof BasilTestCaseInterface) {
            $testPath = $test::getBasilTestPath();

            if ($this->currentTestPath !== $testPath) {
                $this->currentTestPath = $testPath;

                $relativePath = substr($testPath, strlen($this->projectRootPath) + 1);

                if (false === $this->isFirstTest) {
                    $this->writeEmptyLine();
                }

                $testPathTerminalString = (new TerminalString($relativePath))
                    ->withDecoration(TerminalString::DECORATION_BOLD);

                $this->write((string) $testPathTerminalString);
                $this->writeEmptyLine();

                if ($this->isFirstTest) {
                    $this->isFirstTest = false;
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function endTest(Test $test, float $time): void
    {
        if ($test instanceof BasilTestCaseInterface) {
            $testEndStatus = $this->getTestEndStatus($test);

            $this->write($this->createStepName($test));
            $this->writeEmptyLine();

            foreach ($test->getCompletedStatements() as $statement) {
                $this->write($this->decorateCompletedStatement($statement));
                $this->writeEmptyLine();
            }

            if (BaseTestRunner::STATUS_PASSED !== $testEndStatus) {
                $failedStatement = $test->getCurrentStatement();

                if ($failedStatement instanceof Statement) {
                    $this->write($this->decorateFailedStatement($failedStatement));
                    $this->writeEmptyLine();
                }
            }
        }
    }

    private function createStepName(BasilTestCaseInterface $test): string
    {
        $testEndStatus = $this->getTestEndStatus($test);

        $stepNameContent = sprintf(
            '  %s %s',
            $this->getEndTestIcon($test),
            $test->getBasilStepName()
        );

        $contentColour = BaseTestRunner::STATUS_PASSED === $testEndStatus
            ? TerminalString::COLOUR_GREEN
            : TerminalString::COLOUR_RED;

        return (string) (new TerminalString($stepNameContent))->withForegroundColour($contentColour);
    }

    private function decorateCompletedStatement(Statement $statement): string
    {
        $icon = $this->icons[BaseTestRunner::STATUS_PASSED];
        $iconContent = (string) (new TerminalString($icon))->withForegroundColour(TerminalString::COLOUR_GREEN);

        return '     ' . $iconContent . ' ' . $statement->getContent();
    }

    private function decorateFailedStatement(Statement $statement): string
    {
        $icon = $this->icons[BaseTestRunner::STATUS_FAILURE];
        $iconContent = (string) (new TerminalString($icon))->withForegroundColour(TerminalString::COLOUR_RED);

        $lead = '     ' . $iconContent . ' ';

        return $lead . (string) (new TerminalString($statement->getContent()))
            ->withForegroundColour(TerminalString::COLOUR_WHITE)
            ->withBackgroundColour(TerminalString::COLOUR_RED);
    }

    private function getEndTestIcon(Test $test): string
    {
        return $this->icons[$this->getTestEndStatus($test)] ?? self::DEFAULT_ICON;
    }

    private function getTestEndStatus(Test $test): int
    {
        if ($test instanceof TestCase || $test instanceof BasilTestCaseInterface) {
            return $test->getStatus();
        }

        return BaseTestRunner::STATUS_UNKNOWN;
    }

    private function writeEmptyLine(): void
    {
        $this->write("\n");
    }
}
