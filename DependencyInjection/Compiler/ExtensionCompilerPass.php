<?php

namespace Harmony\Bundle\ExtensionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function is_dir;

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
        $twigFilesystemLoaderDefinition = $container->findDefinition('twig.loader.filesystem');

        foreach ($container->getParameter('kernel.extensions_metadata') as $name => $extension) {
            // register extensions as Twig namespaces
            if (is_dir($path = $extension['path'] . '/Resources/views')) {
                $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$path, $extension['namespace']]);
            }
        }
    }
}