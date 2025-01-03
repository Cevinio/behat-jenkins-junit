<?php

declare(strict_types=1);

namespace Cevinio\BehatJenkinsJUnit;

use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Cevinio\BehatJenkinsJUnit\Formatter\JJUnitFormatterFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class JJUnitFormatterExtension implements Extension
{
    public const CONFIG_KEY = 'cev_jjunit';

    private const OUTPUT_EXTENSION_CONFIG_KEY = 'formatters';

    public function process(ContainerBuilder $container): void
    {
    }

    public function getConfigKey(): string
    {
        return self::CONFIG_KEY;
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
        /** @var OutputExtension $outputExtension */
        $outputExtension = $extensionManager->getExtension(self::OUTPUT_EXTENSION_CONFIG_KEY);
        $outputExtension->registerFormatterFactory(new JJUnitFormatterFactory());
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
    }

    public function load(ContainerBuilder $container, array $config): void
    {
    }
}
