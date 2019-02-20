<?php

namespace Harmony\Bundle\ExtensionBundle\DependencyInjection\Compiler;

use Harmony\Sdk\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ExtensionCompilerPass
 *
 * @package Harmony\Bundle\ExtensionBundle\DependencyInjection\Compiler
 */
class ExtensionCompilerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $twigFilesystemLoaderDefinition = $container->getDefinition('twig.loader.filesystem');
        // register extensions as Twig namespaces
        foreach ($container->getParameter('kernel.extensions') as $namespace => $class) {
            /** @var ExtensionInterface $extensionClass */
            $extensionClass = new $class();
            if (\is_dir($path = $extensionClass->getPath() . '/Resources/views')) {
                $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$path, $namespace]);
            }
        }
    }
}