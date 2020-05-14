<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Runner\BaseTestRunner;
use PHPUnit\Util\Printer;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilDomIdentifierFactory\Factory as DomIdentifierFactory;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryLineFactory;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryHandler;

class ResultPrinter extends Printer implements TestListener
{
    private const INDENT = '  ';

    /**
     * @var ConsoleOutputFactory
     */
    private $consoleOutputFactory;

    /**
     * @var ActivityLineFactory
     */
    private $activityLineFactory;

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
     * @var SummaryHandler
     */
    private $failedAssertionSummaryHandler;

    public function __construct($out = null)
    {
        parent::__construct($out);

        $projectRootPath = (ProjectRootPathProvider::create())->get();

        $consoleOutputFactory = new ConsoleOutputFactory();

        $this->projectRootPath = $projectRootPath;
        $this->isFirstTest = true;
        $this->consoleOutputFactory = $consoleOutputFactory;
        $this->activityLineFactory = new ActivityLineFactory($consoleOutputFactory);
        $this->failedAssertionSummaryHandler = new SummaryHandler(
            DomIdentifierFactory::createFactory(),
            new SummaryLineFactory($consoleOutputFactory)
        );
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

                $this->write($this->consoleOutputFactory->createTestPath($relativePath));
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
            $testEndStatus = $test->getStatus();

            $stepNameLine = $this->activityLineFactory->createStepNameLine($test);
            $this->write($this->indent($stepNameLine) . "\n");

            $handledStatements = $test->getHandledStatements();
            $failedStatement = null;

            if (BaseTestRunner::STATUS_PASSED !== $testEndStatus) {
                $failedStatement = array_pop($handledStatements);
            }

            $completedStatementLines = [];
            foreach ($handledStatements as $statement) {
                $completedStatementLine = $this->activityLineFactory->createCompletedStatementLine($statement);

                $completedStatementLines[] = $this->indent($completedStatementLine, 2);
            }

            $completedStatementLinesString = implode("\n", $completedStatementLines);

            $this->write($completedStatementLinesString);

            if ($failedStatement instanceof StatementInterface) {
                if (0 !== count($completedStatementLines)) {
                    $this->write("\n");
                }

                $failedStatementLine = $this->activityLineFactory->createFailedStatementLine($failedStatement);
                $this->write($this->indent($failedStatementLine, 2) . "\n");

                $summaryActivityLine = null;

                if ($failedStatement instanceof AssertionInterface) {
                    $summaryActivityLine = $this->failedAssertionSummaryHandler->handle(
                        $failedStatement,
                        (string) $test->getExpectedValue(),
                        (string) $test->getExaminedValue()
                    );
                }

                if (is_string($summaryActivityLine)) {
                    $this->write($this->indent($summaryActivityLine, 2));
                }
            }

            $this->writeEmptyLine();
        }
    }

    private function indent(string $content, int $depth = 1): string
    {
        $indentContent = str_repeat(self::INDENT, $depth);

        $lines = explode("\n", $content);

        array_walk($lines, function (&$line) use ($indentContent) {
            $line = $indentContent . $line;
        });

        return implode("\n", $lines);
    }

    private function writeEmptyLine(): void
    {
        $this->write("\n");
    }
}
