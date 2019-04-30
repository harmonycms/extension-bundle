<?php

namespace Harmony\Bundle\ExtensionBundle\Manager;

use Harmony\Sdk\Extension\AbstractExtension;
use Harmony\Sdk\Extension\Component;

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