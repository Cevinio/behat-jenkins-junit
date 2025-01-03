<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cevinio\BehatJenkinsJUnit\Formatter;

use Behat\Behat\Output\Node\EventListener\JUnit\JUnitDurationListener;
use Behat\Behat\Output\Node\EventListener\JUnit\JUnitOutlineStoreListener;
use Behat\Behat\Output\Statistics\PhaseStatistics;
use Cevinio\BehatJenkinsJUnit\EventListener\JJUnitFeatureElementListener;
use Behat\Behat\Output\Node\EventListener\Statistics\HookStatsListener;
use Behat\Behat\Output\Node\EventListener\Statistics\ScenarioStatsListener;
use Behat\Behat\Output\Node\EventListener\Statistics\StepStatsListener;
use Cevinio\BehatJenkinsJUnit\Printer\JJUnitFeaturePrinter;
use Cevinio\BehatJenkinsJUnit\Printer\JJUnitOutputPrinter;
use Cevinio\BehatJenkinsJUnit\Printer\JJUnitScenarioPrinter;
use Cevinio\BehatJenkinsJUnit\Printer\JJUnitSetupPrinter;
use Cevinio\BehatJenkinsJUnit\Printer\JJUnitStepPrinter;
use Cevinio\BehatJenkinsJUnit\Printer\JJUnitSuitePrinter;
use Behat\Testwork\Exception\ServiceContainer\ExceptionExtension;
use Behat\Testwork\Output\Node\EventListener\ChainEventListener;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Behat\Testwork\Output\Printer\Factory\FilesystemOutputFactory;
use Behat\Testwork\Output\ServiceContainer\Formatter\FormatterFactory;
use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Behat junit formatter factory.
 *
 * @author Wouter J <wouter@wouterj.nl>
 */
final class JJUnitFormatterFactory implements FormatterFactory
{
    private const PARAMETER_PATHS_BASE = 'paths.base';
    private const ROOT_LISTENER_ID = 'cevinio.jjunit.output.node.listener.junit';
    private const JUNIT_PATH_PARSER = 'cevinio.jjunit.path.parser';
    private const JUNIT_STATISTICS = 'cevinio.jjunit.output.junit.statistics';
    private const NODE_PRINTER_JUNIT_SUITE = 'cevinio.jjunit.output.node.printer.junit.suite';
    private const NODE_LISTENER_JUNIT_DURATION = 'cevinio.jjunit.output.node.listener.junit.duration';
    private const NODE_PRINTER_JUNIT_FEATURE = 'cevinio.jjunit.output.node.printer.junit.feature';
    private const NODE_LISTENER_JUNIT_OUTLINE = 'cevinio.jjunit.output.node.listener.junit.outline';
    private const NODE_PRINTER_JUNIT_SCENARIO = 'cevinio.jjunit.output.node.printer.junit.scenario';
    private const NODE_PRINTER_JUNIT_STEP = 'cevinio.jjunit.output.node.printer.junit.step';
    private const NODE_PRINTER_JUNIT_SETUP = 'cevinio.jjunit.output.node.printer.junit.setup';

    public function buildFormatter(ContainerBuilder $container): void
    {
        $this->loadRootNodeListener($container);
        $this->loadPrinterHelpers($container);
        $this->loadCorePrinters($container);
        $this->loadFormatter($container);
    }

    public function processFormatter(ContainerBuilder $container): void
    {
    }

    /**
     * Loads printer helpers.
     *
     * @param ContainerBuilder $container
     */
    private function loadPrinterHelpers(ContainerBuilder $container): void
    {
        $definition = new Definition(JJUnitPathParser::class, [
            $container->getParameter(self::PARAMETER_PATHS_BASE),
        ]);
        $container->setDefinition(self::JUNIT_PATH_PARSER, $definition);
    }

