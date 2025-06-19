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

use PKP\facades\Locale;
use PKP\i18n\interfaces\LocaleInterface;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class DefaultTranslationPlugin extends GenericPlugin
{
    /** Flag to prevent infinite recursion */
    private static bool $isTranslating = false;

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.defaultTranslation.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.defaultTranslation.description');
    }

    /**
     * @copydoc LazyLoadPlugin::register()
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled($mainContextId)) {
            Hook::add('Locale::translate', [$this, 'handleTranslation']);
        }
        return $success;
    }

    /**
     * @copydoc Plugin::getSeq()
     */
    public function getSeq(): int
    {
        return -1;
    }

    /**
     * Hook callback to handle missing translations
     */
    public function handleTranslation(string $hookName, array $args): bool
    {
        [&$value, $key, $params, $number, $locale, $localeBundle] = $args;

        // Skip if we already have a value, are currently translating, or are in English
        if ($value !== null || self::$isTranslating || $locale === 'en' || $locale === LocaleInterface::DEFAULT_LOCALE) {
            return false;
        }

        // Prevent infinite recursion
        self::$isTranslating = true;

        try {
            // Try to get English translation
            $englishValue = $number === null
                ? Locale::get($key, $params, 'en')
                : Locale::choice($key, $number, $params, 'en');

            // If we got a valid translation, use it
            if ($englishValue && !str_starts_with($englishValue, '##')) {
                $value = $englishValue;
                return true;
            }
        } catch (\Exception $e) {
            // Ignore any errors and continue
        } finally {
            self::$isTranslating = false;
        }

        return false;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\defaultTranslation\DefaultTranslationPlugin', '\DefaultTranslationPlugin');
}
