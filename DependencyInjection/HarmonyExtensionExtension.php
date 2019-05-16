<?php

namespace Harmony\Bundle\ExtensionBundle\DependencyInjection;

use Harmony\Bundle\ExtensionBundle\Translation\Translator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class HarmonyExtensionExtension
 *
 * @package Harmony\Bundle\ExtensionBundle\DependencyInjection
 */
class HarmonyExtensionExtension extends Extension implements PrependExtensionInterface
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

        // Register translation path for each extensions
        $translator = $container->register(Translator::class, Translator::class);
        $translator->setArgument('$translator', new Reference(Translator::class . '.inner'));
        $translator->setDecoratedService('translator', null, 5);
        $translator->addMethodCall('addExtensionResources', [
            '$extensionsMetadata' => $container->getParameter('kernel.extensions_metadata')
        ]);
    }

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        // get all bundles
        $bundles = $container->getParameter('kernel.bundles');

//        if (isset($bundles['HarmonySettingsManagerBundle'])) {
//            $extensions = $container->getParameter('kernel.extensions_metadata');
//            $metaTypes  = [];
//            foreach ($extensions as $name => $metadata) {
//                if (isset($metadata['type'])) {
//                    $metaTypes[$metadata['type']] = [];
//                    array_push($metaTypes[$metadata['type']], $name);
//                }
//            }
//
//            $settings = [];
//            foreach (array_keys($metaTypes) as $type) {
//                $settings[$type] = [
//                    'name'    => $type,
//                    'type'    => 'choice',
//                    'domain'  => 'extension',
//                    'tags'    => ['components'],
//                    'choices' => $metaTypes[$type]
//                ];
//            }
//
//            // Prepend the `harmony_settings_manager` settings
//            $container->prependExtensionConfig('harmony_settings_manager', ['settings' => $settings]);
//        }
    }
}