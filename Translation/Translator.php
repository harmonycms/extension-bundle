<?php

namespace Harmony\Bundle\ExtensionBundle\Translation;

use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator as BaseTranslator;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class Translator
 *
 * @package Harmony\Bundle\ExtensionBundle\Translation
 */
class Translator implements LegacyTranslatorInterface, TranslatorInterface, TranslatorBagInterface
{

    /** @var BaseTranslator $translator */
    protected $translator;

    /**
     * Translator constructor.
     *
     * @param BaseTranslator $translator
     */
    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    /**
     * Translates the given choice message by choosing a translation according to a number.
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param int         $number     The number to use to find the index of the message
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }

    /**
     * Translates the given message.
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    /**
     * Sets the current locale.
     *
     * @param string $locale The locale
     *
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public function setLocale($locale)
    {
        $this->translator->setLocale($locale);
    }

    /**
     * Returns the current locale.
     *
     * @return string The locale
     */
    public function getLocale()
    {
        return $this->translator->getLocale();
    }

    /**
     * Gets the catalogue by locale.
     *
     * @param string|null $locale The locale or null to use the default
     *
     * @return MessageCatalogueInterface
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public function getCatalogue($locale = null)
    {
        return $this->translator->getCatalogue($locale);
    }

    /**
     * Adds a Resource.
     *
     * @param string $format   The name of the loader (@see addLoader())
     * @param mixed  $resource The resource name
     * @param string $locale   The locale
     * @param string $domain   The domain
     *
     * @return
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public function addResource($format, $resource, $locale, $domain = null)
    {
        return $this->translator->addResource($format, $resource, $locale, $domain);
    }

    /**
     * @param array $extensionsMetadata
     */
    public function addExtensionResources(array $extensionsMetadata)
    {
        $dirs = [];
        foreach ($extensionsMetadata as $name => $extension) {
            if (is_dir($dir = $extension['path'] . '/Resources/translations')) {
                $dirs[] = $dir;
            }
        }

        // Register translation resources
        if (count($dirs) > 0) {
            $files  = [];
            $finder = Finder::create()
                ->followLinks()
                ->files()
                ->filter(function (SplFileInfo $file) {
                    return 2 === substr_count($file->getBasename(), '.') &&
                        preg_match('/\.\w+$/', $file->getBasename());
                })
                ->in($dirs)
                ->sortByName();

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                // filename is domain.locale.format
                list($domain, $locale, $format) = explode('.', $file->getBasename(), 3);
                $this->addResource($format, (string)$file, $locale, $domain);
                if (!isset($files[$locale])) {
                    $files[$locale] = [];
                }
                $files[$locale][] = (string)$file;
            }
        }
    }

}