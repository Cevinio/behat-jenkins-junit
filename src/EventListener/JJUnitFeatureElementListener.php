<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cevinio\BehatJenkinsJUnit\EventListener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureSetup;
use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioSetup;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepSetup;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\Output\Node\Printer\FeaturePrinter;
use Behat\Behat\Output\Node\Printer\SetupPrinter;
use Behat\Behat\Output\Node\Printer\StepPrinter;
use Behat\Testwork\Event\Event;
use Behat\Testwork\EventDispatcher\Event\AfterSuiteSetup;
use Behat\Testwork\EventDispatcher\Event\AfterSuiteTested;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Node\EventListener\EventListener;
use Behat\Testwork\Tester\Result\TestResult;
use Cevinio\BehatJenkinsJUnit\Printer\JJUnitScenarioPrinter;

/**
 * Listens to feature, scenario and step events and calls appropriate printers.
 *
 * @author Wouter J <wouter@wouterj.nl>
 */
final class JJUnitFeatureElementListener implements EventListener
{
    /** @var AfterStepTested[] */
    private array $afterStepTestedEvents = [];

    /** @var AfterStepSetup[] */
    private array $afterStepSetupEvents = [];

    private ?AfterSuiteSetup $afterSuiteSetup = null;

    private ?AfterFeatureSetup $afterFeatureSetup = null;

    private AfterScenarioSetup $afterScenarioSetup;

    /**
     * Initializes listener.
     *
     * @param FeaturePrinter $featurePrinter
     * @param JJUnitScenarioPrinter $scenarioPrinter
     * @param StepPrinter $stepPrinter
     * @param SetupPrinter $setupPrinter
     */
    public function __construct(
        private readonly FeaturePrinter $featurePrinter,
        private readonly JJUnitScenarioPrinter $scenarioPrinter,
        private readonly StepPrinter $stepPrinter,
        private readonly SetupPrinter $setupPrinter,
    ) {
    }

    public function listenEvent(Formatter $formatter, Event $event, $eventName): void
    {
        $this->captureSuiteSetupEvent($formatter, $event);
        $this->printFeatureOnBeforeEvent($formatter, $event);
        $this->captureStepEvent($event);
        $this->printScenarioEvent($formatter, $event);
        $this->printFeatureOnAfterEvent($formatter, $event);
        $this->printSuiteTeardownEvent($formatter, $event);
    }

    /**
     * Captures any failures in suite setup.
     * They will be printed later when the first scenario is printed
     */
    private function captureSuiteSetupEvent(Formatter $formatter, Event $event): void
    {
        if ($event instanceof AfterSuiteSetup) {
            $this->afterSuiteSetup = $event;
        }
    }

    /**
     * Prints any failures in suite teardown.
     */
    private function printSuiteTeardownEvent(Formatter $formatter, Event $event): void
    {
        if ($event instanceof AfterSuiteTested) {
            // if needed, add a failure node to the last testCase node that has been created
            $this->setupPrinter->printTeardown($formatter, $event->getTeardown());
        }
    }

    /**
     * Prints the header for the feature.
     */
    private function printFeatureOnBeforeEvent(Formatter $formatter, Event $event): void
    {
        if ($event instanceof BeforeFeatureTested) {
            $this->featurePrinter->printHeader($formatter, $event->getFeature());
            return;
        }
        if ($event instanceof AfterFeatureSetup) {
            // Captures any failures in feature setup.
            // They will be printed later when the first scenario is printed
            $this->afterFeatureSetup = $event;
        }
    }

    /**
     * Captures step tested event.
     */
    private function captureStepEvent(Event $event): void
    {
        if ($event instanceof AfterStepTested) {
            $this->afterStepTestedEvents[$event->getStep()->getLine()] = $event;
        }
        if ($event instanceof AfterStepSetup) {
            $this->afterStepSetupEvents[$event->getStep()->getLine()] = $event;
        }
    }

    /**
     * Prints the scenario tested event.
     */
    private function printScenarioEvent(Formatter $formatter, Event $event): void
    {
        if ($event instanceof AfterScenarioSetup) {
            $this->afterScenarioSetup = $event;
            return;
        }
        if (!$event instanceof AfterScenarioTested) {
            return;
        }
        $afterScenarioTested = $event;
        $this->scenarioPrinter->printOpenTag(
            $formatter,
            $afterScenarioTested->getFeature(),
            $afterScenarioTested->getScenario(),
        );

        if ($this->afterSuiteSetup !== null) {
            $this->setupPrinter->printSetup($formatter, $this->afterSuiteSetup->getSetup());
            $this->afterSuiteSetup = null;
        }
        if ($this->afterFeatureSetup !== null) {
            $this->setupPrinter->printSetup($formatter, $this->afterFeatureSetup->getSetup());
            $this->afterFeatureSetup = null;
        }
        $this->setupPrinter->printSetup($formatter, $this->afterScenarioSetup->getSetup());

        foreach ($this->afterStepSetupEvents as $afterStepSetup) {
            $this->setupPrinter->printSetup($formatter, $afterStepSetup->getSetup());
        }
        foreach ($this->afterStepTestedEvents as $afterStepTested) {
            $this->stepPrinter->printStep($formatter, $afterScenarioTested->getScenario(), $afterStepTested->getStep(), $afterStepTested->getTestResult());
            $this->setupPrinter->printTeardown($formatter, $afterStepTested->getTeardown());
        }

        if ($afterScenarioTested->getTestResult() === TestResult::SKIPPED) {
            $this->scenarioPrinter->printSkippedResult($formatter);
        }

        $this->setupPrinter->printTeardown($formatter, $afterScenarioTested->getTeardown());

        $this->afterStepTestedEvents = [];
        $this->afterStepSetupEvents = [];
    }

    /**
     * Prints the feature on AFTER event.
     */
    private function printFeatureOnAfterEvent(Formatter $formatter, Event $event): void
    {
        if (!$event instanceof AfterFeatureTested) {
            return;
        }
        $this->setupPrinter->printTeardown($formatter, $event->getTeardown());
        $this->featurePrinter->printFooter($formatter, $event->getTestResult());
    }
}
