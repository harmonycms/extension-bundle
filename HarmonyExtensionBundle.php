<?php

namespace Harmony\Bundle\ExtensionBundle;

use Harmony\Bundle\ExtensionBundle\DependencyInjection\Compiler\ExtensionCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class HarmonyExtensionBundle
 *
 * @package Harmony\Bundle\ExtensionBundle
 */
class HarmonyExtensionBundle extends Bundle
{

    /**
     * Builds the bundle.
     * It is only ever called once when the cache is empty.
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ExtensionCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, - 10);
    }
}