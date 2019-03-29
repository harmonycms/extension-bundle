<?php

namespace Harmony\Bundle\ExtensionBundle\DependencyInjection\Compiler;

use SplFileInfo;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use function array_merge_recursive;
use function explode;
use function is_dir;
use function preg_match;
use function substr_count;

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
        foreach ($container->getParameter('kernel.extensions_metadata') as $name => $extension) {
            // register extensions as Twig namespaces
            if (is_dir($path = $extension['path'] . '/Resources/views')) {
                $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$path, $extension['namespace']]);
            }
            // set translations directory path if exists
            if (is_dir($dir = $extension['path'] . '/Resources/translations')) {
                $transPaths[] = $dir;
            }
        }

        // Register translation resources
        if ($transPaths) {
            $files  = [];
            $finder = Finder::create()
                ->followLinks()
                ->files()
                ->filter(function (SplFileInfo $file) {
                    return 2 === substr_count($file->getBasename(), '.') &&
                        preg_match('/\.\w+$/', $file->getBasename());
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

            $options = array_merge_recursive($translatorDefinition->getArgument(4), ['resource_files' => $files]);

            $translatorDefinition->replaceArgument(4, $options);
        }
    }
}