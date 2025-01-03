<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cevinio\BehatJenkinsJUnit\Printer;

use Behat\Behat\Tester\Result\StepResult;
use Behat\Behat\Output\Node\Printer\StepPrinter;
use Behat\Gherkin\Node\ScenarioLikeInterface as Scenario;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\Exception\ExceptionPresenter;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Tester\Result\TestResult;
use Behat\Testwork\Tester\Result\ExceptionResult;

/**
 * Prints step with optional results.
 *
 * @author Wouter J <wouter@wouterj.nl>
 * @author James Watson <james@sitepulse.org>
 */
class JJUnitStepPrinter implements StepPrinter
{
    public function __construct(
        private readonly ExceptionPresenter $exceptionPresenter,
    ) {
    }

    /**
     * Prints step using provided printer.
     *
     * @param Formatter  $formatter
     * @param Scenario   $scenario
     * @param StepNode   $step
     * @param StepResult $result
     */
    public function printStep(Formatter $formatter, Scenario $scenario, StepNode $step, StepResult $result): void
    {
        /** @var JJUnitOutputPrinter $outputPrinter */
        $outputPrinter = $formatter->getOutputPrinter();

        $node = match ($result->getResultCode()) {
            TestResult::PASSED, TestResult::SKIPPED => null,
            TestResult::PENDING, TestResult::UNDEFINED => 'error',
            TestResult::FAILED => 'failure',
        };

        if (null === $node) {
            return;
        }

        $message = '[L ' . $step->getLine() . '] ' . trim($step->getKeyword() . ' ' . $step->getText());
        $exception = null;

        if ($result instanceof ExceptionResult && $result->hasException()) {
            $exception = $this->exceptionPresenter->presentException($result->getException());
        }

        $outputPrinter->addTestcaseChild($node, [ 'message' => $message ], $exception);
    }
}
