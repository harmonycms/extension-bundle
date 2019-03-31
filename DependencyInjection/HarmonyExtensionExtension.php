<?php

namespace Harmony\Bundle\ExtensionBundle\DependencyInjection;

use Harmony\Bundle\ExtensionBundle\Translation\Translator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class HarmonyExtensionExtension
 *
 * @package Harmony\Bundle\ExtensionBundle\DependencyInjection
 */
class HarmonyExtensionExtension extends Extension
{

    /**
     * Loads a specific configuration.
     *
     * @param array            $configs   An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));
        $loader->load('services.yaml');

        $translator = $container->register(Translator::class, Translator::class);
        $translator->setArgument('$translator', new Reference('translator.default'));
        $translator->setDecoratedService('translator', null, 5);
        $translator->addMethodCall('addExtensionResources', [
            '$extensionsMetadata' => $container->getParameter('kernel.extensions_metadata')
        ]);
    }
}