    /**
     * Loads the printers used to print the basic JUnit report.
     *
     * @param ContainerBuilder $container
     */
    private function loadCorePrinters(ContainerBuilder $container): void
    {
        $definition = new Definition(JJUnitSuitePrinter::class, [
            new Reference(self::JUNIT_STATISTICS),
        ]);
        $container->setDefinition(self::NODE_PRINTER_JUNIT_SUITE, $definition);

        $definition = new Definition(JJUnitFeaturePrinter::class, [
            new Reference(self::JUNIT_STATISTICS),
            new Reference(self::JUNIT_PATH_PARSER),
            new Reference(self::NODE_LISTENER_JUNIT_DURATION),
        ]);
        $container->setDefinition(self::NODE_PRINTER_JUNIT_FEATURE, $definition);

        $definition = new Definition(JJUnitScenarioPrinter::class, [
            new Reference(self::NODE_LISTENER_JUNIT_OUTLINE),
            new Reference(self::JUNIT_PATH_PARSER),
            new Reference(self::NODE_LISTENER_JUNIT_DURATION),
        ]);
        $container->setDefinition(self::NODE_PRINTER_JUNIT_SCENARIO, $definition);

        $definition = new Definition(JJUnitStepPrinter::class, [
            new Reference(ExceptionExtension::PRESENTER_ID),
        ]);
        $container->setDefinition(self::NODE_PRINTER_JUNIT_STEP, $definition);

        $definition = new Definition(JJUnitSetupPrinter::class, [
            new Reference(ExceptionExtension::PRESENTER_ID),
        ]);
        $container->setDefinition(self::NODE_PRINTER_JUNIT_SETUP, $definition);
    }

    /**
     * Loads the node listeners required for JUnit printers to work.
     *
     * @param ContainerBuilder $container
     */
    private function loadRootNodeListener(ContainerBuilder $container): void
    {
        $definition = new Definition(JUnitOutlineStoreListener::class, [
            new Reference(self::NODE_PRINTER_JUNIT_SUITE),
        ]);
        $container->setDefinition(self::NODE_LISTENER_JUNIT_OUTLINE, $definition);

        $definition = new Definition(JUnitDurationListener::class);
        $container->setDefinition(self::NODE_LISTENER_JUNIT_DURATION, $definition);

        $definition = new Definition(ChainEventListener::class, [
            [
                new Reference(self::NODE_LISTENER_JUNIT_DURATION),
                new Definition(JJUnitFeatureElementListener::class, [
                    new Reference(self::NODE_PRINTER_JUNIT_FEATURE),
                    new Reference(self::NODE_PRINTER_JUNIT_SCENARIO),
                    new Reference(self::NODE_PRINTER_JUNIT_STEP),
                    new Reference(self::NODE_PRINTER_JUNIT_SETUP),
                ]),
                new Reference(self::NODE_LISTENER_JUNIT_OUTLINE),
            ],
        ]);
        $container->setDefinition(self::ROOT_LISTENER_ID, $definition);
    }

    /**
     * Loads formatter itself.
     *
     * @param ContainerBuilder $container
     */
    private function loadFormatter(ContainerBuilder $container): void
    {
        $definition = new Definition(PhaseStatistics::class);
        $container->setDefinition(self::JUNIT_STATISTICS, $definition);

        $definition = new Definition(NodeEventListeningFormatter::class, [
            JJUnitFormatter::NAME,
            'Outputs the test results in Jenkins xUnit / JUnit compatible files.',
            JJUnitFormatter::defaults(),
            $this->createOutputPrinterDefinition(),
            new Definition(ChainEventListener::class, [
                [
                    new Reference(self::ROOT_LISTENER_ID),
                    new Definition(ScenarioStatsListener::class, [
                        new Reference(self::JUNIT_STATISTICS),
                    ]),
                    new Definition(StepStatsListener::class, [
                        new Reference(self::JUNIT_STATISTICS),
                        new Reference(ExceptionExtension::PRESENTER_ID),
                    ]),
                    new Definition(HookStatsListener::class, [
                        new Reference(self::JUNIT_STATISTICS),
                        new Reference(ExceptionExtension::PRESENTER_ID),
                    ]),
                ],
            ]),
        ]);
        $definition->addTag(OutputExtension::FORMATTER_TAG, ['priority' => 100]);
        $container->setDefinition(OutputExtension::FORMATTER_TAG . '.jjunit', $definition);
    }

    /**
     * Creates output printer definition.
     *
     * @return Definition
     */
    private function createOutputPrinterDefinition(): Definition
    {
        return new Definition(JJUnitOutputPrinter::class, [
            new Definition(FilesystemOutputFactory::class),
        ]);
    }
}
