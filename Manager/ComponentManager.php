<?php

namespace Harmony\Bundle\ExtensionBundle\Manager;

use Harmony\Bundle\CoreBundle\Component\HttpKernel\AbstractKernel;
use Harmony\Sdk\Extension\AbstractExtension;
use Harmony\Sdk\Extension\Component;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class Component
 *
 * @package Harmony\Bundle\ExtensionBundle\Manager
 */
class ComponentManager
{

    /** @var Component[] $components */
    protected $components = [];

    /**
     * ComponentManager constructor.
     *
     * @param KernelInterface|AbstractKernel $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        foreach ($kernel->getComponents() as $name => $component) {
            $this->add($component);
        }
    }

    /**
     * Add component to manager
     *
     * @param AbstractExtension $extension
     *
     * @retunr void
     */
    public function add(AbstractExtension $extension): void
    {
        $this->components[$extension->getName()] = $extension;
    }

    /**
     * @return Component[]
     */
    public function all(): array
    {
        return $this->components;
    }
}