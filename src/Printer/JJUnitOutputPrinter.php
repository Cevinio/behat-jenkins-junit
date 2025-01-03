<?php

/*
 * This file is part of the Behat Testwork.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cevinio\BehatJenkinsJUnit\Printer;

use Behat\Testwork\Output\Exception\MissingExtensionException;
use Behat\Testwork\Output\Exception\MissingOutputPathException;
use Behat\Testwork\Output\Printer\Factory\FilesystemOutputFactory;
use Behat\Testwork\Output\Printer\StreamOutputPrinter;
use DOMDocument;
use DOMElement;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A convenient wrapper around the ConsoleOutputPrinter to write valid JUnit
 * reports.
 *
 * @author Wouter J <wouter@wouterj.nl>
 * @author James Watson <james@sitepulse.org>
 */
final class JJUnitOutputPrinter extends StreamOutputPrinter
{
    public const XML_VERSION  = '1.0';
    public const XML_ENCODING = 'UTF-8';

    private ?DOMDocument $domDocument = null;
    private ?DOMElement $currentTestsuite = null;
    private ?DOMElement $currentTestcase = null;
    private ?DOMElement $testSuites = null;
    private ?string $currentBehatSuiteName = null;

    public function __construct(FilesystemOutputFactory $outputFactory)
    {
        parent::__construct($outputFactory);
    }

    /**
     * Creates a new JUnit file.
     *
     * The file will be initialized with an XML definition and the root element.
     *
     * @param string  $name   The name of the current suite (also determines the filename)
     * @param ?string $prefix A prefix for the filename
     * @param ?string $suffix A suffix for the filename (added before the extension)
     */
    public function createNewFile(
        string $name,
        ?string $prefix = null,
        ?string $suffix = null,
        array $testsuitesAttributes = [],
    ): void {
        // This requires the DOM extension to be enabled.
        if (!extension_loaded('dom')) {
            throw new MissingExtensionException('The PHP DOM extension is required to generate JUnit reports.');
        }
        $this->setFileName(strtolower(trim(preg_replace('/[^[:alnum:]_]+/', '_', $prefix . $name . $suffix), '_')));

        $this->currentBehatSuiteName = $name;
        $this->currentTestsuite = null;
        $this->currentTestcase = null;

        $this->domDocument = new DOMDocument(self::XML_VERSION, self::XML_ENCODING);
        $this->domDocument->formatOutput = true;

        $this->testSuites = $this->domDocument->createElement('testsuites');
        $this->domDocument->appendChild($this->testSuites);
        $this->addAttributesToNode($this->testSuites, $testsuitesAttributes);
        $this->flush();
    }

    /**
     * Adds a new <testsuite> node.
     *
     * @param array $testsuiteAttributes
     */
    public function addTestsuite(array $testsuiteAttributes = []): void
    {
        $this->currentTestsuite = $this->domDocument->createElement('testsuite');
        $this->testSuites->appendChild($this->currentTestsuite);
        $this->extendTestsuiteAttributes(['package' => $this->currentBehatSuiteName, ...$testsuiteAttributes]);
    }

    /**
     * Extends the current <testsuite> node.
     *
     * @param array<string, string|int|null> $testsuiteAttributes
     */
    public function extendTestsuiteAttributes(array $testsuiteAttributes): void
    {
        if (true === isset($testsuiteAttributes['name'])) {
            $testsuiteAttributes['name'] = str_replace('/', '.', $testsuiteAttributes['name']);
        }

        $this->addAttributesToNode($this->currentTestsuite, $testsuiteAttributes);
    }


    /**
     * Adds a new <testcase> node.
     *
     * @param array $testcaseAttributes
     */
    public function addTestcase(array $testcaseAttributes = []): void
    {
        $this->currentTestcase = $this->domDocument->createElement('testcase');
        $this->currentTestsuite->appendChild($this->currentTestcase);

        if (true === isset($testcaseAttributes['classname'])) {
            if (null !== $this->currentBehatSuiteName) {
                $testcaseAttributes['classname'] = $this->currentBehatSuiteName . '.' . $testcaseAttributes['classname'];
            }

            $testcaseAttributes['classname'] = str_replace('/', '.', $testcaseAttributes['classname']);
        }

        $this->addAttributesToNode($this->currentTestcase, $testcaseAttributes);
    }

    /**
     * Add a testcase child element.
     *
     * @param string $nodeName
     * @param array  $nodeAttributes
     * @param string|null $nodeValue
     */
    public function addTestcaseChild(string $nodeName, array $nodeAttributes = [], ?string $nodeValue = null): void
    {
        $childNode = $this->domDocument->createElement($nodeName);
        $this->currentTestcase->appendChild($childNode);
        $this->addAttributesToNode($childNode, $nodeAttributes);

        if ($nodeValue !== null && $nodeValue !== '') {
            $childNode->appendChild($this->domDocument->createCDATASection($nodeValue));
        }
    }

    private function addAttributesToNode(DOMElement $node, array $attributes): void
    {
        foreach ($attributes as $name => $value) {
            if ($value !== null && $value !== '') {
                $node->setAttribute($name, (string)$value);
            }
        }
    }

    /**
     * Sets file name.
     *
     * @param string $fileName
     * @param string $extension The file extension, defaults to "xml"
     */
    public function setFileName(string $fileName, string $extension = 'xml'): void
    {
        if ('.' . $extension !== substr($fileName, strlen($extension) + 1)) {
            $fileName .= '.' . $extension;
        }
        $outputFactory = $this->getOutputFactory();
        assert($outputFactory instanceof FilesystemOutputFactory);
        $outputFactory->setFileName($fileName);
    }

    /**
     * Generate XML from the DOMDocument and parse to the writing stream
     */
    public function flush(): void
    {
        if ($this->domDocument instanceof DOMDocument && $this->currentTestsuite?->firstElementChild !== null) {
            try {
                $this->getWritingStream()->write($this->domDocument->saveXML(), false, OutputInterface::OUTPUT_RAW);
            } catch (MissingOutputPathException) {
                throw new MissingOutputPathException(
                    'The `output_path` option must be specified for the jjunit formatter.',
                );
            }
        }

        parent::flush();
    }
}
