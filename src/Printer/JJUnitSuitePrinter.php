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

use Behat\Behat\Output\Node\Printer\SuitePrinter;
use Behat\Behat\Output\Statistics\PhaseStatistics;
use Cevinio\BehatJenkinsJUnit\Formatter\JJUnitFormatter;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Suite\Suite;

/**
 * Creates new JUnit report file.
 *
 * @author Wouter J <wouter@wouterj.nl>
 */
final class JJUnitSuitePrinter implements SuitePrinter
{
    public function __construct(
        private readonly ?PhaseStatistics $statistics = null,
    ) {
    }

    public function printHeader(Formatter $formatter, Suite $suite): void
    {
        $this->statistics?->reset();

        /** @var JJUnitOutputPrinter $outputPrinter */
        $outputPrinter = $formatter->getOutputPrinter();
        $outputPrinter->createNewFile(
            name: $suite->getName(),
            prefix: $formatter->getParameter(JJUnitFormatter::SETTING_PREFIX),
            suffix: $formatter->getParameter(JJUnitFormatter::SETTING_SUFFIX),
        );
    }

    public function printFooter(Formatter $formatter, Suite $suite): void
    {
        $formatter->getOutputPrinter()->flush();
    }
}
