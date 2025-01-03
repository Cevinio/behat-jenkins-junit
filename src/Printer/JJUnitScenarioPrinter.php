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
use Behat\Behat\Output\Node\EventListener\JUnit\JUnitOutlineStoreListener;
use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioLikeInterface;
use Behat\Testwork\Output\Formatter;
use Cevinio\BehatJenkinsJUnit\Formatter\JJUnitPathParser;

/**
 * Prints the <testcase> element.
 *
 * @author Wouter J <wouter@wouterj.nl>
 */
final class JJUnitScenarioPrinter
{
    private ?OutlineNode $lastOutline = null;
    private int $outlineStepCount = 0;

    public function __construct(
        private readonly JUnitOutlineStoreListener $outlineStoreListener,
        private readonly JJUnitPathParser $pathParser,
        private readonly ?JUnitDurationListener $durationListener = null,
    ) {
    }

    public function printOpenTag(
        Formatter $formatter,
        FeatureNode $feature,
        ScenarioLikeInterface $scenario,
    ): void {
        if ($scenario instanceof ExampleNode) {
            $name = $this->buildExampleName($scenario);
        } else {
            $name = implode(' ', array_map(trim(...), explode("\n", $scenario->getTitle() ?? '')));
        }

        /** @var JJUnitOutputPrinter $outputPrinter */
        $outputPrinter = $formatter->getOutputPrinter();

        $outputPrinter->addTestcase([
            'name'      => $name,
            'classname' => $this->pathParser->strip($feature->getFile()),
            'time'      => $this->durationListener ? $this->durationListener->getDuration($scenario) : '',
        ]);
    }

    /**
     * @param ExampleNode $scenario
     * @return string
     */
    private function buildExampleName(ExampleNode $scenario): string
    {
        $currentOutline = $this->outlineStoreListener->getCurrentOutline($scenario);
        if ($currentOutline === $this->lastOutline) {
            $this->outlineStepCount++;
        } else {
            $this->lastOutline = $currentOutline;
            $this->outlineStepCount = 1;
        }

        return $currentOutline->getTitle() . ' #' . $this->outlineStepCount;
    }

    public function printSkippedResult(Formatter $formatter): void
    {
        /** @var JJUnitOutputPrinter $outputPrinter */
        $outputPrinter = $formatter->getOutputPrinter();
        $outputPrinter->addTestcaseChild('skipped', [ 'message' => 'Scenario skipped' ]);
    }
}
