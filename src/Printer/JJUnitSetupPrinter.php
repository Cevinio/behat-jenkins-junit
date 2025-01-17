<?php

declare(strict_types=1);

namespace Cevinio\BehatJenkinsJUnit\Printer;

use Behat\Behat\Hook\Scope\StepScope;
use Behat\Behat\Output\Node\Printer\SetupPrinter;
use Behat\Testwork\Call\CallResult;
use Behat\Testwork\Call\CallResults;
use Behat\Testwork\Exception\ExceptionPresenter;
use Behat\Testwork\Hook\Call\HookCall;
use Behat\Testwork\Hook\Tester\Setup\HookedSetup;
use Behat\Testwork\Hook\Tester\Setup\HookedTeardown;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Tester\Setup\Setup;
use Behat\Testwork\Tester\Setup\Teardown;

/**
 * @author: Jakob Erdmann <jakob.erdmann@rocket-internet.com>
 */
class JJUnitSetupPrinter implements SetupPrinter
{
    public function __construct(
        private readonly ExceptionPresenter $exceptionPresenter,
    ) {
    }

    public function printSetup(Formatter $formatter, Setup $setup): void
    {
        if (!$setup->isSuccessful() && $setup instanceof HookedSetup) {
            $this->handleHookCalls($formatter, $setup->getHookCallResults(), 'setup');
        }
    }

    public function printTeardown(Formatter $formatter, Teardown $teardown): void
    {
        if (!$teardown->isSuccessful() && $teardown instanceof HookedTeardown) {
            $this->handleHookCalls($formatter, $teardown->getHookCallResults(), 'teardown');
        }
    }

    /**
     * @param Formatter $formatter
     * @param CallResults $results
     * @param string $messageType
     */
    private function handleHookCalls(Formatter $formatter, CallResults $results, string $messageType): void
    {
        /** @var CallResult $hookCallResult */
        foreach ($results as $hookCallResult) {
            if ($hookCallResult->hasException()) {
                /** @var HookCall $call */
                $call = $hookCallResult->getCall();
                $scope = $call->getScope();
                /** @var JJUnitOutputPrinter $outputPrinter */
                $outputPrinter = $formatter->getOutputPrinter();

                $message = $call->getCallee()->getName();
                if ($scope instanceof StepScope) {
                    $message .= ': ' . $scope->getStep()->getKeyword() . ' ' . $scope->getStep()->getText();
                }

                $exception = $this->exceptionPresenter->presentException($hookCallResult->getException());

                $outputPrinter->addTestcaseChild('failure', [ 'message' => $message ], $exception);
            }
        }
    }
}
