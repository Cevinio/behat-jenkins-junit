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

use Behat\Behat\Output\Node\EventListener\JUnit\JUnitDurationListener;
use Behat\Behat\Output\Statistics\PhaseStatistics;
use Behat\Behat\Output\Node\Printer\FeaturePrinter;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Tester\Result\TestResult;
use Cevinio\BehatJenkinsJUnit\Formatter\JJUnitPathParser;

/**
 * Prints the <testsuite> element.
 *
 * @author Wouter J <wouter@wouterj.nl>
 */
final class JJUnitFeaturePrinter implements FeaturePrinter
{
    private ?FeatureNode $currentFeature;

    public function __construct(
        private readonly PhaseStatistics $statistics,
        private readonly JJUnitPathParser $pathParser,
        private readonly ?JUnitDurationListener $durationListener = null,
    ) {
    }

    public function printHeader(Formatter $formatter, FeatureNode $feature): void
    {
        $this->statistics->reset();
        $this->currentFeature = $feature;
        /** @var JJUnitOutputPrinter $outputPrinter */
        $outputPrinter = $formatter->getOutputPrinter();
        $outputPrinter->addTestsuite();
    }

    public function printFooter(Formatter $formatter, TestResult $result): void
    {
        $stats = $this->statistics->getScenarioStatCounts();

        if (0 === count($stats)) {
            $totalCount = 0;
        } else {
            $totalCount = (int) array_sum($stats);
        }

        /** @var JJUnitOutputPrinter $outputPrinter */
        $outputPrinter = $formatter->getOutputPrinter();

        $outputPrinter->extendTestsuiteAttributes([
            'name' => $this->pathParser->strip($this->currentFeature->getFile()),
            'tests' => $totalCount,
            'skipped' => $stats[TestResult::SKIPPED],
            'failures' => $stats[TestResult::FAILED],
            'errors' => $stats[TestResult::PENDING] + $stats[TestResult::UNDEFINED],
            'time' => $this->durationListener ? $this->durationListener->getFeatureDuration($this->currentFeature) : '',
        ]);

        $this->statistics->reset();
        $this->currentFeature = null;
    }
}
