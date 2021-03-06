<?php

namespace Behat\Behat\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface,
    Symfony\Component\DependencyInjection\Loader\XmlFileLoader,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\Config\Definition\Processor,
    Symfony\Component\Config\FileLocator;

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Behat service container extension.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class BehatExtension implements ExtensionInterface
{
    /**
     * Configuration processor.
     *
     * @var     Symfony\Component\Config\Definition\Processor
     */
    private $processor;
    /**
     * Configuration holder.
     *
     * @var     Behat\Behat\DependencyInjection\Configuration
     */
    private $configuration;

    /**
     * Initializes configuration.
     */
    public function __construct()
    {
        $this->processor        = new Processor();
        $this->configuration    = new Configuration();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->loadDefaults($container);

        // normalize and merge the actual configuration
        $tree   = $this->configuration->getConfigTree();
        $config = $this->processor->process($tree, $configs);

        // load configs DIC
        foreach ($config as $ns => $subconfig) {
            foreach ($subconfig as $key => $value) {
                $parameterName = "behat.$ns.$key";
                $container->setParameter($parameterName, $value);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__ . '/config/schema';
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return 'http://behat.com/schema/dic/behat';
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'behat';
    }

    /**
     * {@inheritdoc}
     */
    protected function loadDefaults($container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('behat.xml');

        $behatClassLoaderReflection = new \ReflectionClass('Behat\Behat\Console\BehatApplication');
        $gherkinParserReflection    = new \ReflectionClass('Behat\Gherkin\Parser');

        $behatLibPath   = realpath(dirname($behatClassLoaderReflection->getFilename()) . '/../../../../');
        $gherkinLibPath = realpath(dirname($gherkinParserReflection->getFilename()) . '/../../../');

        $container->setParameter('gherkin.paths.lib', $gherkinLibPath);
        $container->setParameter('behat.paths.lib', $behatLibPath);
    }
}
