<?php

/**
 * @file DefaultTranslationPlugin.php
 *
 * Copyright (c) 2013-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class DefaultTranslationPlugin
 *
 * Fallbacks to an English translation if the requested locale key isn't translated for the current locale
 */

namespace APP\plugins\generic\defaultTranslation;

use Generator;
use PKP\facades\Locale;
use PKP\i18n\interfaces\LocaleInterface;
use PKP\i18n\LocaleMetadata;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class DefaultTranslationPlugin extends GenericPlugin
{
    /** Safeguard flag */
    private static ?string $_processingId = null;

    /**
     * @copydoc PKPPlugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.defaultTranslation.displayName');
    }

    /**
     * @copydoc PKPPlugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.defaultTranslation.description');
    }

    /**
     * @copydoc LazyLoadPlugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled()) {
            Hook::add('Locale::translate', fn () => $this->translate(...func_get_args()));
        }
        return $success;
    }

    /**
     * @copydoc PKPPlugin::getSeq()
     */
    public function getSeq(): int
    {
        return -1;
    }

    /**
     * Attempts to find a translation for the requested locale key using the default locale (en)
     */
    public function translate(string $hookName, array $args): bool
    {
        [&$value, $key, $params, $number, $locale, $localeBundle] = $args;

        // If the safeguard is set, then this is an inner hook, raised from our Locale::get/choice call below.
        // So we set the $value with the safeguard flag (null would break Laravel's signature) as a way to signal a failure to the outer hook handler.
        if (static::$_processingId) {
            $value = static::$_processingId;
            return true;
        }

        // The LocaleInterface::DEFAULT_LOCALE is supposed to be the most complete one, there's nothing more we can do
        if ($locale === LocaleInterface::DEFAULT_LOCALE) {
            return false;
        }

        // Setup a unique prefixed safeguard
        static::$_processingId = uniqid($key);
        try {
            foreach ($this->getSuitableLocales($locale) as $fallbackLocale) {
                $value = $number === null
                    ? Locale::get($key, $params, $fallbackLocale)
                    : Locale::choice($key, $number, $params, $fallbackLocale);
                // Failed translations, while inside the hook, will return the safeguard value
                if ($value !== static::$_processingId) {
                    break;
                }
            }
        } finally {
            static::$_processingId = null;
        }
        return true;
    }

    /**
     * Generates a list of suitable languages
     */
    public function getSuitableLocales(string $locale): Generator
    {
        static $cache;
        if (!($locales = $cache[$locale] ?? null)) {
            $metadata = Locale::getMetadata($locale);
            $locales = array_filter(
                Locale::getLocales(),
                fn (LocaleMetadata $locale) =>
                    $locale->getLanguage() === $metadata->getLanguage()
                    && $locale->locale !== $metadata->locale
                    && $locale->locale !== LocaleInterface::DEFAULT_LOCALE
            );
            // Give preference to locales that have the same script as the original locale, otherwise just sort by country and script
            uasort(
                $locales,
                fn (LocaleMetadata $a, LocaleMetadata $b) =>
                    ($b->getScript() === $metadata->getScript()) - ($a->getScript() === $metadata->getScript())
                    ?: strcmp($a->getCountry(), $b->getCountry())
                    ?: strcmp($a->getScript(), $b->getScript())
            );
            $cache[$locale] = $locales = array_map(fn (LocaleMetadata $metadata) => $metadata->locale, $locales);
        }

        yield from $locales;
        yield LocaleInterface::DEFAULT_LOCALE;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\defaultTranslation\DefaultTranslationPlugin', '\DefaultTranslationPlugin');
}
