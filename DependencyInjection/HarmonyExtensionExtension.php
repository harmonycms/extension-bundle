<?php

namespace Harmony\Bundle\ExtensionBundle\DependencyInjection;

use Harmony\Sdk\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

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
        $twigFilesystemLoaderDefinition = $container->getDefinition('twig.loader.filesystem');
        // register extensions as Twig namespaces
        foreach ($container->getParameter('kernel.extensions') as $namespace => $class) {
            /** @var ExtensionInterface $extensionClass */
            $extensionClass = new $class();
            $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$extensionClass->getPath(), $namespace]);
        }
    }
}