<?php

namespace Harmony\Bundle\ExtensionBundle\DependencyInjection\Compiler;

use Harmony\Sdk\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

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
        $translatorDefinition           = $container->findDefinition('translator.default');

        $transPaths = [];
        foreach ($container->getParameter('kernel.extensions') as $namespace => $class) {
            /** @var ExtensionInterface $extensionClass */
            $extensionClass = new $class();

            // register extensions as Twig namespaces
            if (\is_dir($path = $extensionClass->getPath() . '/Resources/views')) {
                $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$path, $namespace]);
            }
            // set translations directory path if exists
            if (\is_dir($dir = $extensionClass->getPath() . '/Resources/translations')) {
                $transPaths[] = $dir;
            }
        }

        // Register translation resources
        if ($transPaths) {
            $files = [];
            $finder = Finder::create()
                ->followLinks()
                ->files()
                ->filter(function (\SplFileInfo $file) {
                    return 2 === substr_count($file->getBasename(), '.') && preg_match('/\.\w+$/', $file->getBasename());
                })
                ->in($transPaths)
                ->sortByName();

            foreach ($finder as $file) {
                list(, $locale) = explode('.', $file->getBasename(), 3);
                if (!isset($files[$locale])) {
                    $files[$locale] = [];
                }
                $files[$locale][] = (string)$file;
            }

            $options = array_merge($translatorDefinition->getArgument(4), ['resource_files' => $files]);

            $translatorDefinition->replaceArgument(4, $options);
        }
    }
}