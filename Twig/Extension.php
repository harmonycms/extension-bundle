<?php

namespace Harmony\Bundle\ExtensionBundle\Twig;

use Harmony\Bundle\SettingsManagerBundle\Settings\SettingsManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Extension
 *
 * @package Harmony\Bundle\ExtensionBundle\Twig
 */
class Extension extends AbstractExtension
{

    /** @var SettingsManager $settingsManager */
    protected $settingsManager;

    /**
     * Extension constructor.
     *
     * @param SettingsManager $settingsManager
     */
    public function __construct(SettingsManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [new TwigFunction('component', [$this, 'getComponent'])];
    }

    /**
     * @param string $type
     *
     * @return null|mixed
     */
    public function getComponent(string $type)
    {
        return $this->settingsManager->getSetting($type, 'extension')->getData();
    }

}