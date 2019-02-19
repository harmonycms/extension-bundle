<?php

namespace Harmony\Bundle\ExtensionBundle\DependencyInjection;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Yaml\Yaml;

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
        $configuredMenus = [];
        foreach ($container->getParameter('kernel.extensions') as $extension) {
            $reflection = new \ReflectionClass($extension);
            if (is_file($file = dirname($reflection->getFileName()) . '/Resources/config/menu.yaml')) {
                $configuredMenus = array_replace_recursive($configuredMenus,
                    Yaml::parse(file_get_contents(realpath($file))));
                $container->addResource(new FileResource($file));
            }
        }

        // validate menu configurations
        foreach ($configuredMenus as $rootName => $menuConfiguration) {
            $configuration                = new Configuration();
            $menuConfiguration[$rootName] = $this->processConfiguration($configuration->setMenuRootName($rootName),
                [$rootName => $menuConfiguration]);
        }
    }
}